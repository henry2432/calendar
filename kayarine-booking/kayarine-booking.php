<?php
/**
 * Plugin Name: Kayarine Booking
 * Plugin URI: https://www.kayarine.com.hk
 * Description: Custom booking system for Kayarine (Single/Double Kayaks, SUP, Add-ons)
 * Version: 1.4.14
 * Author: Kayarine Dev Team
 * Text Domain: kayarine-booking
 */

if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

// Force OPCache refresh on plugin load
if ( function_exists( 'opcache_reset' ) && PHP_SAPI !== 'cli' ) {
	@opcache_reset();
}

// Define Constants
define( 'KAYARINE_BOOKING_VERSION', '1.4.14' );
define( 'KAYARINE_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'KAYARINE_BOOKING_URL', plugin_dir_url( __FILE__ ) );

// Include Main Classes
require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-booking.php';
require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-auth-integration.php';
require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-woocommerce-customizer.php';

// Initialize Plugin with comprehensive error handling
function kayarine_booking_init() {
	try {
		// Verify all dependencies exist before instantiating
		if ( ! class_exists( 'Kayarine_Booking' ) ) {
			error_log( '[Kayarine ' . KAYARINE_BOOKING_VERSION . '] FATAL: Kayarine_Booking class not found after require_once' );
			return;
		}
		
		$plugin = new Kayarine_Booking();
		
		if ( ! method_exists( $plugin, 'run' ) ) {
			error_log( '[Kayarine ' . KAYARINE_BOOKING_VERSION . '] FATAL: Kayarine_Booking::run() method not found' );
			return;
		}
		
		$plugin->run();
		
		// Ensure unified account page exists
		kayarine_ensure_unified_account_page();
		
		error_log( '[Kayarine ' . KAYARINE_BOOKING_VERSION . '] Plugin initialization successful' );
	} catch ( Exception $e ) {
		error_log( '[Kayarine ' . KAYARINE_BOOKING_VERSION . '] FATAL ERROR during initialization: ' . $e->getMessage() );
		error_log( '[Kayarine ' . KAYARINE_BOOKING_VERSION . '] Exception trace: ' . $e->getTraceAsString() );
	}
}
add_action( 'plugins_loaded', 'kayarine_booking_init', 1 );

/**
 * Ensure unified account page exists
 * ✅ 性能優化：使用 transient 緩存，避免每次都查詢資料庫
 */
function kayarine_ensure_unified_account_page() {
	$page_slug = 'account';
	
	// ✅ 檢查 transient 快取（24小時）
	$cache_key = 'kayarine_account_page_exists';
	$page_exists = get_transient( $cache_key );
	
	if ( $page_exists === 'yes' ) {
		return; // 頁面已檢查過，快速退出
	}
	
	// 檢查頁面是否存在
	global $wpdb;
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page' LIMIT 1",
			$page_slug
		)
	);
	
	if ( $existing ) {
		// ✅ 頁面存在，設置 transient（24小時）以避免重複查詢
		set_transient( $cache_key, 'yes', 24 * HOUR_IN_SECONDS );
		error_log( "[Kayarine] Account page found, cached for 24 hours" );
		return;
	}
	
	// 建立頁面（只執行一次）
	$page_data = array(
		'post_title'   => 'Kayarine 會員中心',
		'post_content' => '[kayarine_account]',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_name'    => $page_slug,
	);
	
	$page_id = wp_insert_post( $page_data );
	
	if ( $page_id && ! is_wp_error( $page_id ) ) {
		// ✅ 優化：使用 set_transient 標記而非 flush_rewrite_rules
		// flush_rewrite_rules() 是非常耗時的操作，應該避免在 plugins_loaded 時執行
		set_transient( $cache_key, 'yes', 24 * HOUR_IN_SECONDS );
		error_log( "[Kayarine] Account page created with ID: $page_id, cached for 24 hours" );
		
		// ✅ 註：rewrite rules 會在 WordPress 正常初始化時自動生成
		// 無需手動調用 flush_rewrite_rules()（會導致每次頁面加載都重寫 .htaccess，非常慢）
	} else {
		error_log( "[Kayarine] Failed to create account page: " . (is_wp_error($page_id) ? $page_id->get_error_message() : 'Unknown error') );
	}
}

// Internal Availability Check (AJAX)
// No longer proxies to Flask/Python. Now queries WP DB directly.
add_action( 'wp_ajax_kayarine_proxy_check', 'kayarine_check_stock_internal' );
add_action( 'wp_ajax_nopriv_kayarine_proxy_check', 'kayarine_check_stock_internal' );

function kayarine_check_stock_internal() {
    // ✅ Debug: Log all AJAX requests
    error_log( '[Kayarine AJAX] kayarine_proxy_check received - Method: ' . $_SERVER['REQUEST_METHOD'] . ' | IP: ' . sanitize_text_field($_SERVER['REMOTE_ADDR']) );
    error_log( '[Kayarine AJAX] Request data: ' . json_encode($_REQUEST) );
    
    $date = isset($_REQUEST['date']) ? sanitize_text_field($_REQUEST['date']) : '';
    if ( ! $date ) {
        error_log( '[Kayarine AJAX] ERROR: No date provided' );
        wp_send_json_error( array( 'message' => 'No date provided' ) );
    }

    try {
        error_log( '[Kayarine AJAX] Processing availability check for date: ' . $date );
        
        // Use new Inventory Class to check availability
        $availability = Kayarine_Inventory::get_availability( $date );
        
        error_log( '[Kayarine AJAX] Availability result: ' . json_encode($availability) );
        
        // Format response to match what the frontend JS expects
        // JS expects: { status: 'success', availability: { ID: { remaining: X, ... } } }
        $response = array(
            'status' => 'success',
            'date'   => $date,
            'availability' => $availability
        );

        error_log( '[Kayarine AJAX] Sending response: ' . json_encode($response) );
        wp_send_json( $response );

    } catch ( Exception $e ) {
        error_log( '[Kayarine AJAX] Exception: ' . $e->getMessage() );
        wp_send_json_error( array( 'message' => 'System Error: ' . $e->getMessage() ) );
    }
}
