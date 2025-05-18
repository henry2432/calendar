import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime, timedelta
import pytz

# 設定時區為香港時間
tz = pytz.timezone("Asia/Hong_Kong")
yesterday = (datetime.now(tz) - timedelta(days=1)).strftime("%Y-%m-%d")

# Google Sheets 配置
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
ALL_ORDERS_SHEET = "所有訂單"
DAILY_ORDERS_SHEET = "即日訂單"

# 驗證並連接到 Google Sheets
scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)

# 開啟工作表
sheet_all = client.open_by_key(SHEET_ID).worksheet(ALL_ORDERS_SHEET)
sheet_daily = client.open_by_key(SHEET_ID).worksheet(DAILY_ORDERS_SHEET)

# 獲取所有訂單資料
all_orders = sheet_all.get_all_records()
all_order_dict = {str(r["Order ID"]): r for r in all_orders}

# 獲取昨日即日訂單資料
daily_orders = sheet_daily.get_all_records()

# 更新「訂單到達？」狀態
updated_count = 0
for daily in daily_orders:
    oid = str(daily.get("Order ID", ""))
    arrival_status = daily.get("訂單到達？", "")
    if oid and arrival_status and oid in all_order_dict:
        row_idx = all_orders.index(all_order_dict[oid]) + 2  # +2 因為有標頭且索引從 1 開始
        sheet_all.update_cell(row_idx, 14, arrival_status)  # 第 14 欄為「訂單到達？」
        updated_count += 1

print(f"已為 {yesterday} 的 {updated_count} 筆訂單更新訂單到達狀態。")
