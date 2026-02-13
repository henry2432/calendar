<?php
/**
 * Kayarine Social Authentication Endpoints
 * 
 * 為 Google 和 Apple 社交登入提供 REST API 端點
 * 
 * @package Kayarine_Booking
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Kayarine_Social_Auth {
    
    /**
     * 註冊 REST API 路由
     */
    public static function register_routes() {
        // Google 登入端點
        register_rest_route('kayarine/v1', '/auth/google-login', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'google_login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email'
                ),
                'name' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'google_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'picture' => array(
                    'required' => false,
                    'sanitize_callback' => 'esc_url_raw'
                )
            )
        ));

        // Apple 登入端點
        register_rest_route('kayarine/v1', '/auth/apple-login', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'apple_login'),
            'permission_callback' => '__return_true',
            'args' => array(
                'apple_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email'
                ),
                'name' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'email_verified' => array(
                    'required' => false,
                    'default' => false
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
     * Google 登入處理
     * 
     * POST /wp-json/kayarine/v1/auth/google-login
     */
    public static function google_login($request) {
        $email = $request->get_param('email');
        $name = $request->get_param('name') ?: 'Google User';
        $google_id = $request->get_param('google_id');
        $picture = $request->get_param('picture');

        error_log("[Kayarine Social Auth] Google login attempt: {$email}");

        // 檢查用戶是否存在
        $user = get_user_by('email', $email);

        if (!$user) {
            // 創建新用戶
            $username = self::generate_unique_username($email);
            $password = wp_generate_password(16, true, true);
            
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                error_log("[Kayarine Social Auth] Failed to create user: " . $user_id->get_error_message());
                return new WP_Error(
                    'user_creation_failed',
                    '創建用戶失敗：' . $user_id->get_error_message(),
                    array('status' => 500)
                );
            }

            // 更新用戶資料
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => $name
            ));

            // 設置角色
            $user = new WP_User($user_id);
            $user->set_role('customer');

            // 儲存 Google ID
            update_user_meta($user_id, '_google_id', $google_id);
            
            // 儲存頭像 URL
            if ($picture) {
                update_user_meta($user_id, '_google_picture', $picture);
            }

            // 初始化會員資料
            update_user_meta($user_id, '_kayarine_member_tier', 'Bronze');
            update_user_meta($user_id, '_kayarine_points_balance', 0);
            update_user_meta($user_id, '_kayarine_total_spending', 0);

            error_log("[Kayarine Social Auth] New Google user created: {$user_id}");
        } else {
            // 更新現有用戶的 Google ID
            $user_id = $user->ID;
            update_user_meta($user_id, '_google_id', $google_id);
            
            if ($picture) {
                update_user_meta($user_id, '_google_picture', $picture);
            }

            error_log("[Kayarine Social Auth] Existing user logged in via Google: {$user_id}");
        }

        // 獲取會員資料
        $tier = get_user_meta($user_id, '_kayarine_member_tier', true) ?: 'Bronze';
        $points = get_user_meta($user_id, '_kayarine_points_balance', true) ?: 0;

        return rest_ensure_response(array(
            'success' => true,
            'user_id' => $user_id,
            'tier' => $tier,
            'points' => intval($points),
            'message' => '登入成功'
        ));
    }

    /**
     * Apple 登入處理
     * 
     * POST /wp-json/kayarine/v1/auth/apple-login
     */
    public static function apple_login($request) {
        $apple_id = $request->get_param('apple_id');
        $email = $request->get_param('email');
        $name = $request->get_param('name') ?: 'Apple User';
        $email_verified = $request->get_param('email_verified');

        error_log("[Kayarine Social Auth] Apple login attempt: {$email}");

        // 先嘗試通過 Apple ID 查找用戶
        $users = get_users(array(
            'meta_key' => '_apple_id',
            'meta_value' => $apple_id,
            'number' => 1
        ));

        if (!empty($users)) {
            $user = $users[0];
            $user_id = $user->ID;
            error_log("[Kayarine Social Auth] Existing user found by Apple ID: {$user_id}");
        } else {
            // 檢查 email 是否存在
            $user = get_user_by('email', $email);

            if (!$user) {
                // 創建新用戶
                $username = self::generate_unique_username($email);
                $password = wp_generate_password(16, true, true);
                
                $user_id = wp_create_user($username, $password, $email);

                if (is_wp_error($user_id)) {
                    error_log("[Kayarine Social Auth] Failed to create user: " . $user_id->get_error_message());
                    return new WP_Error(
                        'user_creation_failed',
                        '創建用戶失敗：' . $user_id->get_error_message(),
                        array('status' => 500)
                    );
                }

                // 更新用戶資料
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $name,
                    'first_name' => $name
                ));

                // 設置角色
                $user = new WP_User($user_id);
                $user->set_role('customer');

                // 初始化會員資料
                update_user_meta($user_id, '_kayarine_member_tier', 'Bronze');
                update_user_meta($user_id, '_kayarine_points_balance', 0);
                update_user_meta($user_id, '_kayarine_total_spending', 0);

                error_log("[Kayarine Social Auth] New Apple user created: {$user_id}");
            } else {
                $user_id = $user->ID;
                error_log("[Kayarine Social Auth] Existing user logged in via Apple: {$user_id}");
            }

            // 儲存 Apple ID
            update_user_meta($user_id, '_apple_id', $apple_id);
            
            // 儲存 email 驗證狀態
            if ($email_verified) {
                update_user_meta($user_id, '_apple_email_verified', true);
            }
        }

        // 獲取會員資料
        $tier = get_user_meta($user_id, '_kayarine_member_tier', true) ?: 'Bronze';
        $points = get_user_meta($user_id, '_kayarine_points_balance', true) ?: 0;

        return rest_ensure_response(array(
            'success' => true,
            'user_id' => $user_id,
            'tier' => $tier,
            'points' => intval($points),
            'message' => '登入成功'
        ));
    }

    /**
     * 生成唯一用戶名
     */
    private static function generate_unique_username($email) {
        $username = sanitize_user(str_replace('@', '_', $email));
        $base_username = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }
        
        return $username;
    }
}

// 註冊 REST API 路由
add_action('rest_api_init', array('Kayarine_Social_Auth', 'register_routes'));
