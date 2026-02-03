-- ==========================================
-- Kayarine 庫存問題 Debug SQL 查詢
-- ==========================================

-- 1. 檢查最近的訂單及其元數據
SELECT 
    o.ID as order_id,
    o.post_status,
    o.post_date,
    GROUP_CONCAT(DISTINCT oi.order_item_id) as item_ids
FROM wp_posts o
LEFT JOIN wp_woocommerce_order_items oi ON o.ID = oi.order_id
WHERE o.post_type = 'shop_order'
ORDER BY o.post_date DESC
LIMIT 5;

-- 2. 檢查最近5個訂單的元數據
SELECT 
    oi.order_item_id,
    oi.order_id,
    oim.meta_key,
    oim.meta_value
FROM wp_woocommerce_order_items oi
LEFT JOIN wp_woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
WHERE oi.order_id IN (
    SELECT ID FROM wp_posts 
    WHERE post_type = 'shop_order' 
    ORDER BY post_date DESC 
    LIMIT 5
)
ORDER BY oi.order_id DESC, oi.order_item_id, oim.meta_key;

-- 3. 專門查詢 kayarine_booking_date 元數據
SELECT 
    oi.order_item_id,
    oi.order_id,
    oi.order_item_name,
    oim.meta_key,
    oim.meta_value,
    o.post_status,
    o.post_date
FROM wp_woocommerce_order_items oi
JOIN wp_woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
JOIN wp_posts o ON oi.order_id = o.ID
WHERE oim.meta_key = 'kayarine_booking_date'
AND o.post_type = 'shop_order'
ORDER BY o.post_date DESC
LIMIT 20;

-- 4. 檢查特定日期 2026-03-11 的庫存計算結果
-- 這應該匹配 get_daily_usage('2026-03-11') 的SQL
SELECT
    item_meta_product.meta_value as product_id,
    item_meta_product.order_item_id,
    order_item_meta_qty.meta_value as qty,
    orders.post_status,
    orders.ID as order_id
FROM wp_woocommerce_order_itemmeta as item_meta_date

INNER JOIN wp_woocommerce_order_items as items
    ON item_meta_date.order_item_id = items.order_item_id

INNER JOIN wp_posts as orders
    ON items.order_id = orders.ID

INNER JOIN wp_woocommerce_order_itemmeta as item_meta_product
    ON items.order_item_id = item_meta_product.order_item_id
    AND item_meta_product.meta_key = '_product_id'

INNER JOIN wp_woocommerce_order_itemmeta as order_item_meta_qty
    ON items.order_item_id = order_item_meta_qty.order_item_id
    AND order_item_meta_qty.meta_key = '_qty'

WHERE
    item_meta_date.meta_key = 'kayarine_booking_date'
    AND item_meta_date.meta_value = '2026-03-11'
    AND orders.post_type = 'shop_order'
    AND orders.post_status IN ('wc-pending', 'wc-processing', 'wc-completed', 'wc-on-hold')

GROUP BY product_id;

-- 5. 檢查所有具有 kayarine_booking_date 的訂單狀態分佈
SELECT 
    COUNT(DISTINCT oi.order_id) as order_count,
    o.post_status,
    COUNT(oim2.order_item_id) as kayarine_items
FROM wp_woocommerce_order_items oi
JOIN wp_woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
JOIN wp_posts o ON oi.order_id = o.ID
LEFT JOIN wp_woocommerce_order_itemmeta oim2 ON oi.order_item_id = oim2.order_item_id 
    AND oim2.meta_key = 'kayarine_booking_date'
WHERE oim.meta_key = 'kayarine_booking_date'
AND o.post_type = 'shop_order'
GROUP BY o.post_status;
