import requests
import datetime
import pytz
import gspread
from oauth2client.service_account import ServiceAccountCredentials
import logging

# 設置日誌
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# WooCommerce API 設定
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
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

def get_today_orders(target_date=None):
    if target_date is None:
        target_date = datetime.datetime.now(pytz.timezone('Asia/Hong_Kong'))
    start = target_date.strftime("%Y-%m-%dT00:00:00")
    end = target_date.strftime("%Y-%m-%dT23:59:59")
    params = {
        "after": start,
        "before": end,
        "per_page": 100
    }
    try:
        response = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
        response.raise_for_status()
        return response.json()
    except requests.exceptions.RequestException as e:
        logger.error(f"API 請求失敗: {e}")
        raise

def parse_order(order, tz):
    name = order.get("billing", {}).get("first_name", "")
    phone = order.get("billing", {}).get("phone", "")
    payment = order.get("payment_method_title", "")
    status_map = {
        "processing": "信用卡付款完成",
        "on-hold": "需確認",
        "completed": "已確認",
        "cancelled": "已取消"
    }
    status = status_map.get(order.get("status", ""), order.get("status", ""))

    counts = {k: 0 for k in PRODUCT_IDS}
    counts.update({k: 0 for k in SERVICE_IDS})
    booking_date = ""

    for item in order.get("line_items", []):
        pid = item.get("product_id")
        pname = next((n for n, p in PRODUCT_IDS.items() if p == pid), None)
        if pname:
            for m in item.get("meta_data", []):
                if m.get("key") == "yith_booking_data":
                    b = m.get("value", {})
                    counts[pname] += int(b.get("persons", 0))
                    from_timestamp = int(b.get("from", 0))
                    booking_date = datetime.datetime.fromtimestamp(from_timestamp, tz).strftime("%Y-%m-%d")
                    for sid in b.get("booking_services", []):
                        svc = next((n for n, i in SERVICE_IDS.items() if i == int(sid)), None)
                        if svc:
                            counts[svc] += int(b.get("booking_service_quantities", {}).get(str(sid), 0))
                    break

    return [
        order.get("id", ""), name, phone, booking_date,
        counts["單人獨木舟"], counts["雙人獨木舟"], counts["直立板"],
        counts["浮潛鏡租借"], counts["手機防水袋"], counts["浮潛鏡加購"], counts["防水袋加購"],
        payment, status, ""
    ]

# 主流程
tz = pytz.timezone("Asia/Hong_Kong")
scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)
sheet = client.open_by_key(SHEET_ID).worksheet(SHEET_NAME)

orders = get_today_orders()
if not sheet.get_all_records():
    header = ["Order ID", "姓名", "電話", "預訂日期",
              "單人獨木舟", "雙人獨木舟", "直立板",
              "浮潛鏡租借", "手機防水袋", "浮潛鏡加購", "防水袋加購",
              "付款方式", "訂單狀態", "訂單到達？"]
    sheet.clear()
    sheet.append_row(header)

for order in orders:
    row = parse_order(order, tz)
    if row:
        sheet.append_row(row, value_input_option="USER_ENTERED")

print("即日訂單更新完成。")
