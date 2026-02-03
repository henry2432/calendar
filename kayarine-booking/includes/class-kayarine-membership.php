<?php
/**
 * Kayarine Membership System
 * Handles Loyalty Points, Membership Tiers, and Store Wallet.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Membership {

    const TABLE_LOG = 'kayarine_points_log';
    const META_POINTS = 'kayarine_points_balance';
    const META_WALLET = 'kayarine_wallet_balance';
    const META_TIER   = 'kayarine_membership_tier'; // bronze, silver, gold, vip
    const META_SPEND  = 'kayarine_total_spend_2y';
    const META_VIP    = 'kayarine_is_vip'; // Manual override

    public function __construct() {
        // Register Hooks
        add_action( 'init', array( $this, 'create_tables' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'process_order_rewards' ), 10, 1 );
        
        // Admin Profile Fields
        add_action( 'show_user_profile', array( $this, 'add_admin_profile_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'add_admin_profile_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_admin_profile_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_admin_profile_fields' ) );
    }

    /**
     * Create Database Table for Points Log
     */
    public function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_LOG;
        
        // Check if table exists to avoid running dbDelta on every init
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                type varchar(20) NOT NULL, -- 'earn', 'redeem', 'refund', 'adjust', 'wallet_topup', 'wallet_spend'
                amount decimal(10,2) NOT NULL,
                balance_after decimal(10,2) NOT NULL,
                reference_id varchar(50) DEFAULT '', -- Order ID or Manual Ref
                description text,
                date_created datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

    /**
     * Get Member Tier
     */
    public static function get_tier( $user_id ) {
        // Check manual VIP override first
        if ( get_user_meta( $user_id, self::META_VIP, true ) ) {
            return 'vip';
        }

        // Return calculated/stored tier
        $tier = get_user_meta( $user_id, self::META_TIER, true );
        return $tier ? $tier : 'bronze';
    }

    /**
     * Get Tier Info (Label & Rate)
     */
    public static function get_tier_info( $tier_slug ) {
        $tiers = array(
            'bronze' => array( 'label' => '銅級會員', 'rate' => 0.01, 'next' => 2000 ),
            'silver' => array( 'label' => '銀級會員', 'rate' => 0.02, 'next' => 5000 ),
            'gold'   => array( 'label' => '金級會員', 'rate' => 0.03, 'next' => 999999 ),
            'vip'    => array( 'label' => '尊貴會員', 'rate' => 0.00, 'next' => 0 ) // VIP No Points
        );
        return isset($tiers[$tier_slug]) ? $tiers[$tier_slug] : $tiers['bronze'];
    }

    /**
     * Recalculate User Tier (Rolling 2 Years)
     */
    public function calculate_tier( $user_id ) {
        // If VIP, skip calculation
        if ( get_user_meta( $user_id, self::META_VIP, true ) ) {
            return 'vip';
        }

        $two_years_ago = date( 'Y-m-d H:i:s', strtotime( '-2 years' ) );
        
        // Query Total Spent in completed orders in last 2 years
        // We use wc_get_orders for better compatibility
        $args = array(
            'customer_id' => $user_id,
            'status'      => array( 'completed' ),
            'date_after'  => $two_years_ago,
            'limit'       => -1,
            'return'      => 'ids',
        );
        
        $orders = wc_get_orders( $args );
        $total_spent = 0;

        foreach ( $orders as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                $total_spent += $order->get_total();
            }
        }

        update_user_meta( $user_id, self::META_SPEND, $total_spent );

        // Determine Tier
        $new_tier = 'bronze';
        if ( $total_spent > 5000 ) {
            $new_tier = 'gold';
        } elseif ( $total_spent > 2000 ) {
            $new_tier = 'silver';
        }

        update_user_meta( $user_id, self::META_TIER, $new_tier );
        return $new_tier;
    }

    /**
     * Process Order Completion (Award Points)
     */
    public function process_order_rewards( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $user_id = $order->get_user_id();
        if ( ! $user_id ) return; // Guest order, no points

        // Check if points already awarded to avoid duplicates
        if ( $order->get_meta( '_kayarine_points_awarded' ) ) return;

        // 1. Recalculate Tier FIRST
        $current_tier = self::get_tier( $user_id );
        $tier_info = self::get_tier_info( $current_tier );
        $rate = $tier_info['rate'];

        // 2. Calculate Earnings
        $paid_amount = $order->get_total();
        
        // Exclude Store Credit Top-up Products (SKU: K-WALLET-1500)
        $credit_product_amount = 0;
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( $product && $product->get_sku() === 'K-WALLET-1500' ) { 
                 $credit_product_amount += $item->get_total();
                 
                 // Process Wallet Top-up Here instantly
                 $this->adjust_wallet( $user_id, 1500, 'topup', $order_id, '購買儲值金' );
            }
        }

        $earning_base = $paid_amount - $credit_product_amount;

        if ( $earning_base > 0 && $rate > 0 ) {
            // Points = Amount * Rate (e.g. 100 * 0.01 = 1 Point)
            $points_earned = floor( $earning_base * $rate ); 
            
            if ( $points_earned > 0 ) {
                $this->adjust_points( $user_id, $points_earned, 'earn', $order_id, "訂單 #{$order_id} 回饋 ({$current_tier})" );
                $order->update_meta_data( '_kayarine_points_awarded', $points_earned );
            }
        }

        $order->save();

        // 3. Update Tier for Future
        $this->calculate_tier( $user_id );
    }

    /**
     * Adjust Points Balance
     * FIX: Add validation to prevent negative balances and ensure audit trail
     */
    public function adjust_points( $user_id, $amount, $type, $ref = '', $desc = '' ) {
        global $wpdb;
        
        // Validate user ID
        if ( ! $user_id || $user_id <= 0 ) {
            error_log( "[Kayarine] Attempted point adjustment with invalid user_id: $user_id" );
            return false;
        }

        // Validate type
        $allowed_types = array( 'earn', 'redeem', 'refund', 'adjust', 'wallet_topup', 'wallet_spend' );
        if ( ! in_array( $type, $allowed_types ) ) {
            error_log( "[Kayarine] Attempted point adjustment with invalid type: $type" );
            return false;
        }

        $current = (float) get_user_meta( $user_id, self::META_POINTS, true );
        $new_balance = $current + $amount;
        
        // Prevent negative balance
        if ( $new_balance < 0 ) {
            error_log( "[Kayarine] Attempted negative balance adjustment - User: $user_id, Current: $current, Amount: $amount" );
            $new_balance = 0;
        }

        // Cap balance at reasonable maximum (prevent integer overflow)
        $max_balance = 999999.99;
        if ( $new_balance > $max_balance ) {
            error_log( "[Kayarine] Balance exceeds maximum - User: $user_id, Attempted: $new_balance" );
            $new_balance = $max_balance;
        }

        update_user_meta( $user_id, self::META_POINTS, $new_balance );

        // Log it with timestamp
        $wpdb->insert(
            $wpdb->prefix . self::TABLE_LOG,
            array(
                'user_id' => $user_id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $new_balance,
                'reference_id' => $ref,
                'description' => $desc
            ),
            array( '%d', '%s', '%f', '%f', '%s', '%s' )
        );

        return true;
    }

    /**
     * Adjust Wallet Balance
     * ✅ 修復：添加負值檢查和上限限制
     */
    public function adjust_wallet( $user_id, $amount, $type, $ref = '', $desc = '' ) {
        global $wpdb;
        
        // Validate user ID
        if ( ! $user_id || $user_id <= 0 ) {
            error_log( "[Kayarine] Attempted wallet adjustment with invalid user_id: $user_id" );
            return false;
        }
        
        $current = (float) get_user_meta( $user_id, self::META_WALLET, true );
        $new_balance = $current + $amount;
        
        // Prevent negative balance
        if ( $new_balance < 0 ) {
            error_log( "[Kayarine] Attempted negative wallet balance - User: $user_id, Current: $current, Amount: $amount" );
            $new_balance = 0;
        }
        
        // Cap at reasonable maximum
        $max_balance = 99999.99;
        if ( $new_balance > $max_balance ) {
            error_log( "[Kayarine] Wallet balance exceeds maximum - User: $user_id, Attempted: $new_balance" );
            $new_balance = $max_balance;
        }
        
        update_user_meta( $user_id, self::META_WALLET, $new_balance );

        // Log it (using same log table but different type prefix)
        $wpdb->insert(
            $wpdb->prefix . self::TABLE_LOG,
            array(
                'user_id' => $user_id,
                'type' => 'wallet_' . $type,
                'amount' => $amount,
                'balance_after' => $new_balance,
                'reference_id' => $ref,
                'description' => $desc
            ),
            array( '%d', '%s', '%f', '%f', '%s', '%s' )
        );
        
        return true;
    }

    /**
     * Admin Profile Fields
     * ✅ 修復：添加 nonce 字段以防 CSRF 攻擊
     */
    public function add_admin_profile_fields( $user ) {
        if ( ! current_user_can( 'manage_options' ) ) return;
        
        $points = get_user_meta( $user->ID, self::META_POINTS, true );
        $wallet = get_user_meta( $user->ID, self::META_WALLET, true );
        $tier   = get_user_meta( $user->ID, self::META_TIER, true );
        $is_vip = get_user_meta( $user->ID, self::META_VIP, true );
        $spend  = get_user_meta( $user->ID, self::META_SPEND, true );
        ?>
        <h3>Kayarine 會員管理</h3>
        <table class="form-table">
            <tr>
                <th><label>會員等級</label></th>
                <td>
                    <input type="text" value="<?php echo esc_attr( $tier ? $tier : 'bronze' ); ?>" disabled class="regular-text" />
                    <p class="description">過去2年消費: $<?php echo number_format((float)$spend, 2); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="kayarine_is_vip">尊貴會員 (VIP)</label></th>
                <td>
                    <input type="checkbox" name="kayarine_is_vip" id="kayarine_is_vip" value="1" <?php checked( $is_vip, 1 ); ?> />
                    <span class="description">勾選後將強制設為 VIP，享有全年平日價且不累積積分。</span>
                </td>
            </tr>
            <tr>
                <th><label for="kayarine_adjust_points">積分餘額: <?php echo $points ? $points : 0; ?></label></th>
                <td>
                    <input type="number" name="kayarine_adjust_points" id="kayarine_adjust_points" value="" placeholder="+/- 調整數值" class="regular-text" />
                    <p class="description">輸入正負數來手動調整積分。</p>
                </td>
            </tr>
            <tr>
                <th><label for="kayarine_adjust_wallet">錢包餘額: $<?php echo $wallet ? $wallet : 0; ?></label></th>
                <td>
                    <input type="number" name="kayarine_adjust_wallet" id="kayarine_adjust_wallet" value="" placeholder="+/- 調整數值" class="regular-text" />
                    <p class="description">輸入正負數來手動調整儲值金。</p>
                </td>
            </tr>
        </table>
        <?php wp_nonce_field( 'kayarine_membership_nonce', 'kayarine_membership_nonce' ); ?>
        <?php
    }

    public function save_admin_profile_fields( $user_id ) {
        if ( ! current_user_can( 'manage_options' ) ) return;
        
        // ✅ 驗證 nonce 防止 CSRF 攻擊
        if ( ! isset( $_POST['kayarine_membership_nonce'] ) || ! wp_verify_nonce( $_POST['kayarine_membership_nonce'], 'kayarine_membership_nonce' ) ) {
            error_log( "[Kayarine] CSRF attack attempt detected on user profile save" );
            wp_die( '安全驗證失敗' );
        }

        // Save VIP
        if ( isset( $_POST['kayarine_is_vip'] ) ) {
            update_user_meta( $user_id, self::META_VIP, 1 );
        } else {
            delete_user_meta( $user_id, self::META_VIP );
        }

        // Adjust Points
        if ( ! empty( $_POST['kayarine_adjust_points'] ) ) {
            $amount = floatval( $_POST['kayarine_adjust_points'] );
            if ( $amount != 0 ) {
                // ✅ 記錄是哪個管理員進行調整
                $admin_id = get_current_user_id();
                $admin_user = get_user_by( 'id', $admin_id );
                $admin_name = $admin_user ? $admin_user->user_login : 'unknown';
                $desc = "後台管理員({$admin_name})手動調整 - 金額: {$amount}";
                $this->adjust_points( $user_id, $amount, 'adjust', "admin-{$admin_id}", $desc );
            }
        }

        // Adjust Wallet
        if ( ! empty( $_POST['kayarine_adjust_wallet'] ) ) {
            $amount = floatval( $_POST['kayarine_adjust_wallet'] );
            if ( $amount != 0 ) {
                // ✅ 記錄是哪個管理員進行調整
                $admin_id = get_current_user_id();
                $admin_user = get_user_by( 'id', $admin_id );
                $admin_name = $admin_user ? $admin_user->user_login : 'unknown';
                $desc = "後台管理員({$admin_name})手動調整 - 金額: {$amount}";
                $this->adjust_wallet( $user_id, $amount, 'adjust', "admin-{$admin_id}", $desc );
            }
        }
    }
}
