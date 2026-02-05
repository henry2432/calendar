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

        // 創建訂單端點（結帳時使用）
        register_rest_route('kayarine/v1', '/orders/create', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'create_order'),
            'permission_callback' => '__return_true', // 後續可加上 nonce 驗證
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

        try {
            // 驗證庫存
            $booking_date = null;
            foreach ($items as $item) {
                if (isset($item['bookingDate'])) {
                    $booking_date = $item['bookingDate'];
                    
                    // 檢查日期是否被黑名單
                    if (Kayarine_Inventory::is_blackout($booking_date, $item['id'])) {
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
                        return new WP_Error(
                            'insufficient_inventory',
                            $item['name'] . ' 庫存不足',
                            array('status' => 400)
                        );
                    }
                }
            }

            // 創建 WooCommerce 訂單
            $order = wc_create_order();
            
            // 設置帳單資訊
            $order->set_billing_email($email);
            $order->set_billing_phone($phone);

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

            return rest_ensure_response(array(
                'success' => true,
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'order_key' => $order->get_order_key(),
                'total' => $order->get_total(),
                'status' => $order->get_status()
            ));

        } catch (Exception $e) {
            return new WP_Error(
                'order_creation_failed',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }
}

// 註冊 REST API 路由
add_action('rest_api_init', array('Kayarine_REST_API', 'register_routes'));
