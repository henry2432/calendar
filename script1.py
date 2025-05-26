import gspread
from oauth2client.service_account import ServiceAccountCredentials
import pytz
import logging
import json
from datetime import datetime

# 設置日誌
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# -----------------------------
# 配置與常量
# -----------------------------
tz = pytz.timezone("Asia/Hong_Kong")
now = datetime.now(tz)
today = now.strftime("%Y-%m-%d")  # 即日日期：2025-05-26
logger.info(f"當前日期: {today}")

SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
ALL_ORDERS_SHEET = "所有訂單"
RESCHEDULED_SHEET = "改期表"
EQUIPMENT_SHEET = "設備名額表"

# 預期的標頭行
EXPECTED_HEADERS = [
    "Order ID", "姓名", "電話", "預訂日期",
    "單人獨木舟", "雙人獨木舟", "直立板",
    "浮潛鏡租借", "手機防水袋", "浮潛鏡加購", "防水袋加購",
    "付款方式", "訂單狀態", "訂單總額", "訂單到達？"
]

# 設備名額表標頭
EQUIPMENT_HEADERS = ["日期", "單人獨木舟", "雙人獨木舟", "直立板"]

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
    sheet_rescheduled = client.open_by_key(SHEET_ID).worksheet(RESCHEDULED_SHEET)
    sheet_equipment = client.open_by_key(SHEET_ID).worksheet(EQUIPMENT_SHEET)
    logger.info("成功連接到 Google Sheets")
except Exception as e:
    logger.error(f"無法連接到 Google Sheets: {e}")
    raise

# -----------------------------
# 提取即日訂單並更新設備名額表
# -----------------------------
try:
    # 讀取所有訂單
    rows_all = sheet_all.get_all_records(expected_headers=EXPECTED_HEADERS)
    logger.info(f"從「所有訂單」讀取 {len(rows_all)} 筆訂單")

    # 讀取改期表訂單
    rows_rescheduled = sheet_rescheduled.get_all_records(expected_headers=EXPECTED_HEADERS)
    logger.info(f"從「改期表」讀取 {len(rows_rescheduled)} 筆訂單")

    # 合併訂單並去重
    all_orders = {row["Order ID"]: row for row in rows_all}
    for row in rows_rescheduled:
        all_orders[row["Order ID"]] = row  # 後者覆蓋前者，優先使用改期表數據
    combined_orders = list(all_orders.values())
    logger.info(f"合併後總共 {len(combined_orders)} 筆訂單")

    # 提取即日訂單
    immediate_orders = []
    for row in combined_orders:
        booking_date_raw = str(row["預訂日期"])  # 原始日期值
        # 去除所有空格、換行符，並僅保留日期部分
        booking_date = booking_date_raw.strip().split()[0] if " " in booking_date_raw else booking_date_raw
        logger.info(f"訂單 {row['Order ID']} 原始預訂日期: '{booking_date_raw}', 處理後日期: '{booking_date}', 訂單狀態: {row['訂單狀態']}")
        if booking_date == today:
            immediate_orders.append(row)
            logger.info(f"提取即日訂單: {row['Order ID']}，預訂日期: {booking_date}")

    # 按日期統計設備預訂總數
    equipment_bookings = {}
    for row in combined_orders:
        booking_date = str(row["預訂日期"]).strip().split()[0]
        if booking_date:
            if booking_date not in equipment_bookings:
                equipment_bookings[booking_date] = {"單人獨木舟": 0, "雙人獨木舟": 0, "直立板": 0}
            equipment_bookings[booking_date]["單人獨木舟"] += int(row["單人獨木舟"] or 0)
            equipment_bookings[booking_date]["雙人獨木舟"] += int(row["雙人獨木舟"] or 0)
            equipment_bookings[booking_date]["直立板"] += int(row["直立板"] or 0)

    # 準備設備名額表數據
    equipment_data = [EQUIPMENT_HEADERS]
    for date in sorted(equipment_bookings.keys()):
        if date >= today:  # 僅顯示未來日期（含今日）
            equipment_data.append([
                date,
                equipment_bookings[date]["單人獨木舟"],
                equipment_bookings[date]["雙人獨木舟"],
                equipment_bookings[date]["直立板"]
            ])

    # 更新設備名額表
    sheet_equipment.clear()
    sheet_equipment.update("A1:D" + str(len(equipment_data)), equipment_data, value_input_option="USER_ENTERED")
    logger.info("成功更新設備名額表")
except Exception as e:
    logger.error(f"更新設備名額表失敗: {e}")
    raise

print(f"提取 {len(immediate_orders)} 筆即日訂單")
