import requests
import logging
import json
import base64
from datetime import datetime

# Logging setup
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class WCHandler:
    def __init__(self):
        try:
            import config
            self.url = getattr(config, "WC_URL", "https://kayarine.com")
            self.consumer_key = getattr(config, "WC_CONSUMER_KEY", "")
            self.consumer_secret = getattr(config, "WC_CONSUMER_SECRET", "")
        except ImportError:
            self.url = "https://kayarine.com"
            self.consumer_key = ""
            self.consumer_secret = ""

        self.api_url = f"{self.url}/wp-json/wc/v3"
        
        # Prepare Auth
        self.auth = (self.consumer_key, self.consumer_secret)

    def create_coupon(self, code, amount, expiry_date=None, min_spend=0):
        """
        Creates a coupon in WooCommerce.
        """
        endpoint = f"{self.api_url}/coupons"
        
        # Mock Check: If keys are default placeholders
        if "ck_" not in self.consumer_key:
            logger.info(f"[MOCK] Creating Coupon {code} for ${amount}")
            return {"id": 9999, "code": code, "amount": amount}

        payload = {
            "code": code,
            "discount_type": "fixed_cart", # Fixed amount discount
            "amount": str(amount),
            "individual_use": True,
            "exclude_sale_items": True,
            "minimum_amount": str(min_spend),
            "usage_limit": 1, # Ensure only used once
            "usage_limit_per_user": 1 # Ensure strictly one per user if they log in
        }
        
        if expiry_date:
            # WC expects ISO8601 usually, or YYYY-MM-DD
            payload["date_expires"] = f"{expiry_date}T23:59:59"

        try:
            response = requests.post(endpoint, auth=self.auth, json=payload)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            logger.error(f"WC API Error creating coupon: {e}")
            if response is not None:
                logger.error(f"Response: {response.text}")
            return None

if __name__ == "__main__":
    # Test
    wc = WCHandler()
    res = wc.create_coupon("TEST-CODE-123", "50", "2024-12-31")
    print(res)
