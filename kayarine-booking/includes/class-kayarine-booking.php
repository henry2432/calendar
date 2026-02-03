<?php
/**
 * Main Plugin Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Booking {

	public function run() {
		$start_time = microtime(true);
		
		$this->load_dependencies();
		$load_time = microtime(true) - $start_time;
		error_log( "[Kayarine Perf] load_dependencies: " . number_format($load_time*1000, 2) . "ms" );
		
		$this->define_admin_hooks();
		$admin_time = microtime(true) - $start_time - $load_time;
		error_log( "[Kayarine Perf] define_admin_hooks: " . number_format($admin_time*1000, 2) . "ms" );
		
		$this->define_public_hooks();
		$public_time = microtime(true) - $start_time - $load_time - $admin_time;
		error_log( "[Kayarine Perf] define_public_hooks: " . number_format($public_time*1000, 2) . "ms" );
		
		$total_time = microtime(true) - $start_time;
		error_log( "[Kayarine Perf] Total plugin load: " . number_format($total_time*1000, 2) . "ms" );
	}

private function load_dependencies() {
	require_once KAYARINE_BOOKING_PATH . 'includes/kayarine-config.php';
	require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-pricing.php';
	// Inventory & Admin
	require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-inventory.php';
	require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-admin.php';

	   // New Product-Based Classes
	   require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-booking-display.php';
	   require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-cart-manager.php';
	   require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-unified-booking.php'; // Unified Page
	   require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-membership.php'; // Membership System
	   require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-member-dashboard.php'; // Member Dashboard
	   require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-checkout-manager.php'; // Checkout Logic
	         
	         // Deprecated but kept if needed for backward compatibility during transition
	// require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-booking-shortcode.php';
	// require_once KAYARINE_BOOKING_PATH . 'includes/class-kayarine-booking-ajax.php';
}

	private function define_admin_hooks() {
		// Initialize Admin Settings Page
		new Kayarine_Admin();
	}

	private function define_public_hooks() {
		// Initialize Product-Based Logic
		   new Kayarine_Booking_Display();
		   new Kayarine_Cart_Manager();
		   new Kayarine_Booking_Pricing();
		   new Kayarine_Unified_Booking(); // Unified Page
		   new Kayarine_Membership(); // Membership System
		   new Kayarine_Member_Dashboard(); // FIX: Member Dashboard (displays orders, points, etc.)
		   new Kayarine_Checkout_Manager(); // Checkout Logic

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		      // 5. Shipping Restrictions
		      add_filter( 'woocommerce_package_rates', array( $this, 'filter_shipping_methods' ), 10, 2 );
		      add_filter( 'woocommerce_cart_needs_shipping', array( $this, 'filter_needs_shipping' ) );

		            // 6. Cache Invalidation (Clear Inventory Cache on Order)
		            // FIX: Use proper hooks for status changes
		            add_action( 'woocommerce_checkout_order_processed', array( $this, 'clear_inventory_cache' ), 10, 3 );
		            add_action( 'woocommerce_order_status_changed', array( $this, 'clear_inventory_cache_on_status_change' ), 10, 4 );
		            add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'on_order_status_change' ), 10, 1 );
		            add_action( 'woocommerce_order_status_pending_to_completed', array( $this, 'on_order_status_change' ), 10, 1 );
		            add_action( 'woocommerce_order_status_pending_to_on_hold', array( $this, 'on_order_status_change' ), 10, 1 );
		            add_action( 'woocommerce_order_status_completed_to_cancelled', array( $this, 'on_order_status_change' ), 10, 1 );
		            add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'on_order_status_change' ), 10, 1 );
		            
		            // ✅ 新增：監聽訂單 trash 事件以清除快取
		            // 說明：訂單被 trash 後，post_status 變成 'trash'
		            // SQL 查詢只找 pending/processing/completed/on-hold，自動排除 trash 訂單
		            // 但快取仍存在，需要清除以獲得最新庫存
		            add_action( 'trashed_post', array( $this, 'clear_inventory_cache_on_trash' ), 10, 1 );
}

		  /**
		   * Clear Inventory Cache when a new order is placed
		   */
		  public function clear_inventory_cache( $order_id, $posted_data, $order ) {
		      $this->process_order_dates_for_clearing( $order );
		  }

		  /**
		   * Clear Inventory Cache when order status changes (e.g. Cancelled, Refunded)
		   * This ensures stock is freed up immediately in cache.
		   */
		  public function clear_inventory_cache_on_status_change( $order_id, $old_status, $new_status, $order ) {
		      error_log( "[Kayarine Cache] Order Status Changed - ID: $order_id, From: $old_status, To: $new_status" );
		      $this->process_order_dates_for_clearing( $order );
		  }

		  /**
		   * Helper to find dates in order and clear cache
		   * ✅ 修復：確保所有相關日期的快取都被清除
		   */
		  private function process_order_dates_for_clearing( $order ) {
		      if ( ! $order ) return;
	
		      $order_id = $order->get_id();
		      $dates_to_clear = array();
	
		      foreach ( $order->get_items() as $item ) {
		          // Check item meta for date
		          // Meta key: '_kayarine_booking_date' (with underscore prefix)
		          $date = $item->get_meta( '_kayarine_booking_date' );
		          if ( $date ) {
		              $dates_to_clear[$date] = true; // Use array key for unique
		          }
		      }
	
        if ( ! empty( $dates_to_clear ) ) {
            error_log( "[Kayarine Cache Clear] Order ID: $order_id | Clearing " . count($dates_to_clear) . " dates: " . implode(', ', array_keys($dates_to_clear)) );
            foreach ( array_keys( $dates_to_clear ) as $date ) {
                Kayarine_Inventory::clear_cache( $date );
                error_log( "[Kayarine Cache Clear] Cleared cache for: $date" );
            }
        } else {
            error_log( "[Kayarine Cache Clear] Order ID: $order_id | No kayarine_booking_date found in items" );
        }

        if ( $order_id ) {
            Kayarine_Inventory::clear_pending_usage( $order_id );
        }
		  }
		  
		      /**
		       * FIX: Unified status change handler
		       */
		      public function on_order_status_change( $order_id ) {
		          $order = wc_get_order( $order_id );
		          $this->process_order_dates_for_clearing( $order );
		      }
		  
		      /**
		       * Disable Shipping for Specific Categories
		   * 1. 設備租借 (Equipment Rental)
		   * 2. 加購項目 (Add-ons)
		   * 3. 水上活動 (Water Activities)
		   */
		  public function filter_shipping_methods( $rates, $package ) {
		      static $category_cache = array(); // Cache product category checks
		      
		      $restricted_categories = array( '設備租借', '加購項目', '水上活動' );
		      $has_restricted_item = false;
	
		      foreach ( $package['contents'] as $item ) {
		          $product_id = $item['product_id'];
		          
		          // Check cache first
		          if ( ! isset( $category_cache[ $product_id ] ) ) {
		              $is_restricted = false;
		              $terms = get_the_terms( $product_id, 'product_cat' );
		              
		              if ( $terms && ! is_wp_error( $terms ) ) {
		                  foreach ( $terms as $term ) {
		                      if ( in_array( $term->name, $restricted_categories ) ) {
		                          $is_restricted = true;
		                          break;
		                      }
		                  }
		              }
		              
		              $category_cache[ $product_id ] = $is_restricted;
		          }
		          
		          if ( $category_cache[ $product_id ] ) {
		              $has_restricted_item = true;
		              break;
		          }
		      }
	
		      if ( $has_restricted_item ) {
		          // Remove all PAID shipping. If Local Pickup exists, keep it.
		          foreach ( $rates as $rate_id => $rate ) {
		              if ( 'local_pickup' !== $rate->method_id ) {
		                  unset( $rates[ $rate_id ] );
		              }
		          }
		      }
	
		      return $rates;
		  }

		  /**
		   * Force "No Shipping Needed" if all items are in restricted categories?
		   * Or if they are physical but we just don't ship them.
		   */
		  public function filter_needs_shipping( $needs_shipping ) {
		      static $category_cache = array(); // Cache product category checks
		      
		      if ( ! $needs_shipping ) {
		          return false;
		      }
	
		      $restricted_categories = array( '設備租借', '加購項目', '水上活動' );
		      
		      if ( WC()->cart->is_empty() ) {
		          return $needs_shipping;
		      }
	
		      $all_restricted = true;
		      
		      foreach ( WC()->cart->get_cart() as $cart_item ) {
		          $product_id = $cart_item['product_id'];
		          
		          // Check cache first
		          if ( ! isset( $category_cache[ $product_id ] ) ) {
		              $item_is_restricted = false;
		              $terms = get_the_terms( $product_id, 'product_cat' );
		              
		              if ( $terms && ! is_wp_error( $terms ) ) {
		                  foreach ( $terms as $term ) {
		                      if ( in_array( $term->name, $restricted_categories ) ) {
		                          $item_is_restricted = true;
		                          break;
		                      }
		                  }
		              }
		              
		              $category_cache[ $product_id ] = $item_is_restricted;
		          }
		          
		          if ( ! $category_cache[ $product_id ] ) {
		              $all_restricted = false;
		              break;
		          }
		      }
	
		      if ( $all_restricted ) {
		          return false; // Disable shipping entirely for these orders
		      }
	
		      return $needs_shipping;
		  }

