import requests
import json
from datetime import datetime
import time

def generate_mock_order(order_id, status="processing"):
    # Current time as timestamp for YITH
    now_ts = int(time.time())
    
    # Mock data structure matching WooCommerce Webhook payload
    return {
        "id": order_id,
        "parent_id": 0,
        "status": status,
        "currency": "HKD",
        "date_created": datetime.now().isoformat(),
        "total": "550.00",
        "payment_method_title": "PayMe",
        "billing": {
            "first_name": "Tai Man",
            "last_name": "Chan",
            "phone": "85212345678",
            "email": "taiman@example.com"
        },
        "line_items": [
            {
                "id": 111,
                "name": "Single Kayak",
                "product_id": 81,
                "quantity": 1,
                "meta_data": [
                    {
                        "key": "yith_booking_data",
                        "value": {
                            "from": str(now_ts), # Booking for TODAY
                            "to": str(now_ts + 86400),
                            "persons": 1,
                            "booking_services": [34], # Snorkel Mask Rent
                            "booking_service_quantities": {"34": 2}
                        }
                    }
                ]
            },
            {
                "id": 112,
                "name": "Double Kayak",
                "product_id": 82,
                "quantity": 1,
                "meta_data": [
                     {
                        "key": "yith_booking_data",
                        "value": {
                            "from": str(now_ts),
                            "persons": 1
                        }
                    }
                ]
            }
        ]
    }

def send_webhook(data):
    url = "http://localhost:5000/webhook/order_created"
    headers = {"Content-Type": "application/json"}
    try:
        response = requests.post(url, json=data, headers=headers)
        print(f"Sent Order {data['id']}: Status Code {response.status_code}")
        print(f"Response: {response.text}")
    except Exception as e:
        print(f"Failed to send webhook: {e}")

if __name__ == "__main__":
    print("Simulating Webhooks...")
    
    # Simulate Order 1001
    order1 = generate_mock_order(1001)
    send_webhook(order1)
    
    # Simulate Order 1002 (CRM check: same phone number to test aggregation)
    time.sleep(1)
    order2 = generate_mock_order(1002)
    order2['total'] = "1200.00"
    # Change booking date to tomorrow for variety? No, let's keep today to test queue/race condition logic if we ran parallel.
    send_webhook(order2)

    print("Done. Check logs for processing details.")
