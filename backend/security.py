"""
Security Module for Kayarine Booking System
Handles rate limiting, request validation, and monitoring
"""

import os
import logging
import hmac
import hashlib
import base64
from datetime import datetime, timedelta
from functools import wraps
from flask import request, jsonify, abort
import ipaddress
import json

logger = logging.getLogger(__name__)

class SuspiciousIPMonitor:
    """
    Monitor and track suspicious IP addresses based on request patterns
    """
    def __init__(self, threshold=50, time_window=30):
        """
        threshold: Number of requests to trigger alert
        time_window: Time window in seconds to count requests
        """
        self.suspicious_ips = {}
        self.threshold = threshold
        self.time_window = time_window
    
    def check_request(self, ip, endpoint="unknown"):
        """Monitor request from IP and return whether it's suspicious"""
        now = datetime.now()
        
        if ip not in self.suspicious_ips:
            self.suspicious_ips[ip] = {
                'count': 0,
                'first_seen': now,
                'last_seen': now,
                'endpoints': []
            }
        
        record = self.suspicious_ips[ip]
        record['count'] += 1
        record['last_seen'] = now
        record['endpoints'].append(endpoint)
        
        # Check if request is within time window
        time_diff = (now - record['first_seen']).total_seconds()
        
        if time_diff <= self.time_window:
            if record['count'] > self.threshold:
                logger.warning(
                    f"🚨 SUSPICIOUS IP DETECTED: {ip} | "
                    f"Requests: {record['count']} | "
                    f"Window: {time_diff:.1f}s | "
                    f"Endpoints: {', '.join(set(record['endpoints']))}"
                )
                return True
        else:
            # Reset if outside window
            self.suspicious_ips[ip] = {
                'count': 1,
                'first_seen': now,
                'last_seen': now,
                'endpoints': [endpoint]
            }
        
        return False
    
    def get_stats(self):
        """Return current monitoring statistics"""
        return {
            'total_ips': len(self.suspicious_ips),
            'ips': self.suspicious_ips
        }
    
    def clear_old_records(self, hours=24):
        """Clean up old IP records"""
        cutoff = datetime.now() - timedelta(hours=hours)
        to_delete = [ip for ip, data in self.suspicious_ips.items()
                     if data['last_seen'] < cutoff]
        
        for ip in to_delete:
            del self.suspicious_ips[ip]
        
        logger.info(f"Cleaned {len(to_delete)} old IP records")


class IPWhitelist:
    """
    Manage IP whitelist for webhook sources
    """
    def __init__(self):
        self.woocommerce_ranges = []
        self.whatsapp_ranges = []
        self._load_from_env()
    
    def _load_from_env(self):
        """Load IP ranges from environment variables"""
        try:
            wc_cidrs = os.getenv('WHITELIST_WOOCOMMERCE', '').split(',')
            wa_cidrs = os.getenv('WHITELIST_WHATSAPP', '').split(',')
            
            self.woocommerce_ranges = [
                ipaddress.ip_network(cidr.strip(), strict=False)
                for cidr in wc_cidrs if cidr.strip()
            ]
            
            self.whatsapp_ranges = [
                ipaddress.ip_network(cidr.strip(), strict=False)
                for cidr in wa_cidrs if cidr.strip()
            ]
            
            logger.info(f"Loaded {len(self.woocommerce_ranges)} WooCommerce IP ranges")
            logger.info(f"Loaded {len(self.whatsapp_ranges)} WhatsApp IP ranges")
        except Exception as e:
            logger.error(f"Error loading IP whitelist from environment: {e}")
    
    def is_ip_allowed(self, ip_str, service='woocommerce'):
        """Check if IP is in whitelist for given service"""
        try:
            ip = ipaddress.ip_address(ip_str)
            
            if service.lower() == 'woocommerce':
                return any(ip in network for network in self.woocommerce_ranges)
            elif service.lower() == 'whatsapp':
                return any(ip in network for network in self.whatsapp_ranges)
            else:
                logger.warning(f"Unknown service type: {service}")
                return False
        except ValueError:
            logger.error(f"Invalid IP address: {ip_str}")
            return False


class WebhookVerifier:
    """
    Verify webhook signatures from WooCommerce and other sources
    """
    
    @staticmethod
    def verify_woocommerce_signature(request_obj, secret):
        """
        Verify WooCommerce webhook signature using HMAC-SHA256
        
        WooCommerce sends signature in X-WC-Webhook-Signature header
        Signature = Base64(HMAC-SHA256(body, secret))
        """
        try:
            provided_signature = request_obj.headers.get('X-WC-Webhook-Signature', '')
            
            if not provided_signature:
                logger.warning("Missing X-WC-Webhook-Signature header")
                return False
            
            body = request_obj.get_data()
            
            # Calculate expected signature
            expected_hmac = hmac.new(
                secret.encode(),
                body,
                hashlib.sha256
            ).digest()
            expected_signature = base64.b64encode(expected_hmac).decode()
            
            # Compare signatures (constant-time comparison to prevent timing attacks)
            is_valid = hmac.compare_digest(provided_signature, expected_signature)
            
            if not is_valid:
                logger.warning(
                    f"Invalid webhook signature. "
                    f"Expected: {expected_signature[:20]}..., "
                    f"Got: {provided_signature[:20]}..."
                )
            
            return is_valid
        
        except Exception as e:
            logger.error(f"Error verifying webhook signature: {e}")
            return False
    
    @staticmethod
    def verify_whatsapp_signature(token, request_obj):
        """
        Verify WhatsApp webhook token
        WhatsApp sends verification token in hub.verify_token parameter
        """
        try:
            provided_token = request_obj.args.get('hub.verify_token', '')
            
            if not provided_token:
                logger.warning("Missing WhatsApp verification token")
                return False
            
            is_valid = hmac.compare_digest(provided_token, token)
            
            if not is_valid:
                logger.warning("Invalid WhatsApp verification token")
            
            return is_valid
        
        except Exception as e:
            logger.error(f"Error verifying WhatsApp signature: {e}")
            return False


