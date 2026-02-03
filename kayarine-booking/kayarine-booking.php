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
 */
function kayarine_ensure_unified_account_page() {
	$page_slug = 'account';
	
	// Check if page already exists using direct query
	global $wpdb;
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page'",
			$page_slug
		)
	);
	
	if ( $existing ) {
		return; // Page already exists
	}
	
	// Create the unified account page using kayarine_account shortcode (which has proper AJAX handlers)
	$page_data = array(
		'post_title'   => 'Kayarine 會員中心',
		'post_content' => '[kayarine_account]',  // Use kayarine_account shortcode which has full AJAX implementation
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_name'    => $page_slug,
	);
	
	$page_id = wp_insert_post( $page_data );
	
	// Flush rewrite rules to ensure page is accessible
	if ( $page_id && ! is_wp_error( $page_id ) ) {
		flush_rewrite_rules( false );
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
