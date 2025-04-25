import gspread
from oauth2client.service_account import ServiceAccountCredentials
import requests
from datetime import datetime
import pytz
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

# 讀取即日表現有紀錄，用於去重（使用姓名和電話組合作為唯一識別）
existing = sheet_today.get_all_records()
existing_keys = set(f"{r['姓名']}|{r['電話']}" for r in existing)

# WooCommerce 資料解析設定
PRODUCT_IDS = {"單人獨木舟": 288, "雙人獨木舟": 289, "直立板": 290}
SERVICE_IDS = {"浮潛鏡": 31, "防水袋": 32, "電話防水袋": 33}

# 取得單筆訂單資料

def fetch_order(order_id):
    resp = requests.get(f"{WC_API_URL}/{order_id}", auth=(CONSUMER_KEY, CONSUMER_SECRET))
    if resp.status_code == 200:
        return resp.json()
    return None

# 判斷訂單 booking 日期是否為今天
def booking_is_today(order):
    for item in order.get("line_items", []):
        for m in item.get("meta_data", []):
            if m.get("key") == "yith_booking_data":
                ts = m.get("value", {}).get("from")
                if ts:
                    bd = datetime.fromtimestamp(ts, tz).date()
                    return bd == today
    return False

# 解析訂單成要寫入即日表的行資料
# 使用與 script1.py 相同邏輯

def parse_order(order):
    name = order["billing"]["first_name"]
    phone = order["billing"]["phone"]
    payment = order["payment_method_title"]
    status_map = {"processing": "信用卡付款完成", "on-hold": "需確認",
                  "completed": "已確認", "cancelled": "已取消"}
    status = status_map.get(order.get("status", ""), order.get("status", ""))

    counts = {k: 0 for k in PRODUCT_IDS}
    counts.update({k: 0 for k in SERVICE_IDS})

    for item in order.get("line_items", []):
        pid = item.get("product_id")
        # 旅程
        pname = next((n for n, id_ in PRODUCT_IDS.items() if id_ == pid), None)
        if pname:
            for m in item.get("meta_data", []):
                if m.get("key") == "yith_booking_data":
                    b = m.get("value", {})
                    counts[pname] += int(b.get("persons", 0))
                    # 服務
                    for sid in b.get("booking_services", []):
                        sid = int(sid)
                        sname = next((n for n, id_ in SERVICE_IDS.items() if id_ == sid), None)
                        if sname:
                            counts[sname] += int(b.get("booking_service_quantities", {}).get(str(sid), 0))
                    break

    return [name, phone,
            counts["單人獨木舟"], counts["雙人獨木舟"], counts["直立板"],
            counts["浮潛鏡"], counts["防水袋"], counts["電話防水袋"],
            payment, status]

# 將改期單追加到即日表並套用深綠色字體格式

def append_rescheduled(order):
    row = parse_order(order)
    key = f"{row[0]}|{row[1]}"
    if key in existing_keys:
        return
    sheet_today.append_row(row, value_input_option="USER_ENTERED")
    all_vals = sheet_today.get_all_values()
    row_idx = len(all_vals)
    green_fmt = CellFormat(
        textFormat=textFormat(foregroundColor={"red": 0.0, "green": 0.4, "blue": 0.0})
    )
    format_cell_range(sheet_today, f"A{row_idx}:J{row_idx}", green_fmt)

# 主流程：處理改期紀錄
for rec in res_records:
    oid = str(rec.get("訂單號碼", "")).strip()
    date_str = rec.get("新預約日期", "").strip()
    if not oid or not date_str:
        continue
    try:
        nd = datetime.strptime(date_str, "%Y/%m/%d").date()
    except ValueError:
        continue
    if nd != today:
        continue
    order = fetch_order(oid)
    if not order or not booking_is_today(order):
        continue
    append_rescheduled(order)

if __name__ == "__main__":
    pass

