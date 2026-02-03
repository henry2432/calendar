import gspread
from oauth2client.service_account import ServiceAccountCredentials
import logging
import json
import os
from datetime import datetime
import pytz
from gspread_formatting import format_cell_range, CellFormat, textFormat

# Logging setup
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Constants
SHEET_ID = "11szACNGbY4LiDdPXOauXWmINkORYinA7oS3O-4ZIAXc"
TZ = pytz.timezone("Asia/Hong_Kong")

# Product/Service IDs (Legacy)
PRODUCT_IDS = {
    "Single Kayak": 81,
    "Double Kayak": 82,
    "SUP": 84
}
PRODUCT_NAME_MAP = {
    81: "Single Kayak",
    82: "Double Kayak",
    84: "SUP",
    # Plugin IDs
    101: "Single Kayak",
    102: "Double Kayak",
    104: "Snorkel Mask",
    105: "Phone Case",
    # Real IDs from CSV (v5)
    6954: "Single Kayak",
    6955: "Double Kayak",
    6956: "SUP",
    6963: "Family Kayak",
    6966: "Snorkel Mask",
    6967: "Phone Case",
    # Tours & Courses (Real IDs v5)
    6957: "白沙洲日出直立板團",
    6958: "白沙洲日落直立板團",
    6959: "白沙洲直立板入門班",
    6960: "橋咀島獨木舟浮潛體驗",
    6961: "威士忌直立板進階班",
    6962: "直立板中級課程 – 銅章",
    6964: "直立板中級課程 – 銀章",
    6965: "Sup Yoga 直立板瑜伽",
    # Legacy/Placeholder
    10: "白沙洲日出直立板團",
    11: "白沙洲日落直立板團",
    12: "白沙洲直立板入門班",
    13: "橋咀島獨木舟浮潛體驗",
    15: "直立板中級課程 – 銅章",
    16: "直立板中級課程 – 銀章",
    20: "Sup Yoga 直立板瑜伽"
}

# Match by Name to avoid ID mismatch issues during import
TOUR_COURSE_NAMES = {
    "白沙洲日出直立板團",
    "白沙洲日落直立板團",
    "白沙洲直立板入門班",
    "橋咀島獨木舟浮潛體驗",
    "直立板中級課程 – 銅章",
    "直立板中級課程 – 銀章",
    "Sup Yoga 直立板瑜伽",
    "威士忌直立板進階班"
}

SERVICE_IDS = {
    "Snorkel Mask Rent": 34,
    "Phone Case": 35,
    "Snorkel Mask Buy": 36,
    "Phone Case Buy": 37
}
# Assuming new Product IDs for the Add-ons added via plugin (103, 104, 105)
# If these are separate products in WC, map them here.
# If they are treated as Services/Fees, logic differs.
# Based on plugin code, they are products added to cart.
ADDON_IDS = {
    6966: "Snorkel Mask",
    6967: "Phone Case"
}

SERVICE_NAME_MAP = {
    34: "Snorkel Mask Rent",
    35: "Phone Case",
    36: "Snorkel Mask Buy",
    37: "Phone Case Buy"
}

# Sheet Headers
HEADERS = {
    "All Orders": [
        "Order ID", "Date", "Amount", "Status", "Customer Name", "Phone",
        "Items (JSON)", "Payment Method", "Source"
    ],
    "Today's Orders": [
        "Order ID", "Customer Name", "Phone", "Status", "Items (Qty)",
        "Arrival Status", "Remarks"
    ],
    "Equipment Availability": [
        "Date", "Single Kayak", "Double Kayak", "SUP", "Snorkel Mask", "Phone Case"
    ],
    "Tours & Courses": [
        "Order ID", "Date", "Customer Name", "Phone", "Product Name", "Category", "Status"
    ],
    "Customer List": [
        "Phone", "Name", "Total Spend", "Visit Count", "Avg Spend",
        "First Visit", "Last Visit", "Loyalty Tier",
        "Preferred Product", "Recency (Days)", "Wati Sent", "Coupons Used"
    ],
    "Coupons": [
        "Coupon Code", "Discount Amount", "Expiry", "Status", "Assigned To", "Campaign Source"
    ],
    "Promotions": [
        "Promo Name", "Start Date", "Coupons Issued", "Coupons Redeemed", "Conversion Rate"
    ],
    "Tours & Courses": [
        "Order ID", "Date", "Customer Name", "Phone", "Product Name", "Quantity", "Booking Date", "Status"
    ],
    "Settings": [
        "Key", "Value", "Description", "Blackout Dates"
    ],
    "Campaigns": [
        "Campaign Name", "Template Name", "Coupon Count", "Coupon Prefix", "Discount Amount", "Status", "Redeemed Count", "Usage Rate"
    ]
}

