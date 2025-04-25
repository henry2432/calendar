import requests
import datetime
import pytz
import gspread
from oauth2client.service_account import ServiceAccountCredentials

# WooCommerce API 設定
WC_API_URL = "https://kayarine.club/wp-json/wc/v3/orders"
CONSUMER_KEY = "ck_9269bc61a6553f1d1515a6ba7ad01f225a379b9a"
CONSUMER_SECRET = "cs_4df8324d11b0d8df493b2335efc3a26929ec73b5"

# Google Sheets 設定
SHEET_ID = "1hIQ8lhv91ZlUtA0JuKiBIoJMaSDRtcIEPe24h7ID6zs"
SHEET_NAME_TOUR = "Tour Orders"

# Tour 產品對應 ID
TOUR_PRODUCT_IDS = {
    1381: "親子團",
    1115: "日落團",
    296: "威士忌直立板",
    297: "吊鐘",
    298: "日出",
    299: "SUP初階",
    291: "橋咀獨木舟"
}

def get_tour_orders():
    today = datetime.datetime.now(pytz.timezone('Asia/Hong_Kong'))
    four_months_ago = today - datetime.timedelta(days=120)
    params = {
        "after": four_months_ago.strftime("%Y-%m-%dT00:00:00"),
        "per_page": 100
    }
    response = requests.get(WC_API_URL, auth=(CONSUMER_KEY, CONSUMER_SECRET), params=params)
    orders = response.json()

    tour_orders = []
    for order in orders:
        for item in order.get("line_items", []):
            product_id = item["product_id"]
            if product_id not in TOUR_PRODUCT_IDS:
                continue
            for meta in item.get("meta_data", []):
                if meta["key"] == "yith_booking_data":
                    booking_date = datetime.datetime.fromtimestamp(
                        meta["value"]["from"], pytz.timezone('Asia/Hong_Kong')
                    )
                    tour_orders.append((order, item, booking_date))
                    break
    tour_orders.sort(key=lambda x: x[2])
    return tour_orders

def parse_tour_order(order_item_tuple):
    order, item, booking_date = order_item_tuple
    booking_data = next(
        (meta["value"] for meta in item["meta_data"] if meta["key"] == "yith_booking_data"), {}
    )
    name = order["billing"]["first_name"]
    phone = order["billing"]["phone"]
    payment = order["payment_method_title"]
    status_raw = order["status"]

    status_map = {
        "processing": "信用卡付款完成",
        "on-hold": "需確認",
        "completed": "已確認",
        "cancelled": "已取消"
    }
    status = status_map.get(status_raw, status_raw)
    product_id = item["product_id"]
    tour_name = TOUR_PRODUCT_IDS.get(product_id, "未知旅程")
    persons = int(booking_data.get("persons", 0))

    return [booking_date.strftime("%Y-%m-%d"), name, phone, tour_name, persons, payment, status]

def write_tour_sheet(data_rows):
    scope = ['https://spreadsheets.google.com/feeds', 'https://www.googleapis.com/auth/drive']
    creds = ServiceAccountCredentials.from_json_keyfile_name('/tmp/credentials.json', scope)
    client = gspread.authorize(creds)
    sheet = client.open_by_key(SHEET_ID).worksheet(SHEET_NAME_TOUR)

    sheet.clear()
    headers = ["日期", "姓名", "電話", "旅程", "人數", "付款方式", "訂單狀態"]
    sheet.append_row(headers)
    for row in data_rows:
        sheet.append_row(row)

def main():
    tour_orders = get_tour_orders()
    rows = [parse_tour_order(order_item) for order_item in tour_orders]
    write_tour_sheet(rows)

if __name__ == "__main__":
    main()
