from flask import Flask, request, jsonify, render_template
from flask_cors import CORS
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
import logging
from sheet_manager import SheetManager
from whatsapp_handler import WhatsAppHandler
from db_manager import DBManager
from security import (
    suspicious_ip_monitor, ip_whitelist, webhook_verifier,
    get_client_ip, log_request_info, require_api_token
)
import config
import threading
import queue
import time
import os
import json
from datetime import datetime

app = Flask(__name__)

# ==============================================================================
# CORS Configuration (Restricted)
# ==============================================================================
cors_config = {
    "origins": [
        "https://kayarine.club",
        "https://www.kayarine.club",
    ],
    "methods": ["GET", "POST"],
    "allow_headers": ["Content-Type", "Authorization"],
}

# Apply CORS to specific routes only (not all routes)
CORS(app, resources={
    r"/api/*": cors_config,
})
# Note: /webhook/* routes should NOT have CORS enabled

# ==============================================================================
# Rate Limiting Setup
# ==============================================================================
limiter = Limiter(
    app=app,
    key_func=get_remote_address,
    default_limits=["200 per day", "50 per hour"],
    storage_uri="memory://"
)

# Database Manager for Chat
db_manager = DBManager()

# Logging configuration with file output
logging.basicConfig(
    level=config.LOG_LEVEL,
    format='%(asctime)s - %(levelname)s - [%(name)s] - %(message)s',
    handlers=[
        logging.FileHandler(config.LOG_FILE),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)
logger.info("="*80)
logger.info(f"Kayarine Webhook Server Started - {datetime.now()}")
logger.info(f"Environment: {config.FLASK_ENV}")
logger.info(f"Rate Limiting: {'ENABLED' if config.RATE_LIMIT_ENABLED else 'DISABLED'}")
logger.info(f"Request Monitoring: {'ENABLED' if config.ENABLE_REQUEST_MONITORING else 'DISABLED'}")
logger.info("="*80)

# Thread-safe queue for processing orders sequentially
order_queue = queue.Queue()

# Initialize SheetManager and WhatsAppHandler
try:
    # Use absolute path for credentials in production
    creds_path = "/home/kayarine_server/kayarine-webhook/credentials.json"
    if not os.path.exists(creds_path):
        creds_path = "./credentials.json" # Fallback for local dev

    sheet_manager = SheetManager(credentials_path=creds_path)
    whatsapp_handler = WhatsAppHandler()
    
    # Initialize sheets on startup
    sheet_manager.init_sheets()
except Exception as e:
    logger.error(f"Failed to initialize Managers: {e}")
    # Initialize globals to None if failed, to avoid NameError
    if 'sheet_manager' not in globals():
        sheet_manager = None
    if 'whatsapp_handler' not in globals():
        whatsapp_handler = None

def worker():
    """
    Worker thread that processes orders from the queue sequentially.
    This ensures that Google Sheets operations (checking stock, updating counts)
    are atomic-like and don't suffer from race conditions.
    """
    logger.info("Worker thread started...")
    while True:
        try:
            # Get data from queue (blocking)
            data = order_queue.get()
            if data is None: # Sentinel to stop
                break
            
            order_id = data.get('id', 'Unknown')
            logger.info(f"Processing order {order_id} from queue...")
            
            try:
                # Add a small delay to ensure GSheets API rate limits aren't hit too hard
                # and to separate log entries clearly
                time.sleep(1)
                
                # Try to process if sheet_manager is active
                success = False
                order_data = None

                if sheet_manager and sheet_manager.sheet:
                    try:
                        success = sheet_manager.process_order(data)
                    except Exception as sheet_err:
                        logger.error(f"Sheet processing failed: {sheet_err}")
                        success = False
                
                # Even if sheet update fails (or is mock), we try to parse for Wati
                if sheet_manager:
                    order_data = sheet_manager.parse_webhook_payload(data)

                # Mock success if we are in testing mode (sheet is None but order_data exists)
                if not success and order_data and not (sheet_manager and sheet_manager.sheet):
                     logger.info("SheetManager in Mock mode, assuming success for Wati testing")
                     success = True

                if success and order_data:
                    logger.info(f"Successfully processed/parsed order {order_id}")
                    
                    # WhatsApp Automation Logic
                    if order_data:
                        status = order_data.get('status')
                        phone = order_data.get('phone')
                        
                        if status == 'pending':
                            whatsapp_handler.send_payment_reminder(order_data)
                            
                        elif status in ['processing', 'completed']:
                            # Send Booking Confirmation
                            whatsapp_handler.send_booking_confirmation(order_data)
                            
                            # Check if it's a tour
                            items_str = order_data.get('items_str', '')
                            if 'Tour' in items_str: # Naive check, improve based on actual product names
                                whatsapp_handler.send_tour_confirmation(order_data)
                                
                            # CRM & Coupons
                            # Trigger coupon logic (Upsell)
                            # Only if status is completed? Or processing?
                            if status == 'completed':
                                coupon = sheet_manager.assign_coupon(phone)
                                if coupon:
                                    whatsapp_handler.send_coupon_distribution(
                                        phone,
                                        coupon['Coupon Code'],
                                        coupon['Discount Amount']
                                    )

                else:
                    logger.warning(f"Processed order {order_id} but sheet manager returned False")
                    
            except Exception as e:
                logger.error(f"Error processing order {order_id}: {e}")
            
            order_queue.task_done()
        except Exception as e:
            logger.error(f"Worker exception: {e}")

# Start worker thread
# daemon=True means the thread will die when the main program exits
threading.Thread(target=worker, daemon=True).start()

# ==============================================================================
# Request Monitoring and Logging
# ==============================================================================
@app.before_request
def before_request_logging():
    """Log incoming requests and monitor for suspicious activity"""
    if config.ENABLE_REQUEST_MONITORING:
        client_ip = get_client_ip(request)
        log_request_info(request, suspicious_ip_monitor)

@app.after_request
def after_request_logging(response):
    """Log response details"""
    if config.ENABLE_REQUEST_MONITORING:
        client_ip = get_client_ip(request)
        logger.debug(f"RESPONSE: {response.status_code} for {request.method} {request.path} from {client_ip}")
    
    return response

@app.route('/', methods=['GET'])
@limiter.limit("30/minute")
def health_check():
    return f"Webhook Server is Running. Queue size: {order_queue.qsize()}", 200

@app.route('/chat', methods=['GET'])
def chat_ui():
    """Serves the Chat UI."""
    return render_template('inbox.html')

@app.route('/webhook/whatsapp', methods=['GET'])
@limiter.limit(config.RATE_LIMIT_WEBHOOK_DEFAULT)
def verify_whatsapp_webhook():
    """
    Verification endpoint for Meta Webhook setup.
    """
    mode = request.args.get('hub.mode')
    token = request.args.get('hub.verify_token')
    challenge = request.args.get('hub.challenge')
    
    # Use token from config/environment
    VERIFY_TOKEN = config.WHATSAPP_VERIFY_TOKEN
    
    if mode and token:
        if mode == 'subscribe' and token == VERIFY_TOKEN:
            logger.info(f"WhatsApp Webhook Verified from {get_client_ip(request)}")
            return challenge, 200
        else:
            logger.warning(f"WhatsApp Verification failed from {get_client_ip(request)} (invalid token)")
            return "Forbidden", 403
    return "Ignored", 200

@app.route('/webhook/whatsapp', methods=['POST'])
@limiter.limit(config.RATE_LIMIT_WEBHOOK_DEFAULT)
def receive_whatsapp_message():
    """
    Receives incoming messages from WhatsApp Cloud API.
    """
    try:
        client_ip = get_client_ip(request)
        data = request.get_json()
        logger.info(f"WhatsApp webhook from {client_ip}: {json.dumps(data)[:200]}...")
        
        # Check if it's a message
        if data.get('object') == 'whatsapp_business_account':
            for entry in data.get('entry', []):
                for change in entry.get('changes', []):
                    value = change.get('value', {})
                    
                    if 'messages' in value:
                        for msg in value['messages']:
                            phone = msg.get('from') # User's phone number
                            msg_type = msg.get('type')
                            content = ""
                            media_url = None
                            
                            if msg_type == 'text':
                                content = msg.get('text', {}).get('body', '')
                            elif msg_type == 'image':
                                content = "[Image]"
                                # We need to fetch the image ID and download it (Later feature: Drive Sync)
                                media_id = msg.get('image', {}).get('id')
                                media_url = f"media_id:{media_id}"
                            
                            # Save to DB
                            db_manager.save_message(phone, "inbound", content, msg_type, media_url)
                            logger.info(f"Saved message from {phone}: {content}")
                            
        return "EVENT_RECEIVED", 200
    except Exception as e:
        logger.error(f"Error processing WhatsApp webhook: {e}")
        return "ERROR", 500

# --- CHAT UI API ENDPOINTS ---

@app.route('/api/chats', methods=['GET'])
@limiter.limit(config.RATE_LIMIT_API_CHATS)
def get_chats():
    """Returns list of active conversations."""
    logger.info(f"API request: GET /api/chats from {get_client_ip(request)}")
    chats = db_manager.get_chats()
    return jsonify(chats), 200

@app.route('/api/messages', methods=['GET'])
@limiter.limit(config.RATE_LIMIT_API_MESSAGES)
def get_messages():
    """Returns message history for a specific phone number."""
    phone = request.args.get('phone')
    if not phone:
        return jsonify({"error": "Phone required"}), 400
    
    logger.info(f"API request: GET /api/messages for phone {phone} from {get_client_ip(request)}")
    msgs = db_manager.get_messages(phone)
    return jsonify(msgs), 200

@app.route('/api/send_message', methods=['POST'])
@limiter.limit(config.RATE_LIMIT_API_SEND_MESSAGE)
def send_manual_message():
    """Sends a manual text message via UI."""
    data = request.get_json()
    phone = data.get('phone')
    message = data.get('message')
    
    if not phone or not message:
        return jsonify({"error": "Missing phone or message"}), 400
    
    logger.info(f"API request: POST /api/send_message to {phone} from {get_client_ip(request)}")
        
    # Use WhatsAppHandler to send (Need to expose raw send text method or use template?)
    # For free-form chat, we send standard text messages, NOT templates (unless outside 24h window).
    # Since WhatsAppHandler is designed for templates, we might need a raw send method.
    # But for now, let's assume we can send text if we add a method to handler.
    
    # We need to add 'send_text_message' to WhatsAppHandler
    try:
        # Temporary direct call or update handler. Let's update handler next.
        # Assuming handler update:
        if whatsapp_handler:
            res = whatsapp_handler.send_text_message(phone, message)
            
            if res and 'messages' in res:
                # Save outbound to DB
                db_manager.save_message(phone, "outbound", message)
                return jsonify({"status": "sent"}), 200
            else:
                return jsonify({"status": "failed", "details": res}), 500
        else:
             return jsonify({"status": "error", "details": "Handler not init"}), 500

    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/webhook/order_created', methods=['POST'])
@limiter.limit(config.RATE_LIMIT_WEBHOOK_DEFAULT)
def webhook_order_created():
    """
    Endpoint to receive WooCommerce Order Created/Updated webhooks.
    """
    try:
        client_ip = get_client_ip(request)
        
        # Try to parse JSON regardless of Content-Type header
        data = request.get_json(force=True, silent=True)
        
        # Fallback for form-encoded data (rare for WC Webhooks but possible for generic tests)
        if not data and request.form:
            data = request.form.to_dict()

        if not data:
            # If still no data, check if it's a raw string (sometimes happens with specific setups)
            try:
                import json
                data = json.loads(request.data)
            except:
                pass

        if not data:
            logger.warning(f"Order webhook from {client_ip}: No data received")
            return jsonify({"status": "ignored", "message": "No data received"}), 200 # Return 200 to stop retries

        order_id = data.get("id")
        status = data.get("status")
        
        # Basic validation
        if "line_items" not in data:
             logger.warning(f"Order webhook from {client_ip}: Non-order payload (ID: {order_id})")
             return jsonify({"status": "ignored", "message": "Not an order payload"}), 200

        logger.info(f"✅ Order webhook from {client_ip}: Order ID {order_id} (Status: {status}) queued. Queue size: {order_queue.qsize()}")
        
        # Put into queue
        order_queue.put(data)
        
        # Return immediate success to WooCommerce so it doesn't timeout
        return jsonify({"status": "queued", "message": f"Order {order_id} queued for processing"}), 200

    except Exception as e:
        logger.error(f"❌ Error accepting webhook from {client_ip}: {e}")
        return jsonify({"status": "error", "message": str(e)}), 500

@app.route('/api/check_availability', methods=['GET'])
@limiter.limit(config.RATE_LIMIT_API_AVAILABILITY)
def check_availability():
    """
    API Endpoint for Frontend Plugin to check stock availability for a specific date.
    Query Param: date (YYYY-MM-DD)
    """
    client_ip = get_client_ip(request)
    date_str = request.args.get('date')
    if not date_str:
        logger.warning(f"Availability check from {client_ip}: Missing date parameter")
        return jsonify({"status": "error", "message": "Date parameter is required"}), 400

    if not sheet_manager or not sheet_manager.sheet:
        # Mock Response if Sheet Manager unavailable
        logger.warning(f"Availability check from {client_ip} on {date_str}: SheetManager unavailable, returning mock")
        return jsonify({
            "status": "success",
            "date": date_str,
            "mock": True,
            "availability": {
                6954: {"name": "Single Kayak", "limit": 50, "used": 0, "remaining": 50},
                6955: {"name": "Double Kayak", "limit": 20, "used": 0, "remaining": 20}
            }
        }), 200

    try:
        logger.debug(f"Checking availability from {client_ip} for {date_str}")
        availability = sheet_manager.check_availability(date_str)
        return jsonify({
            "status": "success",
            "date": date_str,
            "availability": availability
        }), 200
    except Exception as e:
        logger.error(f"Error checking availability for {date_str} from {client_ip}: {e}")
        return jsonify({"status": "error", "message": str(e)}), 500

# ==============================================================================
# Error Handlers
# ==============================================================================
@app.errorhandler(429)
def ratelimit_handler(e):
    """Handle rate limit exceeded errors"""
    client_ip = get_client_ip(request)
    logger.warning(f"⚠️ Rate limit exceeded for {client_ip} on {request.path}")
    return jsonify({"status": "error", "message": "Rate limit exceeded. Please try again later."}), 429

@app.errorhandler(403)
def forbidden_handler(e):
    """Handle forbidden errors"""
    client_ip = get_client_ip(request)
    logger.warning(f"🚫 Forbidden access from {client_ip} to {request.path}")
    return jsonify({"status": "error", "message": "Forbidden"}), 403

# ==============================================================================
# Monitoring Endpoints (for debugging - should be restricted in production)
# ==============================================================================
@app.route('/api/monitoring/stats', methods=['GET'])
@limiter.limit("5/minute")
def get_monitoring_stats():
    """Returns current monitoring statistics (requires authentication)"""
    # In Phase 2, this should require API token authentication
    # For now, restrict to localhost only in production
    client_ip = get_client_ip(request)
    
    if config.FLASK_ENV == 'production' and client_ip not in ['127.0.0.1', '::1']:
        logger.warning(f"Unauthorized monitoring request from {client_ip}")
        return jsonify({"error": "Forbidden"}), 403
    
    stats = {
        "queue_size": order_queue.qsize(),
        "suspicious_ips": len(suspicious_ip_monitor.suspicious_ips),
        "timestamp": datetime.now().isoformat(),
    }
    
    logger.info(f"Monitoring stats requested from {client_ip}")
    return jsonify(stats), 200

if __name__ == '__main__':
    # Run locally
    # debug=True can cause the reloader to spawn two processes, which means two queues.
    # For dev, use use_reloader=False if you want to test the queue strictly,
    # or just accept that the reloader process handles the requests.
    config.print_config_summary()
    app.run(host='0.0.0.0', port=5000, debug=config.FLASK_DEBUG, use_reloader=False)
