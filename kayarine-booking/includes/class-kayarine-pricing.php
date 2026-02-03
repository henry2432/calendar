<?php
/**
 * Pricing Logic Class
 * Handles dynamic pricing based on date (Weekend vs Weekday).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Booking_Pricing {

    public function __construct() {
        // Hook into WooCommerce cart calculation
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'update_cart_prices' ), 10, 1 );
    }

    public function update_cart_prices( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        // Avoid recursion
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item ) {
            // Check if this item has booking date data
            // FIX: Use correct metadata key 'kayarine_booking_date' not 'booking_date'
            if ( isset( $cart_item['kayarine_booking_date'] ) ) {
                $date = $cart_item['kayarine_booking_date'];
                $product_id = $cart_item['product_id'];

                // Get dynamic price from Config
                $new_price = Kayarine_Config::get_price( $product_id, $date );

                // If price > 0, update it
                if ( $new_price > 0 ) {
                    $cart_item['data']->set_price( $new_price );
                }
            }
        }
    }
}
