import time
import logging
import random
import string
from sheet_manager import SheetManager
from whatsapp_handler import WhatsAppHandler
from wc_handler import WCHandler
from datetime import datetime
import pytz

# Logging setup
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

# Constants
POLL_INTERVAL = 30 # Check every 30 seconds
BATCH_SIZE = 10 # Send 10 messages per cycle

def generate_code_suffix():
    """Generates random 4-char suffix like 9X2A"""
    return ''.join(random.choices(string.ascii_uppercase + string.digits, k=4))

def run_worker():
    logger.info("Starting Campaign Worker (Tab-Based)...")
    
    try:
        # Use absolute path for credentials in production
        creds_path = "/home/kayarine_server/kayarine-webhook/credentials.json"
        if not os.path.exists(creds_path):
            creds_path = "./credentials.json" # Fallback for local dev

        sm = SheetManager(credentials_path=creds_path)
        wh = WhatsAppHandler()
        wc = WCHandler()
        
        if not sm.sheet:
            logger.error("Could not connect to Sheets. Exiting.")
            return

    except Exception as e:
        logger.error(f"Initialization failed: {e}")
        return

    while True:
        try:
            logger.info("Polling Campaigns sheet...")
            ws_campaigns = sm.sheet.worksheet("Campaigns")
            campaign_records = ws_campaigns.get_all_records()
            
            # Loop through campaigns
            for i, camp in enumerate(campaign_records):
                row_idx = i + 2
                status = str(camp.get('Status')).strip()
                c_name = camp.get('Campaign Name')
                
                # --- PHASE 1: GENERATION ---
                if status == "Generating":
                    logger.info(f"Detected Generation request for '{c_name}'")
                    
                    try:
                        count = int(camp.get('Coupon Count', 0))
                        prefix = camp.get('Coupon Prefix', 'PROMO')
                        discount = camp.get('Discount Amount', 0)
                        
                        if count <= 0:
                            raise ValueError("Coupon Count must be > 0")

                        generated_codes = []
                        
                        # Generate Coupons
                        logger.info(f"Generating {count} coupons...")
                        for _ in range(count):
                            code = f"{prefix}-{generate_code_suffix()}"
                            # Create in WC
                            wc.create_coupon(code, discount)
                            generated_codes.append(code)
                            # Small delay to be nice to WC API?
                            # time.sleep(0.1) 

                        # Create Tab
                        success = sm.create_campaign_tab(c_name, generated_codes)
                        
                        if success:
                            # Log to Central Coupons Sheet
                            coupon_log_data = [
                                {
                                    'code': c,
                                    'amount': discount,
                                    'campaign': c_name,
                                    'expiry': '' # Could add expiry logic from sheet later
                                }
                                for c in generated_codes
                            ]
                            sm.log_coupons(coupon_log_data)

                            # Update Status
                            ws_campaigns.update_cell(row_idx, 6, "Ready for Input") # Col 6 is Status
                            logger.info(f"Campaign '{c_name}' setup complete.")
                        else:
                            ws_campaigns.update_cell(row_idx, 6, "Error: Sheet Exists?")
                            
                    except Exception as e:
                        logger.error(f"Generation failed: {e}")
                        ws_campaigns.update_cell(row_idx, 6, f"Error: {str(e)}")

                # --- PHASE 2: SENDING ---
                elif status == "Ready to Send":
                    logger.info(f"Processing Sending for '{c_name}'")
                    
                    try:
                        ws_camp_tab = sm.sheet.worksheet(c_name)
                        rows = ws_camp_tab.get_all_records()
                        
                        sent_count = 0
                        template_name = camp.get('Template Name')
                        discount_val = camp.get('Discount Amount')
                        
                        # Iterate rows in the specific campaign tab
                        for j, row in enumerate(rows):
                            # Check limit
                            if sent_count >= BATCH_SIZE:
                                break
                                
                            c_row_idx = j + 2
                            phone = str(row.get('Phone'))
                            row_status = row.get('Status')
                            code = row.get('Coupon Code')
                            name = row.get('Customer Name')
                            
                            # Condition: Has Phone AND Status is Pending/Empty
                            if phone and (not row_status or row_status == "Pending"):
                                logger.info(f"Sending to {phone} ({name}) code {code}")
                                
                                # Send WhatsApp (Meta)
                                # Assumes template variables are {{1}}=Name, {{2}}=Code, {{3}}=Amount
                                params = [
                                    {"name": "name", "value": name if name else "Customer"},
                                    {"name": "coupon_code", "value": code},
                                    {"name": "discount_amount", "value": str(discount_val)}
                                ]
                                
                                res = wh.send_template_message(phone, template_name, params)
                                
                                if res and res.get('result') != 'error':
                                    ws_camp_tab.update_cell(c_row_idx, 4, "Sent") # Col 4 Status
                                    ws_camp_tab.update_cell(c_row_idx, 5, datetime.now().strftime("%Y-%m-%d %H:%M:%S"))
                                    sent_count += 1
                                else:
                                    ws_camp_tab.update_cell(c_row_idx, 4, "Error")
                                    
                                time.sleep(0.5) # Rate limit safety
                        
                        if sent_count == 0:
                            logger.info("No more pending targets found for this batch.")
                            # Optional: Check if ALL are sent, then mark campaign Completed?
                            # For now, let user manage the main status.
                            
                    except Exception as e:
                        logger.error(f"Sending loop failed: {e}")

        except Exception as e:
            logger.error(f"Main Worker Loop Error: {e}")
            
        time.sleep(POLL_INTERVAL)

if __name__ == "__main__":
    run_worker()
