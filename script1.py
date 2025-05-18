import requests
import datetime
import pytz
import gspread
from oauth2client.service_account import ServiceAccountCredentials

# WooCommerce API 設定
WC_API_URL = "https://kayarine.club/wp-json/wooapi/v3/orders"
CONSUMER_KEY = "ck_634b531fa4ac6b7a58a3ba3a33ad49174449e1d1"
CONSUMER_SECRET = "cs_4c8599ff7dcbad53e34cef3b67e4d86955b18175"

# Google Sheets 設定
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
SHEET_NAME = "即日訂單"

# Booking 產品對應 ID
PRODUCT_IDS = {
    "單人獨木舟": 81,
    "雙人獨木舟": 82,
    "直立板": 84
}

SERVICE_IDS = {
    "浮潛鏡租借": 34,
    "手機防水袋": 35,
    "浮潛鏡加購": 36,
    "防水袋加購": 37
}

def get_today_orders():
    today = datetime.datetime.now(pytz.timezone('Asia/Hong_Kong'))
    start = today.strftime("%Y-%m-%dT00:00:00")
    end = today.strftime("%Y-%m-%dT23:59:59")
    params = {
        "after": start,
        "before": end,
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
    status_raw = order["status"]

    status_map = {
        "processing": "信用卡付款完成",
        "on-hold": "需確認",
        "completed": "已確認",
        "cancelled": "已取消"
    }
    status = status_map.get(status_raw, status_raw)

    product_counts = {
        "單人獨木舟": 0,
        "雙人獨木舟": 0,
        "直立板": 0,
        "浮潛鏡租借": 0,
        "手機防水袋": 0,
        "浮潛鏡加購": 0,
        "防水袋加購": 0
    }

    for item in order.get("line_items", []):
        product_id = item["product_id"]
        product_name = next((name_key for name_key, pid in PRODUCT_IDS.items() if product_id == pid), None)
        if not product_name:
            continue

        for meta in item.get("meta_data", []):
            if meta["key"] == "yith_booking_data":
                booking = meta["value"]
                persons = int(booking.get("persons", 0))
                product_counts[product_name] += persons

                selected_services = booking.get("booking_services", [])
                service_quantities = booking.get("booking_service_quantities", {})

                for service_id in selected_services:
                    sid = int(service_id)
                    for service_name, known_sid in SERVICE_IDS.items():
                        if sid == known_sid:
                            qty = int(service_quantities.get(str(sid), 0))
                            product_counts[service_name] += qty
                break

    return [
        name, phone,
        product_counts["單人獨木舟"], product_counts["雙人獨木舟"], product_counts["直立板"],
        product_counts["浮潛鏡租借"], product_counts["手機防水袋"],
        product_counts["浮潛鏡加購"], product_counts["防水袋加購"],
        payment, status,
        ""  # 訂單到達？（初始為空）
    ]

def write_to_sheet(data_rows):
    scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
    creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
    client = gspread.authorize(creds)
    sheet = client.open_by_key(SHEET_ID).worksheet(SHEET_NAME)
    
    sheet.clear()
    headers = [
        "姓名", "電話", "單人獨木舟", "雙人獨木舟", "直立板",
        "浮潛鏡租借", "手機防水袋", "浮潛鏡加購", "防水袋加購",
        "付款方式", "訂單狀態", "訂單到達？"
    ]
    sheet.append_row(headers)
    for row in data_rows:
        sheet.append_row(row)

def main():
    orders = get_today_orders()
    rows = [parse_order(order) for order in orders]
    write_to_sheet(rows)

if __name__ == "__main__":
    main()


