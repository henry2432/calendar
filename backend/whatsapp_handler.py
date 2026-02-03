import requests
import logging
import json
import os

# Logging setup
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class WhatsAppHandler:
    def __init__(self):
        try:
            import config
            self.api_url = getattr(config, "WHATSAPP_API_URL", "https://graph.facebook.com/v18.0")
            self.phone_id = getattr(config, "WHATSAPP_PHONE_ID", "")
            self.access_token = getattr(config, "WHATSAPP_ACCESS_TOKEN", "")
        except ImportError:
            self.api_url = "https://graph.facebook.com/v18.0"
            self.phone_id = ""
            self.access_token = ""

        self.headers = {
            "Authorization": f"Bearer {self.access_token}",
            "Content-Type": "application/json"
        }

    def send_template_message(self, phone, template_name, parameters, language_code="zh_HK"):
        """
        Sends a template message via WhatsApp Cloud API.
        
        Args:
            phone (str): Recipient phone number (e.g., "85212345678").
            template_name (str): Name of the template in Meta Business Manager.
            parameters (list): List of parameter dicts (mapped from old Wati format).
                               Wati format: [{"name": "name", "value": "John"}, ...]
                               
        Note on Mapping:
            Meta templates use positional parameters {{1}}, {{2}} in the Body.
            We assume the input 'parameters' list is ALREADY sorted in the correct order 
            expected by the template (Body 1, Body 2, etc.).
        """
        if not self.phone_id or not self.access_token:
            logger.warning("[MOCK] WhatsApp credentials missing. Skipping send.")
            return {"result": "mock_success"}

        url = f"{self.api_url}/{self.phone_id}/messages"
        
        # Convert Wati-style named params to Meta-style positional params
        # We assume the order in the list is the order of {{1}}, {{2}}, etc.
        body_components = []
        for param in parameters:
            body_components.append({
                "type": "text",
                "text": str(param.get("value", ""))
            })

        payload = {
            "messaging_product": "whatsapp",
            "to": phone,
            "type": "template",
            "template": {
                "name": template_name,
                "language": {
                    "code": language_code
                },
                "components": [
                    {
                        "type": "body",
                        "parameters": body_components
                    }
                ]
            }
        }

        try:
            response = requests.post(url, headers=self.headers, json=payload)
            response.raise_for_status()
            logger.info(f"WhatsApp Message sent to {phone}")
            return response.json()
        except requests.exceptions.RequestException as e:
            logger.error(f"WhatsApp API Error: {e}")
            if 'response' in locals() and response is not None:
                logger.error(f"Response: {response.text}")
            return {"result": "error", "message": str(e)}

    def send_text_message(self, phone, message):
        """
        Sends a free-form text message (only allowed within 24h window).
        """
        if not self.phone_id or not self.access_token:
            return {"result": "mock_success"}

        url = f"{self.api_url}/{self.phone_id}/messages"
        
        payload = {
            "messaging_product": "whatsapp",
            "to": phone,
            "type": "text",
            "text": {"body": message}
        }

        try:
            response = requests.post(url, headers=self.headers, json=payload)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            logger.error(f"WhatsApp Text Error: {e}")
            return {"result": "error", "message": str(e)}

    # Compatibility Wrappers for existing calls
    def send_payment_reminder(self, order_details):
        # Template: payment_reminder_v1
        # Params: customer_name, order_id, amount, payment_link
        params = [
            {"name": "customer_name", "value": order_details.get("customer_name")},
            {"name": "order_id", "value": order_details.get("order_id")},
            {"name": "amount", "value": order_details.get("amount")},
            {"name": "payment_link", "value": "https://payme.hsbc/kayarine"}
        ]
        return self.send_template_message(order_details.get("phone"), "payment_reminder_v1", params)

    def send_booking_confirmation(self, order_details):
        # Template: booking_confirmation_v1
        # Params: customer_name, order_id, booking_date, items, location, safety
        params = [
            {"name": "customer_name", "value": order_details.get("customer_name")},
            {"name": "order_id", "value": order_details.get("order_id")},
            {"name": "booking_date", "value": order_details.get("booking_date")},
            {"name": "items", "value": order_details.get("items_str")},
            {"name": "location_link", "value": "https://maps.google.com/?q=Kayarine"},
            {"name": "safety_link", "value": "https://kayarine.com/safety"}
        ]
        return self.send_template_message(order_details.get("phone"), "booking_confirmation_v1", params)

    def send_tour_confirmation(self, order_details):
        # Template: tour_confirmation_v1
        params = [
            {"name": "customer_name", "value": order_details.get("customer_name")},
            {"name": "tour_name", "value": "Tour"},
            {"name": "meeting_time", "value": "09:00 AM"},
            {"name": "meeting_point", "value": "Center"}
        ]
        return self.send_template_message(order_details.get("phone"), "tour_confirmation_v1", params)

    def send_coupon_distribution(self, phone, coupon_code, discount_amount):
        # Template: coupon_distribution_v1
        params = [
            {"name": "coupon_code", "value": coupon_code},
            {"name": "discount_amount", "value": discount_amount}
        ]
        return self.send_template_message(phone, "coupon_distribution_v1", params)
