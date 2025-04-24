import os
import requests
import datetime
import pytz
import gspread
from oauth2client.service_account import ServiceAccountCredentials

# === 參數設定（由環境變數讀取） ===
CK   = os.getenv("ck_9269bc61a6553f1d1515a6ba7ad01f225a379b9a")
CS   = os.getenv("cs_4df8324d11b0d8df493b2335efc3a26929ec73b5")
WC_API_URL = os.getenv("WC_SITE", "https://kayarine.club") + "/wp-json/wc/v3/orders"

SHEET_NAME = os.getenv("SHEET_NAME", "WooCommerce Orders")
CREDS_PATH = "/tmp/credentials.json"  # workflow 會先把 GCP_SA_JSON 寫到這裡

# 時區 & 日期
HK = pytz.timezone("Asia/Hong_Kong")
today = datetime.datetime.now(HK).date()
start_date = (today - datetime.timedelta(days=120)).isoformat() + "T00:00:00"

# Google Sheets 認證
scope = ["https://spreadsheets.google.com/feeds","https://www.googleapis.com/auth/drive"]
creds = ServiceAccountCredentials.from_json_keyfile_name(CREDS_PATH, scope)
gc    = gspread.authorize(creds)
sheet = gc.open(SHEET_NAME).sheet1

# 清空舊資料，重寫標題
sheet.clear()
header = [
    "姓名","電話",
    "單人獨木舟","雙人獨木舟","直立板","Tour",
    "浮潛鏡","防水袋","電話防水袋"
]
sheet.append_row(header)

# 服務 ID→名稱對照
SERVICE_MAP = {
    "31": "浮潛鏡",
    "32": "防水袋",
    "33": "電話防水袋"
}

# 分頁抓取 WooCommerce 訂單
all_orders = []
for page in range(1, 11):
    params = {
        "per_page": 100,
        "page": page,
        "after": start_date,
        "consumer_key": CK,
        "consumer_secret": CS
    }
    resp = requests.get(WC_API_URL, params=params)
    resp.raise_for_status()
    batch = resp.json()
    if not batch:
        break
    all_orders.extend(batch)

# 篩選「今日預約」的訂單並彙總
rows = []
for order in all_orders:
    # 訂單整體的設備數量
    equip = {"單人獨木舟":0,"雙人獨木舟":0,"直立板":0,"Tour":0}
    # 以中文名稱為 key 來累加服務數量
    svc   = {name: 0 for name in SERVICE_MAP.values()}
    found_today = False

    for item in order.get("line_items", []):
        # 累加設備數量
        pid = item["product_id"]
        qty = item.get("quantity", 1)
        if   pid == 288: equip["單人獨木舟"] += qty
        elif pid == 289: equip["雙人獨木舟"] += qty
        elif pid == 290: equip["直立板"]     += qty
        else:            equip["Tour"]        += qty

        # 處理 YITH booking 資料
        for meta in item.get("meta_data", []):
            if meta.get("key") == "yith_booking_data":
                data = meta["value"]
                bk_date = datetime.datetime.fromtimestamp(int(data["from"]), HK).date()
                if bk_date == today:
                    found_today = True
                    # booking_service_quantities 是 dict of {ID: count}
                    qs = data.get("booking_service_quantities", {})
                    for sid, name in SERVICE_MAP.items():
                        svc[name] += int(qs.get(sid, "0"))
                break  # 每個 item 只需處理一次

    if found_today:
        b = order["billing"]
        name  = f"{b.get('first_name','')} {b.get('last_name','')}".strip()
        phone = b.get("phone","")
        row = [
            name, phone,
            equip["單人獨木舟"], equip["雙人獨木舟"],
            equip["直立板"], equip["Tour"],
            svc["浮潛鏡"], svc["防水袋"], svc["電話防水袋"]
        ]
        rows.append(row)

# 批次寫入
if rows:
    sheet.append_rows(rows, value_input_option="USER_ENTERED")

print(f"完成，寫入 {len(rows)} 筆今日預約訂單。")
