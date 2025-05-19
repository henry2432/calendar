import gspread
from oauth2client.service_account import ServiceAccountCredentials
import requests
from datetime import datetime, timedelta
import pytz
from gspread_formatting import format_cell_range, CellFormat, textFormat
import os

# -----------------------------
# 配置與常量
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
now = datetime.now(tz)
yesterday = now - timedelta(days=1)

# WooCommerce API 配置
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = "ck_634b531fa4ac6b7a58a3ba3a33ad49174449e1d1"
CONSUMER_SECRET = "cs_4c8599ff7dcbad53e34cef3b67e4d86955b18175"

SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
ALL_ORDERS_SHEET = "所有訂單"

GREEN_FMT = CellFormat(
    textFormat=textFormat(foregroundColor={"red":0.0,"green":0.4,"blue":0.0})
)

# WooCommerce 對應產品與服務
PRODUCT_IDS = {
    "單人獨木舟": 81,
    "雙人獨木舟": 82,
    "直立板": 84
}
SERVICE_IDS = {
    "浮潛鏡租借": 34,
    "手機防水袋": 35,
    "浮潛鏡加購": 36,
    "防水袋加購": 37
}

# -----------------------------
# Google Sheets 客戶端
# -----------------------------
scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)
sheet_all = client.open_by_key(SHEET_ID).worksheet(ALL_ORDERS_SHEET)

# -----------------------------
# 讀取已存在 Order ID，用於去重
# -----------------------------
#rows = sheet_all.get_all_records()
#existing_oids = { str(r.get("Order ID","")).strip() for r in rows }

# -----------------------------
# 拉取新訂單（按目標日期）
# -----------------------------
def fetch_new_orders(target_date):
    start = target_date.strftime("%Y-%m-%dT00:00:00")
    end = target_date.strftime("%Y-%m-%dT23:59:59")
    params = {
        "after": start,
        "before": end,
        "per_page": 100
    }
    try:
        resp = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
        resp.raise_for_status()
        return resp.json()
    except requests.exceptions.HTTPError as e:
        print(f"HTTP Error: {e}, URL: {resp.url}")
        raise
    except requests.exceptions.RequestException as e:
        print(f"Request Error: {e}")
        raise

# -----------------------------
# 解析訂單條目（包含預訂日期）
# -----------------------------
def parse_order(order):
    name = order["billing"]["first_name"]
    phone = order["billing"]["phone"]
    payment = order["payment_method_title"]
    status_map = {
        "processing": "信用卡付款完成",
        "on-hold": "需確認",
        "completed": "已確認",
        "cancelled": "已取消"
    }
    status = status_map.get(order["status"], order["status"])

    counts = {k:0 for k in PRODUCT_IDS}
    counts.update({k:0 for k in SERVICE_IDS})

    booking_date = ""
    for item in order.get("line_items", []):
        pid = item["product_id"]
        pname = next((n for n,p in PRODUCT_IDS.items() if p==pid), None)
        if pname:
            for m in item.get("meta_data", []):
                if m.get("key")=="yith_booking_data":
                    b = m.get("value", {})
                    counts[pname] += int(b.get("persons",0))
                    booking_date = datetime.fromtimestamp(int(b.get("from")), tz).strftime("%Y-%m-%d")
                    for sid in b.get("booking_services", []):
                        svc = next((n for n,i in SERVICE_IDS.items() if i==int(sid)), None)
                        if svc:
                            counts[svc] += int(b.get("booking_service_quantities", {}).get(str(sid),0))
                    break

    return [
        order["id"],         # Order ID
        name, phone,
        booking_date,        # 預訂日期
        counts["單人獨木舟"], counts["雙人獨木舟"], counts["直立板"],
        counts["浮潛鏡租借"], counts["手機防水袋"], counts["浮潛鏡加購"], counts["防水袋加購"],
        payment, status,
        ""                   # 訂單到達？（初始為空）
    ]

# -----------------------------
# 主流程：寫入新訂單
# -----------------------------
target_date = yesterday  # 提取昨日訂單
new_orders = fetch_new_orders(target_date)
if not rows:
    header = ["Order ID", "姓名", "電話", "預訂日期",
              "單人獨木舟", "雙人獨木舟", "直立板",
              "浮潛鏡租借", "手機防水袋", "浮潛鏡加購", "防水袋加購",
              "付款方式", "訂單狀態", "訂單到達？"]
    sheet_all.clear()
    sheet_all.append_row(header)

for ord_json in new_orders:
    oid = str(ord_json["id"])
    if oid in existing_oids:
        continue

    row = parse_order(ord_json)
    sheet_all.append_row(row, value_input_option="USER_ENTERED")
    idx = len(sheet_all.get_all_values())
    format_cell_range(sheet_all, f"A{idx}:N{idx}", GREEN_FMT)

print(f"{len(new_orders)} 筆訂單處理完成 (去重後新增 {len(new_orders)-len(existing_oids&{str(o['id']) for o in new_orders})} 筆)。")
