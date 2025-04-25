# availability.py
import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime, timedelta
import pytz
import pandas as pd

# 全域設定
SHEET_ID = '1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs'
NEW_ORDERS_SHEET_NAME = 'New Orders'            # 歷史訂單表
EQUIPMENT_SHEET_NAME = 'Equipment Availability'  # 設備名額表

# 設定時區與日期範圍
tz = pytz.timezone('Asia/Hong_Kong')
today = datetime.now(tz).date()
end_date = today + timedelta(days=90)

# 設備容量設定 (可改為讀自 Sheet)
CAPACITY = {
    '單人獨木舟': 50,
    '雙人獨木舟': 80,
    '直立板': 20
}

# 授權並取得 client
def get_client():
    scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
    creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
    return gspread.authorize(creds)

client = get_client()
sheet_orders = client.open_by_key(SHEET_ID).worksheet(NEW_ORDERS_SHEET_NAME)

# 讀取歷史訂單與彙整使用量
df_orders = pd.DataFrame(sheet_orders.get_all_records())
df_orders['日期'] = pd.to_datetime(df_orders['日期'], format='%Y/%m/%d').dt.date
df_usage = df_orders.groupby('日期')[list(CAPACITY.keys())].sum().reindex(
    pd.date_range(today, end_date).date, fill_value=0
)

# 計算剩餘量
df_remain = df_usage.copy()
for equip, cap in CAPACITY.items():
    df_remain[equip] = cap - df_usage[equip]

# 寫入「設備名額表」
sheet_equip = client.open_by_key(SHEET_ID).worksheet(EQUIPMENT_SHEET_NAME)
sheet_equip.clear()
header = ['日期'] + list(CAPACITY.keys())
sheet_equip.append_row(header)
for date, row in df_remain.iterrows():
    sheet_equip.append_row([date.strftime('%Y/%m/%d')] + row.tolist(), value_input_option='USER_ENTERED')

# 列印標題和容量設定
print('設備名額表標題:', header)
print('設備總數量設定:')
for equip, cap in CAPACITY.items():
    print(f'  - {equip}: {cap}')