class APITokenAuth:
    """
    Manage API token authentication for protected endpoints
    """
    def __init__(self):
        self.tokens = {}
        self._load_from_env()
    
    def _load_from_env(self):
        """Load API tokens from environment variables"""
        try:
            frontend_token = os.getenv('FRONTEND_API_TOKEN', '')
            internal_token = os.getenv('INTERNAL_API_TOKEN', '')
            
            if frontend_token:
                self.tokens['frontend'] = frontend_token
            if internal_token:
                self.tokens['internal'] = internal_token
            
            logger.info(f"Loaded {len(self.tokens)} API tokens")
        except Exception as e:
            logger.error(f"Error loading API tokens: {e}")
    
    def verify_token(self, request_obj):
        """Extract and verify token from Authorization header"""
        try:
            auth_header = request_obj.headers.get('Authorization', '')
            
            if not auth_header:
                logger.warning("Missing Authorization header")
                return False
            
            # Support "Bearer <token>" format
            parts = auth_header.split()
            if len(parts) == 2 and parts[0].lower() == 'bearer':
                token = parts[1]
            else:
                token = auth_header
            
            # Check if token exists in our tokens
            is_valid = token in self.tokens.values()
            
            if not is_valid:
                logger.warning(f"Invalid API token provided")
            
            return is_valid
        
        except Exception as e:
            logger.error(f"Error verifying API token: {e}")
            return False


def require_api_token(f):
    """Decorator to require API token for endpoint"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        auth = APITokenAuth()
        
        if not auth.verify_token(request):
            logger.warning(f"Unauthorized API request from {request.remote_addr}")
            return jsonify({"error": "Unauthorized"}), 403
        
        return f(*args, **kwargs)
    
    return decorated_function


def require_webhook_signature(service='woocommerce'):
    """
    Decorator to verify webhook signature
    service: 'woocommerce' or 'whatsapp'
    """
    def decorator(f):
        @wraps(f)
        def decorated_function(*args, **kwargs):
            if service.lower() == 'woocommerce':
                secret = os.getenv('WC_WEBHOOK_SECRET', '')
                if not secret:
                    logger.warning("WC_WEBHOOK_SECRET not configured")
                    return jsonify({"error": "Webhook verification not configured"}), 500
                
                if not WebhookVerifier.verify_woocommerce_signature(request, secret):
                    logger.warning(f"Failed webhook signature from {request.remote_addr}")
                    return jsonify({"error": "Invalid signature"}), 403
            
            return f(*args, **kwargs)
        
        return decorated_function
    
    return decorator


def get_client_ip(request_obj):
    """
    Get client IP address from request
    Considers X-Forwarded-For header from reverse proxy/Cloudflare
    """
    # Check for X-Forwarded-For (set by Cloudflare or reverse proxy)
    if 'X-Forwarded-For' in request_obj.headers:
        return request_obj.headers.get('X-Forwarded-For').split(',')[0].strip()
    
    # Check for CF-Connecting-IP (Cloudflare)
    if 'CF-Connecting-IP' in request_obj.headers:
        return request_obj.headers.get('CF-Connecting-IP')
    
    # Fallback to direct remote address
    return request_obj.remote_addr


def log_request_info(request_obj, monitor=None):
    """
    Log detailed request information for monitoring
    """
    ip = get_client_ip(request_obj)
    
    log_data = {
        'timestamp': datetime.now().isoformat(),
        'method': request_obj.method,
        'path': request_obj.path,
        'ip': ip,
        'user_agent': request_obj.headers.get('User-Agent', 'Unknown'),
        'content_length': request_obj.content_length or 0,
    }
    
    # Check if suspicious
    if monitor:
        is_suspicious = monitor.check_request(ip, request_obj.path)
        log_data['suspicious'] = is_suspicious
    
    logger.debug(f"REQUEST: {json.dumps(log_data)}")
    
    return ip


# Initialize global instances
suspicious_ip_monitor = SuspiciousIPMonitor(
    threshold=int(os.getenv('SUSPICIOUS_REQUEST_THRESHOLD', '50')),
    time_window=int(os.getenv('SUSPICIOUS_REQUEST_WINDOW', '30'))
)

ip_whitelist = IPWhitelist()
webhook_verifier = WebhookVerifier()
api_token_auth = APITokenAuth()
