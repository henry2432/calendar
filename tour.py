import gspread
from oauth2client.service_account import ServiceAccountCredentials
import requests
from datetime import datetime, timedelta
import pytz
import logging
import json

# 設置日誌
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# -----------------------------
# 配置與常量
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
now = datetime.now(tz)
four_months_ago = now - timedelta(days=120)

# WooCommerce API 配置
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = "ck_634b531fa4ac6b7a58a3ba3a33ad49174449e1d1"
CONSUMER_SECRET = "cs_4c8599ff7dcbad53e34cef3b67e4d86955b18175"

SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
TOUR_SHEET = "旅行團訂單"

# 定義旅行團產品 ID（假設）
TOUR_PRODUCT_IDS = {81, 82, 84}

# 允許的訂單狀態
VALID_STATUSES = {"completed", "processing", "on-hold"}

# -----------------------------
# Google Sheets 客戶端
# -----------------------------
scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
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
    sheet = client.open_by_key(SHEET_ID).worksheet(TOUR_SHEET)
except Exception as e:
    logger.error(f"無法連接到 Google Sheets: {e}")
    raise

# -----------------------------
# 拉取旅行團訂單
# -----------------------------
def get_tour_orders():
    params = {
        "after": four_months_ago.strftime("%Y-%m-%dT00:00:00"),
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

def parse_order(order):
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
    email = order.get("billing", {}).get("email", "")
    total = order.get("total", "0.00")
    status_map = {
        "processing": "信用卡付款完成",
        "on-hold": "需確認",
        "completed": "已確認",
        "cancelled": "已取消"
    }
    status_display = status_map.get(status, status)

    date_created = order.get("date_created", "")
    order_date = ""
    if date_created:
        try:
            order_date = datetime.strptime(date_created, "%Y-%m-%dT%H:%M:%S").strftime("%Y-%m-%d")
        except ValueError as e:
            logger.error(f"無法解析訂單日期 {date_created}: {e}")
            order_date = ""

    return [order.get("id", ""), name, phone, email, order_date, status_display, total]

# 主流程
orders = get_tour_orders()
rows = sheet.get_all_records()
if not rows:
    header = ["Order ID", "姓名", "電話", "電子郵件", "訂單日期", "訂單狀態", "訂單總額"]
    sheet.clear()
    sheet.append_row(header)

for order in orders:
    row = parse_order(order)
    if row:
        try:
            sheet.append_row(row, value_input_option="USER_ENTERED")
            logger.info(f"新增訂單 {row[0]} 到旅行團訂單")
        except Exception as e:
            logger.error(f"寫入訂單 {row[0]} 失敗: {e}")

print("旅行團訂單更新完成。")
