<?php
/**
 * Kayarine Checkout Manager
 * Handles Points Redemption and Wallet Usage at Checkout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Checkout_Manager {

    public function __construct() {
        // Display Points UI in Checkout (Wallet removed)
        add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_loyalty_options' ) );
        
        // Display "應付" label after total
        add_action( 'woocommerce_review_order_after_order_total', array( $this, 'display_payable_label' ) );
        
        // Process AJAX actions for points redemption only
        add_action( 'wp_ajax_kayarine_apply_points', array( $this, 'ajax_apply_points' ) );
        add_action( 'wp_ajax_nopriv_kayarine_apply_points', array( $this, 'ajax_apply_points' ) );
        
        // ✅ 新增：動態重新計算可用積分（購物車數量改變時調用）
        add_action( 'wp_ajax_kayarine_recalc_points', array( $this, 'ajax_recalc_points' ) );
        add_action( 'wp_ajax_nopriv_kayarine_recalc_points', array( $this, 'ajax_recalc_points' ) );

        // Calculate Totals (Discount)
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_discounts' ) );
        
        // Deduct points on successful payment
        add_action( 'woocommerce_order_status_processing', array( $this, 'deduct_loyalty_balance' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'deduct_loyalty_balance' ) );
        
        // FIX: Handle order cancellation to refund points
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'refund_loyalty_balance' ) );
        add_action( 'woocommerce_order_status_refunded', array( $this, 'refund_loyalty_balance' ) );
        
        // FIX: Hook into order creation to ensure points are properly deducted
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_checkout_points' ), 10, 3 );
    }

    /**
     * Display "應付" label after total
     */
    public function display_payable_label() {
        if ( ! is_user_logged_in() ) return;
        
        $points = WC()->session->get( 'kayarine_points_applied', 0 );
        if ( $points <= 0 ) return;
        
        $cart_total = WC()->cart->get_total('');
        echo '<tr class="payable-amount"><td colspan="2" style="text-align: right; font-weight: bold; padding-top: 10px;">應付: ' . wc_price( WC()->cart->get_total('') ) . '</td></tr>';
    }

    /**
     * Render UI above Payment Methods
     */
    public function render_loyalty_options() {
        if ( ! is_user_logged_in() ) {
            echo '<div class="woocommerce-info">登入後可使用會員積分。 <a href="' . get_permalink( get_option('woocommerce_myaccount_page_id') ) . '">按此登入</a></div>';
            return;
        }

        $user_id = get_current_user_id();
        $points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );
        $tier = Kayarine_Membership::get_tier( $user_id );
        
        // Calculate max usable (Cart Total)
        $cart_total = WC()->cart->get_subtotal() + WC()->cart->get_shipping_total();
        
        // Determine if user can use points
        $can_use_points = $points > 0 && $cart_total > 0;
        $auto_points = $can_use_points ? min( $points, $cart_total ) : 0;
        
        // Create nonce for AJAX validation
        $nonce = wp_create_nonce( 'kayarine_checkout_nonce' );
        
        ?>
        <div id="kayarine-loyalty-checkout" class="kayarine-booking-card" style="padding: 15px; margin-bottom: 20px; border-color: #ed8936;">
            <h4 style="margin-top:0;">會員優惠 (<?php echo Kayarine_Membership::get_tier_info($tier)['label']; ?>)</h4>
            
            <!-- Points Section Only - Auto Apply -->
            <div class="kb-checkout-section" style="margin-bottom: 15px;">
                <?php if ( $can_use_points ): ?>
                    <label>
                        <input type="checkbox" id="use_points_check" checked>
                        自動使用積分折抵 (現有: <?php echo $points; ?> 分)
                    </label>
                    <div id="points_display" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                        <p style="margin: 0; font-size: 14px;">
                            <strong>將折抵: <?php echo $auto_points; ?> 分 = HK$<?php echo $auto_points; ?></strong>
                            <?php if ( $points > $cart_total ): ?>
                                <br><small style="color: #666;">（帳單 HK$<?php echo number_format($cart_total, 2); ?>，剩餘 <?php echo ($points - $auto_points); ?> 分）</small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <p class="description" style="font-size: 12px; margin-top: 8px;">每 1 積分可折抵 HK$1。系統會自動使用最多積分，不會超過帳單金額。</p>
                <?php else: ?>
                    <p style="color: #666; margin: 0;">
                        <?php if ( $points <= 0 ): ?>
                            目前沒有可用積分
                        <?php else: ?>
                            購物車為空，無法使用積分
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div id="loyalty_message"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // ✅ 修復：在頁面加載時立即初始化積分
            // 複選框預設被勾選，但 change 事件不會在初始加載時觸發
            var initialAmount = 0;
            var $checkbox = $('#use_points_check');
            
            if ($checkbox.is(':checked') && <?php echo $auto_points; ?> > 0) {
                initialAmount = <?php echo $auto_points; ?>;
                console.log('[Kayarine] Page loaded - Auto applying default points: ' + initialAmount);
                trigger_ajax('points', initialAmount);
            }

            // Toggle points usage when checkbox is changed
            $checkbox.change(function() {
                var amount = this.checked ? <?php echo $auto_points; ?> : 0;
                console.log('[Kayarine] Checkbox changed - Applying points: ' + amount);
                trigger_ajax('points', amount);
            });

            function trigger_ajax(type, amount) {
                $('#loyalty_message').text('處理中...').css('color', '#666');
                console.log('[Kayarine] Sending AJAX - type: ' + type + ', amount: ' + amount);

                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        action: 'kayarine_apply_' + type,
                        amount: amount,
                        _wpnonce: '<?php echo esc_attr( $nonce ); ?>'
                    },
                    success: function(response) {
                        if(response.success) {
                            console.log('[Kayarine] Points applied successfully: ' + response.data.message);
                            $('#loyalty_message').text(response.data.message).css('color', 'green');
                            $('body').trigger('update_checkout');
                        } else {
                            console.error('[Kayarine] Points application failed: ' + response.data.message);
                            $('#loyalty_message').text(response.data.message).css('color', 'red');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[Kayarine] AJAX error: ' + error);
                        $('#loyalty_message').text('系統錯誤，請刷新頁面').css('color', 'red');
                    }
                });
            }
            
            // ✅ 新增：監聽結帳頁更新事件，重新計算可用積分
            $(document.body).on('updated_checkout', function() {
                console.log('[Kayarine] Checkout updated, recalculating points...');
                
                // 調用後端重新計算可用積分
                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: {
                        action: 'kayarine_recalc_points'
                    },
                    success: function(response) {
                        if(response.success) {
                            var data = response.data;
                            console.log('[Kayarine] Points recalculated:', data);
                            
                            // 更新積分複選框狀態
                            var $checkbox = $('#use_points_check');
                            var $display = $('#points_display');
                            
                            // 如果可用積分為 0，禁用複選框
                            if(data.available_points <= 0) {
                                $checkbox.prop('disabled', true).prop('checked', false);
                                if($display.length) {
                                    $display.html('<p style="color: #999;">沒有可用積分</p>');
                                }
                            } else {
                                $checkbox.prop('disabled', false);
                                // 保持當前選擇狀態，但更新顯示的積分額度
                                if($checkbox.is(':checked') && data.applied_points > 0) {
                                    if($display.length) {
                                        $display.html('<p style="margin: 0;"><strong>將折抵: ' + data.applied_points + ' 分 = HK$' + data.applied_points + '</strong></p>');
                                    }
                                }
                            }
                        } else {
                            console.error('[Kayarine] Points recalculation failed:', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[Kayarine] Points recalculation error:', error);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Apply Points
     * FIX: Add strict validation and nonce check to prevent exploitation
     */
    public function ajax_apply_points() {
        // Validate AJAX request
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'kayarine_checkout_nonce' ) ) {
            wp_send_json_error( array( 'message' => '安全驗證失敗，請刷新頁面' ) );
        }

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => '請先登入' ) );
        }
        
        $user_id = get_current_user_id();
        
        // Sanitize and validate amount
        $amount = isset( $_POST['amount'] ) ? intval( $_POST['amount'] ) : 0;
        
        // Get fresh points from DB (prevent session manipulation)
        $max_points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );
        
        error_log( "[Kayarine Checkout] Applying points - Amount: $amount, Max: $max_points, User: $user_id" );

        // Validation 1: Cannot be negative
        if ( $amount < 0 ) {
            error_log( "[Kayarine Checkout] Negative amount rejected: $amount" );
            wp_send_json_error( array( 'message' => '積分數量不可為負數' ) );
        }

        // Validation 2: Cannot exceed user balance
        if ( $amount > $max_points ) {
            error_log( "[Kayarine Checkout] Insufficient points - Requested: $amount, Available: $max_points" );
            wp_send_json_error( array( 'message' => '積分不足 (可用: ' . $max_points . ' 分)' ) );
        }

        // Validation 3: Get fresh cart total to prevent overpayment
        $cart = WC()->cart;
        $cart_subtotal = $cart->get_subtotal();
        $cart_shipping = $cart->get_shipping_total();
        $cart_total = $cart_subtotal + $cart_shipping;

        // Prevent applying more points than cart total
        if ( $amount > $cart_total ) {
            error_log( "[Kayarine Checkout] Amount exceeds cart total - Requested: $amount, Cart: $cart_total, Clamped" );
            $amount = intval( $cart_total );
        }

        // Final validation: Amount cannot be fractional
        if ( $amount != intval( $amount ) ) {
            error_log( "[Kayarine Checkout] Fractional points rejected: $amount" );
            wp_send_json_error( array( 'message' => '積分必須為整數' ) );
        }
        
        // Apply to session
        WC()->session->set( 'kayarine_points_applied', $amount );
        error_log( "[Kayarine Checkout] Successfully applied $amount points for user $user_id" );
        
        wp_send_json_success( array( 'message' => $amount > 0 ? "已套用 {$amount} 積分 = HK\${$amount}" : "已取消積分使用" ) );
    }

    /**
     * Calculate Fees (Negative Fees = Discount) - Points Only
     * FIX: Add multiple safety checks to prevent over-application
     */
    public function apply_discounts( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( ! is_user_logged_in() ) return;

        $points = WC()->session->get( 'kayarine_points_applied', 0 );
        
        // Safety Check 1: Against user balance
        $user_id = get_current_user_id();
        $max_points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );

        if ( $points > $max_points ) {
            $points = $max_points;
            WC()->session->set( 'kayarine_points_applied', $max_points );
        }

        // Safety Check 2: Only non-negative points
        if ( $points < 0 ) {
            $points = 0;
            WC()->session->set( 'kayarine_points_applied', 0 );
        }

        // Calculate Cart Total to prevent negative (Before fees to avoid recursion)
        $cart_total = $cart->subtotal + $cart->shipping_total;

        // Safety Check 3: Prevent negative cart total
        if ( $points > $cart_total ) {
            $points = intval( $cart_total );
            WC()->session->set( 'kayarine_points_applied', $points );
        }
        
        if ( $points > 0 ) {
            // 1 Point = $1 Discount (明確的 1:1 比率)
            $discount = min( $points, $cart_total );
            
            // Only apply fee if not already applied (prevent duplicate)
            $has_points_fee = false;
            foreach ( $cart->get_fees() as $fee ) {
                if ( $fee->get_name() == '會員積分折抵' ) {
                    $has_points_fee = true;
                    break;
                }
            }
            
            if ( ! $has_points_fee ) {
                $cart->add_fee( '會員積分折抵', -$discount );
            }
        }
    }

    /**
     * Deduct Balance on Order Payment - Points Only
     */
    public function deduct_loyalty_balance( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        
        // Check if already deducted
        if ( $order->get_meta( '_kayarine_loyalty_deducted' ) ) return;

        $user_id = $order->get_user_id();
        
        // Parse Fees to find points deductions
        $points_used = 0;

        foreach ( $order->get_fees() as $fee ) {
            if ( $fee->get_name() == '會員積分折抵' ) {
                $points_used += abs( $fee->get_total() ); // Fee is negative
            }
        }

        $membership = new Kayarine_Membership();

        if ( $points_used > 0 ) {
            $membership->adjust_points( $user_id, -$points_used, 'redeem', $order_id, "訂單 #{$order_id} 折抵" );
            $order->update_meta_data( '_kayarine_loyalty_deducted', 1 );
            $order->save();
        }
        
        // Clear session
        if ( isset( WC()->session ) ) {
            WC()->session->set( 'kayarine_points_applied', 0 );
        }
    }

    /**
     * FIX: Refund Points on Order Cancellation/Refund
     * Process when order is cancelled or refunded
     */
    public function refund_loyalty_balance( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        // Check if refund has already been processed
        if ( $order->get_meta( '_kayarine_loyalty_refunded' ) ) return;

        $user_id = $order->get_user_id();
        if ( ! $user_id ) return;

        // Parse Fees to find points deductions
        $points_used = 0;

        foreach ( $order->get_fees() as $fee ) {
            if ( $fee->get_name() == '會員積分折抵' ) {
                $points_used += abs( $fee->get_total() );
            }
        }

        $membership = new Kayarine_Membership();

        if ( $points_used > 0 ) {
            // Refund the points
            $membership->adjust_points( $user_id, $points_used, 'refund', $order_id, "訂單 #{$order_id} 取消/退款 - 退還積分" );
            $order->update_meta_data( '_kayarine_loyalty_refunded', 1 );
            $order->save();

            error_log( "[Kayarine Checkout] Points refunded - Order: $order_id, Amount: $points_used" );
        }
    }

    /**
     * FIX: Process Points Deduction at Checkout (Ensure it happens)
     * Called when order is initially created
     */
    public function process_checkout_points( $order_id, $posted_data, $order ) {
        if ( ! is_user_logged_in() ) return;

        $user_id = get_current_user_id();
        $points_applied = WC()->session->get( 'kayarine_points_applied', 0 );

        if ( $points_applied > 0 ) {
            // Ensure the fee is captured in the order
            $membership = new Kayarine_Membership();
            
            // Double-check against user balance
            $user_points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );
            $actual_deduction = min( $points_applied, $user_points );

            if ( $actual_deduction > 0 ) {
                // The deduction will be handled in deduct_loyalty_balance hook
                // But we mark it here to avoid double processing
                $order->update_meta_data( '_kayarine_points_to_deduct', $actual_deduction );
                $order->save();
            }
        }
    }
    
    /**
     * ✅ 新增：動態重新計算可用積分 (當購物車數量改變時調用)
     * 根據購物車總額重新計算積分折抵額度
     */
    public function ajax_recalc_points() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => '請先登入' ) );
        }
        
        $user_id = get_current_user_id();
        
        // 獲取用戶當前積分
        $user_points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );
        
        // 獲取購物車總額
        $cart = WC()->cart;
        $cart_subtotal = $cart->get_subtotal();
        $cart_shipping = $cart->get_shipping_total();
        $cart_total = $cart_subtotal + $cart_shipping;
        
        // 計算可以使用的積分（最多使用購物車總額）
        $available_points = min( $user_points, intval( $cart_total ) );
        
        // 當前應用的積分
        $applied_points = WC()->session->get( 'kayarine_points_applied', 0 );
        
        // 如果購物車總額降低，自動調整應用的積分不超過新的總額
        if ( $applied_points > $cart_total ) {
            $applied_points = intval( $cart_total );
            WC()->session->set( 'kayarine_points_applied', $applied_points );
            error_log( "[Kayarine Checkout] Recalculated points - Cart total reduced, adjusted to: $applied_points" );
        }
        
        error_log( "[Kayarine Checkout] Points recalculation - User: $user_id, Available: $available_points, Applied: $applied_points, CartTotal: $cart_total" );
        
        wp_send_json_success( array(
            'available_points' => $available_points,
            'applied_points' => $applied_points,
            'cart_total' => $cart_total
        ) );
    }
}
