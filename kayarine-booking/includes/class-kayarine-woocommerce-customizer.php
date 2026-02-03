<?php
/**
 * Kayarine WooCommerce Customizer - Minimal Test Version
 * Testing if shortcode registration works
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Kayarine_WooCommerce_Customizer {

    public function __construct() {
        // 只註冊 shortcode，測試是否能工作
        add_shortcode( 'kayarine_account', array( $this, 'render_kayarine_account_shortcode' ) );
        
        // 註冊自定義端點
        add_action( 'init', array( $this, 'add_custom_endpoint' ) );
        
        // 自定義帳戶菜單
        add_filter( 'woocommerce_account_menu_items', array( $this, 'customize_account_menu' ), 10, 1 );
        
        // 渲染自定義端點
        add_action( 'woocommerce_account_kayarine-membership_endpoint', array( $this, 'render_membership_dashboard' ) );
        
        // AJAX 處理
        add_action( 'wp_ajax_nopriv_kayarine_custom_login', array( $this, 'handle_custom_login' ) );
        add_action( 'wp_ajax_nopriv_kayarine_custom_register', array( $this, 'handle_custom_register' ) );
        // 已移至 class-kayarine-member-dashboard.php 避免衝突
        // add_action( 'wp_ajax_kayarine_reschedule_booking', array( $this, 'ajax_reschedule_booking' ) );
        // add_action( 'wp_ajax_kayarine_cancel_booking', array( $this, 'ajax_cancel_booking' ) );
        
        // 樣式和腳本
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_custom_styles' ) );
        
        // 查詢變數
        add_filter( 'query_vars', array( $this, 'add_custom_query_vars' ) );
    }

    public function add_custom_endpoint() {
        add_rewrite_endpoint( 'kayarine-membership', EP_ROOT | EP_PAGES );
    }

    public function add_custom_query_vars( $vars ) {
        $vars[] = 'kayarine-membership';
        return $vars;
    }

    /**
     * Main shortcode
     */
    public function render_kayarine_account_shortcode() {
        // Ensure styles and scripts are enqueued
        $this->enqueue_custom_styles();
        
        ob_start();
        
        if ( is_user_logged_in() ) {
            $this->render_logged_in_account();
        } else {
            $this->render_login_register_ui();
        }
        
        $content = ob_get_clean();
        
        // Don't add inline styles/scripts - they should be properly enqueued
        // to avoid CSP violations
        
        return $content;
    }

    /**
     * Render login/register UI
     */
    private function render_login_register_ui() {
        ?>
        <div class="kayarine-auth-wrapper-orange">
            <div class="kayarine-auth-container-orange">
                <div class="kayarine-auth-header-orange">
                    <h1>Kayarine 會員中心</h1>
                    <p>登入或註冊以管理您的預約</p>
                </div>

                <div class="kayarine-auth-tabs-orange">
                    <button class="kayarine-auth-tab-btn-orange active" data-tab="login" type="button">
                        🔐 會員登入
                    </button>
                    <button class="kayarine-auth-tab-btn-orange" data-tab="register" type="button">
                        ✨ 免費註冊
                    </button>
                </div>

                <div id="kayarine-login-tab" class="kayarine-auth-panel-orange active">
                    <div class="kayarine-auth-panel-content-orange">
                        <?php $this->render_login_form(); ?>
                    </div>
                </div>

                <div id="kayarine-register-tab" class="kayarine-auth-panel-orange">
                    <div class="kayarine-auth-panel-content-orange">
                        <?php $this->render_register_form(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_logged_in_account() {
        ?>
        <div class="kayarine-logged-in-account">
            <!-- No Navigation Menu - Direct to Kayarine Dashboard -->
            <div class="kayarine-account-content">
                <?php
                // Always show Kayarine dashboard - no menu navigation
                $this->render_kayarine_dashboard();
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get default menu items (before filtering)
     */
    private function get_default_menu_items() {
        return array(
            'dashboard'        => __( 'Dashboard', 'woocommerce' ),
            'orders'           => __( 'Orders', 'woocommerce' ),
            'downloads'        => __( 'Downloads', 'woocommerce' ),
            'edit-address'     => __( 'Addresses', 'woocommerce' ),
            'edit-account'     => __( 'Account Details', 'woocommerce' ),
            'customer-logout'  => __( 'Logout', 'woocommerce' ),
        );
    }
    
    /**
     * Get URL for menu item
     */
    private function get_menu_item_url( $key ) {
        $base_url = wc_get_account_endpoint_url( '' );
        
        switch ( $key ) {
            case 'kayarine-membership':
                return wc_get_account_endpoint_url( 'kayarine-membership' );
            case 'customer-logout':
            case 'logout':
                return wp_logout_url();
            default:
                return wc_get_account_endpoint_url( $key );
        }
    }
    
    /**
     * Check if menu item is active
     */
    private function is_menu_item_active( $key ) {
        if ( $key === 'kayarine-membership' ) {
            return ! empty( get_query_var( 'kayarine-membership' ) );
        }
        
        if ( $key === 'customer-logout' || $key === 'logout' ) {
            return false;
        }
        
        $current_endpoint = WC()->query->get_current_endpoint();
        return $current_endpoint === $key;
    }

    private function render_login_form() {
        $redirect_url = home_url( '/account/' );
        ?>
        <h2>登入您的帳戶</h2>
        <p class="kayarine-auth-subtitle">登入以管理您的預約、查看優惠和管理會員檔案。</p>

        <form id="kayarine-login-form" class="kayarine-login-form">
            <input type="hidden" name="action" value="kayarine_custom_login">
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_url ); ?>">
            <?php wp_nonce_field( 'kayarine_login_nonce', 'kayarine_login_nonce_field', false ); ?>
            
            <div class="kayarine-form-group">
                <label for="kayarine_user_login">電子郵件或用戶名</label>
                <input 
                    type="text" 
                    name="log" 
                    id="kayarine_user_login" 
                    class="kayarine-form-input"
                    placeholder="請輸入電子郵件或用戶名"
                    required
                >
            </div>

            <div class="kayarine-form-group">
                <label for="kayarine_user_pass">密碼</label>
                <input 
                    type="password" 
                    name="pwd" 
                    id="kayarine_user_pass" 
                    class="kayarine-form-input"
                    placeholder="請輸入密碼"
                    required
                >
            </div>

            <div class="kayarine-form-group kayarine-checkbox-group">
                <label for="kayarine_rememberme">
                    <input type="checkbox" name="rememberme" id="kayarine_rememberme" value="forever">
                    記住我
                </label>
            </div>

            <button type="submit" class="kayarine-btn-submit">登入</button>
            <div id="kayarine-login-message" class="kayarine-form-message"></div>
        </form>

        <div class="kayarine-auth-footer">
            <p><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">忘記密碼？</a></p>
        </div>
        <?php
    }

    private function render_register_form() {
        ?>
        <h2>建立帳戶</h2>
        <p class="kayarine-auth-subtitle">加入 Kayarine 會員社群，享受獨家優惠及積分獎勵</p>

        <div class="kayarine-benefits-grid">
            <div class="kayarine-benefit-card">
                <div class="kayarine-benefit-icon">✨</div>
                <h3>累積積分</h3>
                <p>每筆消費自動賺取積分</p>
            </div>
            <div class="kayarine-benefit-card">
                <div class="kayarine-benefit-icon">📅</div>
                <h3>輕鬆管理</h3>
                <p>在線管理及改期預約</p>
            </div>
            <div class="kayarine-benefit-card">
                <div class="kayarine-benefit-icon">💰</div>
                <h3>專屬折扣</h3>
                <p>升級享受會員等級優惠</p>
            </div>
        </div>

        <form id="kayarine-register-form" class="kayarine-register-form">
            <input type="hidden" name="action" value="kayarine_custom_register">
            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( home_url( '/account/' ) ); ?>">
            <?php wp_nonce_field( 'kayarine_register_nonce', 'kayarine_register_nonce_field', false ); ?>
            
            <div class="kayarine-form-group">
                <label for="kayarine_reg_email">電子郵件 *</label>
                <input 
                    type="email" 
                    name="email" 
                    id="kayarine_reg_email" 
                    class="kayarine-form-input"
                    placeholder="輸入電子郵件"
                    required
                >
            </div>

            <div class="kayarine-form-group">
                <label for="kayarine_reg_username">用戶名 *</label>
                <input 
                    type="text" 
                    name="username" 
                    id="kayarine_reg_username" 
                    class="kayarine-form-input"
                    placeholder="輸入用戶名"
                    required
                >
            </div>

            <div class="kayarine-form-group">
                <label for="kayarine_reg_password">密碼 *</label>
                <input 
                    type="password" 
                    name="password" 
                    id="kayarine_reg_password" 
                    class="kayarine-form-input"
                    placeholder="輸入密碼（至少 8 個字元）"
                    required
                >
            </div>

            <div class="kayarine-form-group kayarine-checkbox-group">
                <label for="kayarine_reg_agree">
                    <input type="checkbox" name="agree" id="kayarine_reg_agree" required>
                    我已閱讀並同意<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank">隱私政策</a>
                </label>
            </div>

            <button type="submit" class="kayarine-btn-submit">建立帳戶</button>
            <div id="kayarine-register-message" class="kayarine-form-message"></div>
        </form>

        <div class="kayarine-auth-footer">
            <p>已有帳戶？<button type="button" class="kayarine-switch-tab-btn" data-switch-to="login">直接登入</button></p>
        </div>
        <?php
    }

    public function handle_custom_login() {
        // Add logging for diagnostics
        error_log( 'Kayarine: Login attempt - POST data: ' . json_encode( $_POST ) );
        error_log( 'Kayarine: Nonce field received: ' . ( isset( $_POST['kayarine_login_nonce_field'] ) ? $_POST['kayarine_login_nonce_field'] : 'NOT SET' ) );
        
        // Verify nonce - but don't fail silently, log the actual status
        if ( isset( $_POST['kayarine_login_nonce_field'] ) ) {
            $nonce_result = wp_verify_nonce( $_POST['kayarine_login_nonce_field'], 'kayarine_login_nonce' );
            error_log( 'Kayarine: Nonce verification result: ' . $nonce_result );
            
            if ( ! $nonce_result ) {
                error_log( 'Kayarine: Nonce verification FAILED for login' );
                // Don't fail here - continue to show real error
            }
        } else {
            error_log( 'Kayarine: No nonce field in POST data' );
        }
        
        $username = isset( $_POST['log'] ) ? sanitize_user( $_POST['log'] ) : '';
        $password = isset( $_POST['pwd'] ) ? sanitize_text_field( $_POST['pwd'] ) : '';
        $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url( $_POST['redirect_to'] ) : home_url( '/account/' );
        
        error_log( 'Kayarine: Login processing - username: ' . $username );
        
        if ( empty( $username ) || empty( $password ) ) {
            wp_send_json_error( array( 'message' => '請輸入用戶名和密碼' ) );
        }
        
        $user = wp_authenticate( $username, $password );
        
        if ( is_wp_error( $user ) ) {
            wp_send_json_error( array( 'message' => '用戶名或密碼不正確' ) );
        }
        
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
        
        wp_send_json_success( array(
            'message' => '登入成功，正在重定向...',
            'redirect' => $redirect_to
        ) );
    }

    public function handle_custom_register() {
        check_ajax_referer( 'kayarine_register_nonce', 'kayarine_register_nonce_field' );
        
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $username = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : '';
        $password = isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';
        $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url( $_POST['redirect_to'] ) : home_url( '/account/' );
        
        if ( empty( $email ) || !is_email( $email ) ) {
            wp_send_json_error( array( 'message' => '請輸入有效的電子郵件' ) );
        }
        
        if ( empty( $username ) || strlen( $username ) < 3 ) {
            wp_send_json_error( array( 'message' => '用戶名至少需要 3 個字元' ) );
        }
        
        if ( empty( $password ) || strlen( $password ) < 8 ) {
            wp_send_json_error( array( 'message' => '密碼至少需要 8 個字元' ) );
        }
        
        if ( username_exists( $username ) ) {
            wp_send_json_error( array( 'message' => '用戶名已存在' ) );
        }
        
        if ( email_exists( $email ) ) {
            wp_send_json_error( array( 'message' => '電子郵件已被註冊' ) );
        }
        
        $user_id = wp_create_user( $username, $password, $email );
        
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => '註冊失敗：' . $user_id->get_error_message() ) );
        }
        
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );
        
        if ( class_exists( 'Kayarine_Membership' ) ) {
            update_user_meta( $user_id, Kayarine_Membership::META_POINTS, 0 );
            update_user_meta( $user_id, Kayarine_Membership::META_WALLET, 0 );
            update_user_meta( $user_id, Kayarine_Membership::META_SPEND, 0 );
        }
        
        wp_send_json_success( array(
            'message' => '註冊成功，正在重定向...',
            'redirect' => $redirect_to
        ) );
    }

    public function customize_account_menu( $items ) {
        // DIAGNOSTIC: Log all incoming menu items
        error_log( '[Kayarine 1.4.10] Menu Filter - START ================================' );
        error_log( '[Kayarine 1.4.10] Menu Filter - is_user_logged_in(): ' . ( is_user_logged_in() ? 'true' : 'false' ) );
        error_log( '[Kayarine 1.4.10] Menu Filter - Incoming items: ' . json_encode( array_keys( $items ) ) );
        error_log( '[Kayarine 1.4.10] Menu Filter - Total incoming items: ' . count( $items ) );
        
        if ( is_user_logged_in() ) {
            // Use whitelist approach: only include specific items
            // Exclude: orders, downloads, edit-address, edit-account, coupons
            $allowed_keys = array( 'dashboard', 'kayarine-membership', 'customer-logout', 'logout' );
            $new_items = array();
            
            // Iterate through allowed keys
            foreach ( $allowed_keys as $key ) {
                if ( isset( $items[ $key ] ) ) {
                    if ( $key === 'dashboard' ) {
                        $new_items[ $key ] = '🏅 Kayarine 會員中心';
                    } elseif ( $key === 'kayarine-membership' ) {
                        $new_items[ $key ] = '📊 我的進度';
                    } else {
                        $new_items[ $key ] = $items[ $key ];
                    }
                    error_log( '[Kayarine 1.4.10] Menu Filter - Added item: ' . $key );
                }
            }
            
            // If kayarine-membership not in items, add it anyway
            if ( ! isset( $new_items['kayarine-membership'] ) ) {
                $new_items['kayarine-membership'] = '📊 我的進度';
                error_log( '[Kayarine 1.4.10] Menu Filter - Added kayarine-membership (not found in items)' );
            }
            
            // DIAGNOSTIC: Log all outgoing menu items
            error_log( '[Kayarine 1.4.10] Menu Filter - Outgoing items: ' . json_encode( array_keys( $new_items ) ) );
            error_log( '[Kayarine 1.4.10] Menu Filter - Hidden items: ' . json_encode( array_diff( array_keys( $items ), array_keys( $new_items ) ) ) );
            error_log( '[Kayarine 1.4.10] Menu Filter - END ================================' );
            
            return $new_items;
        }
        
        error_log( '[Kayarine 1.4.10] Menu Filter - User not logged in, returning original items' );
        return $items;
    }

    /**
     * Render Kayarine custom dashboard (replaces WooCommerce account dashboard)
     * Based on member_dashboard_preview.html design (excluding store credit section)
     */
    private function render_kayarine_dashboard() {
        if ( ! is_user_logged_in() ) {
            return;
        }
        
        $user_id = get_current_user_id();
        $user = get_user_by( 'id', $user_id );
        
        if ( ! $user ) {
            return;
        }
        
        // Get membership data
        $points = 0;
        $tier = 'bronze';
        $tier_label = '銅級會員';
        $tier_info = array(
            'label' => '銅級會員',
            'next' => 2000,
            'reward' => '2%'
        );
        $spend = 0;
        
        if ( class_exists( 'Kayarine_Membership' ) ) {
            $points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );
            $tier = Kayarine_Membership::get_tier( $user_id );
            $tier_info = Kayarine_Membership::get_tier_info( $tier );
            $tier_label = $tier_info['label'];
            $spend = (float) get_user_meta( $user_id, Kayarine_Membership::META_SPEND, true );
        }
        
        // Get upcoming bookings
        $upcoming_bookings = $this->get_user_upcoming_bookings( $user_id );
        
        // Calculate progress
        $progress_percent = 0;
        $upgrade_msg = "您已是最高等級會員！";
        
        if ( $tier_info['next'] > 0 ) {
            $progress_percent = min( 100, ($spend / $tier_info['next']) * 100 );
            $remaining = $tier_info['next'] - $spend;
            $all_tiers = array('bronze', 'silver', 'gold');
            $current_idx = array_search( $tier, $all_tiers );
            $next_tier_slug = isset($all_tiers[$current_idx+1]) ? $all_tiers[$current_idx+1] : 'vip';
            $next_tier_info = Kayarine_Membership::get_tier_info( $next_tier_slug );
            $upgrade_msg = "再消費 <strong>HK$" . number_format($remaining) . "</strong> 即可升級" . $next_tier_info['label'] . "！";
        }
        
        // Determine avatar emoji based on tier
        $avatar_emoji = '👤';
        if ( $tier === 'gold' ) $avatar_emoji = '👑';
        elseif ( $tier === 'silver' ) $avatar_emoji = '⭐';
        elseif ( $tier === 'vip' ) $avatar_emoji = '💎';
        
        // Get tier badge class
        $tier_badge_class = $tier;
        
        ?>
        <style>
            :root {
                --primary-color: #3182ce;
                --primary-dark: #2c5282;
                --accent-color: #ed8936;
                --bg-color: #f7fafc;
                --card-bg: #ffffff;
                --text-main: #2d3748;
                --text-sub: #718096;
                --border-color: #e2e8f0;
            }
        </style>
        <div class="kayarine-member-dashboard">
            <!-- SIDEBAR -->
            <aside class="kayarine-dashboard-card kayarine-sidebar">
                <!-- Profile Header -->
                <div class="kayarine-profile-header">
                    <div class="kayarine-user-avatar"><?php echo $avatar_emoji; ?></div>
                    <div class="kayarine-user-name"><?php echo esc_html( $user->display_name ); ?></div>
                    <div class="kayarine-tier-badge kayarine-tier-badge-<?php echo esc_attr( $tier_badge_class ); ?>">
                        <?php echo esc_html( $tier_label ); ?>
                    </div>
                </div>

                <!-- Stats Grid (Points only - NO store credit) -->
                <div class="kayarine-stats-grid">
                    <div class="kayarine-stat-box">
                        <div class="kayarine-stat-label">現有積分</div>
                        <div class="kayarine-stat-value kayarine-points-val"><?php echo number_format( $points ); ?></div>
                    </div>
                </div>

                <!-- Progress Section -->
                <div class="kayarine-progress-section">
                    <div class="kayarine-progress-label">
                        <span>目前消費: HK$<?php echo number_format( $spend ); ?></span>
                        <span>目標: HK$<?php echo number_format( $tier_info['next'] ); ?></span>
                    </div>
                    <div class="kayarine-progress-bar-bg">
                        <div class="kayarine-progress-bar-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                    </div>
                    <div class="kayarine-upgrade-hint"><?php echo $upgrade_msg; ?></div>
                </div>
                
                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                
                <!-- Benefits -->
                <div class="kayarine-benefits">
                    <strong style="font-size: 0.9rem; color: #4a5568;">目前權益：</strong>
                    <ul style="padding-left: 20px; margin-top: 5px; font-size: 0.9rem; color: #4a5568;">
                        <li>每筆消費享 <?php echo esc_html( $tier_info['reward'] ?? '2%' ); ?> 積分回饋</li>
                        <li>積分可折抵現金</li>
                        <li>早上 9:00 前免費改期</li>
                    </ul>
                </div>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="kayarine-main-content">
                <!-- Upcoming Bookings -->
                <div class="kayarine-dashboard-card">
                    <div class="kayarine-card-title">我的預約 Upcoming Bookings</div>
                    
                    <?php if ( ! empty( $upcoming_bookings ) ): ?>
                        <div class="kayarine-booking-list">
                            <?php foreach ( $upcoming_bookings as $booking ): ?>
                                <div class="kayarine-booking-item">
                                    <div class="kayarine-booking-info">
                                        <span class="kayarine-booking-date">
                                            <?php echo esc_html( $this->format_booking_date( $booking ) ); ?>
                                        </span>
                                        <div class="kayarine-booking-details">
                                            <?php echo esc_html( $booking['service'] ); ?>
                                            <span class="kayarine-booking-status"><?php echo esc_html( $booking['status'] ); ?></span>
                                        </div>
                                    </div>
                                    <div class="kayarine-booking-actions">
                                        <button class="kayarine-btn-action kayarine-btn-reschedule" data-booking-id="<?php echo esc_attr( $booking['order_id'] ); ?>">改期</button>
                                        <button class="kayarine-btn-action kayarine-btn-cancel" data-booking-id="<?php echo esc_attr( $booking['order_id'] ); ?>">取消</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #a0aec0; margin-top: 20px; font-size: 0.9rem;">
                            沒有更多預約了
                        </p>
                    <?php endif; ?>
                </div>
            </main>
        </div>

        <style>
            .kayarine-member-dashboard {
                max-width: 1000px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: 1fr;
                gap: 24px;
            }

            @media (min-width: 768px) {
                .kayarine-member-dashboard {
                    grid-template-columns: 300px 1fr;
                }
            }

            /* Cards */
            .kayarine-dashboard-card {
                background: var(--card-bg);
                border-radius: 16px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
                padding: 24px;
                border: 1px solid var(--border-color);
            }

            .kayarine-card-title {
                font-size: 1.25rem;
                font-weight: 700;
                margin-bottom: 20px;
                color: var(--text-main);
                border-bottom: 2px solid var(--primary-color);
                padding-bottom: 10px;
                display: inline-block;
            }

            /* Profile / Sidebar */
            .kayarine-profile-header {
                text-align: center;
                margin-bottom: 20px;
            }

            .kayarine-user-avatar {
                width: 80px;
                height: 80px;
                background: #cbd5e0;
                border-radius: 50%;
                margin: 0 auto 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2rem;
                color: white;
            }

            .kayarine-user-name {
                font-size: 1.2rem;
                font-weight: 700;
                color: var(--text-main);
            }

            .kayarine-tier-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 0.85rem;
                font-weight: 600;
                margin-top: 5px;
                background: #E6FFFA;
                color: #2C7A7B;
                border: 1px solid #B2F5EA;
            }
            
            .kayarine-tier-badge-bronze { background: #fffaf0; color: #9c4221; border-color: #fbd38d; }
            .kayarine-tier-badge-silver { background: #edf2f7; color: #4a5568; border-color: #cbd5e0; }
            .kayarine-tier-badge-gold { background: #fffff0; color: #b7791f; border-color: #f6e05e; }
            .kayarine-tier-badge-vip { background: #2d3748; color: #fff; border-color: #1a202c; }

            /* Stats Grid */
            .kayarine-stats-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 16px;
                margin-bottom: 20px;
            }

            .kayarine-stat-box {
                background: #f7fafc;
                padding: 15px;
                border-radius: 12px;
                text-align: center;
            }

            .kayarine-stat-label {
                font-size: 0.85rem;
                color: var(--text-sub);
                margin-bottom: 4px;
            }

            .kayarine-stat-value {
                font-size: 1.2rem;
                font-weight: 800;
                color: var(--primary-dark);
            }

            .kayarine-points-val { color: var(--accent-color); }

            /* Progress Bar */
            .kayarine-progress-section {
                margin-top: 20px;
            }

            .kayarine-progress-label {
                display: flex;
                justify-content: space-between;
                font-size: 0.85rem;
                margin-bottom: 8px;
                color: var(--text-sub);
            }

            .kayarine-progress-bar-bg {
                width: 100%;
                height: 10px;
                background: #edf2f7;
                border-radius: 5px;
                overflow: hidden;
            }

            .kayarine-progress-bar-fill {
                height: 100%;
                background: linear-gradient(90deg, #4299e1, #667eea);
                border-radius: 5px;
                transition: width 0.5s ease;
            }

            .kayarine-upgrade-hint {
                font-size: 0.8rem;
                color: var(--primary-color);
                margin-top: 8px;
                text-align: center;
            }

            /* Booking List */
            .kayarine-booking-list {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }

            .kayarine-booking-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16px;
                border: 1px solid #edf2f7;
                border-radius: 12px;
                background: #fff;
                transition: box-shadow 0.2s;
            }

            .kayarine-booking-item:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }

            .kayarine-booking-info {
                display: flex;
                flex-direction: column;
                flex: 1;
            }

            .kayarine-booking-date {
                font-weight: 700;
                font-size: 1.1rem;
                color: var(--text-main);
            }

            .kayarine-booking-details {
                font-size: 0.9rem;
                color: var(--text-sub);
                margin-top: 4px;
            }

            .kayarine-booking-status {
                font-size: 0.8rem;
                padding: 2px 8px;
                border-radius: 4px;
                margin-left: 8px;
                background: #C6F6D5;
                color: #22543D;
                display: inline-block;
                margin-top: 4px;
            }

            .kayarine-booking-actions {
                display: flex;
                gap: 8px;
                margin-left: 16px;
            }

            .kayarine-btn-action {
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                border: none;
                transition: opacity 0.2s;
            }

            .kayarine-btn-reschedule {
                background: var(--primary-color);
                color: white;
            }

            .kayarine-btn-cancel {
                background: #FED7D7;
                color: #C53030;
            }

            .kayarine-btn-action:hover {
                opacity: 0.9;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .kayarine-member-dashboard {
                    grid-template-columns: 1fr;
                }

                .kayarine-booking-item {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .kayarine-booking-actions {
                    margin-left: 0;
                    margin-top: 12px;
                    width: 100%;
                }

                .kayarine-btn-action {
                    flex: 1;
                }
            }

            @media (max-width: 480px) {
                .kayarine-dashboard-card {
                    padding: 16px;
                }

                .kayarine-card-title {
                    font-size: 1.1rem;
                }

                .kayarine-booking-item {
                    padding: 12px;
                }

                .kayarine-user-avatar {
                    width: 60px;
                    height: 60px;
                    font-size: 1.5rem;
                }

                .kayarine-user-name {
                    font-size: 1.1rem;
                }
            }
        </style>
        <?php
    }

    /**
     * Get user's upcoming bookings from WooCommerce orders
     */
    private function get_user_upcoming_bookings( $user_id ) {
        $bookings = array();
        
        // Query user's orders
        // ✅ 修復：包含 pending 狀態確保新訂單立即顯示
        $orders = wc_get_orders( array(
            'customer' => $user_id,
            'status'   => array( 'pending', 'processing', 'completed', 'on-hold' ),
            'limit'    => 20,
        ) );
        
        if ( ! empty( $orders ) ) {
            foreach ( $orders as $order ) {
                // Get order items
                foreach ( $order->get_items() as $item ) {
                    // Skip if booking is cancelled
                    if ( $item->get_meta( '_kayarine_booking_cancelled' ) === 'yes' ) {
                        error_log( '[Kayarine] Skipping cancelled booking item: ' . $item->get_id() );
                        continue;
                    }
                    
                    // Get kayarine_booking_date meta if it exists
                    $booking_date = $item->get_meta( '_kayarine_booking_date' );
                    
                    if ( ! empty( $booking_date ) ) {
                        // Only show future bookings
                        $booking_timestamp = strtotime( $booking_date );
                        if ( $booking_timestamp && $booking_timestamp > current_time( 'timestamp' ) ) {
                            $bookings[] = array(
                                'id' => $item->get_id(),
                                'order_id' => $order->get_id(),
                                'date' => $booking_date,
                                'service' => $item->get_name(),
                                'status' => '已付款'
                            );
                            error_log( '[Kayarine] Added upcoming booking: ' . $item->get_id() . ' on ' . $booking_date . ' (Order: ' . $order->get_id() . ')' );
                        }
                    }
                }
            }
        }
        error_log( '[Kayarine] Total upcoming bookings found: ' . count( $bookings ) );
        
        // Sort by date
        usort( $bookings, function( $a, $b ) {
            return strtotime( $a['date'] ) - strtotime( $b['date'] );
        } );
        
        return array_slice( $bookings, 0, 10 );
    }

    /**
     * Format booking date for display
     */
    private function format_booking_date( $booking ) {
        $date = strtotime( $booking['date'] );
        if ( $date ) {
            return date_i18n( 'Y年 n月 j日 (l)', $date );
        }
        return $booking['date'];
    }

    public function render_membership_dashboard() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user_id = get_current_user_id();
        $user = get_user_by( 'id', $user_id );
        
        // DIAGNOSTIC: Log user ID for troubleshooting
        error_log( '[Kayarine 1.4.5] Membership Dashboard - Current user ID: ' . $user_id );
        error_log( '[Kayarine 1.4.5] Membership Dashboard - User email: ' . $user->user_email );
        
        // DIAGNOSTIC: Query upcoming bookings
        global $wpdb;
        $upcoming_bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_content FROM {$wpdb->posts}
                 WHERE post_type = 'kayarine_booking'
                 AND post_author = %d
                 AND post_status = 'publish'
                 LIMIT 10",
                $user_id
            )
        );
        error_log( '[Kayarine 1.4.5] Membership Dashboard - Upcoming bookings count: ' . count( $upcoming_bookings ) );
        if ( ! empty( $upcoming_bookings ) ) {
            error_log( '[Kayarine 1.4.5] Membership Dashboard - Booking IDs: ' . json_encode( array_column( $upcoming_bookings, 'ID' ) ) );
        } else {
            error_log( '[Kayarine 1.4.5] Membership Dashboard - No kayarine_booking posts found for user ' . $user_id );
        }
        
        // Check if booking tables exist
        $tables = $wpdb->get_col( "SHOW TABLES LIKE '%booking%'" );
        error_log( '[Kayarine 1.4.5] Membership Dashboard - Available booking tables: ' . json_encode( $tables ) );
        
        $points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );
        $tier = Kayarine_Membership::get_tier( $user_id );
        $spend = (float) get_user_meta( $user_id, Kayarine_Membership::META_SPEND, true );
        $tier_info = Kayarine_Membership::get_tier_info( $tier );
        
        $next_target = $tier_info['next'];
        $progress_percent = 0;
        $upgrade_msg = "您已是最高等級會員！";
        
        if ( $next_target > 0 ) {
            $progress_percent = min( 100, ($spend / $next_target) * 100 );
            $remaining = $next_target - $spend;
            $all_tiers = array('bronze', 'silver', 'gold');
            $current_idx = array_search( $tier, $all_tiers );
            $next_tier_slug = isset($all_tiers[$current_idx+1]) ? $all_tiers[$current_idx+1] : 'vip';
            $next_tier_info = Kayarine_Membership::get_tier_info( $next_tier_slug );
            $upgrade_msg = "再消費 <strong>HK$" . number_format($remaining) . "</strong> 即可升級" . $next_tier_info['label'] . "！";
        }
        ?>
        <div class="kayarine-membership-dashboard">
            <h2>會員進度</h2>
            
            <div class="kayarine-membership-header">
                <div class="kayarine-membership-avatar">👤</div>
                <div class="kayarine-membership-info">
                    <div class="kayarine-membership-name"><?php echo esc_html( $user->display_name ); ?></div>
                    <div class="kayarine-membership-tier"><?php echo $tier_info['label']; ?> <?php if($tier=='vip') echo '👑'; ?></div>
                </div>
            </div>

            <div class="kayarine-membership-stats">
                <div class="kayarine-stat-box">
                    <div class="kayarine-stat-label">現有積分</div>
                    <div class="kayarine-stat-value"><?php echo number_format($points); ?></div>
                </div>
            </div>

            <?php if ( $next_target > 0 ): ?>
            <div class="kayarine-membership-progress">
                <div class="kayarine-progress-label">
                    <span>目前消費: HK$<?php echo number_format($spend); ?></span>
                    <span>目標: HK$<?php echo number_format($next_target); ?></span>
                </div>
                <div class="kayarine-progress-bar-bg">
                    <div class="kayarine-progress-bar-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                </div>
                <div class="kayarine-progress-hint"><?php echo $upgrade_msg; ?></div>
            </div>
            <?php endif; ?>

            <div class="kayarine-membership-benefits">
                <h3>目前權益</h3>
                <ul>
                    <li>✓ 每筆消費享 2% 積分回饋</li>
                    <li>✓ 積分可折抵現金</li>
                    <li>✓ 早上 9:00 前免費改期</li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function enqueue_custom_styles() {
        wp_enqueue_style( 'kayarine-booking-css' );
        wp_add_inline_style( 'kayarine-booking-css', $this->get_custom_css() );
        
        wp_enqueue_script( 'jquery' );
        
        // Ensure ajaxurl is defined for JavaScript
        wp_localize_script( 'jquery', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
        
        // Localize booking nonce and other data
        wp_localize_script( 'jquery', 'kayarineBooking', array(
            'nonce' => wp_create_nonce( 'kayarine_booking_nonce' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ) );
        
        // Add inline script with CSP nonce if available
        $script_tag = '';
        if ( function_exists( 'wp_get_csp_nonce' ) ) {
            $nonce = wp_get_csp_nonce( 'script' );
            if ( $nonce ) {
                $script_tag = "nonce='" . esc_attr( $nonce ) . "'";
            }
        }
        
        wp_add_inline_script( 'jquery', $this->get_ajax_handler_js() );
    }

    private function get_ajax_handler_js() {
        return <<<'JS'
(function($) {
    $(document).ready(function() {
        // Diagnostic: Check how many login forms exist
        var loginFormCount = $('#kayarine-login-form').length;
        console.log('[Kayarine Debug] Login forms found: ' + loginFormCount);
        
        // Diagnostic: Check nonce fields
        var nonceFields = $('input[name="kayarine_login_nonce_field"]');
        console.log('[Kayarine Debug] Nonce fields found: ' + nonceFields.length);
        nonceFields.each(function(i) {
            console.log('[Kayarine Debug] Nonce field ' + i + ' value: ' + $(this).val());
        });
        
        // Booking Actions: Reschedule Button Handler
        $(document).on('click', '.kayarine-btn-reschedule', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var bookingId = $btn.data('booking-id');
            var $bookingItem = $btn.closest('.kayarine-booking-item');
            var currentDate = $bookingItem.find('.kayarine-booking-date').text();
            
            console.log('[Kayarine] Reschedule button clicked for booking ' + bookingId);
            console.log('[Kayarine] Current date: ' + currentDate);
            console.log('[Kayarine] Nonce available: ' + (kayarineBooking && kayarineBooking.nonce ? 'yes' : 'no'));
            
            // Add visual feedback
            $btn.prop('disabled', true).text('加載中...');
            
            // Show date picker modal after a brief delay
            setTimeout(function() {
                $btn.prop('disabled', false).text('改期');
                showRescheduleModal(bookingId, currentDate);
            }, 300);
        });
        
        // Booking Actions: Cancel Button Handler
        $(document).on('click', '.kayarine-btn-cancel', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var bookingId = $btn.data('booking-id');
            
            if ( ! confirm('確定要取消此預約嗎？此操作無法復原。') ) {
                return;
            }
            
            console.log('[Kayarine] Cancel button clicked for booking ' + bookingId);
            console.log('[Kayarine] Sending nonce: ' + (kayarineBooking && kayarineBooking.nonce ? kayarineBooking.nonce : 'MISSING'));
            
            // Add visual feedback
            $btn.prop('disabled', true).text('取消中...');
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'kayarine_cancel_booking',
                    order_id: bookingId,
                    nonce: kayarineBooking.nonce
                },
                success: function(response) {
                    console.log('[Kayarine] Cancel response: ' + JSON.stringify(response));
                    if (response.success) {
                        alert(response.data.message);
                        // Reload page to show updated bookings
                        location.reload();
                    } else {
                        alert('取消失敗：' + response.data.message);
                        $btn.prop('disabled', false).text('取消');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[Kayarine] Cancel error: ' + error);
                    console.error('[Kayarine] Response text: ' + xhr.responseText);
                    alert('出錯，請重試');
                    $btn.prop('disabled', false).text('取消');
                }
            });
        });
        
        // Function to show reschedule modal
        function showRescheduleModal(bookingId, currentDate) {
            var html = '<div class="kayarine-modal-overlay kayarine-reschedule-overlay">' +
                       '<div class="kayarine-modal-content">' +
                       '<div class="kayarine-modal-header">' +
                       '<h3>改期預約</h3>' +
                       '<button class="kayarine-modal-close">&times;</button>' +
                       '</div>' +
                       '<div class="kayarine-modal-body">' +
                       '<p>當前預約日期: <strong>' + currentDate + '</strong></p>' +
                       '<p style="margin-top: 15px; margin-bottom: 10px;">請選擇新日期:</p>' +
                       '<input type="date" id="kayarine-new-date" class="kayarine-form-input" required>' +
                       '</div>' +
                       '<div class="kayarine-modal-footer">' +
                       '<button class="kayarine-btn-cancel" id="kayarine-modal-cancel">取消</button>' +
                       '<button class="kayarine-btn-submit" id="kayarine-modal-confirm">確認改期</button>' +
                       '</div>' +
                       '</div>' +
                       '</div>';
            
            $('body').append(html);
            
            // Set minimum date to tomorrow
            var tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            var minDate = tomorrow.toISOString().split('T')[0];
            $('#kayarine-new-date').attr('min', minDate);
            
            // Focus on input
            $('#kayarine-new-date').focus();
            
            // Close button handler
            $('.kayarine-modal-close, #kayarine-modal-cancel, .kayarine-reschedule-overlay').on('click', function(e) {
                if (e.target === this || $(this).hasClass('kayarine-modal-close') || $(this).attr('id') === 'kayarine-modal-cancel') {
                    $('.kayarine-reschedule-overlay').remove();
                }
            });
            
           // Confirm button handler
           $('#kayarine-modal-confirm').on('click', function() {
               var $confirmBtn = $(this);
               var newDate = $('#kayarine-new-date').val();
               
               if ( ! newDate ) {
                   alert('請選擇新日期');
                   return;
               }
               
               console.log('[Kayarine] Submitting reschedule request');
               console.log('[Kayarine] Booking ID: ' + bookingId);
               console.log('[Kayarine] New date: ' + newDate);
               console.log('[Kayarine] Nonce: ' + (kayarineBooking && kayarineBooking.nonce ? kayarineBooking.nonce : 'MISSING'));
               
               // Add visual feedback
               $confirmBtn.prop('disabled', true).text('改期中...');
               $('#kayarine-new-date').prop('disabled', true);
               
               // Send AJAX request
               $.ajax({
                   url: ajaxurl,
                   type: 'POST',
                   data: {
                       action: 'kayarine_reschedule_booking',
                       order_id: bookingId,
                       new_date: newDate,
                       nonce: kayarineBooking.nonce
                   },
                   success: function(response) {
                       console.log('[Kayarine] Reschedule response: ' + JSON.stringify(response));
                       if (response.success) {
                           alert(response.data.message);
                           // Reload page to show updated bookings
                           location.reload();
                       } else {
                           alert('改期失敗：' + response.data.message);
                           $confirmBtn.prop('disabled', false).text('確認改期');
                           $('#kayarine-new-date').prop('disabled', false);
                       }
                   },
                   error: function(xhr, status, error) {
                       console.error('[Kayarine] Reschedule error: ' + error);
                       console.error('[Kayarine] Response text: ' + xhr.responseText);
                       alert('出錯，請重試');
                       $confirmBtn.prop('disabled', false).text('確認改期');
                       $('#kayarine-new-date').prop('disabled', false);
                   }
               });
           });
        }
        
        $('.kayarine-auth-tab-btn-orange').on('click', function(e) {
            e.preventDefault();
            var tabName = $(this).data('tab');
            
            $('.kayarine-auth-tab-btn-orange').removeClass('active');
            $('.kayarine-auth-panel-orange').removeClass('active');
            
            $(this).addClass('active');
            $('#kayarine-' + tabName + '-tab').addClass('active');
        });

        $('.kayarine-switch-tab-btn').on('click', function(e) {
            e.preventDefault();
            var targetTab = $(this).data('switch-to');
            
            $('.kayarine-auth-tab-btn-orange').removeClass('active');
            $('.kayarine-auth-panel-orange').removeClass('active');
            
            $('.kayarine-auth-tab-btn-orange[data-tab="' + targetTab + '"]').addClass('active');
            $('#kayarine-' + targetTab + '-tab').addClass('active');
        });

        $('#kayarine-login-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var $message = $('#kayarine-login-message');
            
            // Diagnostic: Detailed form data logging
            var nonceValue = $form.find('input[name="kayarine_login_nonce_field"]').val();
            console.log('[Kayarine Debug] Login form submitted');
            console.log('[Kayarine Debug] Form ID: ' + $form.attr('id'));
            console.log('[Kayarine Debug] Nonce value from this form: ' + nonceValue);
            console.log('[Kayarine Debug] Username: ' + $form.find('input[name="log"]').val());
            
            $btn.prop('disabled', true).text('登入中...');
            $message.html('').hide();
            
            var postData = {
                action: 'kayarine_custom_login',
                log: $form.find('input[name="log"]').val(),
                pwd: $form.find('input[name="pwd"]').val(),
                redirect_to: $form.find('input[name="redirect_to"]').val(),
                kayarine_login_nonce_field: nonceValue
            };
            
            console.log('[Kayarine Debug] POST data: ' + JSON.stringify(postData));
            
            $.post(ajaxurl, postData, function(response) {
                if (response.success) {
                    $message.html('<span style="color: #4caf50;">' + response.data.message + '</span>').show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    $message.html('<span style="color: #f44336;">' + response.data.message + '</span>').show();
                    $btn.prop('disabled', false).text('登入');
                }
            }).fail(function() {
                $message.html('<span style="color: #f44336;">出錯，請重試</span>').show();
                $btn.prop('disabled', false).text('登入');
            });
        });

        $('#kayarine-register-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var $message = $('#kayarine-register-message');
            
            // Prevent double submission
            if ($btn.prop('disabled')) {
                return false;
            }
            
            // Diagnostic: Detailed form data logging
            var nonceValue = $form.find('input[name="kayarine_register_nonce_field"]').val();
            console.log('[Kayarine Debug] Register form submitted');
            console.log('[Kayarine Debug] Form ID: ' + $form.attr('id'));
            console.log('[Kayarine Debug] Nonce value from this form: ' + nonceValue);
            console.log('[Kayarine Debug] Email: ' + $form.find('input[name="email"]').val());
            
            $btn.prop('disabled', true).text('註冊中...');
            $message.html('').hide();
            
            var postData = {
                action: 'kayarine_custom_register',
                email: $form.find('input[name="email"]').val(),
                username: $form.find('input[name="username"]').val(),
                password: $form.find('input[name="password"]').val(),
                redirect_to: $form.find('input[name="redirect_to"]').val(),
                kayarine_register_nonce_field: nonceValue
            };
            
            console.log('[Kayarine Debug] POST data: ' + JSON.stringify(postData));
            
            $.post(ajaxurl, postData, function(response) {
                if (response.success) {
                    $message.html('<span style="color: #4caf50;">' + response.data.message + '</span>').show();
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 1000);
                } else {
                    $message.html('<span style="color: #f44336;">' + response.data.message + '</span>').show();
                    $btn.prop('disabled', false).text('建立帳戶');
                }
            }).fail(function() {
                $message.html('<span style="color: #f44336;">出錯，請重試</span>').show();
                $btn.prop('disabled', false).text('建立帳戶');
            });
        });
    });
})(jQuery);
JS;
    }

    /**
     * AJAX Handler: Reschedule Booking
     */
    public function ajax_reschedule_booking() {
        error_log( '[Kayarine Reschedule] Called' );
        
        // Verify nonce
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $nonce_result = wp_verify_nonce( $nonce, 'kayarine_booking_nonce' );
        
        error_log( '[Kayarine Reschedule] Nonce result: ' . $nonce_result );
        
        if ( ! $nonce_result ) {
            error_log( '[Kayarine Reschedule] Nonce failed' );
            wp_send_json_error( array( 'message' => '安全驗證失敗，請重新整理頁面後再試' ) );
        }
        
        if ( ! is_user_logged_in() ) {
            error_log( '[Kayarine Reschedule] User not logged in' );
            wp_send_json_error( array( 'message' => '請先登入' ) );
        }
        
        $user_id = get_current_user_id();
        $booking_id = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;
        $new_date = isset( $_POST['new_date'] ) ? sanitize_text_field( $_POST['new_date'] ) : '';
        
        error_log( "[Kayarine Reschedule] User: $user_id, Booking: $booking_id, NewDate: $new_date" );
        
        if ( ! $booking_id || ! $new_date ) {
            error_log( '[Kayarine Reschedule] Missing booking_id or new_date' );
            wp_send_json_error( array( 'message' => '請提供有效的預約 ID 和日期' ) );
        }
        
        // Validate new date is in future
        $new_timestamp = strtotime( $new_date );
        if ( ! $new_timestamp || $new_timestamp <= current_time( 'timestamp' ) ) {
            error_log( '[Kayarine Reschedule] New date is not in future' );
            wp_send_json_error( array( 'message' => '新日期必須是未來日期' ) );
        }
        
        // ✅ 新增：檢查新日期是否為 blackout 日期
        if ( class_exists( 'Kayarine_Inventory' ) ) {
            if ( Kayarine_Inventory::is_blackout( $new_date ) ) {
                error_log( '[Kayarine Reschedule] New date is blackout' );
                wp_send_json_error( array( 'message' => '選定日期不可用（已被阻止或超賣），請選擇其他日期' ) );
            }
        }
        
        // Verify user owns this booking
        $orders = wc_get_orders( array(
            'customer' => $user_id,
            'status'   => array( 'completed', 'processing' ),
            'limit'    => 50,
        ) );
        
        error_log( '[Kayarine Reschedule] Found ' . count( $orders ) . ' orders' );
        
        $booking_found = false;
        foreach ( $orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                if ( $item->get_id() == $booking_id ) {
                    error_log( '[Kayarine Reschedule] Found booking item in order ' . $order->get_id() );
                    $booking_found = true;
                    
                    // Check if booking is already cancelled
                    if ( $item->get_meta( '_kayarine_booking_cancelled' ) === 'yes' ) {
                        error_log( '[Kayarine Reschedule] Booking already cancelled' );
                        wp_send_json_error( array( 'message' => '該預約已取消，無法改期' ) );
                    }
                    
                    // Check if it's after 9:00 AM (reschedule cutoff)
                    $booking_date = $item->get_meta( '_kayarine_booking_date' );
                    if ( $booking_date ) {
                        $booking_time = strtotime( $booking_date );
                        $today_9am = strtotime( date( 'Y-m-d 09:00:00' ) );
                        
                        if ( current_time( 'timestamp' ) > $today_9am && date( 'Y-m-d', $booking_time ) == date( 'Y-m-d' ) ) {
                            error_log( '[Kayarine Reschedule] After 9:00 AM cutoff' );
                            wp_send_json_error( array( 'message' => '今日預約在上午 9:00 後無法免費改期' ) );
                        }
                    }
                    
                    // ✅ 新增：驗證新日期是否有足夠庫存
                    if ( class_exists( 'Kayarine_Inventory' ) ) {
                        $product_id = (int) $item->get_product_id();
                        $qty = (int) $item->get_quantity();
                        
                        // 獲取新日期的庫存使用情況
                        $daily_usage = Kayarine_Inventory::get_daily_usage( $new_date );
                        $limits = Kayarine_Inventory::get_limits();
                        $limit = isset( $limits[$product_id] ) ? $limits[$product_id] : 0;
                        $current_used = isset( $daily_usage[$product_id] ) ? $daily_usage[$product_id] : 0;
                        
                        // 檢查是否有足夠庫存
                        if ( ( $current_used + $qty ) > $limit ) {
                            error_log( "[Kayarine Reschedule] Insufficient inventory - Product: $product_id, Qty: $qty, Used: $current_used, Limit: $limit" );
                            wp_send_json_error( array( 'message' => '選定日期該設備庫存不足，請選擇其他日期' ) );
                        }
                        
                        error_log( "[Kayarine Reschedule] Inventory check passed - Product: $product_id, Qty: $qty, Used: $current_used, Limit: $limit" );
                    }
                    
                    // Update booking date
                    $item->update_meta_data( '_kayarine_booking_date', $new_date );
                    $item->update_meta_data( '_kayarine_rescheduled_at', current_time( 'mysql' ) );
                    $item->save();
                    
                    // ✅ 新增：清除庫存快取確保實時更新
                    if ( class_exists( 'Kayarine_Inventory' ) ) {
                        Kayarine_Inventory::clear_cache( $new_date );
                        // Also clear old date cache
                        if ( $booking_date ) {
                            Kayarine_Inventory::clear_cache( $booking_date );
                        }
                    }
                    
                    error_log( '[Kayarine Reschedule] Successfully updated to ' . $new_date );
                    
                    wp_send_json_success( array(
                        'message' => '預約已成功改期',
                        'new_date' => $this->format_booking_date( array( 'date' => $new_date ) )
                    ) );
                    return;
                }
            }
        }
        
        if ( ! $booking_found ) {
            error_log( '[Kayarine Reschedule] Booking not found' );
            wp_send_json_error( array( 'message' => '找不到該預約' ) );
        }
    }
    
    /**
     * AJAX Handler: Cancel Booking
     */
    public function ajax_cancel_booking() {
        error_log( '[Kayarine Cancel] Called' );
        
        // Verify nonce
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $nonce_result = wp_verify_nonce( $nonce, 'kayarine_booking_nonce' );
        
        error_log( '[Kayarine Cancel] Nonce result: ' . $nonce_result );
        
        if ( ! $nonce_result ) {
            error_log( '[Kayarine Cancel] Nonce failed' );
            wp_send_json_error( array( 'message' => '安全驗證失敗，請重新整理頁面後再試' ) );
        }
        
        if ( ! is_user_logged_in() ) {
            error_log( '[Kayarine Cancel] User not logged in' );
            wp_send_json_error( array( 'message' => '請先登入' ) );
        }
        
        $user_id = get_current_user_id();
        $booking_id = isset( $_POST['booking_id'] ) ? intval( $_POST['booking_id'] ) : 0;
        
        error_log( "[Kayarine Cancel] User: $user_id, Booking: $booking_id" );
        
        if ( ! $booking_id ) {
            error_log( '[Kayarine Cancel] Missing booking_id' );
            wp_send_json_error( array( 'message' => '請提供有效的預約 ID' ) );
        }
        
        // Verify user owns this booking
        $orders = wc_get_orders( array(
            'customer' => $user_id,
            'status'   => array( 'completed', 'processing' ),
            'limit'    => 50,
        ) );
        
        error_log( '[Kayarine Cancel] Found ' . count( $orders ) . ' orders' );
        
        $booking_found = false;
        foreach ( $orders as $order ) {
            foreach ( $order->get_items() as $item ) {
                if ( $item->get_id() == $booking_id ) {
                    error_log( '[Kayarine Cancel] Found booking item in order ' . $order->get_id() );
                    $booking_found = true;
                    
                    // Check if already cancelled
                    if ( $item->get_meta( '_kayarine_booking_cancelled' ) === 'yes' ) {
                        error_log( '[Kayarine Cancel] Booking already cancelled' );
                        wp_send_json_error( array( 'message' => '該預約已取消' ) );
                    }
                    
                    // Get item price (original order price before any discounts)
                    $item_price = (float) $item->get_subtotal();
                    error_log( "[Kayarine Cancel] Item price: $item_price" );
                    
                    // Mark as cancelled - leave record
                    $item->update_meta_data( '_kayarine_booking_cancelled', 'yes' );
                    $item->update_meta_data( '_kayarine_cancelled_at', current_time( 'mysql' ) );
                    $item->save();
                    error_log( '[Kayarine Cancel] Marked as cancelled' );
                    
                    // Refund points based on what was earned
                    if ( class_exists( 'Kayarine_Membership' ) ) {
                        $tier = Kayarine_Membership::get_tier( $user_id );
                        $tier_info = Kayarine_Membership::get_tier_info( $tier );
                        $reward_rate = floatval( str_replace( '%', '', $tier_info['reward'] ) ) / 100;
                        $refund_points = intval( $item_price * $reward_rate );
                        
                        error_log( "[Kayarine Cancel] Tier: $tier, Rate: $reward_rate, Refund: $refund_points" );
                        
                        if ( $refund_points > 0 ) {
                            $current_points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );
                            $new_points = $current_points + $refund_points;
                            update_user_meta( $user_id, Kayarine_Membership::META_POINTS, $new_points );
                            error_log( "[Kayarine Cancel] Points refunded - Old: $current_points, Refund: $refund_points, New: $new_points" );
                        }
                    }
                    
                    wp_send_json_success( array(
                        'message' => '預約已成功取消，積分已退回'
                    ) );
                    return;
                }
            }
        }
        
        if ( ! $booking_found ) {
            error_log( '[Kayarine Cancel] Booking not found' );
            wp_send_json_error( array( 'message' => '找不到該預約' ) );
        }
    }

   private function get_custom_css() {
       return <<<'CSS'
/* Modal Styles */
.kayarine-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.kayarine-reschedule-overlay {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.kayarine-modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.kayarine-modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.kayarine-modal-header h3 {
    margin: 0;
    font-size: 1.3rem;
    color: #2d3748;
}

.kayarine-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.kayarine-modal-close:hover {
    color: #333;
}

.kayarine-modal-body {
    padding: 20px;
}

.kayarine-modal-body p {
    margin: 0 0 15px 0;
    color: #4a5568;
    font-size: 0.95rem;
}

.kayarine-modal-body input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
}

.kayarine-modal-body input[type="date"]:focus {
    border-color: #3182ce;
    outline: none;
    box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
}

.kayarine-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.kayarine-modal-footer button {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.kayarine-modal-footer .kayarine-btn-cancel {
    background: #f0f0f0;
    color: #666;
}

.kayarine-modal-footer .kayarine-btn-cancel:hover {
    background: #e0e0e0;
}

.kayarine-modal-footer .kayarine-btn-submit {
    background: #3182ce;
    color: white;
}

.kayarine-modal-footer .kayarine-btn-submit:hover {
    background: #2c5282;
}

.kayarine-modal-footer .kayarine-btn-submit:disabled {
    background: #cbd5e0;
    cursor: not-allowed;
}

.kayarine-auth-wrapper-orange {
    max-width: 700px;
    margin: 40px auto;
    padding: 20px;
}

.kayarine-auth-container-orange {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.kayarine-auth-header-orange {
    background: linear-gradient(135deg, #FF8C42 0%, #FF7A3D 100%);
    padding: 30px;
    text-align: center;
    color: white;
}

.kayarine-auth-header-orange h1 {
    margin: 0 0 8px 0;
    font-size: 1.8rem;
    font-weight: 700;
}

.kayarine-auth-header-orange p {
    margin: 0;
    font-size: 0.95rem;
    opacity: 0.95;
}

.kayarine-auth-tabs-orange {
    display: flex;
    border-bottom: 2px solid #f0f0f0;
    background: #fafafa;
}

.kayarine-auth-tab-btn-orange {
    flex: 1;
    padding: 15px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: 600;
    color: #999;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
}

.kayarine-auth-tab-btn-orange:hover {
    color: #2d3748;
    border-bottom-color: #e2e8f0;
    background: transparent;
}

.kayarine-auth-tab-btn-orange.active {
    color: #2d3748;
    border-bottom-color: #FF8C42;
    background: #ffffff;
}

.kayarine-auth-panel-orange {
    display: none;
    opacity: 0;
}

.kayarine-auth-panel-orange.active {
    display: block;
    opacity: 1;
}

.kayarine-auth-panel-content-orange {
    padding: 30px;
}

.kayarine-auth-panel-content-orange h2 {
    margin-top: 0;
    font-size: 1.5rem;
    color: #2d3748;
    margin-bottom: 10px;
}

.kayarine-auth-subtitle {
    color: #666 !important;
    font-size: 0.95rem !important;
    margin-bottom: 20px !important;
}

.kayarine-form-group {
    margin-bottom: 15px;
}

.kayarine-form-group label {
    display: block !important;
    margin-bottom: 6px !important;
    font-weight: 600 !important;
    color: #333 !important;
    font-size: 0.9rem !important;
}

.kayarine-form-input {
    width: 100% !important;
    padding: 10px 12px !important;
    border: 1px solid #ddd !important;
    border-radius: 6px !important;
    font-size: 1rem !important;
    box-sizing: border-box !important;
    transition: all 0.2s ease !important;
}

.kayarine-form-input:focus {
    border-color: #FF8C42 !important;
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1) !important;
}

.kayarine-checkbox-group {
    display: flex;
    align-items: center;
}

.kayarine-checkbox-group label {
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    margin-bottom: 0 !important;
    font-weight: normal !important;
}

.kayarine-checkbox-group input[type="checkbox"] {
    accent-color: #FF8C42 !important;
    cursor: pointer !important;
}

.kayarine-btn-submit {
    width: 100% !important;
    padding: 12px 20px !important;
    background: linear-gradient(135deg, #FF8C42 0%, #FF7A3D 100%) !important;
    color: white !important;
    border: none !important;
    border-radius: 6px !important;
    font-weight: 700 !important;
    font-size: 1rem !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 8px rgba(255, 140, 66, 0.25) !important;
}

.kayarine-btn-submit:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(255, 140, 66, 0.35) !important;
}

.kayarine-btn-submit:disabled {
    opacity: 0.7 !important;
    cursor: not-allowed !important;
}

.kayarine-form-message {
    display: none;
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    text-align: center;
}

.kayarine-benefits-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.kayarine-benefit-card {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e0e0e0;
    transition: all 0.2s ease;
}

.kayarine-benefit-card:hover {
    border-color: #FF8C42;
    background: rgba(255, 140, 66, 0.05);
}

.kayarine-benefit-icon {
    font-size: 2rem;
    margin-bottom: 8px;
    display: block;
}

.kayarine-benefit-card h3 {
    font-size: 0.95rem;
    color: #333;
    margin: 0 0 6px 0;
    font-weight: 700;
}

.kayarine-benefit-card p {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
    line-height: 1.4;
}

.kayarine-auth-footer {
    padding: 0 30px 30px 30px;
    text-align: center;
    font-size: 0.9rem;
    color: #666;
}

.kayarine-auth-panel-content-orange a {
    color: #FF8C42 !important;
    text-decoration: none !important;
}

.kayarine-auth-panel-content-orange a:hover {
    color: #FF7A3D !important;
    text-decoration: underline !important;
}

.kayarine-switch-tab-btn {
    background: none !important;
    border: none !important;
    color: #FF8C42 !important;
    font-weight: 700 !important;
    cursor: pointer !important;
    text-decoration: none !important;
    font-size: 0.9rem !important;
    padding: 0 !important;
    margin-left: 4px !important;
}

.kayarine-switch-tab-btn:hover {
    color: #FF7A3D !important;
    text-decoration: underline !important;
}

.kayarine-membership-dashboard {
    background: #f9f9f9;
    padding: 30px;
    border-radius: 12px;
}

.kayarine-membership-dashboard h2 {
    margin-top: 0;
    color: #2d3748;
    font-size: 1.5rem;
}

.kayarine-membership-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border-radius: 8px;
}

.kayarine-membership-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #FF8C42 0%, #FF7A3D 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    box-shadow: 0 2px 8px rgba(255, 140, 66, 0.3);
    flex-shrink: 0;
}

.kayarine-membership-info {
    flex: 1;
}

.kayarine-membership-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2d3748;
}

