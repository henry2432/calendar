import os
import requests
import datetime
import pytz
import gspread
from oauth2client.service_account import ServiceAccountCredentials

# 讀取環境變數
CK   = os.getenv("CONSUMER_KEY")
CS   = os.getenv("CONSUMER_SECRET")
WC   = os.getenv("WC_SITE", "https://kayarine.club") + "/wp-json/wc/v3/orders"
SHEET = os.getenv("SHEET_NAME", "WooCommerce Orders")
# 本地憑證 json 內容放在 GCP_SA_JSON secret
import json
with open('/tmp/credentials.json','w') as f:
    f.write(os.getenv("GCP_SA_JSON"))
CREDS = '/tmp/credentials.json'

# 時區與日期
HK = pytz.timezone("Asia/Hong_Kong")
today = datetime.datetime.now(HK).date()
start = (today - datetime.timedelta(days=120)).isoformat() + "T00:00:00"

# 連 Google Sheets
scope = ["https://spreadsheets.google.com/feeds","https://www.googleapis.com/auth/drive"]
creds = ServiceAccountCredentials.from_json_keyfile_name(CREDS, scope)
sh = gspread.authorize(creds).open(SHEET).sheet1

# 清表（保留標題）
sh.clear()
sh.append_row(["姓名","電話","單人獨木舟","雙人獨木舟","直立板","Tour"])

# 抓訂單
rows = []
for p in range(1,11):
    resp = requests.get(WC, auth=(CK,CS), params={"per_page":100,"page":p,"after":start})
    data = resp.json()
    if not data: break
    for o in data:
        cnt = {"單人獨木舟":0,"雙人獨木舟":0,"直立板":0,"Tour":0}
        found = False
        for it in o["line_items"]:
            for m in it["meta_data"]:
                if m["key"]=="yith_booking_data":
                    dt = datetime.datetime.fromtimestamp(m["value"]["from"], HK).date()
                    if dt==today:
                        pid,itq = it["product_id"], it["quantity"]
                        if   pid==288: cnt["單人獨木舟"]+=itq
                        elif pid==289: cnt["雙人獨木舟"]+=itq
                        elif pid==290: cnt["直立板"]    +=itq
                        else:          cnt["Tour"]       +=itq
                        found = True
            if found:
                b = o["billing"]
                rows.append([f"{b['first_name']} {b['last_name']}", b["phone"],
                             cnt["單人獨木舟"],cnt["雙人獨木舟"],cnt["直立板"],cnt["Tour"]])
                break

# 寫入
if rows:
    sh.append_rows(rows, value_input_option="USER_ENTERED")
print(f"寫入 {len(rows)} 筆今日訂單")
