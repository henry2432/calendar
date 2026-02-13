<?php
/**
 * Kayarine Authentication Endpoints
 * 
 * 為 Next.js 前端提供註冊和認證相關的 REST API 端點
 * 
 * @package Kayarine_Booking
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Kayarine_Auth_Endpoints {
    
    /**
     * 註冊 REST API 路由
     */
    public static function register_routes() {
        // 用戶註冊端點
        register_rest_route('kayarine/v1', '/auth/register', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'register_user'),
            'permission_callback' => '__return_true', // 公開端點
            'args' => array(
                'name' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function($param) {
                        return !empty($param) && strlen($param) >= 2;
                    }
                ),
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                ),
                'password' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        // 密碼至少 8 個字符，包含大寫和數字
                        return strlen($param) >= 8 &&
                               preg_match('/[A-Z]/', $param) &&
                               preg_match('/[0-9]/', $param);
                    }
                ),
                'otp_code' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // P0-4: 發送註冊驗證碼
        register_rest_route('kayarine/v1', '/auth/send-otp', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'send_registration_otp'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                )
            )
        ));

        // P0-4: 驗證註冊 OTP
        register_rest_route('kayarine/v1', '/auth/verify-otp', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'verify_registration_otp'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                ),
                'otp_code' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // P0-3: 忘記密碼 - 發送重設連結/OTP
        register_rest_route('kayarine/v1', '/auth/forgot-password', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'forgot_password'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                )
            )
        ));

        // P0-3: 驗證重設 OTP
        register_rest_route('kayarine/v1', '/auth/verify-reset-otp', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'verify_reset_otp'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                ),
                'otp_code' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // P0-3: 重設密碼
        register_rest_route('kayarine/v1', '/auth/reset-password', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'reset_password'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                ),
                'otp_code' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'new_password' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return strlen($param) >= 8 &&
                               preg_match('/[A-Z]/', $param) &&
                               preg_match('/[0-9]/', $param);
                    }
                )
            )
        ));

        // 添加 CORS 支持
        add_action('rest_api_init', function() {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
                self::add_cors_headers();
                return $served;
            }, 10, 4);
        });
    }

    /**
     * 添加 CORS 頭
     */
    private static function add_cors_headers() {
        $allowed_origins = array(
            'http://localhost:3000',
            'http://104.199.144.122:3000',
            'http://kayarine.club',
            'https://kayarine.club',
            'http://www.kayarine.club',
            'https://www.kayarine.club'
        );
        
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-WP-Nonce');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * 處理用戶註冊
     * 
     * POST /wp-json/kayarine/v1/auth/register
     * Body: { "name": "John Doe", "email": "john@example.com", "password": "Password123" }
     */
    public static function register_user($request) {
        $name = $request->get_param('name');
        $email = $request->get_param('email');
        $password = $request->get_param('password');
        $otp_code = $request->get_param('otp_code');

        // 檢查 WordPress 是否允許註冊
        if (!get_option('users_can_register')) {
            return new WP_Error(
                'registration_disabled',
                '網站目前不開放註冊',
                array('status' => 403)
            );
        }

        // 如果提供了 OTP，驗證它（可選功能）
        if (!empty($otp_code)) {
            $otp_verified = Kayarine_OTP::verify_otp($email, $otp_code, Kayarine_OTP::TYPE_REGISTRATION);
            if (is_wp_error($otp_verified)) {
                return $otp_verified;
            }
        }

        // 檢查 email 是否已存在
        if (email_exists($email)) {
            return new WP_Error(
                'email_exists',
                '此電子郵件已被註冊，請使用其他電子郵件或直接登入',
                array('status' => 400)
            );
        }

        // 生成用戶名（從 email）
        $username = sanitize_user(str_replace('@', '_', $email));
        
        // 確保用戶名唯一
        $base_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }

        // 創建用戶
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return new WP_Error(
                'registration_failed',
                '註冊失敗：' . $user_id->get_error_message(),
                array('status' => 500)
            );
        }

        // 更新用戶顯示名稱
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name
        ));

        // 設置為 WooCommerce 客戶角色
        $user = new WP_User($user_id);
        $user->set_role('customer');

        // 初始化會員等級和積分
        update_user_meta($user_id, '_kayarine_member_tier', 'Bronze');
        update_user_meta($user_id, '_kayarine_points_balance', 0);
        update_user_meta($user_id, '_kayarine_total_spending', 0);

        // 發送歡迎郵件（可選）
        wp_new_user_notification($user_id, null, 'user');

        return rest_ensure_response(array(
            'success' => true,
            'message' => '註冊成功！請登入您的帳戶',
            'data' => array(
                'user_id' => $user_id,
                'username' => $username,
                'email' => $email
            )
        ));
    }

    /**
     * P0-4: 發送註冊驗證碼
     *
     * POST /wp-json/kayarine/v1/auth/send-otp
     * Body: { "email": "user@example.com" }
     */
    public static function send_registration_otp($request) {
        $email = $request->get_param('email');

        error_log("[Kayarine Auth] Sending registration OTP to: {$email}");

        // 檢查 email 是否已註冊
        if (email_exists($email)) {
            return new WP_Error(
                'email_exists',
                '此電子郵件已被註冊，請直接登入',
                array('status' => 400)
            );
        }

        // 生成 OTP
        $otp_code = Kayarine_OTP::generate_otp($email, Kayarine_OTP::TYPE_REGISTRATION);

        if (is_wp_error($otp_code)) {
            return $otp_code;
        }

        // TODO: 發送郵件（待 P0-1 Email 系統完成）
        // 目前先返回 OTP（開發階段）
        error_log("[Kayarine Auth] Registration OTP generated: {$otp_code}");

        return rest_ensure_response(array(
            'success' => true,
            'message' => '驗證碼已發送到您的電子郵件',
            'dev_otp' => $otp_code, // 開發階段顯示，生產環境應移除
            'expires_in' => Kayarine_OTP::OTP_EXPIRY
        ));
    }

    /**
     * P0-4: 驗證註冊 OTP
     *
     * POST /wp-json/kayarine/v1/auth/verify-otp
     * Body: { "email": "user@example.com", "otp_code": "123456" }
     */
    public static function verify_registration_otp($request) {
        $email = $request->get_param('email');
        $otp_code = $request->get_param('otp_code');

        error_log("[Kayarine Auth] Verifying registration OTP for: {$email}");

        // 驗證 OTP
        $verified = Kayarine_OTP::verify_otp($email, $otp_code, Kayarine_OTP::TYPE_REGISTRATION);

        if (is_wp_error($verified)) {
            return $verified;
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => '驗證成功，請完成註冊',
            'verified' => true
        ));
    }

    /**
     * P0-3: 忘記密碼 - 發送重設 OTP
     *
     * POST /wp-json/kayarine/v1/auth/forgot-password
     * Body: { "email": "user@example.com" }
     */
    public static function forgot_password($request) {
        $email = $request->get_param('email');

        error_log("[Kayarine Auth] Password reset requested for: {$email}");

        // 檢查用戶是否存在
        $user = get_user_by('email', $email);
        if (!$user) {
            // 為了安全，不透露用戶是否存在
            return rest_ensure_response(array(
                'success' => true,
                'message' => '如果該電子郵件已註冊，您將收到重設密碼的驗證碼'
            ));
        }

        // 生成密碼重設 OTP
        $otp_code = Kayarine_OTP::generate_otp($email, Kayarine_OTP::TYPE_PASSWORD_RESET);

        if (is_wp_error($otp_code)) {
            return $otp_code;
        }

        // TODO: 發送郵件（待 P0-1 Email 系統完成）
        error_log("[Kayarine Auth] Password reset OTP generated: {$otp_code}");

        return rest_ensure_response(array(
            'success' => true,
            'message' => '密碼重設驗證碼已發送到您的電子郵件',
            'dev_otp' => $otp_code, // 開發階段顯示，生產環境應移除
            'expires_in' => Kayarine_OTP::OTP_EXPIRY
        ));
    }

    /**
     * P0-3: 驗證密碼重設 OTP
     *
     * POST /wp-json/kayarine/v1/auth/verify-reset-otp
     * Body: { "email": "user@example.com", "otp_code": "123456" }
     */
    public static function verify_reset_otp($request) {
        $email = $request->get_param('email');
        $otp_code = $request->get_param('otp_code');

        error_log("[Kayarine Auth] Verifying password reset OTP for: {$email}");

        // 驗證 OTP
        $verified = Kayarine_OTP::verify_otp($email, $otp_code, Kayarine_OTP::TYPE_PASSWORD_RESET);

        if (is_wp_error($verified)) {
            return $verified;
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => '驗證成功，請設定新密碼',
            'verified' => true
        ));
    }

    /**
     * P0-3: 重設密碼
     *
     * POST /wp-json/kayarine/v1/auth/reset-password
     * Body: { "email": "user@example.com", "otp_code": "123456", "new_password": "NewPassword123" }
     */
    public static function reset_password($request) {
        $email = $request->get_param('email');
        $otp_code = $request->get_param('otp_code');
        $new_password = $request->get_param('new_password');

        error_log("[Kayarine Auth] Password reset attempt for: {$email}");

        // 再次驗證 OTP（確保仍然有效）
        $verified = Kayarine_OTP::verify_otp($email, $otp_code, Kayarine_OTP::TYPE_PASSWORD_RESET);

        if (is_wp_error($verified)) {
            return $verified;
        }

        // 獲取用戶
        $user = get_user_by('email', $email);
        if (!$user) {
            return new WP_Error(
                'user_not_found',
                '找不到此用戶',
                array('status' => 404)
            );
        }

        // 重設密碼
        wp_set_password($new_password, $user->ID);

        // 使所有現有的 session 失效（強制重新登入）
        $sessions = WP_Session_Tokens::get_instance($user->ID);
        $sessions->destroy_all();

        error_log("[Kayarine Auth] Password reset successful for user ID: {$user->ID}");

        return rest_ensure_response(array(
            'success' => true,
            'message' => '密碼重設成功，請使用新密碼登入'
        ));
    }
}

// 註冊 REST API 路由
add_action('rest_api_init', array('Kayarine_Auth_Endpoints', 'register_routes'));