public function enqueue_scripts() {
		   // Enqueue conditionally? Better done in Display class, but here is fine for now.
		   // We register them here, but enqueue in Display class.
		wp_register_style( 'kayarine-booking-css', KAYARINE_BOOKING_URL . 'assets/css/style.css', array(), KAYARINE_BOOKING_VERSION, 'all' );
		wp_register_script( 'kayarine-booking-js', KAYARINE_BOOKING_URL . 'assets/js/script.js', array( 'jquery' ), KAYARINE_BOOKING_VERSION, true );
		
		   // Pass Configuration & AJAX URL to JS
		$js_data = Kayarine_Config::get_js_config();
		
		wp_localize_script( 'kayarine-booking-js', 'kayarine_config', $js_data );
		wp_localize_script( 'kayarine-booking-js', 'kayarine_vars', array(
		    'ajax_url' => admin_url( 'admin-ajax.php' )
		));
}

	/**
	 * ✅ 新增：監聽訂單 trash 事件
	 * 當訂單被移至垃圾桶時，清除相關日期的庫存快取
	 *
	 * 說明：
	 * - 訂單 trash 後，post_status 變成 'trash'
	 * - SQL 查詢自動排除 trash 訂單（只查 pending/processing/completed/on-hold）
	 * - 但快取仍然存在（5秒 TTL），需要清除
	 * - 下次查詢時會重新計算，自動排除已 trash 的訂單
	 */
	public function clear_inventory_cache_on_trash( $post_id ) {
		// 確認這是訂單 post
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'shop_order' ) {
			return;
		}
		
		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			return;
		}
		
		error_log( "[Kayarine Cache] Order TRASHED - ID: $post_id, 清除相關日期快取" );
		$this->process_order_dates_for_clearing( $order );
	}
}
