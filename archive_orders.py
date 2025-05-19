import gspread
from oauth2client.service_account import ServiceAccountCredentials
import requests
from datetime import datetime, timedelta
import pytz
from gspread_formatting import format_cell_range, CellFormat, textFormat
import os
import logging
import json

# 設置日誌
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# -----------------------------
# 配置與常量
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
now = datetime.now(tz)
seven_days_ago = now - timedelta(days=7)  # 提取最近一週訂單

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

# 允許的訂單狀態（排除 checkout-draft 和 cancelled）
VALID_STATUSES = {"completed", "processing", "on-hold"}

# -----------------------------
# Google Sheets 客戶端
# -----------------------------
scope = ['https://spreadsheets.google.com/feeds','https://www.googleapis.com/auth/drive']
try:
    with open('/tmp/credentials.json', 'r') as f:
        creds_content = f.read()
        if not creds_content:
            logger.error("credentials.json 檔案為空")
            raise ValueError("credentials.json 檔案為空")
        creds_json = json.loads(creds_content)
        logger.info(f"成功讀取 credentials.json，client_email: {creds_json.get('client_email', 'N/A')}")
    creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
    client = gspread.authorize(creds)
    sheet_all = client.open_by_key(SHEET_ID).worksheet(ALL_ORDERS_SHEET)
except Exception as e:
    logger.error(f"無法連接到 Google Sheets: {e}")
    raise

# -----------------------------
# 讀取已存在 Order ID，用於去重
# -----------------------------
try:
    rows = sheet_all.get_all_records()
    existing_oids = {str(r.get("Order ID", "")).strip(): idx + 2 for idx, r in enumerate(rows)}  # 記錄行號
    logger.info(f"從 Google Sheets 讀取 {len(rows)} 筆現有訂單")
except Exception as e:
    logger.error(f"無法讀取 Google Sheets 資料: {e}")
    raise

# -----------------------------
# 拉取最近一週訂單
# -----------------------------
def fetch_new_orders():
    start = seven_days_ago.strftime("%Y-%m-%dT00:00:00")
    end = now.strftime("%Y-%m-%dT23:59:59")
    params = {
        "after": start,
        "before": end,
        "per_page": 100
    }
    try:
        resp = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
        resp.raise_for_status()
        logger.info(f"API 請求成功，狀態碼: {resp.status_code}")
        logger.info(f"原始回應: {resp.text}")
        orders = resp.json()
        if not isinstance(orders, list):
            logger.error(f"API 回應格式錯誤: {orders}")
            raise TypeError("預期為訂單列表，但收到其他類型")
        logger.info(f"從 API 提取 {len(orders)} 筆訂單，時間範圍: {start} 至 {end}")
        for order in orders:
            logger.info(f"訂單 ID: {order.get('id', 'N/A')}, 創建日期: {order.get('date_created', 'N/A')}, 狀態: {order.get('status', 'N/A')}")
        return orders
    except requests.exceptions.HTTPError as e:
        logger.error(f"HTTP 錯誤: {e}, URL: {resp.url}")
        raise
    except requests.exceptions.RequestException as e:
        logger.error(f"請求錯誤: {e}")
        raise

# -----------------------------
# 解析訂單條目（使用 yith_booking_data 的 from）
# -----------------------------
def parse_order(order):
    if not isinstance(order, dict):
        logger.error(f"無效的訂單格式: {order}")
        return None

    # 過濾訂單狀態
    status = order.get("status", "")
    if status not in VALID_STATUSES:
        logger.info(f"跳過訂單 {order.get('id', 'N/A')}，狀態為 {status}，不符條件")
        return None

    name = order.get("billing", {}).get("first_name", "")
    phone = order.get("billing", {}).get("phone", "")
    payment = order.get("payment_method_title", "")
    total = order.get("total", "0.00")
    status_map = {
        "processing": "信用卡付款完成",
        "on-hold": "需確認",
        "completed": "已確認",
        "cancelled": "已取消"
    }
    status_display = status_map.get(status, status)

    counts = {k:0 for k in PRODUCT_IDS}
    counts.update({k:0 for k in SERVICE_IDS})

    booking_date = ""
    for item in order.get("line_items", []):
        pid = item.get("product_id")
        pname = next((n for n,p in PRODUCT_IDS.items() if p==pid), None)
        if pname:
            for m in item.get("meta_data", []):
                if m.get("key")=="yith_booking_data":
                    b = m.get("value", {})
                    counts[pname] += int(b.get("persons",0))
                    from_timestamp = int(b.get("from", 0))
                    booking_date = datetime.fromtimestamp(from_timestamp, tz).strftime("%Y-%m-%d")
                    for sid in b.get("booking_services", []):
                        svc = next((n for n,i in SERVICE_IDS.items() if i==int(sid)), None)
                        if svc:
                            counts[svc] += int(b.get("booking_service_quantities", {}).get(str(sid),0))
                    break

    return [
        order.get("id", ""), name, phone, booking_date,
        counts["單人獨木舟"], counts["雙人獨木舟"], counts["直立板"],
        counts["浮潛鏡租借"], counts["手機防水袋"], counts["浮潛鏡加購"], counts["防水袋加購"],
        payment, status_display, total, ""
    ]

# -----------------------------
# 主流程：寫入新訂單
# -----------------------------
new_orders = fetch_new_orders()
if not rows:
    header = ["Order ID", "姓名", "電話", "預訂日期",
              "單人獨木舟", "雙人獨木舟", "直立板",
              "浮潛鏡租借", "手機防水袋", "浮潛鏡加購", "防水袋加購",
              "付款方式", "訂單狀態", "訂單總額", "訂單到達？"]
    sheet_all.clear()
    sheet_all.append_row(header)

for ord_json in new_orders:
    row = parse_order(ord_json)
    if row:
        oid = str(row[0])
        if oid in existing_oids:
            # 若訂單已存在，更新該行
            row_idx = existing_oids[oid]
            try:
                sheet_all.update(f"A{row_idx}:O{row_idx}", [row], value_input_option="USER_ENTERED")
                format_cell_range(sheet_all, f"A{row_idx}:O{row_idx}", GREEN_FMT)
                logger.info(f"更新訂單 {oid} 在第 {row_idx} 行")
            except Exception as e:
                logger.error(f"更新訂單 {oid} 失敗: {e}")
        else:
            # 若訂單不存在，新增一行
            try:
                sheet_all.append_row(row, value_input_option="USER_ENTERED")
                idx = len(sheet_all.get_all_values())
                format_cell_range(sheet_all, f"A{idx}:O{idx}", GREEN_FMT)
                logger.info(f"新增訂單 {oid} 在第 {idx} 行")
            except Exception as e:
                logger.error(f"新增訂單 {oid} 失敗: {e}")

print(f"{len(new_orders)} 筆訂單處理完成。")
