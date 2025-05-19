import requests
import datetime
import pytz
import gspread
from oauth2client.service_account import ServiceAccountCredentials
import logging
import json

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

# 允許的訂單狀態
VALID_STATUSES = {"completed", "processing", "on-hold"}

def get_today_orders(target_date=None):
    tz = pytz.timezone('Asia/Hong_Kong')
    if target_date is None:
        target_date = datetime.datetime.now(tz)
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
        logger.info(f"API 請求成功，狀態碼: {response.status_code}")
        logger.info(f"原始回應: {response.text}")
        orders = response.json()
        if not isinstance(orders, list):
            logger.error(f"API 回應格式錯誤: {orders}")
            raise TypeError("預期為訂單列表，但收到其他類型")
        logger.info(f"從 API 提取 {len(orders)} 筆訂單")
        return orders
    except requests.exceptions.HTTPError as e:
        logger.error(f"HTTP 錯誤: {e}, URL: {response.url}")
        raise
    except requests.exceptions.RequestException as e:
        logger.error(f"請求錯誤: {e}")
        raise

def parse_order(order, tz):
    if not isinstance(order, dict):
        logger.error(f"無效的訂單格式: {order}")
        return None

    # 過濾訂單狀態
    status = order.get("status", "")
    if status not in VALID_STATUSES:
        logger.info(f"跳過訂單 {order.get('id', 'N/A')}，狀態為 {status}，不符條件")
        return None

    name = order.get("billing", {}).get("first_name", "")
    phone = order.get("billing", {}).get("phone", "")
    payment = order.get("payment_method_title", "")
    total = order.get("total", "0.00")
    status_map = {
        "processing": "信用卡付款完成",
        "on-hold": "需確認",
        "completed": "已確認",
        "cancelled": "已取消"
    }
    status_display = status_map.get(status, status)

    counts = {k: 0 for k in PRODUCT_IDS}
    counts.update({k: 0 for k in SERVICE_IDS})

    # 使用 date_created 作為訂單日期
    date_created = order.get("date_created", "")
    order_date = ""
    if date_created:
        try:
            order_date = datetime.strptime(date_created, "%Y-%m-%dT%H:%M:%S").strftime("%Y-%m-%d")
        except ValueError as e:
            logger.error(f"無法解析訂單日期 {date_created}: {e}")
            order_date = ""

    for item in order.get("line_items", []):
        pid = item.get("product_id")
        pname = next((n for n,p in PRODUCT_IDS.items() if p==pid), None)
        if pname:
            for m in item.get("meta_data", []):
                if m.get("key")=="yith_booking_data":
                    b = m.get("value", {})
                    counts[pname] += int(b.get("persons", 0))
                    for sid in b.get("booking_services", []):
                        svc = next((n for n,i in SERVICE_IDS.items() if i==int(sid)), None)
                        if svc:
                            counts[svc] += int(b.get("booking_service_quantities", {}).get(str(sid), 0))
                    break

    return [
        order.get("id", ""), name, phone, order_date,
        counts["單人獨木舟"], counts["雙人獨木舟"], counts["直立板"],
        counts["浮潛鏡租借"], counts["手機防水袋"], counts["浮潛鏡加購"], counts["防水袋加購"],
        payment, status_display, total, ""
    ]

# 主流程
tz = pytz.timezone("Asia/Hong_Kong")
scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
try:
    with open('/tmp/credentials.json', 'r') as f:
        creds_content = f.read()
        if not creds_content:
            logger.error("credentials.json 檔案為空")
            raise ValueError("credentials.json 檔案為空")
        creds_json = json.loads(creds_content)
        logger.info(f"成功讀取 credentials.json，client_email: {creds_json.get('client_email', 'N/A')}")
    creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
    client = gspread.authorize(creds)
    sheet = client.open_by_key(SHEET_ID).worksheet(SHEET_NAME)
except Exception as e:
    logger.error(f"無法連接到 Google Sheets: {e}")
    raise

orders = get_today_orders()
if not sheet.get_all_records():
    header = ["Order ID", "姓名", "電話", "訂單日期",
              "單人獨木舟", "雙人獨木舟", "直立板",
              "浮潛鏡租借", "手機防水袋", "浮潛鏡加購", "防水袋加購",
              "付款方式", "訂單狀態", "訂單總額", "訂單到達？"]
    sheet.clear()
    sheet.append_row(header)

for order in orders:
    row = parse_order(order, tz)
    if row:
        try:
            sheet.append_row(row, value_input_option="USER_ENTERED")
            logger.info(f"新增訂單 {row[0]} 到即日訂單")
        except Exception as e:
            logger.error(f"寫入訂單 {row[0]} 失敗: {e}")

print("即日訂單更新完成。")
