<?php
/**
 * Cart Manager
 * Handles validation, adding items to cart, and storing booking data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Cart_Manager {

    public function __construct() {
        // Validate before adding to cart
        add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 3 );
        
        // Add custom data to cart item
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
        
        // Display custom data in cart
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );

        // Add "Add-on Products" to cart simultaneously
        add_action( 'woocommerce_add_to_cart', array( $this, 'add_addons_to_cart' ), 10, 6 );
        
        // Optimize Cart Update Logic to prevent recursion
         add_action( 'woocommerce_check_cart_items', array( $this, 'validate_cart_inventory' ) );
         
         // ✅ NEW: Save booking_date to order item meta when order line item is created
         // This is BEFORE woocommerce_checkout_order_processed, so we have access to cart
         add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_booking_date_to_order_item' ), 10, 4 );
         
         // ✅ FIX: Use woocommerce_checkout_order_processed
         // At this point, order is fully saved with real Order ID and Item IDs
         // Unlike woocommerce_checkout_create_order_line_item where IDs are 0
         add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_order_booking_meta_after_checkout' ), 10, 3 );
     }

    /**
     * Optimize Inventory Check (Batch Validation)
     * Checks all items in cart against inventory limit.
     * Removes items if over limit to prevent "Zombie Stock" in session.
     */
    public function validate_cart_inventory() {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        $cart = WC()->cart->get_cart();
        $date_usage = array(); // date => product_id => count

        // 1. Sum up current cart contents
        foreach ( $cart as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['kayarine_booking_date'] ) ) {
                $date = $cart_item['kayarine_booking_date'];
                $pid = $cart_item['product_id'];
                $qty = $cart_item['quantity'];

                if ( ! isset( $date_usage[$date] ) ) $date_usage[$date] = array();
                if ( ! isset( $date_usage[$date][$pid] ) ) $date_usage[$date][$pid] = 0;
                
                $date_usage[$date][$pid] += $qty;
            }
        }

        // 2. Check against Limits + DB Usage
        // We need to fetch DB usage ONCE per date involved to avoid query explosion.
        $limits = Kayarine_Inventory::get_limits();
        
        foreach ( $date_usage as $date => $products ) {
            // Get already booked qty from DB (processing/completed)
            $db_usage = Kayarine_Inventory::get_daily_usage( $date );

            foreach ( $products as $pid => $cart_qty ) {
                $limit = isset($limits[$pid]) ? $limits[$pid] : 0;
                $db_qty = isset($db_usage[$pid]) ? $db_usage[$pid] : 0;
                
                // Total needed = Cart + DB
                if ( ($cart_qty + $db_qty) > $limit ) {
                    // Over limit!
                    // Calculate how many we can keep
                    $allowed_in_cart = max(0, $limit - $db_qty);
                    
                    if ( $allowed_in_cart < $cart_qty ) {
                        // We need to reduce or remove items.
                        // Strategy: Remove items until we fit, starting from current cart item context?
                        // Simple strategy: Trigger Error and let user fix, OR auto-adjust.
                        // Auto-adjusting inside this loop is dangerous (recursion).
                        // Best practice: Add Error Notice and maybe remove the *last added* item?
                        
                        // For stability: Just show error. Do NOT modify cart inside this loop violently.
                        wc_add_notice( sprintf(
                            'Inventory Error: Product %s on %s is overbooked (Max: %d, Booked: %d, Your Cart: %d). Please reduce quantity.',
                            get_the_title($pid), $date, $limit, $db_qty, $cart_qty
                        ), 'error' );
                    }
                }
            }
        }
    }

    /**
     * Step 1: Validation
     */
    public function validate_add_to_cart( $passed, $product_id, $quantity ) {
        // Only validate if it's one of our bookable products
        if ( ! isset( Kayarine_Config::$product_rules[$product_id] ) ) {
            return $passed;
        }

        if ( empty( $_POST['kayarine_booking_date'] ) ) {
            wc_add_notice( '請選擇預訂日期 Please select a booking date.', 'error' );
            return false;
        }

        // Additional Logic: Check if date is blocked (Server-side Double Check)
        $date = sanitize_text_field( $_POST['kayarine_booking_date'] );
        if ( Kayarine_Config::is_blackout($date) ) {
            wc_add_notice( '所選日期不可預訂 Selected date is unavailable.', 'error' );
            return false;
        }

        return $passed;
    }

    /**
     * Step 2: Store Data (Date) in the Main Product's Cart Item
     */
    public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $_POST['kayarine_booking_date'] ) ) {
            $cart_item_data['kayarine_booking_date'] = sanitize_text_field( $_POST['kayarine_booking_date'] );
            
            // Generate a unique ID for this booking group to link main item + add-ons
            $cart_item_data['kayarine_booking_group'] = uniqid(); 
        }
        return $cart_item_data;
    }

    /**
     * Step 3: Automatically Add Selected Add-ons to Cart
     */
    public function add_addons_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        
        // Avoid infinite loops: Only process if this is the "Main Action" and has our data
        if ( ! isset( $_POST['kayarine_addon'] ) || ! is_array( $_POST['kayarine_addon'] ) ) {
            return;
        }
        
        // We use a static flag or check if we are already processing
        // But woocommerce_add_to_cart fires *after* the item is added. 
        // We just need to add *other* items now.
        
        // Check if this specific item execution is the "Parent" trigger
        // The simplistic way is: check if $_POST exists. But we must ensure we don't re-add addons 
        // when we programmatically add an addon (which triggers this hook again).
        
        // Better check: Is the product_id one of our MAIN products?
        if ( ! isset( Kayarine_Config::$product_rules[$product_id] ) ) {
            return; 
        }

        $date = sanitize_text_field( $_POST['kayarine_booking_date'] );
        $group_id = isset($cart_item_data['kayarine_booking_group']) ? $cart_item_data['kayarine_booking_group'] : uniqid();

        foreach ( $_POST['kayarine_addon'] as $addon_id => $qty ) {
            $qty = intval( $qty );
            if ( $qty > 0 ) {
                // Prepare metadata for the Add-on
                $addon_data = array(
                    'kayarine_booking_date' => $date,
                    'kayarine_parent_product' => $product_id,
                    'kayarine_booking_group' => $group_id
                );

                // Add to cart programmatically
                // Note: This recursively calls 'woocommerce_add_to_cart', 
                // so the check at the top (Kayarine_Config::$product_rules) is crucial to stop recursion,
                // because Add-on IDs are NOT in $product_rules keys.
                WC()->cart->add_to_cart( $addon_id, $qty, 0, array(), $addon_data );
            }
        }

        // Clear POST to prevent double addition if something weird happens
        unset( $_POST['kayarine_addon'] );
    }

    /**
     * Display Date in Cart & Checkout
     */
    public function display_cart_item_data( $item_data, $cart_item ) {
        if ( isset( $cart_item['kayarine_booking_date'] ) ) {
            $item_data[] = array(
                'key'     => '預訂日期 Date',
                'value'   => $cart_item['kayarine_booking_date'],
                'display' => $cart_item['kayarine_booking_date'],
            );
        }
        return $item_data;
    }

    /**
     * ✅ NEW: Save booking_date to order item during line item creation
     * Called during woocommerce_checkout_create_order_line_item hook
     * At this point, cart still has data and we can access kayarine_booking_date
     *
     * @param WC_Order_Item_Product $item - Order line item object
     * @param string $cart_item_key - Cart item key
     * @param array $values - Cart item data (contains kayarine_booking_date)
     * @param WC_Order $order - Order object
     */
    public function save_booking_date_to_order_item( $item, $cart_item_key, $values, $order ) {
        try {
            error_log( '════════════════════════════════════════════' );
            error_log( '[Cart Manager] save_booking_date_to_order_item() CALLED' );
            
            // Check if this cart item has booking date
            if ( ! isset( $values['kayarine_booking_date'] ) ) {
                error_log( '[Cart Manager] ⚠️ No kayarine_booking_date in cart item' );
                return;
            }
            
            $booking_date = $values['kayarine_booking_date'];
            error_log( '[Cart Manager] Found booking_date: ' . $booking_date );
            
            // Save to order item meta
            $item->update_meta_data( '_kayarine_booking_date', $booking_date );
            $item->save();
            
            error_log( '[Cart Manager] ✅ Saved _kayarine_booking_date to order item' );
            error_log( '[Cart Manager] Order ID: ' . $order->get_id() . ', Item ID: ' . $item->get_id() );
            error_log( '════════════════════════════════════════════' );
        } catch ( Exception $e ) {
            error_log( '[Cart Manager] ❌ EXCEPTION in save_booking_date_to_order_item: ' . $e->getMessage() );
        }
    }

    /**
     * ✅ FINAL FIX: Save order item meta after order is fully processed
     * At this point, Order ID and Item IDs are REAL, not 0
     *
     * @param int $order_id - Valid real order ID
     * @param array $posted_data - POST data from checkout form
     * @param WC_Order $order - Order object (fully populated)
     */
    public function save_order_booking_meta_after_checkout( $order_id, $posted_data, $order ) {
        try {
            error_log( '════════════════════════════════════════════' );
            error_log( '🔧 [Cart Manager] save_order_booking_meta_after_checkout() CALLED' );
            error_log( '[Cart Manager] Order ID: ' . $order_id . ' (Real: ' . ($order_id > 0 ? 'YES ✅' : 'NO ❌') . ')' );
            
            if ( ! $order_id || ! $order ) {
                error_log( '[Cart Manager] ❌ Invalid order_id or order object' );
                return;
            }
            
            // Process each order item
            $items_processed = 0;
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                $qty = $item->get_quantity();
                
                error_log( '[Cart Manager] Processing Item ' . $item_id . ' - Product: ' . $product_id . ', Qty: ' . $qty );
                
                // ✅ 改進：從訂單項目元數據讀取 booking_date（已由 save_booking_date_to_order_item() 保存）
                // 這樣避免依賴購物車，購物車在 woocommerce_checkout_order_processed 時可能已被清空
                $booking_date = $item->get_meta( '_kayarine_booking_date' );
                
                if ( ! $booking_date ) {
                    error_log( '[Cart Manager] ⚠️ No _kayarine_booking_date found for item ' . $item_id );
                    continue;
                }
                
                error_log( '[Cart Manager] ✅ Retrieved _kayarine_booking_date = ' . $booking_date . ' from Item ' . $item_id );
                
                // Record pending usage for inventory
                if ( class_exists('Kayarine_Inventory') ) {
                    Kayarine_Inventory::record_pending_usage( $order_id, $booking_date, $product_id, $qty );
                    error_log( '[Inventory] ✅ Recorded pending usage: Order ' . $order_id . ', Date: ' . $booking_date . ', Product: ' . $product_id . ', Qty: ' . $qty );
                }
                
                $items_processed++;
            }
            
            error_log( '[Cart Manager] Complete - Processed ' . $items_processed . ' items' );
            error_log( '════════════════════════════════════════════' );
        } catch ( Exception $e ) {
            error_log( '[Cart Manager] ❌ EXCEPTION: ' . $e->getMessage() );
            error_log( '[Cart Manager] Stack: ' . $e->getTraceAsString() );
        }
    }
}
