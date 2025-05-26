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
IMMEDIATE_SHEET = "即日訂單"  # 用於存儲即日訂單

# 「所有訂單」和「即日訂單」的標頭
EXPECTED_HEADERS = [
    "Order ID", "姓名", "電話", "預訂日期",
    "單人獨木舟", "雙人獨木舟", "直立板",
    "浮潛鏡租借", "手機防水袋", "浮潛鏡加購", "防水袋加購",
    "付款方式", "訂單狀態", "訂單總額", "訂單到達？"
]

# 「改期表」的標頭
RESCHEDULED_HEADERS = [
    "時間戳記", "電子郵件地址", "訂單號碼", "新預約日期",
    "備註", "電號號碼", "改期原因（如即日改期）", "訂單狀態"
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
    # 嘗試訪問「改期表」工作表
    try:
        sheet_rescheduled = client.open_by_key(SHEET_ID).worksheet(RESCHEDULED_SHEET)
    except gspread.exceptions.WorksheetNotFound:
        logger.warning("「改期表」工作表不存在，將僅使用「所有訂單」數據")
        sheet_rescheduled = None
    sheet_equipment = client.open_by_key(SHEET_ID).worksheet(EQUIPMENT_SHEET)
    # 嘗試訪問「即日訂單」工作表，如果不存在則創建
    try:
        sheet_immediate = client.open_by_key(SHEET_ID).worksheet(IMMEDIATE_SHEET)
    except gspread.exceptions.WorksheetNotFound:
        sheet_immediate = client.open_by_key(SHEET_ID).add_worksheet(title=IMMEDIATE_SHEET, rows=100, cols=20)
        sheet_immediate.append_row(EXPECTED_HEADERS)
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

    # 將「所有訂單」數據轉為字典，方便查找
    all_orders_dict = {str(row["Order ID"]): row for row in rows_all}

    # 提取即日訂單（直接從「所有訂單」）
    immediate_orders_from_all = []
    for row in rows_all:
        booking_date_raw = str(row["預訂日期"])
        booking_date = booking_date_raw.strip().split()[0] if " " in booking_date_raw else booking_date_raw
        logger.info(f"「所有訂單」訂單 {row['Order ID']} 原始預訂日期: '{booking_date_raw}', 處理後日期: '{booking_date}', 訂單狀態: {row['訂單狀態']}")
        if booking_date == today:
            immediate_orders_from_all.append(row)
            logger.info(f"從「所有訂單」提取即日訂單: {row['Order ID']}，預訂日期: {booking_date}")

    # 讀取改期表訂單（如果存在）
    immediate_orders_from_rescheduled = []
    if sheet_rescheduled:
        try:
            # 檢查「改期表」標頭
            rescheduled_headers = sheet_rescheduled.row_values(1)
            logger.info(f"「改期表」標頭: {rescheduled_headers}")
            if not all(header in rescheduled_headers for header in ["訂單號碼", "新預約日期"]):
                logger.warning("「改期表」缺少必要標頭（訂單號碼或新預約日期），跳過該工作表")
            else:
                rows_rescheduled = sheet_rescheduled.get_all_records()
                logger.info(f"從「改期表」讀取 {len(rows_rescheduled)} 筆訂單")
                for row in rows_rescheduled:
                    booking_date_raw = str(row["新預約日期"])
                    booking_date = booking_date_raw.strip().split()[0] if " " in booking_date_raw else booking_date_raw
                    order_id = str(row["訂單號碼"])
                    logger.info(f"「改期表」訂單 {order_id} 原始預訂日期: '{booking_date_raw}', 處理後日期: '{booking_date}'")
                    if booking_date == today and order_id in all_orders_dict:
                        order_data = all_orders_dict[order_id].copy()
                        order_data["預訂日期"] = booking_date  # 更新預訂日期為新預約日期
                        immediate_orders_from_rescheduled.append(order_data)
                        logger.info(f"從「改期表」提取即日訂單: {order_id}，新預約日期: {booking_date}")
        except Exception as e:
            logger.warning(f"讀取「改期表」失敗，跳過該工作表：{e}")

    # 合併即日訂單並去重
    immediate_orders_dict = {row["Order ID"]: row for row in immediate_orders_from_rescheduled}  # 改期表優先
    for row in immediate_orders_from_all:
        if row["Order ID"] not in immediate_orders_dict:
            immediate_orders_dict[row["Order ID"]] = row
    immediate_orders = list(immediate_orders_dict.values())
    logger.info(f"合併後總共提取 {len(immediate_orders)} 筆即日訂單")

    # 清除「即日訂單」工作表的舊內容
    sheet_immediate.clear()
    sheet_immediate.append_row(EXPECTED_HEADERS)

    # 將即日訂單寫入「即日訂單」工作表
    if immediate_orders:
        immediate_data = [EXPECTED_HEADERS]
        for order in immediate_orders:
            immediate_data.append([
                order["Order ID"], order["姓名"], order["電話"], order["預訂日期"],
                order["單人獨木舟"], order["雙人獨木舟"], order["直立板"],
                order["浮潛鏡租借"], order["手機防水袋"], order["浮潛鏡加購"], order["防水袋加購"],
                order["付款方式"], order["訂單狀態"], order["訂單總額"], order["訂單到達？"]
            ])
        sheet_immediate.update("A1:O" + str(len(immediate_data)), immediate_data, value_input_option="USER_ENTERED")
        logger.info("成功將即日訂單寫入「即日訂單」工作表")
    else:
        logger.warning("未提取到即日訂單，「即日訂單」工作表僅保留標頭")

    # 按日期統計設備預訂總數（包括改期表更新後的日期）
    equipment_bookings = {}
    for row in all_orders_dict.values():
        # 如果訂單在改期表中，使用新預約日期
        booking_date_raw = row["預訂日期"]
        order_id = str(row["Order ID"])
        if order_id in immediate_orders_dict:
            booking_date_raw = immediate_orders_dict[order_id]["預訂日期"]
        booking_date = booking_date_raw.strip().split()[0] if " " in booking_date_raw else booking_date_raw
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
