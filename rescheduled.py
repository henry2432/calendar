import gspread
from oauth2client.service_account import ServiceAccountCredentials
from datetime import datetime
import pytz
import requests
from gspread_formatting import format_cell_range, CellFormat, textFormat

# 時區與今日日期
tz = pytz.timezone("Asia/Hong_Kong")
today = datetime.now(tz).date()

# WooCommerce API 設定
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = "ck_9269bc61a6553f1d1515a6ba7ad01f225a379b9a"
CONSUMER_SECRET = "cs_4df8324d11b0d8df493b2335efc3a26929ec73b5"

# Google Sheets 設定
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
DAILY_SHEET_NAME = "WooCommerce Orders"
RESCHEDULE_SHEET_NAME = "改期表"

# 授權並取得工作表
scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
client = gspread.authorize(creds)
sheet_today = client.open_by_key(SHEET_ID).worksheet(DAILY_SHEET_NAME)
sheet_res = client.open_by_key(SHEET_ID).worksheet(RESCHEDULE_SHEET_NAME)

# 讀取改期表資料
res_records = sheet_res.get_all_records()

# 單筆訂單資料查詢
# 使用 GET /orders/{id} 保證能抓到特定訂單

def fetch_order_by_id(order_id):
    url = f"{WC_API_URL}/{order_id}"
    resp = requests.get(url, auth=(CONSUMER_KEY, CONSUMER_SECRET))
    if resp.status_code == 200:
        return resp.json()
    return None

# 解析 WooCommerce 訂單為要 append 的一列
# 與 script1.py 相同邏輯

PRODUCT_IDS = {"單人獨木舟": 288, "雙人獨木舟": 289, "直立板": 290}
SERVICE_IDS = {"浮潛鏡": 31, "防水袋": 32, "電話防水袋": 33}


def parse_order(order):
    name = order["billing"]["first_name"]
    phone = order["billing"]["phone"]
    payment = order["payment_method_title"]
    status_map = {"processing": "信用卡付款完成", "on-hold": "需確認",
                  "completed": "已確認", "cancelled": "已取消"}
    status = status_map.get(order.get("status", ""), order.get("status", ""))

    counts = {**{k: 0 for k in PRODUCT_IDS}, **{k: 0 for k in SERVICE_IDS}}
    for item in order.get("line_items", []):
        pid = item.get("product_id")
        # 旅程
        for name_key, p in PRODUCT_IDS.items():
            if pid == p:
                # booking_data persons
                for m in item.get("meta_data", []):
                    if m.get("key") == "yith_booking_data":
                        b = m.get("value", {})
                        counts[name_key] += int(b.get("persons", 0))
                        # services
                        for sid in b.get("booking_services", []):
                            for svc_name, svc_id in SERVICE_IDS.items():
                                if int(sid) == svc_id:
                                    counts[svc_name] += int(b.get("booking_service_quantities", {}).get(str(sid), 0))
                        break
        # 非旅程 items ignored

    return [name, phone,
            counts["單人獨木舟"], counts["雙人獨木舟"], counts["直立板"],
            counts["浮潛鏡"], counts["防水袋"], counts["電話防水袋"],
            payment, status]

# 匹配訂單的 booking 日期是否為目標日期
def booking_is_on(order, target_date):
    for item in order.get("line_items", []):
        for m in item.get("meta_data", []):
            if m.get("key") == "yith_booking_data":
                ts = m.get("value", {}).get("from")
                if ts:
                    dt = datetime.fromtimestamp(ts, tz).date()
                    return dt == target_date
    return False

# 追加改期訂單到即日表並標示深綠色

def append_rescheduled(order):
    row = parse_order(order)
    sheet_today.append_row(row, value_input_option="USER_ENTERED")
    # 找到剛 append 的列號
    all_vals = sheet_today.get_all_values()
    row_idx = len(all_vals)
    fmt = CellFormat(textFormat=textFormat(foregroundColor={"red":0.0,"green":0.4,"blue":0.0}))
    format_cell_range(sheet_today, f"A{row_idx}:J{row_idx}", fmt)

# 主流程
for rec in res_records:
    oid = str(rec.get("訂單號碼", "")).strip()
    date_str = rec.get("新預約日期", "").strip()
    if not oid or not date_str:
        continue
    try:
        nd = datetime.strptime(date_str, "%Y/%m/%d").date()
    except:
        continue
    # 只要新預約為今天
    if nd != today:
        continue
    # fetch single order
    ord_data = fetch_order_by_id(oid)
    if not ord_data:
        continue
    # 確認 booking_data.from 也是今天
    if not booking_is_on(ord_data, today):
        continue
    # 避免重複 append: 檢查即日表是否已有相同訂單ID, 假設姓名+電話+狀態能區分
    # 這裡簡單以電話做識別
    existing = sheet_today.get_all_records()
    phones = [r.get("電話") for r in existing]
    if ord_data.get("billing",{}).get("phone") in phones:
        continue
    append_rescheduled(ord_data)
