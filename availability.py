import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime, timedelta
import pytz
import requests
import os

# -----------------------------
# 常數與設定
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
now = datetime.now(tz)
today_str = now.strftime("%Y-%m-%d")

PRODUCT_IDS = {
    "單人獨木舟": 288,
    "雙人獨木舟": 289,
    "直立板": 290
}

# -----------------------------
# Google Sheets 授權與連線
# -----------------------------
scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)

equipment_sheet = client.open_by_key("1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs").worksheet("設備名額表")
reschedule_sheet = client.open_by_key("1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs").worksheet("改期表")

# -----------------------------
# 擷取 WooCommerce 訂單
# -----------------------------
WC_API_URL = os.getenv("WC_API_URL")
CONSUMER_KEY = os.getenv("CONSUMER_KEY")
CONSUMER_SECRET = os.getenv("CONSUMER_SECRET")

params = {
    "after": (now - timedelta(days=1)).strftime("%Y-%m-%dT00:00:00"),
    "per_page": 100
}
resp = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
orders = resp.json()

# -----------------------------
# 整理未來訂單（含改期單）
# -----------------------------
def parse_date(timestamp):
    return datetime.fromtimestamp(int(timestamp), tz).strftime("%Y-%m-%d")

future_counts = {}

def add_counts(date_str, pid, persons):
    if date_str < today_str:
        return
    if date_str not in future_counts:
        future_counts[date_str] = {k: 0 for k in PRODUCT_IDS}
    for k, v in PRODUCT_IDS.items():
        if v == pid:
            future_counts[date_str][k] += persons

# 處理 WooCommerce 原始訂單
for order in orders:
    for item in order.get("line_items", []):
        for m in item.get("meta_data", []):
            if m.get("key") == "yith_booking_data":
                b = m["value"]
                date_str = parse_date(b.get("from"))
                persons = int(b.get("persons", 0))
                add_counts(date_str, item["product_id"], persons)

# 處理改期單（只統計尚未完成改期的）
reschedules = reschedule_sheet.get_all_records()
for row in reschedules:
    if row.get("是否完成改期") != "是":
        new_date = row.get("新預約日期")
        oid = str(row.get("訂單號碼")).strip()
        for order in orders:
            if str(order["id"]) == oid:
                for item in order.get("line_items", []):
                    for m in item.get("meta_data", []):
                        if m.get("key") == "yith_booking_data":
                            persons = int(m["value"].get("persons", 0))
                            add_counts(new_date, item["product_id"], persons)

# -----------------------------
# 寫入 Google Sheet
# -----------------------------
header = ["日期"] + list(PRODUCT_IDS.keys())
data = [header]
for date in sorted(future_counts.keys()):
    row = [date] + [future_counts[date][k] for k in PRODUCT_IDS]
    data.append(row)

# 清除舊資料並寫入
equipment_sheet.clear()
equipment_sheet.append_rows(data, value_input_option="USER_ENTERED")

print("設備名額統計完成。")

