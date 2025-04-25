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
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = "ck_9269bc61a6553f1d1515a6ba7ad01f225a379b9a"
CONSUMER_SECRET = "cs_4df8324d11b0d8df493b2335efc3a26929ec73b5"

# -----------------------------
# Google Sheets 設定
# -----------------------------
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
DAILY_SHEET_NAME = "WooCommerce Orders"  # 即日訂單分頁名稱
RESCHEDULE_SHEET_NAME = "改期表"           # 改期表單回傳分頁名稱

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
# 收集所有標記「已完結」的訂單號
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
PRODUCT_IDS = {"單人獨木舟":288,"雙人獨木舟":289,"直立板":290}
SERVICE_IDS = {"浮潛鏡":31,"防水袋":32,"電話防水袋":33}

# -----------------------------
# 函式：取得單筆訂單
# -----------------------------
def fetch_order(oid):
    resp = requests.get(f"{WC_API_URL}/{oid}", auth=(CONSUMER_KEY,CONSUMER_SECRET))
    return resp.json() if resp.status_code == 200 else None

# -----------------------------
# 函式：解析訂單成写入列
# -----------------------------
def parse_order(order):
    name = order['billing']['first_name']
    phone = order['billing']['phone']
    payment = order['payment_method_title']
    status_map = {
        "processing":"信用卡付款完成",
        "on-hold":"需確認",
        "completed":"已確認",
        "cancelled":"已取消"
    }
    status = status_map.get(order.get('status',''), order.get('status',''))

    counts = {k:0 for k in PRODUCT_IDS}
    counts.update({k:0 for k in SERVICE_IDS})

    for item in order.get('line_items', []):
        pid = item.get('product_id')
        pname = next((n for n, id_ in PRODUCT_IDS.items() if id_ == pid), None)
        if pname:
            for m in item.get('meta_data', []):
                if m.get('key') == 'yith_booking_data':
                    b = m.get('value', {})
                    counts[pname] += int(b.get('persons', 0))
                    for sid in b.get('booking_services', []):
                        sid = int(sid)
                        svc = next((n for n, id_ in SERVICE_IDS.items() if id_ == sid), None)
                        if svc:
                            counts[svc] += int(b.get('booking_service_quantities', {}).get(str(sid), 0))
                    break

    return [
        name, phone,
        counts['單人獨木舟'], counts['雙人獨木舟'], counts['直立板'],
        counts['浮潛鏡'], counts['防水袋'], counts['電話防水袋'],
        payment, status
    ]

# -----------------------------
# 主流程：檢查並 append 改期單
# -----------------------------
for rec in res_records:
    oid = str(rec.get('訂單號碼','')).strip()
    date_str = rec.get('新預約日期','').strip()
    status_flag = str(rec.get('改期單狀態','')).strip()
    if not oid or not date_str:
        continue
    # 解析並比對日期
    try:
        nd = datetime.strptime(date_str, '%Y/%m/%d').date()
    except ValueError:
        continue
    if nd != today:
        continue
    # 只處理「尚未完結」的改期單
    if status_flag == '已完結':
        continue

    # 取得訂單
    order = fetch_order(oid)
    if not order:
        # 無法識別：紫色
        row = ["", "", 0,0,0,0,0,0, "", "無法識別訂單號"]
        sheet_today.append_row(row, value_input_option='USER_ENTERED')
        idx = len(sheet_today.get_all_values())
        format_cell_range(sheet_today, f"A{idx}:J{idx}", PURPLE_FMT)
        continue

    # 構造寫入行
    row = parse_order(order)
    key = f"{row[0]}|{row[1]}"
    if key in existing_keys:
        continue

    # 判斷紅字：若此訂單號曾在改期表標為「已完結」後，又重新提交
    if oid in completed_oids:
        fmt = RED_FMT
    else:
        fmt = GREEN_FMT

    # 寫入並格式化
    sheet_today.append_row(row, value_input_option='USER_ENTERED')
    idx = len(sheet_today.get_all_values())
    format_cell_range(sheet_today, f"A{idx}:J{idx}", fmt)

