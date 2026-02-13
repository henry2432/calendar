<?php
/**
 * Kayarine Pricing API
 * 
 * 提供動態定價 REST API 端點供前端查詢價格
 * - 設備租借（Kayak, SUP）：平日/週末不同價格
 * - Tour/Course：直接使用 WooCommerce 產品標價（不分平日週末）
 * 
 * @package Kayarine_Booking
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Kayarine_Pricing_API {
    
    /**
     * 註冊 REST API 路由
     */
    public static function register_routes() {
        // 動態價格查詢端點
        register_rest_route('kayarine/v1', '/pricing/calculate', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'calculate_pricing'),
            'permission_callback' => '__return_true',
            'args' => array(
                'items' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_array($param) && !empty($param);
                    }
                ),
                'date' => array(
                    'required' => false,
                    'validate_callback' => function($param) {
                        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $param);
                    }
                )
            )
        ));
    }

    /**
     * 計算動態價格
     * 
     * POST /wp-json/kayarine/v1/pricing/calculate
     * Body: {
     *   "date": "2026-02-15",
     *   "items": [
     *     {"id": 6954, "quantity": 2},
     *     {"id": 6957, "quantity": 1}
     *   ]
     * }
     */
    public static function calculate_pricing($request) {
        $date = $request->get_param('date');
        $items = $request->get_param('items');
        
        $result = array();
        $total = 0;
        
        // 設備租借產品 ID（有平日/週末價格差異）
        $rental_products = array(
            Kayarine_Config::ID_SINGLE_KAYAK,
            Kayarine_Config::ID_DOUBLE_KAYAK,
            Kayarine_Config::ID_FAMILY_KAYAK,
            Kayarine_Config::ID_SUP,
            Kayarine_Config::ID_SNORKEL_RENT,
        );
        
        foreach ($items as $item) {
            $product_id = intval($item['id']);
            $quantity = intval($item['quantity']);
            
            // 獲取產品
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }
            
            // 判斷是否為租借設備（需要動態定價）
            $is_rental = in_array($product_id, $rental_products);
            
            if ($is_rental && $date) {
                // 設備租借：使用動態定價（平日/週末）
                $is_weekend = Kayarine_Config::is_high_season($date);
                $price = Kayarine_Config::get_price($product_id, $date);
            } else {
                // Tour/Course/Add-ons：使用 WooCommerce 標價
                $price = (float) $product->get_price();
            }
            
            $subtotal = $price * $quantity;
            $total += $subtotal;
            
            $result[] = array(
                'id' => $product_id,
                'name' => $product->get_name(),
                'quantity' => $quantity,
                'unit_price' => $price,
                'subtotal' => $subtotal,
                'is_dynamic_pricing' => $is_rental && $date ? true : false,
                'price_type' => $is_rental && $date ? ($is_weekend ? 'weekend' : 'weekday') : 'standard'
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'date' => $date,
            'items' => $result,
            'total' => $total
        ));
    }
}

// 註冊 REST API 路由
add_action('rest_api_init', array('Kayarine_Pricing_API', 'register_routes'));
