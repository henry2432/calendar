import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime, timedelta
import pytz

# Set the time zone to Hong Kong Time (HKT)
tz = pytz.timezone("Asia/Hong_Kong")

# Calculate yesterday's date in HKT
now = datetime.now(tz)
yesterday = now - timedelta(days=1)
yesterday_str = yesterday.strftime("%Y-%m-%d")

# Google Sheets configuration
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"  # Replace with your actual Sheet ID
ALL_ORDERS_SHEET = "所有訂單"
DAILY_ORDERS_SHEET = "即日訂單"

# Authenticate and connect to Google Sheets
scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)

# Open the worksheets
sheet_all = client.open_by_key(SHEET_ID).worksheet(ALL_ORDERS_SHEET)
sheet_daily = client.open_by_key(SHEET_ID).worksheet(DAILY_ORDERS_SHEET)

# Get all records from both sheets
all_orders = sheet_all.get_all_records()
daily_orders = sheet_daily.get_all_records()

# Create a dictionary of all orders for quick lookup by Order ID
all_order_dict = {str(r["Order ID"]): r for r in all_orders}

# Iterate through the same-day orders and update the arrival status in all orders
for daily in daily_orders:
    oid = str(daily.get("Order ID", ""))
    arrival_status = daily.get("訂單到達？", "")
    
    if oid and arrival_status and oid in all_order_dict:
        # Find the row index in the all orders sheet (adding 2 for header and 1-based index)
        row_idx = all_orders.index(all_order_dict[oid]) + 2
        # Update the "訂單到達？" column (assuming it's the 14th column, adjust if necessary)
        sheet_all.update_cell(row_idx, 14, arrival_status)

print(f"Updated arrival statuses for orders from {yesterday_str}.")
