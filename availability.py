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
WC_API_URL = os.getenv("WC_API_URL") or "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = os.getenv("CONSUMER_KEY")
CONSUMER_SECRET = os.getenv("CONSUMER_SECRET")

# 抓過去 24 小時訂單，以防 missing
params = {
    "after": (now - timedelta(days=1)).strftime("%Y-%m-%dT%H:%M:%S"),
    "per_page": 100
}
resp = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
resp.raise_for_status()
orders = resp.json()

# -----------------------------
# 整理未來訂單（含未完成改期單）
# -----------------------------
def parse_date(ts):
    return datetime.fromtimestamp(int(ts), tz).strftime("%Y-%m-%d")

# 初始化日期統計
future_counts = {}
def add_counts(date_str, pid, persons):
    if date_str < today_str:
        return
    if date_str not in future_counts:
        future_counts[date_str] = {k: 0 for k in PRODUCT_IDS}
    name = next((n for n, v in PRODUCT_IDS.items() if v == pid), None)
    if name:
        future_counts[date_str][name] += persons

# 處理 API 原始訂單
for order in orders:
    for item in order.get("line_items", []):
        pid = item.get("product_id")
        for m in item.get("meta_data", []):
            if m.get("key") == "yith_booking_data":
                b = m.get("value", {})
                date_str = parse_date(b.get("from"))
                persons = int(b.get("persons", 0))
                add_counts(date_str, pid, persons)

# 處理改期表（未完成改期單）
reschedules = reschedule_sheet.get_all_records()
for r in reschedules:
    if str(r.get("是否完成改期","")).strip() != "是":
        new_date = r.get("新預約日期")
        oid = str(r.get("訂單號碼","")).strip()
        # 找 API 訂單
        order = next((o for o in orders if str(o.get("id")) == oid), None)
        if order:
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

equipment_sheet.clear()
equipment_sheet.append_rows(data, value_input_option="USER_ENTERED")

print("設備名額表更新完成。")


