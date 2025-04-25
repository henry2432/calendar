import gspread
from oauth2client.service_account import ServiceAccountCredentials
import requests
from datetime import datetime, timedelta
import pytz
from gspread_formatting import format_cell_range, CellFormat, textFormat
import os

# -----------------------------
# 配置与常量
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
now = datetime.now(tz)
yesterday = now - timedelta(days=1)

# Hardcode WooCommerce API URL (replace with your actual domain)
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"  # Replace with your actual domain
CONSUMER_KEY = os.getenv("ck_9269bc61a6553f1d1515a6ba7ad01f225a379b9a")  # Your Consumer Key
CONSUMER_SECRET = os.getenv("cs_4df8324d11b0d8df493b2335efc3a26929ec73b5")  # Your Consumer Secret

SHEET_ID = os.getenv("1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs")  # Your Google Sheet ID
ALL_ORDERS_SHEET = "所有訂單"

GREEN_FMT = CellFormat(
    textFormat=textFormat(foregroundColor={"red":0.0,"green":0.4,"blue":0.0})
)

# WooCommerce 对应产品与服务
PRODUCT_IDS = {"單人獨木舟":288, "雙人獨木舟":289, "直立板":290}
SERVICE_IDS = {"浮潛鏡":31, "防水袋":32, "電話防水袋":33}

# -----------------------------
# Google Sheets 客户端
# -----------------------------
scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)
sheet_all = client.open_by_key(SHEET_ID).worksheet(ALL_ORDERS_SHEET)

# -----------------------------
# 读取已存在 Order ID，用于去重
# -----------------------------
rows = sheet_all.get_all_records()
existing_oids = { str(r.get("Order ID","")).strip() for r in rows }

# -----------------------------
# 拉取新订单
# -----------------------------
def fetch_new_orders():
    # 计算 24 小时前的时间戳
    after = yesterday.strftime("%Y-%m-%dT%H:%M:%S")
    before = now.strftime("%Y-%m-%dT%H:%M:%S")
    
    params = {
        "after": after,
        "before": before,
        "per_page": 100
    }
    resp = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
    resp.raise_for_status()
    return resp.json()

# -----------------------------
# 解析订单条目
# -----------------------------
def parse_order(order):
    name = order["billing"]["first_name"]
    phone = order["billing"]["phone"]
    payment = order["payment_method_title"]
    status_map = {
        "processing":"信用卡付款完成",
        "on-hold":"需確認",
        "completed":"已確認",
        "cancelled":"已取消"
    }
    status = status_map.get(order["status"], order["status"])

    counts = {k:0 for k in PRODUCT_IDS}
    counts.update({k:0 for k in SERVICE_IDS})

    for item in order.get("line_items", []):
        pid = item["product_id"]
        pname = next((n for n,p in PRODUCT_IDS.items() if p==pid), None)
        if pname:
            for m in item.get("meta_data", []):
                if m.get("key")=="yith_booking_data":
                    b = m.get("value", {})
                    counts[pname] += int(b.get("persons",0))
                    for sid in b.get("booking_services", []):
                        svc = next((n for n,i in SERVICE_IDS.items() if i==int(sid)), None)
                        if svc:
                            counts[svc] += int(b.get("booking_service_quantities", {}).get(str(sid),0))
                    break

    return [
        order["id"],         # Order ID
        name, phone,
        counts["單人獨木舟"], counts["雙人獨木舟"], counts["直立板"],
        counts["浮潛鏡"], counts["防水袋"], counts["電話防水袋"],


