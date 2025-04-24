import requests
import datetime
import pytz
import gspread
from oauth2client.service_account import ServiceAccountCredentials

# WooCommerce API 設定
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = "ck_9269bc61a6553f1d1515a6ba7ad01f225a379b9a"
CONSUMER_SECRET = "cs_4df8324d11b0d8df493b2335efc3a26929ec73b5"

# Google Sheets 設定
SHEET_ID = "your_google_sheet_id"
SHEET_NAME = "TodayBooking"

# Booking 產品對應 ID
PRODUCT_IDS = {
    "單人獨木舟": 288,
    "雙人獨木舟": 289,
    "直立板": 290
}

SERVICE_IDS = {
    "浮潛鏡": 31,
    "防水袋": 32,
    "電話防水袋": 33
}

def get_today_orders():
    today = datetime.datetime.now(pytz.timezone('Asia/Hong_Kong'))
    four_months_ago = today - datetime.timedelta(days=120)
    params = {
        "after": four_months_ago.strftime("%Y-%m-%dT00:00:00"),
        "per_page": 100
    }
    response = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
    orders = response.json()
    
    today_orders = []
    for order in orders:
        for item in order.get("line_items", []):
            for meta in item.get("meta_data", []):
                if meta.get("key") == "yith_booking_data":
                    booking_data = meta["value"]
                    booking_date = datetime.datetime.fromtimestamp(booking_data["from"], pytz.timezone('Asia/Hong_Kong'))
                    if booking_date.date() == today.date():
                        today_orders.append(order)
                        break
            else:
                continue
            break
    return today_orders

def parse_order(order):
    name = order["billing"]["first_name"]
    phone = order["billing"]["phone"]
    payment = order["payment_method_title"]
    status = order["status"]

    product_counts = {
        "單人獨木舟": 0,
        "雙人獨木舟": 0,
        "直立板": 0,
        "浮潛鏡": 0,
        "防水袋": 0,
        "電話防水袋": 0
    }

    for item in order.get("line_items", []):
        product_id = item["product_id"]
        for name_key, pid in PRODUCT_IDS.items():
            if product_id == pid:
                for meta in item.get("meta_data", []):
                    if meta["key"] == "yith_booking_data":
                        booking = meta["value"]
                        persons = int(booking.get("persons", 0))
                        product_counts[name_key] += persons
                        
                        services = booking.get("booking_services", [])
                        service_qty = booking.get("booking_service_quantities", {})

                        for service_id in services:
                            for service_name, sid in SERVICE_IDS.items():
                                if sid == int(service_id):
                                    product_counts[service_name] += int(service_qty.get(str(service_id), 0))
    return [name, phone, product_counts["單人獨木舟"], product_counts["雙人獨木舟"],
            product_counts["直立板"], product_counts["浮潛鏡"],
            product_counts["防水袋"], product_counts["電話防水袋"],
            payment, status]

def write_to_sheet(data_rows):
    scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
    creds = ServiceAccountCredentials.from_json_keyfile_name('credentials.json', scope)
    client = gspread.authorize(creds)
    sheet = client.open_by_key(SHEET_ID).worksheet(SHEET_NAME)
    
    sheet.clear()
    headers = ["姓名", "電話", "單人獨木舟", "雙人獨木舟", "直立板", "浮潛鏡", "防水袋", "電話防水袋", "付款方式", "訂單狀態"]
    sheet.append_row(headers)
    for row in data_rows:
        sheet.append_row(row)

def main():
    orders = get_today_orders()
    rows = [parse_order(order) for order in orders]
    write_to_sheet(rows)

if __name__ == "__main__":
    main()




