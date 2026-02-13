<?php
/**
 * Kayarine OTP Verification System
 * 
 * 處理一次性密碼（OTP）的生成、驗證和過期管理
 * 用於註冊驗證和密碼重設
 * 
 * @package Kayarine_Booking
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Kayarine_OTP {
    
    /**
     * OTP 有效期（秒）
     */
    const OTP_EXPIRY = 600; // 10 分鐘
    
    /**
     * OTP 長度
     */
    const OTP_LENGTH = 6;
    
    /**
     * OTP 類型常量
     */
    const TYPE_REGISTRATION = 'registration';
    const TYPE_PASSWORD_RESET = 'password_reset';
    
    /**
     * 資料庫表名
     */
    private static $table_name = null;
    
    /**
     * 初始化 - 建立資料庫表
     */
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'kayarine_otp';
        self::create_table();
    }
    
    /**
     * 建立 OTP 資料庫表
     */
    private static function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS " . self::$table_name . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            otp_code varchar(10) NOT NULL,
            otp_type varchar(20) NOT NULL,
            expires_at datetime NOT NULL,
            verified tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email_index (email),
            KEY expires_index (expires_at),
            KEY type_index (otp_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[Kayarine OTP] Table created or verified: ' . self::$table_name);
    }
    
    /**
     * 生成 OTP 驗證碼
     * 
     * @param string $email 電子郵件
     * @param string $type OTP 類型（registration 或 password_reset）
     * @return string|WP_Error OTP 碼或錯誤
     */
    public static function generate_otp($email, $type = self::TYPE_REGISTRATION) {
        global $wpdb;
        
        if (!is_email($email)) {
            return new WP_Error('invalid_email', '無效的電子郵件地址');
        }
        
        // 檢查是否在冷卻期內（防止濫用）
        $cooldown_check = self::check_cooldown($email, $type);
        if (is_wp_error($cooldown_check)) {
            return $cooldown_check;
        }
        
        // 生成 6 位數字 OTP
        $otp_code = str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        
        // 計算過期時間
        $expires_at = date('Y-m-d H:i:s', time() + self::OTP_EXPIRY);
        
        // 使舊的 OTP 失效
        $wpdb->update(
            self::$table_name,
            array('verified' => -1), // -1 表示已失效
            array(
                'email' => $email,
                'otp_type' => $type,
                'verified' => 0
            ),
            array('%d'),
            array('%s', '%s', '%d')
        );
        
        // 插入新的 OTP
        $result = $wpdb->insert(
            self::$table_name,
            array(
                'email' => $email,
                'otp_code' => $otp_code,
                'otp_type' => $type,
                'expires_at' => $expires_at,
                'verified' => 0
            ),
            array('%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            error_log('[Kayarine OTP] Failed to insert OTP: ' . $wpdb->last_error);
            return new WP_Error('otp_generation_failed', 'OTP 生成失敗');
        }
        
        error_log("[Kayarine OTP] Generated OTP for {$email}: {$otp_code} (Type: {$type}, Expires: {$expires_at})");
        
        return $otp_code;
    }
    
    /**
     * 驗證 OTP
     * 
     * @param string $email 電子郵件
     * @param string $otp_code OTP 碼
     * @param string $type OTP 類型
     * @return bool|WP_Error 成功返回 true，失敗返回 WP_Error
     */
    public static function verify_otp($email, $otp_code, $type = self::TYPE_REGISTRATION) {
        global $wpdb;
        
        if (!is_email($email)) {
            return new WP_Error('invalid_email', '無效的電子郵件地址');
        }
        
        if (empty($otp_code) || strlen($otp_code) !== self::OTP_LENGTH) {
            return new WP_Error('invalid_otp', '無效的驗證碼格式');
        }
        
        // 查找有效的 OTP
        $otp_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . "
            WHERE email = %s
            AND otp_code = %s
            AND otp_type = %s
            AND verified = 0
            ORDER BY created_at DESC
            LIMIT 1",
            $email,
            $otp_code,
            $type
        ));
        
        if (!$otp_record) {
            error_log("[Kayarine OTP] OTP not found or already used for {$email}");
            return new WP_Error('otp_invalid', '驗證碼無效或已使用');
        }
        
        // 檢查是否過期
        $now = current_time('mysql');
        if ($now > $otp_record->expires_at) {
            error_log("[Kayarine OTP] OTP expired for {$email}");
            
            // 標記為失效
            $wpdb->update(
                self::$table_name,
                array('verified' => -1),
                array('id' => $otp_record->id),
                array('%d'),
                array('%d')
            );
            
            return new WP_Error('otp_expired', '驗證碼已過期，請重新獲取');
        }
        
        // 標記為已驗證
        $result = $wpdb->update(
            self::$table_name,
            array('verified' => 1),
            array('id' => $otp_record->id),
            array('%d'),
            array('%d')
        );
        
        if ($result === false) {
            error_log('[Kayarine OTP] Failed to mark OTP as verified: ' . $wpdb->last_error);
            return new WP_Error('verification_failed', '驗證失敗');
        }
        
        error_log("[Kayarine OTP] OTP verified successfully for {$email}");
        
        return true;
    }
    
    /**
     * 檢查是否在冷卻期內
     * 
     * @param string $email 電子郵件
     * @param string $type OTP 類型
     * @return bool|WP_Error true 表示可以生成新 OTP，WP_Error 表示在冷卻期內
     */
    private static function check_cooldown($email, $type) {
        global $wpdb;
        
        // 60 秒冷卻期
        $cooldown_time = 60;
        $cooldown_threshold = date('Y-m-d H:i:s', time() - $cooldown_time);
        
        $recent_otp = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$table_name . "
            WHERE email = %s
            AND otp_type = %s
            AND created_at > %s",
            $email,
            $type,
            $cooldown_threshold
        ));
        
        if ($recent_otp > 0) {
            error_log("[Kayarine OTP] Cooldown active for {$email}");
            return new WP_Error('otp_cooldown', "請等待 {$cooldown_time} 秒後再重新獲取驗證碼");
        }
        
        return true;
    }
    
    /**
     * 生成密碼重設 Token（備用方案，如果不使用 OTP）
     * 
     * @param string $email 電子郵件
     * @return string|WP_Error Token 或錯誤
     */
    public static function generate_reset_token($email) {
        if (!is_email($email)) {
            return new WP_Error('invalid_email', '無效的電子郵件地址');
        }
        
        $user = get_user_by('email', $email);
        if (!$user) {
            return new WP_Error('user_not_found', '找不到此電子郵件的帳戶');
        }
        
        // 使用 WordPress 內建的密碼重設機制
        $key = get_password_reset_key($user);
        
        if (is_wp_error($key)) {
            return $key;
        }
        
        return $key;
    }
    
    /**
     * 驗證密碼重設 Token
     * 
     * @param string $email 電子郵件
     * @param string $key 重設 Token
     * @return WP_User|WP_Error 用戶對象或錯誤
     */
    public static function verify_reset_token($email, $key) {
        $user = get_user_by('email', $email);
        
        if (!$user) {
            return new WP_Error('user_not_found', '找不到此電子郵件的帳戶');
        }
        
        // 驗證 token
        $check = check_password_reset_key($key, $user->user_login);
        
        if (is_wp_error($check)) {
            return new WP_Error('invalid_token', '重設連結無效或已過期');
        }
        
        return $user;
    }
    
    /**
     * 清理過期的 OTP 記錄（定期任務）
     */
    public static function cleanup_expired_otps() {
        global $wpdb;
        
        $now = current_time('mysql');
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM " . self::$table_name . "
            WHERE expires_at < %s
            OR (verified = -1 AND created_at < DATE_SUB(%s, INTERVAL 1 DAY))",
            $now,
            $now
        ));
        
        if ($deleted !== false) {
            error_log("[Kayarine OTP] Cleaned up {$deleted} expired OTP records");
        }
        
        return $deleted;
    }
    
    /**
     * 檢查 Email 是否已驗證過 OTP（註冊流程用）
     * 
     * @param string $email 電子郵件
     * @param string $type OTP 類型
     * @return bool 是否已驗證
     */
    public static function is_verified($email, $type = self::TYPE_REGISTRATION) {
        global $wpdb;
        
        $verified = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$table_name . "
            WHERE email = %s
            AND otp_type = %s
            AND verified = 1
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $email,
            $type
        ));
        
        return $verified > 0;
    }
}

// 初始化
add_action('plugins_loaded', array('Kayarine_OTP', 'init'));

// 註冊定期清理任務
if (!wp_next_scheduled('kayarine_cleanup_expired_otps')) {
    wp_schedule_event(time(), 'daily', 'kayarine_cleanup_expired_otps');
}
add_action('kayarine_cleanup_expired_otps', array('Kayarine_OTP', 'cleanup_expired_otps'));
