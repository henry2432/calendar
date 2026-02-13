<?php
/**
 * Kayarine REST API Handler
 * 
 * 提供庫存查詢、訂單創建等 REST API 端點供 Next.js 前端使用
 * 利用現有的 Kayarine_Inventory 快取機制確保性能
 * 
 * @package Kayarine_Booking
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Kayarine_REST_API {
    
    /**
     * 添加 CORS 頭以支持前端跨域請求
     */
    private static function add_cors_headers() {
        // 允許的來源（生產環境應該限制為特定域名）
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
        header('Access-Control-Max-Age: 86400'); // 24小時
    }
    
    /**
     * 處理 OPTIONS 預檢請求
     */
    public static function handle_preflight() {
        self::add_cors_headers();
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            status_header(200);
            exit();
        }
    }
    
    /**
     * 註冊 REST API 路由
     */
    public static function register_routes() {
        // 為所有 REST API 請求添加 CORS 頭
        add_action('rest_api_init', function() {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', function($served, $result, $request, $server) {
                self::add_cors_headers();
                return $served;
            }, 10, 4);
        });
        // 庫存可用性查詢端點
        register_rest_route('kayarine/v1', '/inventory/availability', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_availability'),
            'permission_callback' => '__return_true', // 公開端點
            'args' => array(
                'date' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    }
                ),
                'product_ids' => array(
                    'required' => false,
                    'sanitize_callback' => function($param) {
                        return array_map('intval', explode(',', $param));
                    }
                )
            )
        ));

        // 批量日期庫存查詢（用於日曆顯示）
        register_rest_route('kayarine/v1', '/inventory/batch', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'get_batch_availability'),
            'permission_callback' => '__return_true',
            'args' => array(
                'dates' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_array($param) && count($param) <= 62; // 最多 2 個月
                    }
                ),
                'product_id' => array(
                    'required' => false,
                    'sanitize_callback' => 'absint'
                )
            )
        ));

        // 獲取黑名單日期端點
        register_rest_route('kayarine/v1', '/blackout-dates', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_blackout_dates'),
            'permission_callback' => '__return_true',
        ));

        // 創建訂單端點（結帳時使用）
        register_rest_route('kayarine/v1', '/orders/create', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'create_order'),
            'permission_callback' => '__return_true', // 允許已登入和訪客用戶
            'args' => array(
                'customer_email' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_email'
                ),
                'customer_phone' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'items' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_array($param) && !empty($param);
                    }
                ),
                'payment_method' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Guest 訂單查詢端點（通過 order_key）
        register_rest_route('kayarine/v1', '/orders/guest/(?P<order_key>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_guest_order'),
            'permission_callback' => '__return_true', // 公開端點，通過 order_key 驗證
            'args' => array(
                'order_key' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        // Guest 訂單改期端點（通過 order_key）
        register_rest_route('kayarine/v1', '/orders/guest/(?P<order_key>[a-zA-Z0-9_-]+)/reschedule', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'reschedule_guest_order'),
            'permission_callback' => '__return_true', // 公開端點，通過 order_key 驗證
            'args' => array(
                'order_key' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'new_date' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    }
                )
            )
        ));

        // Guest 訂單取消端點（通過 order_key）
        register_rest_route('kayarine/v1', '/orders/guest/(?P<order_key>[a-zA-Z0-9_-]+)/cancel', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'cancel_guest_order'),
            'permission_callback' => '__return_true', // 公開端點，通過 order_key 驗證
            'args' => array(
                'order_key' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
    }

    /**
     * 獲取單日庫存可用性
     * 
     * GET /wp-json/kayarine/v1/inventory/availability?date=2026-02-15&product_ids=123,456
     */
    public static function get_availability($request) {
        $date = $request->get_param('date');
        $product_ids = $request->get_param('product_ids');

        // 使用 Kayarine_Inventory 的快取查詢
        $availability = Kayarine_Inventory::get_availability($date);

        // 如果指定了產品 ID，只返回這些產品
        if (!empty($product_ids) && is_array($product_ids)) {
            $availability = array_intersect_key($availability, array_flip($product_ids));
        }

        return rest_ensure_response(array(
            'success' => true,
            'date' => $date,
            'data' => $availability,
            'cached' => true // 使用了 Kayarine_Inventory 的快取
        ));
    }

    /**
     * 批量獲取多日庫存（用於日曆）
     * 
     * POST /wp-json/kayarine/v1/inventory/batch
     * Body: { "dates": ["2026-02-15", "2026-02-16", ...], "product_id": 123 }
     */
    public static function get_batch_availability($request) {
        $dates = $request->get_param('dates');
        $product_id = $request->get_param('product_id');

        $result = array();

        foreach ($dates as $date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue; // 跳過無效日期
            }

            $availability = Kayarine_Inventory::get_availability($date);

            if ($product_id) {
                // 只返回特定產品的庫存狀態
                if (isset($availability[$product_id])) {
                    $result[$date] = array(
                        'available' => $availability[$product_id]['remaining'] > 0,
                        'remaining' => $availability[$product_id]['remaining'],
                        'limit' => $availability[$product_id]['limit'],
                        'used' => $availability[$product_id]['used']
                    );
                } else {
                    $result[$date] = array(
                        'available' => false,
                        'remaining' => 0
                    );
                }
            } else {
                // 返回所有產品的綜合狀態
                $has_availability = false;
                foreach ($availability as $prod_data) {
                    if ($prod_data['remaining'] > 0) {
                        $has_availability = true;
                        break;
                    }
                }
                $result[$date] = array(
                    'available' => $has_availability,
                    'products' => count($availability)
                );
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => $result,
            'count' => count($result)
        ));
    }

    /**
     * 創建訂單（從 Next.js 前端提交）
     * 
     * POST /wp-json/kayarine/v1/orders/create
     */
    public static function create_order($request) {
        $email = $request->get_param('customer_email');
        $phone = $request->get_param('customer_phone');
        $items = $request->get_param('items');
        $payment_method = $request->get_param('payment_method');

        // 檢查當前用戶是否已登入
        $current_user_id = get_current_user_id();
        
        // 詳細日誌記錄
        error_log('========== 訂單創建請求開始 ==========');
        error_log('當前用戶 ID: ' . ($current_user_id > 0 ? $current_user_id : '訪客'));
        error_log('Email: ' . $email);
        error_log('Phone: ' . $phone);
        error_log('Payment Method: ' . $payment_method);
        error_log('Items: ' . json_encode($items));

        try {
            // 檢查 WooCommerce 是否可用
            if (!function_exists('wc_create_order')) {
                error_log('❌ WooCommerce 函數不存在');
                return new WP_Error(
                    'woocommerce_not_available',
                    'WooCommerce 未正確加載',
                    array('status' => 500)
                );
            }

            // 驗證必要參數
            if (empty($email) || empty($phone) || empty($items)) {
                error_log('❌ 缺少必要參數');
                return new WP_Error(
                    'missing_parameters',
                    '缺少必要的訂單資訊',
                    array('status' => 400)
                );
            }

            // 驗證庫存
            $booking_date = null;
            foreach ($items as $item) {
                // 檢查產品是否存在
                $product = wc_get_product($item['id']);
                if (!$product) {
                    error_log('❌ 產品不存在: ID ' . $item['id']);
                    return new WP_Error(
                        'product_not_found',
                        '產品 ' . ($item['name'] ?? $item['id']) . ' 不存在',
                        array('status' => 400)
                    );
                }

                if (isset($item['bookingDate'])) {
                    $booking_date = $item['bookingDate'];
                    
                    // 檢查日期是否被黑名單
                    if (Kayarine_Inventory::is_blackout($booking_date, $item['id'])) {
                        error_log('❌ 日期被黑名單: ' . $booking_date);
                        return new WP_Error(
                            'blackout_date',
                            '所選日期不可預訂',
                            array('status' => 400)
                        );
                    }

                    // 檢查庫存
                    $availability = Kayarine_Inventory::get_availability($booking_date);
                    if (!isset($availability[$item['id']]) ||
                        $availability[$item['id']]['remaining'] < $item['quantity']) {
                        error_log('❌ 庫存不足: ' . $item['name']);
                        return new WP_Error(
                            'insufficient_inventory',
                            $item['name'] . ' 庫存不足',
                            array('status' => 400)
                        );
                    }
                }
            }

            error_log('✅ 驗證通過，開始創建訂單');

            // 創建 WooCommerce 訂單，如果用戶已登入則關聯用戶
            $order_args = array();
            if ($current_user_id > 0) {
                $order_args['customer_id'] = $current_user_id;
                error_log('✅ 訂單將關聯到用戶 ID: ' . $current_user_id);
            } else {
                error_log('ℹ️ 訂單為訪客訂單');
            }
            
            $order = wc_create_order($order_args);
            
            if (!$order || is_wp_error($order)) {
                error_log('❌ 訂單創建失敗: ' . ($order instanceof WP_Error ? $order->get_error_message() : 'Unknown error'));
                return new WP_Error(
                    'order_creation_failed',
                    '無法創建訂單對象',
                    array('status' => 500)
                );
            }

            error_log('✅ 訂單對象已創建: ' . $order->get_id());
            
            // 設置帳單資訊
            $order->set_billing_email($email);
            $order->set_billing_phone($phone);
            
            // 如果是已登入用戶，從用戶資料獲取更多信息
            if ($current_user_id > 0) {
                $user = get_userdata($current_user_id);
                if ($user) {
                    $order->set_billing_first_name($user->first_name);
                    $order->set_billing_last_name($user->last_name);
                    error_log('✅ 已設置用戶姓名: ' . $user->first_name . ' ' . $user->last_name);
                }
            }

            // 添加訂單項目
            foreach ($items as $item) {
                $product = wc_get_product($item['id']);
                if (!$product) continue;

                $order_item_id = $order->add_product($product, $item['quantity']);
                
                // 添加預訂日期元數據
                if (isset($item['bookingDate']) && $order_item_id) {
                    wc_add_order_item_meta($order_item_id, '_kayarine_booking_date', $item['bookingDate']);
                }
            }

            // 計算總額
            $order->calculate_totals();

            // 設置付款方式
            $order->set_payment_method($payment_method === 'fps' ? 'fps' : 'stripe');
            
            // 設置訂單狀態為 pending（等待付款確認）
            $order->set_status('pending');
            
            // 記錄待處理庫存
            if ($booking_date) {
                $order_id = $order->get_id();
                foreach ($items as $item) {
                    if (isset($item['bookingDate'])) {
                        Kayarine_Inventory::record_pending_usage(
                            $order_id,
                            $item['bookingDate'],
                            $item['id'],
                            $item['quantity']
                        );
                    }
                }
            }

            $order->save();
            
            error_log('✅ 訂單已保存');
            error_log('訂單 ID: ' . $order->get_id());
            error_log('訂單編號: ' . $order->get_order_number());
            error_log('訂單狀態: ' . $order->get_status());
            error_log('訂單總額: ' . $order->get_total());
            error_log('========== 訂單創建成功 ==========');

            return rest_ensure_response(array(
                'success' => true,
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'order_key' => $order->get_order_key(),
                'total' => $order->get_total(),
                'status' => $order->get_status()
            ));

        } catch (Exception $e) {
            error_log('❌ 訂單創建異常: ' . $e->getMessage());
            error_log('異常堆棧: ' . $e->getTraceAsString());
            error_log('========== 訂單創建失敗 ==========');
            
            return new WP_Error(
                'order_creation_failed',
                '訂單創建失敗: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * 獲取黑名單日期列表
     *
     * GET /wp-json/kayarine/v1/blackout-dates
     * 返回所有被黑名單的日期（解析日期範圍）
     */
    public static function get_blackout_dates($request) {
        try {
            $blackout_rules = Kayarine_Inventory::get_blackout_dates();
            $blackout_dates = array();
            
            foreach ($blackout_rules as $rule) {
                $parts = explode('|', $rule);
                $date_part = trim($parts[0]);
                
                // 檢查是否為日期範圍（包含 "to" 或 "~"）
                if (strpos($date_part, ' to ') !== false || strpos($date_part, '~') !== false) {
                    // 日期範圍格式: "2026-01-20 to 2026-02-27" 或 "2026-01-20~2026-02-27"
                    $separator = strpos($date_part, ' to ') !== false ? ' to ' : '~';
                    $range = explode($separator, $date_part);
                    
                    if (count($range) === 2) {
                        $start = strtotime(trim($range[0]));
                        $end = strtotime(trim($range[1]));
                        
                        if ($start && $end) {
                            $current = $start;
                            while ($current <= $end) {
                                $blackout_dates[] = date('Y-m-d', $current);
                                $current = strtotime('+1 day', $current);
                            }
                        }
                    }
                } else {
                    // 單一日期
                    $timestamp = strtotime($date_part);
                    if ($timestamp) {
                        $blackout_dates[] = date('Y-m-d', $timestamp);
                    }
                }
            }
            
            // 去重並排序
            $blackout_dates = array_unique($blackout_dates);
            sort($blackout_dates);
            
            return rest_ensure_response(array(
                'success' => true,
                'dates' => $blackout_dates,
                'count' => count($blackout_dates)
            ));
            
        } catch (Exception $e) {
            return new WP_Error(
                'blackout_fetch_failed',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * 獲取 Guest 訂單詳情（通過 order_key）
     *
     * GET /wp-json/kayarine/v1/orders/guest/{order_key}
     */
    public static function get_guest_order($request) {
        $order_key = $request->get_param('order_key');
        
        error_log('========== Guest 訂單查詢 ==========');
        error_log('Order Key: ' . $order_key);

        try {
            // 通過 order_key 查找訂單
            $order_id = wc_get_order_id_by_order_key($order_key);
            
            if (!$order_id) {
                error_log('❌ 訂單不存在');
                return new WP_Error(
                    'order_not_found',
                    '找不到訂單',
                    array('status' => 404)
                );
            }

            $order = wc_get_order($order_id);
            
            if (!$order) {
                error_log('❌ 無法載入訂單');
                return new WP_Error(
                    'order_load_failed',
                    '無法載入訂單',
                    array('status' => 500)
                );
            }

            // 驗證這是訪客訂單（沒有關聯用戶 ID）
            if ($order->get_customer_id() > 0) {
                error_log('⚠️ 這不是訪客訂單');
                return new WP_Error(
                    'not_guest_order',
                    '此訂單需要登入查看',
                    array('status' => 403)
                );
            }

            // 提取訂單項目和預訂日期
            $items = array();
            $booking_dates = array();
            
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                $booking_date = $item->get_meta('_kayarine_booking_date');
                
                $items[] = array(
                    'id' => $item->get_product_id(),
                    'name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'price' => $item->get_total(),
                    'booking_date' => $booking_date ?: null,
                    'image' => $product ? wp_get_attachment_url($product->get_image_id()) : null
                );
                
                if ($booking_date) {
                    $booking_dates[] = $booking_date;
                }
            }

            error_log('✅ 訂單查詢成功');
            
            return rest_ensure_response(array(
                'success' => true,
                'order' => array(
                    'id' => $order->get_id(),
                    'order_number' => $order->get_order_number(),
                    'order_key' => $order->get_order_key(),
                    'status' => $order->get_status(),
                    'total' => $order->get_total(),
                    'currency' => $order->get_currency(),
                    'payment_method' => $order->get_payment_method_title(),
                    'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
                    'billing_email' => $order->get_billing_email(),
                    'billing_phone' => $order->get_billing_phone(),
                    'billing_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                    'items' => $items,
                    'booking_dates' => array_unique($booking_dates),
                    'can_reschedule' => in_array($order->get_status(), array('pending', 'processing', 'on-hold')) && !empty($booking_dates),
                    'can_cancel' => in_array($order->get_status(), array('pending', 'processing', 'on-hold'))
                )
            ));

        } catch (Exception $e) {
            error_log('❌ 查詢異常: ' . $e->getMessage());
            return new WP_Error(
                'query_failed',
                '查詢訂單失敗',
                array('status' => 500)
            );
        }
    }

    /**
     * Guest 訂單改期（通過 order_key）
     *
     * POST /wp-json/kayarine/v1/orders/guest/{order_key}/reschedule
     */
    public static function reschedule_guest_order($request) {
        $order_key = $request->get_param('order_key');
        $new_date = $request->get_param('new_date');
        
        error_log('========== Guest 訂單改期 ==========');
        error_log('Order Key: ' . $order_key);
        error_log('New Date: ' . $new_date);

        try {
            // 通過 order_key 查找訂單
            $order_id = wc_get_order_id_by_order_key($order_key);
            
            if (!$order_id) {
                return new WP_Error('order_not_found', '找不到訂單', array('status' => 404));
            }

            $order = wc_get_order($order_id);
            
            if (!$order) {
                return new WP_Error('order_load_failed', '無法載入訂單', array('status' => 500));
            }

            // 驗證這是訪客訂單
            if ($order->get_customer_id() > 0) {
                return new WP_Error('not_guest_order', '此訂單需要登入管理', array('status' => 403));
            }

            // 檢查訂單狀態是否允許改期
            if (!in_array($order->get_status(), array('pending', 'processing', 'on-hold'))) {
                return new WP_Error('cannot_reschedule', '訂單狀態不允許改期', array('status' => 400));
            }

            // 檢查新日期是否被黑名單
            $items_updated = 0;
            
            foreach ($order->get_items() as $item_id => $item) {
                $old_date = $item->get_meta('_kayarine_booking_date');
                
                if (!$old_date) {
                    continue; // 跳過沒有預訂日期的項目
                }

                $product_id = $item->get_product_id();
                
                // 檢查黑名單
                if (class_exists('Kayarine_Inventory') && Kayarine_Inventory::is_blackout($new_date, $product_id)) {
                    error_log('❌ 日期被黑名單: ' . $new_date);
                    return new WP_Error('blackout_date', '所選日期不可預訂', array('status' => 400));
                }

                // 檢查庫存
                $availability = Kayarine_Inventory::get_availability($new_date);
                $quantity = $item->get_quantity();
                
                if (!isset($availability[$product_id]) || $availability[$product_id]['remaining'] < $quantity) {
                    error_log('❌ 庫存不足: ' . $item->get_name());
                    return new WP_Error('insufficient_inventory', '所選日期庫存不足', array('status' => 400));
                }

                // 清除舊日期的庫存記錄
                if (class_exists('Kayarine_Inventory')) {
                    Kayarine_Inventory::clear_pending_usage($order_id, $old_date, $product_id);
                }

                // 更新預訂日期
                $item->update_meta_data('_kayarine_booking_date', $new_date);
                $item->update_meta_data('_kayarine_rescheduled_at', current_time('mysql'));
                $item->update_meta_data('_kayarine_rescheduled_from', $old_date);
                $item->save();

                // 記錄新日期的庫存使用
                if (class_exists('Kayarine_Inventory')) {
                    Kayarine_Inventory::record_pending_usage($order_id, $new_date, $product_id, $quantity);
                }

                $items_updated++;
                
                error_log('✅ 項目已改期: ' . $item->get_name() . ' (' . $old_date . ' -> ' . $new_date . ')');
            }

            if ($items_updated === 0) {
                return new WP_Error('no_booking_items', '訂單中沒有可改期的項目', array('status' => 400));
            }

            // 添加訂單備註
            $order->add_order_note('預約日期已改期至 ' . $new_date . ' (訪客改期)');
            $order->save();

            error_log('✅ 改期成功，已更新 ' . $items_updated . ' 個項目');
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => '預約已成功改期',
                'new_date' => $new_date,
                'items_updated' => $items_updated
            ));

        } catch (Exception $e) {
            error_log('❌ 改期異常: ' . $e->getMessage());
            return new WP_Error('reschedule_failed', '改期失敗: ' . $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * Guest 訂單取消（通過 order_key）
     *
     * POST /wp-json/kayarine/v1/orders/guest/{order_key}/cancel
     */
    public static function cancel_guest_order($request) {
        $order_key = $request->get_param('order_key');
        
        error_log('========== Guest 訂單取消 ==========');
        error_log('Order Key: ' . $order_key);

        try {
            // 通過 order_key 查找訂單
            $order_id = wc_get_order_id_by_order_key($order_key);
            
            if (!$order_id) {
                return new WP_Error('order_not_found', '找不到訂單', array('status' => 404));
            }

            $order = wc_get_order($order_id);
            
            if (!$order) {
                return new WP_Error('order_load_failed', '無法載入訂單', array('status' => 500));
            }

            // 驗證這是訪客訂單
            if ($order->get_customer_id() > 0) {
                return new WP_Error('not_guest_order', '此訂單需要登入管理', array('status' => 403));
            }

            // 檢查訂單狀態是否允許取消
            if (!in_array($order->get_status(), array('pending', 'processing', 'on-hold'))) {
                return new WP_Error('cannot_cancel', '訂單狀態不允許取消', array('status' => 400));
            }

            // 清除庫存記錄
            foreach ($order->get_items() as $item_id => $item) {
                $booking_date = $item->get_meta('_kayarine_booking_date');
                
                if ($booking_date && class_exists('Kayarine_Inventory')) {
                    Kayarine_Inventory::clear_pending_usage(
                        $order_id,
                        $booking_date,
                        $item->get_product_id()
                    );
                }
            }

            // 更新訂單狀態為取消
            $order->update_status('cancelled', '訂單已被訪客取消');
            $order->save();

            error_log('✅ 訂單已取消');
            
            return rest_ensure_response(array(
                'success' => true,
                'message' => '訂單已成功取消'
            ));

        } catch (Exception $e) {
            error_log('❌ 取消異常: ' . $e->getMessage());
            return new WP_Error('cancel_failed', '取消訂單失敗: ' . $e->getMessage(), array('status' => 500));
        }
    }
}

// 註冊 REST API 路由
add_action('rest_api_init', array('Kayarine_REST_API', 'register_routes'));
