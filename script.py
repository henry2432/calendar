import requests
import json
import datetime
import pytz
import os
import gspread
from google.oauth2 import service_account

# WooCommerce API 資訊
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
WC_API_KEY = os.environ.get("ck_9269bc61a6553f1d1515a6ba7ad01f225a379b9a")
WC_API_SECRET = os.environ.get("cs_4df8324d11b0d8df493b2335efc3a26929ec73b5")

# Google Sheets 設定
SPREADSHEET_ID = os.environ.get("SPREADSHEET_ID")
SHEET_NAME = "今日預約"

# 時區設定
hong_kong_tz = pytz.timezone("Asia/Hong_Kong")
today = datetime.datetime.now(hong_kong_tz).date()

# 產品 ID 對應名稱
PRODUCT_NAMES = {
    288: "單人獨木舟",
    289: "雙人獨木舟",
    290: "直立板"
}

# 服務名稱對應
SERVICE_NAMES = {
    "31": "浮潛鏡",
    "32": "防水袋",
    "33": "電話防水袋"
}

def fetch_today_orders():
    params = {
        "per_page": 100,
        "orderby": "date",
        "order": "desc",
    }
    res = requests.get(WC_API_URL, auth=(WC_API_KEY, WC_API_SECRET), params=params)
    res.raise_for_status()
    orders = res.json()
    today_orders = []

    for order in orders:
        if order["status"] not in ["processing", "completed", "on-hold"]:
            continue

        order_match = False
        product_counts = {name: 0 for name in PRODUCT_NAMES.values()}
        service_counts = {name: 0 for name in SERVICE_NAMES.values()}

        for item in order["line_items"]:
            product_id = item["product_id"]
            product_name = PRODUCT_NAMES.get(product_id)

            if not product_name:
                continue

            for meta in item.get("meta_data", []):
                if meta["key"] == "yith_booking_data":
                    booking_data = meta["value"]
                    booking_date = datetime.datetime.fromtimestamp(
                        booking_data["from"], hong_kong_tz
                    ).date()
                    if booking_date != today:
                        continue

                    # 有符合今天的預約
                    order_match = True

                    # 累加產品數量
                    product_counts[product_name] += item["quantity"]

                    # 統計服務數量
                    quantities = booking_data.get("booking_service_quantities", {})
                    for sid, cname in SERVICE_NAMES.items():
                        qty = int(quantities.get(sid, "0"))
                        service_counts[cname] += qty

        if order_match:
            today_orders.append({
                "姓名": order["billing"]["first_name"] + " " + order["billing"]["last_name"],
                "電話": order["billing"]["phone"],
                "付款方式": order["payment_method_title"],
                "狀態": order["status"],
                **product_counts,
                **service_counts
            })

    return today_orders

def write_to_sheet(orders):
    credentials_info = json.loads(os.environ["GCP_SA_JSON"])
    credentials = service_account.Credentials.from_service_account_info(
        credentials_info,
        scopes=["https://www.googleapis.com/auth/spreadsheets"]
    )

    client = gspread.authorize(credentials)
    sheet = client.open_by_key(SPREADSHEET_ID).worksheet(SHEET_NAME)
    sheet.clear()

    if not orders:
        sheet.update("A1", [["今天沒有預約"]])
        return

    headers = list(orders[0].keys())
    data = [headers] + [[order.get(h, "") for h in headers] for order in orders]
    sheet.update("A1", data)

if __name__ == "__main__":
    orders = fetch_today_orders()
    write_to_sheet(orders)