class SheetManager:
    def __init__(self, credentials_path='/home/kayarine_server/kayarine-webhook/credentials.json'):
        self.scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
        try:
            logger.info(f"Attempting to load credentials from: {credentials_path}")
            
            # Fallback to local ./credentials.json if absolute path fails (dev environment)
            if not os.path.exists(credentials_path):
                logger.info(f"Path {credentials_path} does not exist.")
                if os.path.exists('./credentials.json'):
                    logger.info("Found fallback ./credentials.json")
                    credentials_path = './credentials.json'
                else:
                    logger.warning(f"Credentials not found at {credentials_path}. Using Mock.")
                    self.sheet = None
                    return
            else:
                logger.info(f"File exists at {credentials_path}")

            self.creds = ServiceAccountCredentials.from_json_keyfile_name(credentials_path, self.scope)
            self.client = gspread.authorize(self.creds)
            self.sheet = self.client.open_by_key(SHEET_ID)
            logger.info("Connected to Google Sheets")
        except Exception as e:
            import traceback
            logger.error(f"Failed to connect to Google Sheets: {e}")
            logger.error(traceback.format_exc())
            # Do not raise, just log
            self.sheet = None

    def init_sheets(self):
        """Creates sheets if they don't exist and sets headers."""
        current_worksheets = {ws.title: ws for ws in self.sheet.worksheets()}
        
        for sheet_name, headers in HEADERS.items():
            if sheet_name not in current_worksheets:
                logger.info(f"Creating sheet: {sheet_name}")
                ws = self.sheet.add_worksheet(title=sheet_name, rows=100, cols=20)
                ws.append_row(headers)
            else:
                logger.info(f"Checking headers for: {sheet_name}")
                ws = current_worksheets[sheet_name]
                current_headers = ws.row_values(1)
                if not current_headers:
                    ws.append_row(headers)
                # Check if we need to add new CRM columns to existing Customer List
                if sheet_name == "Customer List":
                    if "Preferred Product" not in current_headers:
                        logger.info("Adding new CRM columns to Customer List")
                        # Simple append to header row if missing
                        # This assumes we are appending to the right.
                        # Ideally we find the length and update.
                        col_count = len(current_headers)
                        ws.update_cell(1, col_count + 1, "Preferred Product")
                        ws.update_cell(1, col_count + 2, "Recency (Days)")
                        ws.update_cell(1, col_count + 3, "Wati Sent")
                    elif "Wati Sent" not in current_headers:
                         col_count = len(current_headers)
                         ws.update_cell(1, col_count + 1, "Wati Sent")
                    
                    # Add Coupon Tracking column to Customer List
                    if sheet_name == "Customer List" and "Coupons Used" not in current_headers:
                         col_count = len(current_headers)
                         ws.update_cell(1, col_count + 1, "Coupons Used")

            if sheet_name == "Settings":
                 if "Blackout Dates" not in current_headers:
                      col_count = len(current_headers)
                      ws.update_cell(1, col_count + 1, "Blackout Dates")

        # Initialize Settings Sheet with defaults if empty
        if "Settings" in current_worksheets:
            ws_settings = current_worksheets["Settings"]
            if not ws_settings.get_all_values(): # Empty
                 defaults = [
                     ["Single Kayak Max", "50", "Total fleet size for Single Kayak"],
                     ["Double Kayak Max", "20", "Total fleet size for Double Kayak"],
                     ["Family Kayak Max", "10", "Total fleet size for Family Kayak"],
                     ["SUP Max", "20", "Total fleet size for SUP"],
                     ["Snorkel Mask Max", "50", "Inventory for masks"],
                     ["Phone Case Max", "50", "Inventory for phone cases"]
                 ]
                 ws_settings.append_rows(defaults)
                 logger.info("Initialized Settings sheet with defaults")

    def get_settings(self):
        """
        Fetches system settings (Inventory Limits) from the Settings sheet.
        Returns a dict: {'Single Kayak': 50, ...}
        """
        try:
            ws = self.sheet.worksheet("Settings")
            records = ws.get_all_records()
            settings = {}
            for row in records:
                key = row.get("Key")
                val = row.get("Value")
                if key and val is not None:
                     # Remove " Max" suffix to match equipment names if desired,
                     # or keep mapping explicit. Let's keep it flexible.
                     clean_key = key.replace(" Max", "")
                     try:
                         settings[clean_key] = int(val)
                     except:
                         settings[clean_key] = val
            return settings
        except Exception as e:
            logger.error(f"Error fetching settings: {e}")
            # Fallback defaults
            return {
                "Single Kayak": 50,
                "Double Kayak": 20,
                "Family Kayak": 10,
                "SUP": 20
            }

    def check_availability(self, date_str):
        """
        Checks availability for a specific date.
        Returns detailed status for each equipment type.
        """
        # 0. Check Blackout Dates
        try:
            ws_settings = self.sheet.worksheet("Settings")
            # Assuming Blackout Dates is in Column D (index 4)
            # Skip header (row 1)
            blackout_dates = ws_settings.col_values(4)[1:]
            
            is_blackout = False
            for rule in blackout_dates:
                if not rule: continue
                if self._is_blackout(date_str, str(rule)):
                    is_blackout = True
                    break

            if is_blackout:
                logger.info(f"Date {date_str} is blacked out.")
                # Return 0 availability for all
                limits = self.get_settings()
                NAME_TO_ID = {
                    "Single Kayak": 6954,
                    "Double Kayak": 6955,
                    "SUP": 6956,
                    "Family Kayak": 6963,
                    "Snorkel Mask": 6966,
                    "Phone Case": 6967
                }
                availability = {}
                for name, limit in limits.items():
                    pid = NAME_TO_ID.get(name)
                    if pid:
                        availability[pid] = {
                            "name": name,
                            "limit": limit,
                            "used": limit, # Treat as fully used
                            "remaining": 0
                        }
                return availability
        except Exception as e:
            logger.error(f"Error checking blackout dates: {e}")

        # 1. Get Limits
        limits = self.get_settings()
        
        # 2. Get Usage
        usage = {
            "Single Kayak": 0,
            "Double Kayak": 0,
            "Family Kayak": 0,
            "SUP": 0,
            "Snorkel Mask": 0,
            "Phone Case": 0
        }
        
        try:
            ws_eq = self.sheet.worksheet("Equipment Availability")
            cell = ws_eq.find(date_str, in_column=1)
            
            if cell:
                row_values = ws_eq.row_values(cell.row)
                
                # Row structure based on Headers:
                # Date(0), Single(1), Double(2), SUP(3), Snorkel(4), Phone(5)
                # Need to map correctly.
                # Note: Family Kayak is missing from Equipment Availability headers in HEADERS const!
                # Let's fix that assumption or handle it.
                # Current HEADERS['Equipment Availability'] = ["Date", "Single Kayak", "Double Kayak", "SUP", "Snorkel Mask", "Phone Case"]
                
                if len(row_values) > 1: usage["Single Kayak"] = int(row_values[1])
                if len(row_values) > 2: usage["Double Kayak"] = int(row_values[2])
                if len(row_values) > 3: usage["SUP"] = int(row_values[3])
                if len(row_values) > 4: usage["Snorkel Mask"] = int(row_values[4])
                if len(row_values) > 5: usage["Phone Case"] = int(row_values[5])
                
                # Family Kayak isn't in the sheet yet. We treat usage as 0 or need to migrate sheet.
                # For now, assume 0 usage if not tracked.
            else:
                # No bookings for this date yet (cell is None)
                pass
            
        except Exception as e:
            logger.error(f"Error checking availability: {e}")

        # 3. Calculate Remaining
        availability = {}
        
        # Map Internal Names to IDs (matches kayarine-config.php)
        # This is CRITICAL for the JS to understand.
        NAME_TO_ID = {
            "Single Kayak": 6954,
            "Double Kayak": 6955,
            "SUP": 6956,
            "Family Kayak": 6963,
            "Snorkel Mask": 6966,
            "Phone Case": 6967
        }

        for name, limit in limits.items():
            used = usage.get(name, 0)
            remaining = max(0, limit - used)
            pid = NAME_TO_ID.get(name)
            
            if pid:
                availability[pid] = {
                    "name": name,
                    "limit": limit,
                    "used": used,
                    "remaining": remaining
                }
        
        return availability

    def parse_webhook_payload(self, order_json):
        """
        Extracts relevant data from WooCommerce Webhook payload.
        Adapts YITH booking data extraction logic.
        """
        try:
            # If sheet is not initialized, we can still parse, but validation might be looser
            order_id = order_json.get("id")
            status = order_json.get("status")
            date_created = order_json.get("date_created")
            total = float(order_json.get("total", 0.0)) # Ensure float
            payment_method = order_json.get("payment_method_title")
            source = "Web"
            
            billing = order_json.get("billing", {})
            first_name = billing.get("first_name", "")
            last_name = billing.get("last_name", "")
            full_name = f"{first_name} {last_name}".strip()
            phone = billing.get("phone", "")
            
            coupon_lines = order_json.get("coupon_lines", [])
            coupons_used = [c.get("code") for c in coupon_lines if c.get("code")]

            items_summary = []
            parsed_items = []
            booking_date = None
            
            equipment_counts = {
                "Single Kayak": 0,
                "Double Kayak": 0,
                "Family Kayak": 0,
                "SUP": 0,
                "Snorkel Mask": 0,
                "Phone Case": 0
            }

            for item in order_json.get("line_items", []):
                product_id = item.get("product_id")
                product_name = item.get("name")
                quantity = item.get("quantity")
                
                known_name = PRODUCT_NAME_MAP.get(product_id)
                
                yith_data = None
                for meta in item.get("meta_data", []):
                    if meta.get("key") == "yith_booking_data":
                        yith_data = meta.get("value")
                        break
                
                item_booking_date = None
                if yith_data:
                    try:
                        from_ts = int(yith_data.get("from", 0))
                        if from_ts > 0:
                            booking_date_obj = datetime.fromtimestamp(from_ts, TZ)
                            booking_date = booking_date_obj.strftime("%Y-%m-%d")
                            item_booking_date = booking_date
                    except Exception as e:
                        logger.warning(f"Error parsing date from YITH data: {e}")

                    persons = int(yith_data.get("persons", 0))
                    qty_to_use = persons if persons > 0 else quantity
                    
                    if known_name:
                        equipment_counts[known_name] += qty_to_use
                        items_summary.append(f"{known_name} x{qty_to_use}")
                    else:
                         items_summary.append(f"{product_name} x{qty_to_use}")

                    booking_services = yith_data.get("booking_services", [])
                    service_quantities = yith_data.get("booking_service_quantities", {})
                    
                    for svc_id in booking_services:
                        svc_name = SERVICE_NAME_MAP.get(int(svc_id), f"Service {svc_id}")
                        svc_qty = service_quantities.get(str(svc_id), 0)
                        if int(svc_qty) > 0:
                            items_summary.append(f"{svc_name} x{svc_qty}")

                else:
                    items_summary.append(f"{product_name} x{quantity}")

                parsed_items.append({
                    "product_id": product_id,
                    "name": known_name if known_name else product_name,
                    "quantity": quantity, # Use raw quantity for list, or qty_to_use? Usually raw quantity of product is what we want for line items.
                    "booking_date": item_booking_date
                })

            final_date = booking_date if booking_date else date_created

            items_json = json.dumps(items_summary, ensure_ascii=False)
            items_str = ", ".join(items_summary)

            return {
                "order_id": str(order_id),
                "date": final_date,
                "amount": total,
                "status": status,
                "customer_name": full_name,
                "phone": phone,
                "items_json": items_json,
                "items_str": items_str,
                "payment_method": payment_method,
                "source": source,
                "booking_date": booking_date,
                "equipment_counts": equipment_counts,
                "date_created": date_created, # Useful for Recency
                "parsed_items": parsed_items,
                "coupons_used": coupons_used
            }

        except Exception as e:
            logger.error(f"Error parsing payload: {e}")
            return None

    def process_order(self, order_json):
        """Main entry point to process a webhook order."""
        data = self.parse_webhook_payload(order_json)
        if not data:
            return False

        # 1. Update All Orders
        self.update_all_orders(data)
        
        # 2. Update Today's Orders
        now_hk = datetime.now(TZ)
        today_str = now_hk.strftime("%Y-%m-%d")
        if data['booking_date'] == today_str:
            self.update_todays_orders(data)
            
        # 3. Update Equipment Availability
        if data['booking_date']:
            self.update_availability(data)
        
        # 4. Update Tours & Courses Sheet
        self.update_tours_courses(data)

        # 5. Update Customer List (CRM)
        self.update_customer(data)

        # 5. Update Tours & Courses
        self.update_tours_courses(data)

        # 6. Track Coupon Usage
        for code in data.get('coupons_used', []):
             self.track_coupon_usage(code, data['phone'])

        return True

    def update_all_orders(self, data):
        ws = self.sheet.worksheet("All Orders")
        cell = ws.find(data['order_id'], in_column=1)
        if cell:
            row_num = cell.row
            row_data = [
                data['order_id'], data['date'], data['amount'], data['status'],
                data['customer_name'], data['phone'], data['items_str'],
                data['payment_method'], data['source']
            ]
            ws.update(f"A{row_num}:I{row_num}", [row_data])
            logger.info(f"Updated All Orders for {data['order_id']}")
        else:
            row_data = [
                data['order_id'], data['date'], data['amount'], data['status'],
                data['customer_name'], data['phone'], data['items_str'],
                data['payment_method'], data['source']
            ]
            ws.append_row(row_data)
            logger.info(f"Appended to All Orders: {data['order_id']}")

    def update_tours_courses(self, data):
        ws = self.sheet.worksheet("Tours & Courses")
        for item in data.get('parsed_items', []):
            try:
                # Match by Name (Robust) OR ID (Legacy)
                # Normalize name: strip spaces
                p_name = item['name'].strip()
                is_tour = p_name in TOUR_COURSE_NAMES
                
                # Also check ID as backup if name changes slightly but ID is known (legacy)
                if not is_tour:
                     try:
                         pid = int(item['product_id'])
                         # Legacy check
                         # (Optional: Add logic here if we maintain ID list, but Name is safer for now)
                         pass
                     except:
                         pass

                if is_tour:
                    # Idempotency Check
                    try:
                        # Get all records is heavy if sheet is large.
                        # Optimization: Find all cells with Order ID, then check Product Name
                        cell_list = ws.findall(data['order_id'], in_column=1)
                        exists = False
                        if cell_list:
                            # If order ID exists, check if any of the rows correspond to this product
                            for cell in cell_list:
                                row_values = ws.row_values(cell.row)
                                # Product Name is column 5 (index 4)
                                if len(row_values) >= 5 and row_values[4] == item['name']:
                                    exists = True
                                    # Update Status (Column 8) if changed
                                    if len(row_values) >= 8 and row_values[7] != data['status']:
                                        ws.update_cell(cell.row, 8, data['status'])
                                        logger.info(f"Updated status for {data['order_id']} - {item['name']}")
                                    break
                    except Exception as e:
                        logger.warning(f"Error checking existence: {e}")
                        exists = False # Fallback to append if check fails
                    
                    if not exists:
                        booking_d = item['booking_date'] if item['booking_date'] else data['booking_date']
                        if not booking_d: booking_d = data['date'] # Fallback
                        
                        row_data = [
                            data['order_id'],
                            data['date'], # Order Date
                            data['customer_name'],
                            data['phone'],
                            item['name'],
                            item['quantity'],
                            booking_d,
                            data['status']
                        ]
                        ws.append_row(row_data)
                        logger.info(f"Appended to Tours & Courses: {data['order_id']} - {item['name']}")
                        
            except Exception as e:
                logger.error(f"Error processing tour item: {e}")
                continue

    def update_todays_orders(self, data):
        ws = self.sheet.worksheet("Today's Orders")
        cell = ws.find(data['order_id'], in_column=1)
        if cell:
            row_num = cell.row
            ws.update_cell(row_num, 4, data['status'])
            ws.update_cell(row_num, 5, data['items_str'])
            logger.info(f"Updated Today's Orders for {data['order_id']}")
        else:
            row_data = [
                data['order_id'], data['customer_name'], data['phone'],
                data['status'], data['items_str'], "FALSE", ""
            ]
            ws.append_row(row_data)
            logger.info(f"Appended to Today's Orders: {data['order_id']}")

    def update_availability(self, data):
        ws = self.sheet.worksheet("Equipment Availability")
        date_str = data['booking_date']
        
        # Check if date exists
        cell = ws.find(date_str, in_column=1)
        
        if cell:
            # Recalculate regardless
            self._recalculate_daily_availability(date_str)
        else:
            ws.append_row([date_str, 0, 0, 0, ""])
            self._recalculate_daily_availability(date_str)

    def update_tours_courses(self, data):
        """
        Updates the 'Tours & Courses' sheet if the order contains relevant items.
        Categories: '水上活動', '證書課程'
        """
        # Define relevant categories or keywords from product list
        # Based on CSV:
        # ID 10: 白沙洲日出直立板團 (水上活動)
        # ID 11: 白沙洲日落直立板團 (水上活動)
        # ID 12: 白沙洲直立板入門班 (證書課程)
        # ID 13: 橋咀島獨木舟浮潛體驗 (水上活動)
        # ID 15: 直立板中級課程 – 銅章 (證書課程)
        # ID 16: 直立板中級課程 – 銀章 (證書課程)
        # ID 20: Sup Yoga 直立板瑜伽 (水上活動)
        
        # We can detect by product ID or Name since we don't get Category in Webhook payload directly
        # Mapping ID to Category
        TOUR_COURSE_IDS = {
            10: "水上活動",
            11: "水上活動",
            12: "證書課程",
            13: "水上活動",
            15: "證書課程",
            16: "證書課程",
            20: "水上活動"
        }
        
        ws = self.sheet.worksheet("Tours & Courses")
        
        # We need to iterate through items to see if any match
        # The 'data' object has 'items_str' but not detailed item objects with IDs
        # So we might need to parse 'items_json' or rely on string matching if we don't have IDs readily available in 'process_order'
        # Actually 'parse_webhook_payload' logic doesn't preserve per-item ID in the final dict, only summary string.
        # But 'equipment_counts' is insufficient for this.
        # Let's simple check if we can identify them from the items_str or better, modify parse_webhook to pass IDs.
        # For now, let's use string matching on Product Names known from CSV.
        
        known_tours = {
            "白沙洲日出直立板團": "水上活動",
            "白沙洲日落直立板團": "水上活動",
            "白沙洲直立板入門班": "證書課程",
            "橋咀島獨木舟浮潛體驗": "水上活動",
            "直立板中級課程": "證書課程", # partial match
            "Sup Yoga": "水上活動"
        }
        
        # Check items string
        items_str = data.get('items_str', '')
        found_category = None
        found_product = None
        
        for name, cat in known_tours.items():
            if name in items_str:
                found_category = cat
                found_product = name # Simplified, might miss full name if partial match
                break
        
        if found_category:
            # Check if order already exists in this sheet to avoid dupes (though logic allows append)
            # Simple append for now
             row_data = [
                data['order_id'],
                data['date'],
                data['customer_name'],
                data['phone'],
                found_product, # Or full items_str
                found_category,
                data['status']
            ]
             
             # Try to find if order already exists to update status
             try:
                 cell = ws.find(data['order_id'], in_column=1)
                 row_num = cell.row
                 ws.update(f"A{row_num}:G{row_num}", [row_data])
                 logger.info(f"Updated Tours & Courses for {data['order_id']}")
             except gspread.exceptions.CellNotFound:
                 ws.append_row(row_data)
                 logger.info(f"Appended to Tours & Courses: {data['order_id']}")

    def _recalculate_daily_availability(self, date_str):
        ws_all = self.sheet.worksheet("All Orders")
        records = ws_all.get_all_records()
        
        counts = {
            "Single Kayak": 0,
            "Double Kayak": 0,
            "Family Kayak": 0,
            "SUP": 0,
            "Snorkel Mask": 0,
            "Phone Case": 0
        }
        
        for row in records:
            if row.get("Date") == date_str and row.get("Status") not in ['cancelled', 'failed', 'refunded', 'trash']:
                items_str = row.get("Items (JSON)", "")
                # Parsing logic: "Item Name xQty, Item Name 2 xQty"
                parts = items_str.split(", ")
                for part in parts:
                    for key in counts.keys():
                        if f"{key} x" in part: # Simple substring match
                            try:
                                # Extract qty: "Single Kayak x2" -> 2
                                # Find where the key ends and 'x' begins
                                qty_part = part.split(f"{key} x")[1]
                                # Take the first number (in case of trailing chars, though unlikely with our format)
                                qty = int(qty_part.split()[0]) if ' ' in qty_part else int(qty_part)
                                counts[key] += qty
                            except Exception as e:
                                pass
        
        ws_eq = self.sheet.worksheet("Equipment Availability")
        cell = ws_eq.find(date_str, in_column=1)
        if cell:
            row_num = cell.row
            # Update columns 2-7
            row_data = [
                counts["Single Kayak"],
                counts["Double Kayak"],
                counts["SUP"],
                counts["Snorkel Mask"],
                counts["Phone Case"]
            ]
            # Batch update range B(row):G(row)
            ws_eq.update(f"B{row_num}:G{row_num}", [row_data])
            
            logger.info(f"Recalculated availability for {date_str}: {counts}")
        else:
             ws_eq.append_row([
                 date_str,
                 counts["Single Kayak"],
                 counts["Double Kayak"],
                 counts["SUP"],
                 counts["Snorkel Mask"],
                 counts["Phone Case"]
             ])

    def update_customer(self, data):
        """
        Updates the Customer List with CRM metrics:
        Phone, Name, Total Spend, Visit Count, Avg Spend, First Visit, Last Visit, Loyalty Tier, Preferred Product, Recency
        """
        ws = self.sheet.worksheet("Customer List")
        phone = data['phone']
        if not phone:
            return

        order_amount = data['amount']
        # Extract date string YYYY-MM-DD
        if data.get('date_created'):
             try:
                 order_date = data['date_created'][:10]
             except:
                 order_date = datetime.now(TZ).strftime("%Y-%m-%d")
        else:
             order_date = datetime.now(TZ).strftime("%Y-%m-%d")

        cell = ws.find(phone, in_column=1)
        if cell:
            row_num = cell.row
            
            # Read existing data
            # "Phone", "Name", "Total Spend", "Visit Count", "Avg Spend", "First Visit", "Last Visit", "Loyalty Tier", "Preferred Product", "Recency (Days)", "Wati Sent"
            row_values = ws.row_values(row_num)
            
            # Safe getters
            def get_val(idx, default):
                return row_values[idx] if len(row_values) > idx else default

            current_spend = float(get_val(2, 0))
            visit_count = int(get_val(3, 0))
            first_visit = get_val(5, order_date)
            # last_visit = get_val(6, order_date) # We update this
            wati_sent = get_val(10, "")
            
            # Calculate new values
            new_spend = current_spend + order_amount
            new_count = visit_count + 1
            avg_spend = new_spend / new_count
            
            # Update Tier
            loyalty_tier = "New"
            if new_spend > 5000: loyalty_tier = "VIP"
            elif new_spend > 1000: loyalty_tier = "Regular"
            
            # Update Recency
            try:
                last_date_obj = datetime.strptime(order_date, "%Y-%m-%d")
                now_obj = datetime.now() # naive is ok if string is naive, but let's be careful.
                # Actually, Recency is "Days since last visit".
                # For a new order, Recency is 0!
                recency = 0
            except:
                recency = 0

            # Determine Preferred Product (Simple accumulation logic is hard without history,
            # so we just check what they bought THIS time and maybe overwrite or append?
            # Ideally we scan all their orders. For now, let's just log the current one if it's significant.)
            # Better: "Last Product Bought" or keep it simple.
            # Let's try to infer from counts.
            counts = data['equipment_counts']
            # Find max
            preferred = max(counts, key=counts.get) if counts else "None"
            if counts[preferred] == 0: preferred = get_val(8, "None") # Keep old if this order has no equipment (e.g. only service)

            # Update row
            # Columns are 1-based indices for update_cell, but row_values is 0-based list.
            # Phone(1), Name(2), Total(3), Count(4), Avg(5), First(6), Last(7), Tier(8), Pref(9), Recency(10), Wati Sent(11)
            
            # Using batch update for atomic-ish row update
            # We construct the full row
            updated_row = [
                phone,
                data['customer_name'],
                new_spend,
                new_count,
                avg_spend,
                first_visit,
                order_date,
                loyalty_tier,
                preferred,
                recency,
                wati_sent
            ]
            
            ws.update(f"A{row_num}:K{row_num}", [updated_row])
            logger.info(f"Updated Customer {phone}")

        else:
            # Create new customer record
            
            # Preferred
            counts = data['equipment_counts']
            preferred = max(counts, key=counts.get) if counts else "None"
            if counts.get(preferred, 0) == 0: preferred = "None"

            ws.append_row([
                phone,
                data['customer_name'],
                order_amount,
                1,
                order_amount,
                order_date,
                order_date,
                "New",
                preferred,
                0,
                ""
            ])
            logger.info(f"Created new Customer {phone}")

    def create_campaign_tab(self, campaign_name, coupon_codes):
        """
        Creates a new worksheet for a specific campaign and populates it with generated coupon codes.
        """
        try:
            # Check if exists
            try:
                self.sheet.worksheet(campaign_name)
                logger.warning(f"Sheet {campaign_name} already exists. Skipping creation.")
                return False
            except gspread.exceptions.WorksheetNotFound:
                pass

            # Create new sheet
            ws = self.sheet.add_worksheet(title=campaign_name, rows=len(coupon_codes)+50, cols=5)
            
            # Headers
            headers = ["Phone", "Customer Name", "Coupon Code", "Status", "Sent Time"]
            ws.append_row(headers)
            
            # Prepare rows: Phone and Name empty, Code pre-filled
            rows = []
            for code in coupon_codes:
                rows.append(["", "", code, "Pending", ""])
            
            ws.append_rows(rows)
            logger.info(f"Created campaign sheet '{campaign_name}' with {len(coupon_codes)} coupons.")
            return True

        except Exception as e:
            logger.error(f"Error creating campaign tab: {e}")
            return False

    def log_coupons(self, coupon_list):
        """
        Logs generated coupons to the central Coupons sheet.
        coupon_list: List of dicts {'code', 'amount', 'expiry', 'campaign'}
        """
        try:
            ws = self.sheet.worksheet("Coupons")
            existing_codes = ws.col_values(1)
            
            rows_to_add = []
            for c in coupon_list:
                if c['code'] not in existing_codes:
                    rows_to_add.append([
                        c['code'], c['amount'], c.get('expiry', ''), "Available", "", c.get('campaign', '')
                    ])
            
            if rows_to_add:
                ws.append_rows(rows_to_add)
                logger.info(f"Logged {len(rows_to_add)} new coupons to central sheet.")
        except Exception as e:
            logger.error(f"Error logging coupons: {e}")

    def track_coupon_usage(self, coupon_code, phone):
        """
        Updates sheets when a coupon is redeemed.
        1. Coupons Sheet: Status -> Redeemed, Assigned To -> Phone
        2. Customer List: Increment 'Coupons Used'
        3. Campaigns: Increment Redeemed Count, Update Rate
        """
        try:
            # 1. Update Coupons Sheet
            ws_coupons = self.sheet.worksheet("Coupons")
            campaign_name = None
            
            try:
                cell = ws_coupons.find(coupon_code, in_column=1)
                if cell:
                    row = cell.row
                    ws_coupons.update_cell(row, 4, "Redeemed")
                    ws_coupons.update_cell(row, 5, phone)
                    
                    # Get Campaign Name for Step 3 (Col 6)
                    if ws_coupons.col_count >= 6:
                        campaign_name = ws_coupons.cell(row, 6).value
                else:
                    logger.warning(f"Coupon {coupon_code} not found in sheet.")
                    
            except Exception as e:
                logger.error(f"Error updating Coupons sheet: {e}")

            # 2. Update Customer List
            if phone:
                try:
                    ws_cust = self.sheet.worksheet("Customer List")
                    cell = ws_cust.find(phone, in_column=1)
                    if cell:
                        row = cell.row
                        current_val = ws_cust.cell(row, 12).value
                        try:
                            new_val = int(current_val) + 1
                        except:
                            new_val = 1
                        ws_cust.update_cell(row, 12, new_val)
                except Exception as e:
                    logger.error(f"Error updating Customer List: {e}")

            # 3. Update Campaigns Sheet
            if campaign_name:
                try:
                    ws_camp = self.sheet.worksheet("Campaigns")
                    cell = ws_camp.find(campaign_name, in_column=1)
                    if cell:
                        row = cell.row
                        total_str = ws_camp.cell(row, 3).value
                        redeemed_str = ws_camp.cell(row, 7).value
                        
                        try: total = int(total_str)
                        except: total = 0
                            
                        try: redeemed = int(redeemed_str) + 1
                        except: redeemed = 1
                        
                        rate = f"{(redeemed / total * 100):.1f}%" if total > 0 else "0%"
                        
                        ws_camp.update_cell(row, 7, redeemed)
                        ws_camp.update_cell(row, 8, rate)
                except Exception as e:
                    logger.error(f"Error updating Campaigns sheet: {e}")

        except Exception as e:
            logger.error(f"Error tracking coupon usage: {e}")

    def log_coupons(self, coupon_list):
        """
        Logs generated coupons to the central Coupons sheet.
        coupon_list: List of dicts {'code', 'amount', 'expiry', 'campaign'}
        """
        try:
            ws = self.sheet.worksheet("Coupons")
            existing_codes = ws.col_values(1)
            
            rows_to_add = []
            for c in coupon_list:
                if c['code'] not in existing_codes:
                    rows_to_add.append([
                        c['code'], c['amount'], c.get('expiry', ''), "Available", "", c.get('campaign', '')
                    ])
            
            if rows_to_add:
                ws.append_rows(rows_to_add)
                logger.info(f"Logged {len(rows_to_add)} new coupons to central sheet.")
        except Exception as e:
            logger.error(f"Error logging coupons: {e}")

    def track_coupon_usage(self, coupon_code, phone):
        """
        Updates sheets when a coupon is redeemed.
        1. Coupons Sheet: Status -> Redeemed, Assigned To -> Phone
        2. Customer List: Increment 'Coupons Used'
        3. Campaigns: Increment Redeemed Count, Update Rate
        """
        try:
            # 1. Update Coupons Sheet
            ws_coupons = self.sheet.worksheet("Coupons")
            campaign_name = None
            
            try:
                cell = ws_coupons.find(coupon_code, in_column=1)
                if cell:
                    row = cell.row
                    ws_coupons.update_cell(row, 4, "Redeemed")
                    ws_coupons.update_cell(row, 5, phone)
                    
                    # Get Campaign Name for Step 3 (Col 6)
                    if ws_coupons.col_count >= 6:
                        campaign_name = ws_coupons.cell(row, 6).value
                else:
                    logger.warning(f"Coupon {coupon_code} not found in sheet.")
                    
            except Exception as e:
                logger.error(f"Error updating Coupons sheet: {e}")

            # 2. Update Customer List
            if phone:
                try:
                    ws_cust = self.sheet.worksheet("Customer List")
                    cell = ws_cust.find(phone, in_column=1)
                    if cell:
                        row = cell.row
                        current_val = ws_cust.cell(row, 12).value
                        try:
                            new_val = int(current_val) + 1
                        except:
                            new_val = 1
                        ws_cust.update_cell(row, 12, new_val)
                except Exception as e:
                    logger.error(f"Error updating Customer List: {e}")

            # 3. Update Campaigns Sheet
            if campaign_name:
                try:
                    ws_camp = self.sheet.worksheet("Campaigns")
                    cell = ws_camp.find(campaign_name, in_column=1)
                    if cell:
                        row = cell.row
                        total_str = ws_camp.cell(row, 3).value
                        redeemed_str = ws_camp.cell(row, 7).value
                        
                        try: total = int(total_str)
                        except: total = 0
                            
                        try: redeemed = int(redeemed_str) + 1
                        except: redeemed = 1
                        
                        rate = f"{(redeemed / total * 100):.1f}%" if total > 0 else "0%"
                        
                        ws_camp.update_cell(row, 7, redeemed)
                        ws_camp.update_cell(row, 8, rate)
                except Exception as e:
                    logger.error(f"Error updating Campaigns sheet: {e}")

        except Exception as e:
            logger.error(f"Error tracking coupon usage: {e}")

    def _is_blackout(self, check_date_str, rule_str):
        """
        Helper to determine if a date matches a blackout rule.
        Rules:
         - "YYYY-MM-DD" (Single Date)
         - "YYYY-MM-DD to YYYY-MM-DD" (Range)
         - "Every Monday" (Recurring)
        """
        check_date = datetime.strptime(check_date_str, "%Y-%m-%d")
        rule_str = rule_str.strip()

        # 1. Range: "2023-12-25 to 2023-12-26"
        if " to " in rule_str:
            try:
                start_str, end_str = rule_str.split(" to ")
                start_date = datetime.strptime(start_str.strip(), "%Y-%m-%d")
                end_date = datetime.strptime(end_str.strip(), "%Y-%m-%d")
                return start_date <= check_date <= end_date
            except:
                pass # Fall through if parsing fails
        
        # 2. Recurring: "Every Monday"
        if rule_str.lower().startswith("every "):
            day_name = rule_str.split(" ")[1] # e.g., "Monday"
            check_day = check_date.strftime("%A") # Full day name
            return day_name.lower() == check_day.lower()

        # 3. Single Date: "2023-12-25"
        return rule_str == check_date_str

if __name__ == "__main__":
    # Test run
    try:
        sm = SheetManager()
        sm.init_sheets()
        print("Sheets initialized successfully.")
    except Exception as e:
        print(f"Error: {e}")
