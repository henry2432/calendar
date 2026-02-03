<?php
/**
 * 檢查舊訂單是否有 pending usage 記錄
 */

define('WP_USE_THEMES', false);
require('/opt/bitnami/wordpress/wp-load.php');

$args = array(
    'post_type' => 'shop_order',
    'posts_per_page' => 20,
    'orderby' => 'date',
    'order' => 'DESC'
);

$orders = get_posts($args);

echo "最近 20 筆訂單：\n";
echo "=====================================\n";

foreach ($orders as $order_post) {
    $order = wc_get_order($order_post->ID);
    echo sprintf("Order #%d | Status: %s | Date: %s\n", 
        $order->get_id(),
        $order->get_status(),
        $order_post->post_date
    );
    
    foreach ($order->get_items() as $item) {
        echo sprintf("  - Product %d (Qty: %d)\n",
            $item->get_product_id(),
            $item->get_quantity()
        );
    }
    
    echo "\n";
}

// 檢查 kayarine_pending_usage 選項
echo "kayarine_pending_usage 內容：\n";
echo "=====================================\n";
$pending_usage = get_option('kayarine_pending_usage', array());
echo json_encode($pending_usage, JSON_PRETTY_PRINT) . "\n";
?>
