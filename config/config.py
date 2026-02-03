"""
Configuration module for Kayarine Booking System
Loads configuration from environment variables with sensible defaults
"""

import os
from dotenv import load_dotenv

# Load environment variables from .env file (if exists)
load_dotenv()

# ==============================================================================
# Flask Configuration
# ==============================================================================
FLASK_ENV = os.getenv('FLASK_ENV', 'production')
FLASK_DEBUG = os.getenv('FLASK_DEBUG', 'False').lower() == 'true'
SECRET_KEY = os.getenv('SECRET_KEY', 'dev-secret-key-change-this-in-production')

# ==============================================================================
# Google Sheets Configuration
# ==============================================================================
CREDENTIALS_PATH = os.getenv(
    'CREDENTIALS_PATH', 
    "/home/kayarine_server/kayarine-webhook/credentials.json"
)

# ==============================================================================
# WhatsApp Cloud API Configuration
# ==============================================================================
WHATSAPP_API_URL = os.getenv(
    'WHATSAPP_API_URL',
    "https://graph.facebook.com/v18.0"
)
WHATSAPP_PHONE_ID = os.getenv('WHATSAPP_PHONE_ID', "")
WHATSAPP_ACCESS_TOKEN = os.getenv('WHATSAPP_ACCESS_TOKEN', "")
WHATSAPP_VERIFY_TOKEN = os.getenv('WHATSAPP_VERIFY_TOKEN', "kayarine_secret_token_123")

# ==============================================================================
# WooCommerce Configuration
# ==============================================================================
WC_URL = os.getenv('WC_URL', "https://kayarine.club")
WC_CONSUMER_KEY = os.getenv('WC_CONSUMER_KEY', "")
WC_CONSUMER_SECRET = os.getenv('WC_CONSUMER_SECRET', "")
WC_WEBHOOK_SECRET = os.getenv('WC_WEBHOOK_SECRET', "")

# ==============================================================================
# Rate Limiting Configuration
# ==============================================================================
RATE_LIMIT_ENABLED = os.getenv('RATE_LIMIT_ENABLED', 'True').lower() == 'true'
RATE_LIMIT_WEBHOOK_DEFAULT = os.getenv('RATE_LIMIT_WEBHOOK_DEFAULT', "100/minute")
RATE_LIMIT_API_CHATS = os.getenv('RATE_LIMIT_API_CHATS', "30/minute")
RATE_LIMIT_API_MESSAGES = os.getenv('RATE_LIMIT_API_MESSAGES', "30/minute")
RATE_LIMIT_API_AVAILABILITY = os.getenv('RATE_LIMIT_API_AVAILABILITY', "10/minute")
RATE_LIMIT_API_SEND_MESSAGE = os.getenv('RATE_LIMIT_API_SEND_MESSAGE', "20/minute")

# ==============================================================================
# IP Whitelist Configuration (for webhook security)
# ==============================================================================
WHITELIST_WOOCOMMERCE = os.getenv('WHITELIST_WOOCOMMERCE', "203.0.113.0/24")
WHITELIST_WHATSAPP = os.getenv('WHITELIST_WHATSAPP', "31.13.64.0/19")

# ==============================================================================
# API Token Configuration
# ==============================================================================
FRONTEND_API_TOKEN = os.getenv('FRONTEND_API_TOKEN', "")
INTERNAL_API_TOKEN = os.getenv('INTERNAL_API_TOKEN', "")

# ==============================================================================
# Monitoring and Alerts Configuration
# ==============================================================================
SLACK_WEBHOOK_URL = os.getenv('SLACK_WEBHOOK_URL', "")
ENABLE_SUSPICIOUS_IP_ALERTS = os.getenv('ENABLE_SUSPICIOUS_IP_ALERTS', 'True').lower() == 'true'
SUSPICIOUS_REQUEST_THRESHOLD = int(os.getenv('SUSPICIOUS_REQUEST_THRESHOLD', '50'))
SUSPICIOUS_REQUEST_WINDOW = int(os.getenv('SUSPICIOUS_REQUEST_WINDOW', '30'))

# ==============================================================================
# Logging Configuration
# ==============================================================================
LOG_LEVEL = os.getenv('LOG_LEVEL', 'INFO')
LOG_FILE = os.getenv('LOG_FILE', 'flask.log')

# ==============================================================================
# Feature Flags
# ==============================================================================
ENABLE_REQUEST_MONITORING = os.getenv('ENABLE_REQUEST_MONITORING', 'True').lower() == 'true'
ENABLE_CLOUDFLARE_PROTECTION = os.getenv('ENABLE_CLOUDFLARE_PROTECTION', 'True').lower() == 'true'

# ==============================================================================
# Summary/Validation
# ==============================================================================
def print_config_summary():
    """Print configuration summary for debugging (safe, no secrets)"""
    print("\n" + "="*80)
    print("KAYARINE CONFIGURATION SUMMARY")
    print("="*80)
    print(f"Environment: {FLASK_ENV}")
    print(f"Debug Mode: {FLASK_DEBUG}")
    print(f"Rate Limiting: {'ENABLED' if RATE_LIMIT_ENABLED else 'DISABLED'}")
    print(f"Request Monitoring: {'ENABLED' if ENABLE_REQUEST_MONITORING else 'DISABLED'}")
    print(f"Cloudflare Protection: {'ENABLED' if ENABLE_CLOUDFLARE_PROTECTION else 'DISABLED'}")
    print(f"Log Level: {LOG_LEVEL}")
    print(f"Webhook Secret Configured: {'YES' if WC_WEBHOOK_SECRET else 'NO ⚠️'}")
    print(f"WhatsApp Token Configured: {'YES' if WHATSAPP_ACCESS_TOKEN else 'NO ⚠️'}")
    print("="*80 + "\n")
