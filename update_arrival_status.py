import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime, timedelta
import pytz
import logging
import json

# 設置日誌
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# 設定時區為香港時間
tz = pytz.timezone("Asia/Hong_Kong")
yesterday = (datetime.now(tz) - timedelta(days=1)).strftime("%Y-%m-%d")

# Google Sheets 配置
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
ALL_ORDERS_SHEET = "所有訂單"
DAILY_ORDERS_SHEET = "即日訂單"

# 驗證並連接到 Google Sheets
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
    sheet_all = client.open_by_key(SHEET_ID).worksheet(ALL_ORDERS_SHEET)
    sheet_daily = client.open_by_key(SHEET_ID).worksheet(DAILY_ORDERS_SHEET)
except Exception as e:
    logger.error(f"無法連接到 Google Sheets: {e}")
    raise

# 獲取所有訂單資料
try:
    all_orders = sheet_all.get_all_records()
    all_order_dict = {str(r["Order ID"]): r for r in all_orders}
    logger.info(f"從 '所有訂單' 讀取 {len(all_orders)} 筆資料")
except Exception as e:
    logger.error(f"無法讀取 '所有訂單' 資料: {e}")
    raise

# 獲取昨日即日訂單資料
try:
    daily_orders = sheet_daily.get_all_records()
    logger.info(f"從 '即日訂單' 讀取 {len(daily_orders)} 筆資料")
except Exception as e:
    logger.error(f"無法讀取 '即日訂單' 資料: {e}")
    raise

# 更新「訂單到達？」狀態
updated_count = 0
for daily in daily_orders:
    oid = str(daily.get("Order ID", ""))
    arrival_status = daily.get("訂單到達？", "")
    if oid and arrival_status and oid in all_order_dict:
        row_idx = all_orders.index(all_order_dict[oid]) + 2  # +2 因為有標頭且索引從 1 開始
        try:
            sheet_all.update_cell(row_idx, 15, arrival_status)  # 第 15 欄為「訂單到達？」（O 欄）
            updated_count += 1
            logger.info(f"更新訂單 {oid} 的訂單到達狀態為 {arrival_status}")
        except Exception as e:
            logger.error(f"更新訂單 {oid} 的訂單到達狀態失敗: {e}")

print(f"已為 {yesterday} 的 {updated_count} 筆訂單更新訂單到達狀態。")
