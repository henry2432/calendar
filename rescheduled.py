import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime
import pytz
import requests

# 設定時區與日期
tz = pytz.timezone("Asia/Hong_Kong")
today = datetime.now(tz).date()

# WooCommerce API 設定
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = "ck_9269bc61a6553f1d1515a6ba7ad01f225a379b9a"
CONSUMER_SECRET = "cs_4df8324d11b0d8df493b2335efc3a26929ec73b5"

# Google Sheets 設定
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
SHEET_NAME = "WooCommerce Orders"
RESCHEDULE_FORM_SHEET = "改期表"

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

scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)
sheet_today = client.open_by_key(SHEET_ID).worksheet(SHEET_NAME)
sheet_reschedule = client.open_by_key(SHEET_ID).worksheet(RESCHEDULE_FORM_SHEET)

# 取得改期表
reschedule_records = sheet_reschedule.get_all_records()
order_ids_today = []
existing_today_rows = sheet_today.get_all_values()

# 用來避免重複 append
def get_existing_order_ids():
    return [row[0] for row in existing_today_rows[1:] if row]

existing_ids = get_existing_order_ids()

# 取得 WooCommerce 訂單
params = {"after": (today.replace(day=1)).strftime("%Y-%m-%dT00:00:00"), "per_page": 100}
response = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
orders = response.json()

# 比對是否改期為今天
def match_booking_date(order, target_date):
    for item in order.get("line_items", []):
        for meta in item.get("meta_data", []):
            if meta.get("key") == "yith_booking_data":
                booking_data = meta.get("value", {})
                booking_timestamp = booking_data.get("from")
                if booking_timestamp:
                    booking_date = datetime.fromtimestamp(booking_timestamp, tz).date()
                    return booking_date == target_date
    return False

# 分析訂單內容
def parse_order(order):
    name = order["billing"]["first_name"]
    phone = order["billing"]["phone"]
    payment = order["payment_method_title"]
    status_raw = order["status"]
    status_map = {"processing": "信用卡付款完成", "on-hold": "需確認", "completed": "已確認", "cancelled": "已取消"}
    status = status_map.get(status_raw, status_raw)

    product_counts = {k: 0 for k in list(PRODUCT_IDS.keys()) + list(SERVICE_IDS.keys())}

    for item in order.get("line_items", []):
        product_id = item["product_id"]
        product_name = next((name for name, pid in PRODUCT_IDS.items() if pid == product_id), None)
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

    return [name, phone, product_counts["單人獨木舟"], product_counts["雙人獨木舟"], product_counts["直立板"],
            product_counts["浮潛鏡"], product_counts["防水袋"], product_counts["電話防水袋"], payment, status]

# 執行比對與寫入
for record in reschedule_records:
    order_id = str(record.get("訂單號碼")).strip()
    new_date_str = record.get("新預約日期", "")
    if not order_id or not new_date_str:
        continue
    if order_id in existing_ids:
        continue

    try:
        new_date = datetime.strptime(new_date_str, "%Y/%m/%d").date()
    except ValueError:
        continue

    if new_date != today:
        continue

    matching_order = next((o for o in orders if str(o.get("id")) == order_id), None)
    if matching_order and match_booking_date(matching_order, today):
        row = parse_order(matching_order)
        row.append(f"改期單：{order_id}")
        sheet_today.append_row(row, value_input_option="USER_ENTERED")
        # 深綠色字體
        last_row = len(sheet_today.get_all_values())
        sheet_today.format(f"A{last_row}:J{last_row}", {"textFormat": {"foregroundColor": {"red": 0.0, "green": 0.4, "blue": 0.0}}})
