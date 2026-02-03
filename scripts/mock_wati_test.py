import json
import requests
import time
import logging

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Webhook Endpoint (Localhost)
WEBHOOK_URL = "http://localhost:5000/webhook/order_created"

def load_mock_order(filename="mock_payload_created.json"):
    """Loads a mock order payload from a JSON file if it exists, otherwise creates a default one."""
    try:
        with open(filename, 'r') as f:
            return json.load(f)
    except FileNotFoundError:
        logger.info(f"{filename} not found. Using default mock payload.")
        return {
            "id": 99999,
            "status": "pending",
            "date_created": "2023-10-27T10:00:00",
            "total": "500.00",
            "payment_method_title": "Direct Bank Transfer",
            "billing": {
                "first_name": "Test",
                "last_name": "User",
                "phone": "85291234567",
                "email": "test@example.com"
            },
            "line_items": [
                {
                    "product_id": 81,
                    "name": "Single Kayak",
                    "quantity": 2,
                    "meta_data": [
                        {
                            "key": "yith_booking_data",
                            "value": {
                                "from": 1698451200, # 2023-10-28
                                "persons": 2
                            }
                        }
                    ]
                }
            ]
        }

def send_mock_webhook(payload, event_type="created"):
    """Sends the mock payload to the Flask app."""
    try:
        logger.info(f"Sending Mock Webhook (Event: {event_type}, Order ID: {payload.get('id')})...")
        response = requests.post(
            WEBHOOK_URL,
            json=payload,
            headers={"Content-Type": "application/json"}
        )
        logger.info(f"Response Status: {response.status_code}")
        logger.info(f"Response Body: {response.json()}")
    except requests.exceptions.ConnectionError:
        logger.error("Connection Error: Is the Flask app running? (Run 'python app.py' in another terminal)")
    except Exception as e:
        logger.error(f"Error sending webhook: {e}")

if __name__ == "__main__":
    # Test 1: Pending Order (Should trigger Payment Reminder)
    logger.info("--- TEST 1: Pending Order ---")
    pending_payload = load_mock_order()
    pending_payload['id'] = 10001
    pending_payload['status'] = 'pending'
    send_mock_webhook(pending_payload)
    
    time.sleep(2)

    # Test 2: Completed Order (Should trigger Booking Confirmation + Coupon)
    logger.info("\n--- TEST 2: Completed Order ---")
    completed_payload = load_mock_order()
    completed_payload['id'] = 10002
    completed_payload['status'] = 'completed'
    send_mock_webhook(completed_payload)

    time.sleep(2)

    # Test 3: Tour Order (Should trigger Tour Confirmation)
    logger.info("\n--- TEST 3: Tour Order ---")
    tour_payload = load_mock_order()
    tour_payload['id'] = 10003
    tour_payload['status'] = 'processing'
    tour_payload['line_items'][0]['name'] = "Sunset Tour" 
    tour_payload['line_items'][0]['product_id'] = 999 # Fake ID
    send_mock_webhook(tour_payload)