.kayarine-membership-tier {
    font-size: 0.9rem;
    color: #FF8C42;
    font-weight: 600;
    margin-top: 4px;
}

.kayarine-membership-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 30px;
}

.kayarine-stat-box {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.kayarine-stat-label {
    font-size: 0.8rem;
    color: #718096;
    margin-bottom: 4px;
}

.kayarine-stat-value {
    font-size: 1.3rem;
    font-weight: 800;
    color: #FF8C42;
}

.kayarine-membership-progress {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.kayarine-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    margin-bottom: 10px;
    color: #718096;
}

.kayarine-progress-bar-bg {
    width: 100%;
    height: 8px;
    background: #edf2f7;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.kayarine-progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #FF8C42 0%, #FF7A3D 100%);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.kayarine-progress-hint {
    font-size: 0.85rem;
    color: #FF8C42;
    text-align: center;
}

.kayarine-membership-benefits {
    background: white;
    padding: 20px;
    border-radius: 8px;
}

.kayarine-membership-benefits h3 {
    margin-top: 0;
    font-size: 1rem;
    color: #2d3748;
}

.kayarine-membership-benefits ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.kayarine-membership-benefits li {
    padding: 8px 0;
    color: #4a5568;
    font-size: 0.95rem;
}

@media (max-width: 768px) {
    .kayarine-auth-wrapper-orange {
        margin: 20px auto;
        padding: 10px;
    }

    .kayarine-auth-header-orange {
        padding: 20px;
    }

    .kayarine-auth-header-orange h1 {
        font-size: 1.5rem;
    }

    .kayarine-auth-tab-btn-orange {
        padding: 12px 16px;
        font-size: 0.85rem;
    }

    .kayarine-auth-panel-content-orange {
        padding: 20px;
    }

    .kayarine-benefits-grid {
        grid-template-columns: 1fr;
    }

    .kayarine-membership-header {
        flex-direction: column;
        text-align: center;
    }

    .kayarine-membership-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .kayarine-auth-wrapper-orange {
        margin: 10px auto;
        padding: 5px;
    }

    .kayarine-auth-header-orange {
        padding: 15px;
    }

    .kayarine-auth-header-orange h1 {
        font-size: 1.3rem;
    }

    .kayarine-auth-tab-btn-orange {
        padding: 10px 12px;
        font-size: 0.8rem;
    }

    .kayarine-auth-panel-content-orange {
        padding: 15px;
    }

    .kayarine-auth-panel-content-orange input {
        font-size: 16px !important;
    }
}
CSS;
    }
}

// Initialize on plugins_loaded to ensure all dependencies are ready
add_action( 'plugins_loaded', function() {
    // Initialize regardless of WooCommerce, as login/register works independently
    if ( ! class_exists( 'Kayarine_WooCommerce_Customizer' ) ) {
        return;
    }
    new Kayarine_WooCommerce_Customizer();
}, 20 );
