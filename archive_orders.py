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
ALL_ORDERS_SHEET_NAME = "所有訂單"  # 所有訂單分頁名稱

# -----------------------------
# 格式化設定：綠色
# -----------------------------
GREEN_FMT = CellFormat(textFormat=textFormat(foregroundColor={"red":0.0,"green":0.4,"blue":0.0}))

# -----------------------------
# 建立 Google Sheets 客戶端
# -----------------------------
scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)
sheet_all_orders = client.open_by_key(SHEET_ID).worksheet(ALL_ORDERS_SHEET_NAME)

# -----------------------------
# 讀取所有已存在訂單，避免重複寫入
# -----------------------------
existing_rows = sheet_all_orders.get_all_records()
existing_oids = { str(r['Order ID']).strip() for r in existing_rows }

# -----------------------------
# WooCommerce 資料解析設定
# -----------------------------
PRODUCT_IDS = {"單人獨木舟":288,"雙人獨木舟":289,"直立板":290}
SERVICE_IDS = {"浮潛鏡":31,"防水袋":32,"電話防水袋":33}

# -----------------------------
# 函式：取得單筆訂單
# -----------------------------
def fetch_orders():
    # 只獲取今天的訂單
    params = {
        'date_min': today.strftime('%Y-%m-%dT00:00:00'),
        'date_max': today.strftime('%Y-%m-%dT23:59:59'),
    }
    resp = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
    return resp.json() if resp.status_code == 200 else []

# -----------------------------
# 函式：解析訂單成寫入列
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
# 主流程：提取 WooCommerce 訂單並寫入「所有訂單」分頁，並避免重複
# -----------------------------
# 1. 先抓 Google Sheet 上已存在的 Order ID
existing_rows = sheet_all_orders.get_all_records()
existing_oids = { str(r['Order ID']).strip() for r in existing_rows }

# 2. 從 WooCommerce 抓當日訂單
orders = fetch_orders()

# 3. 如果有訂單，逐筆處理
for order in orders:
    oid = str(order['id'])
    # 如果已經寫過，就跳過
    if oid in existing_oids:
        continue

    # 解析成 row，並把 Order ID 插到最前面
    data = parse_order(order)
    row = [ oid ] + data

    # 如果第一次跑，需要先在 Sheet 裡加上 header
    if not existing_rows:
        header = ['Order ID','姓名','電話','單人獨木舟','雙人獨木舟','直立板',
                  '浮潛鏡','防水袋','電話防水袋','付款方式','訂單狀態']
        sheet_all_orders.clear()
        sheet_all_orders.append_row(header)

    # 寫入並格式化
    sheet_all_orders.append_row(row, value_input_option='USER_ENTERED')
    idx = len(sheet_all_orders.get_all_values())
    format_cell_range(sheet_all_orders, f"A{idx}:K{idx}", GREEN_FMT)

print("訂單更新完成！")

