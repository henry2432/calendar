<?php
/**
 * Kayarine Diagnostic Helper
 * 診斷工具：檢查 shortcode 是否已註冊
 * 使用方法：在 wp-config.php 中引入此文件，或創建一個管理員可訪問的頁面
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 診斷函數
function kayarine_diagnostic_check() {
    $diagnostics = array();

    // 1. 檢查 WooCommerce 是否激活
    $diagnostics['woocommerce_active'] = class_exists( 'WooCommerce' ) ? '✓' : '✗';

    // 2. 檢查 Kayarine_WooCommerce_Customizer 類是否存在
    $diagnostics['customizer_class_exists'] = class_exists( 'Kayarine_WooCommerce_Customizer' ) ? '✓' : '✗';

    // 3. 檢查 kayarine_account shortcode 是否已註冊
    $diagnostics['kayarine_account_shortcode_registered'] = shortcode_exists( 'kayarine_account' ) ? '✓' : '✗';

    // 4. 檢查 kayarine_login_register shortcode 是否已註冊
    $diagnostics['kayarine_login_register_shortcode_registered'] = shortcode_exists( 'kayarine_login_register' ) ? '✓' : '✗';

    // 5. 檢查已激活的插件
    $active_plugins = get_option( 'active_plugins', array() );
    $kayarine_booking_active = in_array( 'kayarine-booking/kayarine-booking.php', $active_plugins ) ? '✓' : '✗';
    $diagnostics['kayarine_booking_plugin_active'] = $kayarine_booking_active;

    // 6. 檢查文件是否存在
    $customizer_file = WP_PLUGIN_DIR . '/kayarine-booking/includes/class-kayarine-woocommerce-customizer.php';
    $diagnostics['customizer_file_exists'] = file_exists( $customizer_file ) ? '✓' : '✗';

    return $diagnostics;
}

// 在管理員頁面中顯示診斷信息
add_action( 'wp_footer', 'kayarine_show_diagnostic_footer', 999 );
function kayarine_show_diagnostic_footer() {
    if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['kayarine_debug'] ) ) {
        return;
    }

    $diagnostics = kayarine_diagnostic_check();
    echo '<div style="background: #f5f5f5; padding: 20px; margin: 20px; border: 1px solid #ddd; font-family: monospace; font-size: 12px;">';
    echo '<h3 style="margin-top: 0;">🔍 Kayarine Diagnostic Report</h3>';
    echo '<table style="width: 100%; border-collapse: collapse;">';

    foreach ( $diagnostics as $key => $value ) {
        $status_color = ( $value === '✓' ) ? '#4caf50' : '#f44336';
        echo '<tr style="border-bottom: 1px solid #ddd;">';
        echo '<td style="padding: 8px; width: 60%;">' . esc_html( str_replace( '_', ' ', ucfirst( $key ) ) ) . '</td>';
        echo '<td style="padding: 8px; color: ' . esc_attr( $status_color ) . '; font-weight: bold;">' . esc_html( $value ) . '</td>';
        echo '</tr>';
    }

    echo '</table>';
    echo '<p style="font-size: 11px; color: #666; margin: 10px 0 0 0;">';
    echo 'Debug Mode: Check with <code>?kayarine_debug=1</code> in URL<br>';
    echo 'WordPress Version: ' . esc_html( get_bloginfo( 'version' ) ) . '<br>';
    echo 'PHP Version: ' . esc_html( phpversion() ) . '<br>';
    echo 'Active Plugins Count: ' . count( get_option( 'active_plugins', array() ) ) . '';
    echo '</p>';
    echo '</div>';
}

// 檢查是否應該在管理員 AJAX 中運行診斷
add_action( 'wp_ajax_kayarine_diagnostics', 'kayarine_ajax_diagnostics' );
function kayarine_ajax_diagnostics() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permission denied' );
    }

    $diagnostics = kayarine_diagnostic_check();
    wp_send_json_success( $diagnostics );
}
