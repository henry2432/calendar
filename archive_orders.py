import requests
from datetime import datetime, timedelta
import pytz
import logging

# 設置日誌
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

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
# 拉取昨日訂單
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
# 解析訂單條目
# -----------------------------
def parse_order(order):
    if not isinstance(order, dict):
        logger.error(f"無效的訂單格式: {order}")
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
    status = status_map.get(order.get("status", ""), order.get("status", ""))

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
        payment, status, total, ""
    ]

# -----------------------------
# 主流程：僅提取並打印訂單
# -----------------------------
try:
    target_date = yesterday
    new_orders = fetch_new_orders(target_date)
    for ord_json in new_orders:
        row = parse_order(ord_json)
        if row:
            logger.info(f"解析訂單成功: {row}")
        else:
            logger.warning(f"訂單 {ord_json.get('id', 'N/A')} 解析失敗，跳過")
    print(f"{len(new_orders)} 筆訂單處理完成。")
except Exception as e:
    logger.error(f"腳本執行失敗: {e}")
    raise
