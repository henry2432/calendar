<?php
/**
 * 檢查 on-hold 訂單和 pending usage 情況
 */

define('WP_USE_THEMES', false);
require('/opt/bitnami/wordpress/wp-load.php');

global $wpdb;

// 查詢 pending 和 on-hold 訂單
$orders = $wpdb->get_results("
    SELECT ID, post_date, post_status 
    FROM {$wpdb->posts} 
    WHERE post_type = 'shop_order' 
    AND post_status IN ('pending', 'on-hold')
    ORDER BY post_date DESC 
    LIMIT 30
");

echo "On-Hold / Pending 訂單：\n";
echo "=====================================\n";

if (empty($orders)) {
    echo "沒有 on-hold 或 pending 訂單\n";
} else {
    foreach ($orders as $order_post) {
        $order = wc_get_order($order_post->ID);
        if (!$order) continue;
        
        echo sprintf("Order #%d | Status: %s | Date: %s\n", 
            $order->get_id(),
            $order->get_status(),
            $order_post->post_date
        );
        
        foreach ($order->get_items() as $item) {
            $booking_date = $item->get_meta('_kayarine_booking_date');
            echo sprintf("  - Product %d (Qty: %d) | Booking Date: %s\n",
                $item->get_product_id(),
                $item->get_quantity(),
                $booking_date ? $booking_date : '[無]'
            );
        }
        echo "\n";
    }
}

// 檢查 kayarine_pending_usage
echo "kayarine_pending_usage 內容：\n";
echo "=====================================\n";
$pending_usage = get_option('kayarine_pending_usage', array());
if (empty($pending_usage)) {
    echo "[空 - 完全沒有待處理庫存記錄]\n";
} else {
    echo json_encode($pending_usage, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// 統計
$total_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order'");
$hold_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status = 'on-hold'");
$pending_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status = 'pending'");

echo "\n統計：\n";
echo "=====================================\n";
echo "總訂單數: " . $total_orders . "\n";
echo "On-Hold 訂單: " . $hold_orders . "\n";
echo "Pending 訂單: " . $pending_orders . "\n";
?>
