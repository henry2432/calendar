import gspread
from oauth2client.service_account import ServiceAccountCredentials
import requests
from datetime import datetime
import pytz
from gspread_formatting import format_cell_range, CellFormat, textFormat

# -----------------------------
# 設定時區與今日日期
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
today = datetime.now(tz).date()

# -----------------------------
# WooCommerce API 設定
# -----------------------------
WC_API_URL = "https://kayarine.club/wp-json/wooapi/v3/orders"
CONSUMER_KEY = "ck_634b531fa4ac6b7a58a3ba3a33ad49174449e1d1"
CONSUMER_SECRET = "cs_4c8599ff7dcbad53e34cef3b67e4d86955b18175"

# -----------------------------
# Google Sheets 設定
# -----------------------------
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
DAILY_SHEET_NAME = "即日訂單"
RESCHEDULE_SHEET_NAME = "改期表"

# -----------------------------
# 格式化設定：紅、紫、綠
# -----------------------------
RED_FMT = CellFormat(textFormat=textFormat(foregroundColor={"red":1.0,"green":0.0,"blue":0.0}))
PURPLE_FMT = CellFormat(textFormat=textFormat(foregroundColor={"red":0.5,"green":0.0,"blue":0.5}))
GREEN_FMT = CellFormat(textFormat=textFormat(foregroundColor={"red":0.0,"green":0.4,"blue":0.0}))

# -----------------------------
# 建立 Google Sheets 客戶端
# -----------------------------
scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)
sheet_today = client.open_by_key(SHEET_ID).worksheet(DAILY_SHEET_NAME)
sheet_res = client.open_by_key(SHEET_ID).worksheet(RESCHEDULE_SHEET_NAME)

# -----------------------------
# 讀取改期表資料
# -----------------------------
res_records = sheet_res.get_all_records()
completed_oids = {
    str(r['訂單號碼']).strip()
    for r in res_records
    if str(r.get('改期單狀態','')).strip() == '已完結'
}

# -----------------------------
# 讀取即日表現有資料，避免重複
# -----------------------------
existing_rows = sheet_today.get_all_records()
existing_keys = {
    f"{r['姓名']}|{r['電話']}"
    for r in existing_rows
}

# -----------------------------
# WooCommerce 資料解析設定
# -----------------------------
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
# 函式：取得單筆訂單
# -----------------------------
def fetch_order(oid):
    resp = requests.get(f"{WC_API_URL}/{oid}", auth=(CONSUMER_KEY,CONSUMER_SECRET))
    return resp.json() if resp.status_code == 200 else None

# -----------------------------
# 函式：解析訂單成寫入列
# -----------------------------
def parse_order(order):
    name = order['billing']['first_name']
    phone = order['billing']['phone']
    payment = order['payment_method_title']
    status_map = {
        "processing": "信用卡付款完成",
        "on-hold": "需確認",
        "completed": "已確認",
        "cancelled": "已取消"
    }
    status = status_map.get(order.get('status',''), order.get('status',''))

    counts = {k:0 for k in PRODUCT_IDS.keys()}
    counts.update({k:0 for k in SERVICE_IDS.keys()})

    for item in order.get('line_items', []):
        pid = item.get('product_id')
        pname = next((n for n,id_ in PRODUCT_IDS.items() if id_==pid), None)
        if pname:
            for m in item.get('meta_data', []):
                if m.get('key')=='yith_booking_data':
                    b = m.get('value', {})
                    counts[pname] += int(b.get('persons',0))
                    for sid in b.get('booking_services', []):
                        sid = int(sid)
                        svc = next((n for n,id_ in SERVICE_IDS.items() if id_==sid), None)
                        if svc:
                            counts[svc] += int(b.get('booking_service_quantities', {}).get(str(sid),0))
                    break

    return [
        name, phone,
        counts['單人獨木舟'], counts['雙人獨木舟'], counts['直立板'],
        counts['浮潛鏡租借'], counts['手機防水袋'], counts['浮潛鏡加購'], counts['防水袋加購'],
        payment, status,
        ""  # 訂單到達？（初始為空）
    ]

# -----------------------------
# 主流程：檢查並 append 改期單
# -----------------------------
for rec in res_records:
    oid = str(rec.get('訂單號碼','')).strip()
    date_str = rec.get('新預約日期','').strip()
    status_flag = str(rec.get('改期單狀態','')).strip()
    form_phone = str(rec.get('電話號碼','')).strip()

    if not oid or not date_str:
        continue
    try:
        nd = datetime.strptime(date_str, '%Y/%m/%d').date()
    except ValueError:
        continue
    if nd != today:
        continue
    if status_flag == '已完結':
        continue

    order = fetch_order(oid)
    if not order:
        row = ["", form_phone, 0,0,0,0,0,0,0, "", "無法識別訂單號", ""]
        sheet_today.append_row(row, value_input_option='USER_ENTERED')
        idx = len(sheet_today.get_all_values())
        format_cell_range(sheet_today, f"A{idx}:L{idx}", PURPLE_FMT)
        continue

    row = parse_order(order)
    row[1] = form_phone
    key = f"{row[0]}|{row[1]}"
    if key in existing_keys:
        continue

    fmt = RED_FMT if oid in completed_oids else GREEN_FMT

    sheet_today.append_row(row, value_input_option='USER_ENTERED')
    idx = len(sheet_today.get_all_values())
    format_cell_range(sheet_today, f"A{idx}:L{idx}", fmt)

if __name__=='__main__':
    pass

