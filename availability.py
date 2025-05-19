import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime, timedelta
import pytz
import requests
import os
import logging
import json

# 設置日誌
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# -----------------------------
# 常數與設定
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
now = datetime.now(tz)
today_str = now.strftime("%Y-%m-%d")

PRODUCT_IDS = {
    "單人獨木舟": 81,
    "雙人獨木舟": 82,
    "直立板": 84
}

# 允許的訂單狀態
VALID_STATUSES = {"completed", "processing", "on-hold"}

# -----------------------------
# Google Sheets 授權與連線
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
    equipment_sheet = client.open_by_key("1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs").worksheet("設備名額表")
    reschedule_sheet = client.open_by_key("1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs").worksheet("改期表")
except Exception as e:
    logger.error(f"無法連接到 Google Sheets: {e}")
    raise

# -----------------------------
# 擷取 WooCommerce 訂單
# -----------------------------
WC_API_URL = os.getenv("WC_API_URL") or "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = os.getenv("CONSUMER_KEY") or "ck_634b531fa4ac6b7a58a3ba3a33ad49174449e1d1"
CONSUMER_SECRET = os.getenv("CONSUMER_SECRET") or "cs_4c8599ff7dcbad53e34cef3b67e4d86955b18175"

params = {
    "after": (now - timedelta(days=1)).strftime("%Y-%m-%dT%H:%M:%S"),
    "per_page": 100
}
try:
    resp = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
    resp.raise_for_status()
    logger.info(f"API 請求成功，狀態碼: {resp.status_code}")
    logger.info(f"原始回應: {resp.text}")
    orders = resp.json()
    if not isinstance(orders, list):
        logger.error(f"API 回應格式錯誤: {orders}")
        raise TypeError("預期為訂單列表，但收到其他類型")
    logger.info(f"從 API 提取 {len(orders)} 筆訂單")
except requests.exceptions.HTTPError as e:
    logger.error(f"HTTP 錯誤: {e}, URL: {resp.url}")
    raise
except requests.exceptions.RequestException as e:
    logger.error(f"請求錯誤: {e}")
    raise

# -----------------------------
# 整理未來訂單（含未完成改期單）
# -----------------------------
def parse_date(ts):
    return datetime.fromtimestamp(int(ts), tz).strftime("%Y-%m-%d")

future_counts = {}
def add_counts(date_str, pid, persons):
    if date_str < today_str:
        return
    if date_str not in future_counts:
        future_counts[date_str] = {k: 0 for k in PRODUCT_IDS}
    name = next((n for n, v in PRODUCT_IDS.items() if v == pid), None)
    if name:
        future_counts[date_str][name] += persons

for order in orders:
    if not isinstance(order, dict):
        logger.error(f"無效的訂單格式: {order}")
        continue
    # 過濾訂單狀態
    status = order.get("status", "")
    if status not in VALID_STATUSES:
        logger.info(f"跳過訂單 {order.get('id', 'N/A')}，狀態為 {status}，不符條件")
        continue
    for item in order.get("line_items", []):
        pid = item.get("product_id")
        for m in item.get("meta_data", []):
            if m.get("key") == "yith_booking_data":
                b = m.get("value", {})
                date_str = parse_date(b.get("from"))
                persons = int(b.get("persons", 0))
                add_counts(date_str, pid, persons)

reschedules = reschedule_sheet.get_all_records()
for r in reschedules:
    if str(r.get("是否完成改期","")).strip() != "是":
        new_date = r.get("新預約日期")
        oid = str(r.get("訂單號碼","")).strip()
        order = next((o for o in orders if str(o.get("id")) == oid), None)
        if order and order.get("status", "") in VALID_STATUSES:
            for item in order.get("line_items", []):
                pid = item.get("product_id")
                for m in item.get("meta_data", []):
                    if m.get("key") == "yith_booking_data":
                        persons = int(m.get("value", {}).get("persons", 0))
                        add_counts(new_date, pid, persons)

# -----------------------------
# 寫入 Google Sheet
# -----------------------------
header = ["日期"] + list(PRODUCT_IDS.keys())
data = [header]
for date in sorted(future_counts):
    row = [date] + [future_counts[date][k] for k in PRODUCT_IDS]
    data.append(row)

try:
    equipment_sheet.clear()
    equipment_sheet.append_rows(data, value_input_option="USER_ENTERED")
    logger.info("設備名額表更新完成")
except Exception as e:
    logger.error(f"無法寫入 Google Sheets: {e}")
    raise

print("設備名額表更新完成。")
