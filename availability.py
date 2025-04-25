import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime, timedelta
import pytz

# -----------------------------
# 設定時區與日期
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
today = datetime.now(tz).date()
three_months_later = today + timedelta(days=90)

# -----------------------------
# Google Sheets 設定
# -----------------------------
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
ALL_ORDERS_SHEET_NAME = "所有訂單"  # 所有訂單分頁名稱
AVAILABILITY_SHEET_NAME = "設備名額表"  # 設備名額表分頁名稱

# -----------------------------
# 設備名額設定
# -----------------------------
PRODUCT_IDS = {"單人獨木舟": 50, "雙人獨木舟": 80, "直立板": 20}  # 設備初始數量

# -----------------------------
# 建立 Google Sheets 客戶端
# -----------------------------
scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)
sheet_all_orders = client.open_by_key(SHEET_ID).worksheet(ALL_ORDERS_SHEET_NAME)
sheet_availability = client.open_by_key(SHEET_ID).worksheet(AVAILABILITY_SHEET_NAME)

# -----------------------------
# 讀取所有訂單
# -----------------------------
all_orders = sheet_all_orders.get_all_records()

# -----------------------------
# 設備剩餘數量統計
# -----------------------------
# 初始化設備數量
availability = {key: value for key, value in PRODUCT_IDS.items()}

# 遍歷所有訂單，計算訂單中的設備數量
for order in all_orders:
    order_date_str = order.get('訂單日期', '')  # 假設有訂單日期欄位
    if not order_date_str:
        continue
    try:
        order_date = datetime.strptime(order_date_str, '%Y-%m-%d').date()
    except ValueError:
        continue
    
    # 只處理未來 3 個月內的訂單
    if order_date > three_months_later:
        continue

    # 解析訂單中的設備數量
    for key in availability.keys():
        if order.get(key, 0):  # 假設訂單中有設備數量欄位
            availability[key] -= order.get(key, 0)

# -----------------------------
# 更新設備名額表
# -----------------------------
# 構造設備名額表的資料
availability_data = [['設備', '剩餘數量']]
for key, value in availability.items():
    availability_data.append([key, value])

# 清空並更新設備名額表
sheet_availability.clear()
sheet_availability.insert_rows(availability_data, 1)

print("設備名額表更新完成！")

