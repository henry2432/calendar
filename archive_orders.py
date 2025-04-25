# archive_orders.py
import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime
import pytz

# 全域設定
SHEET_ID = '1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs'
NEW_ORDERS_SHEET_NAME = '所有訂單'  # 新訂單表格（歷史彙整）
DAILY_SHEET_NAME = '即日訂單'          # 每日即日訂單

# 設定時區與今日日期
tz = pytz.timezone('Asia/Hong_Kong')
today = datetime.now(tz).date()

# 建立 Google Sheets 客戶端
def get_client():
    scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
    creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
    return gspread.authorize(creds)

# 主程式：將即日訂單追加到歷史訂單表
client = get_client()
sheet_today = client.open_by_key(SHEET_ID).worksheet(DAILY_SHEET_NAME)
sheet_archive = client.open_by_key(SHEET_ID).worksheet(NEW_ORDERS_SHEET_NAME)

# 讀取即日表並寫入歷史表
rows = sheet_today.get_all_values()
# 加上日期欄位
date_header = ['日期']
if sheet_archive.row_count == 0 or sheet_archive.get_all_values()[0][:len(rows[0])+1] != date_header + rows[0]:
    sheet_archive.clear()
    sheet_archive.append_row(date_header + rows[0])
for r in rows[1:]:
    sheet_archive.append_row([today.strftime('%Y/%m/%d')] + r, value_input_option='USER_ENTERED')
