<?php
/**
 * Kayarine Auth Integration - Orange Theme Only
 * Handles custom login/registration shortcode with single orange color scheme
 * Version: 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Kayarine_Auth_Integration {

    public function __construct() {
        // Register custom shortcode
        add_shortcode( 'kayarine_login_register', array( $this, 'render_login_register' ) );
        
        // ✅ 已移至 class-kayarine-member-dashboard.php 避免衝突
        // add_shortcode( 'kayarine_member_dashboard', array( $this, 'render_member_dashboard' ) );
        
        // Enqueue styles and scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Enqueue CSS and JS for auth interface
     */
    public function enqueue_assets() {
        // CSS already enqueued in main plugin
        wp_enqueue_style( 'kayarine-booking-css' );
        wp_enqueue_script( 'jquery' );
    }

    /**
     * Render custom login/registration interface
     * Shortcode: [kayarine_login_register]
     */
    public function render_login_register() {
        if ( is_user_logged_in() ) {
            return $this->render_member_dashboard();
        }

        ob_start();
        ?>
        <div class="kayarine-auth-wrapper-orange">
            <div class="kayarine-auth-container-orange">
                <!-- Header with Orange Gradient -->
                <div class="kayarine-auth-header-orange">
                    <h1>Kayarine 會員中心</h1>
                    <p>登入或註冊以管理您的預約</p>
                </div>

                <!-- Tab Navigation with Orange Bottom Line -->
                <div class="kayarine-auth-tabs-orange">
                    <button class="kayarine-auth-tab-btn-orange active" data-tab="login" type="button">
                        🔐 會員登入
                    </button>
                    <button class="kayarine-auth-tab-btn-orange" data-tab="register" type="button">
                        ✨ 免費註冊
                    </button>
                </div>

                <!-- Login Tab -->
                <div id="kayarine-login-tab" class="kayarine-auth-panel-orange active">
                    <div class="kayarine-auth-panel-content-orange">
                        <?php $this->render_login_form(); ?>
                    </div>
                </div>

                <!-- Register Tab -->
                <div id="kayarine-register-tab" class="kayarine-auth-panel-orange">
                    <div class="kayarine-auth-panel-content-orange">
                        <?php $this->render_register_form(); ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
        /* Kayarine Auth - Orange Theme Only */
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

        /* Tabs with Orange Bottom Line */
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

        /* Form Elements */
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

        /* Orange Submit Button */
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

        /* Primary Button - Orange */
        .kayarine-btn-primary {
            display: block !important;
            width: 100% !important;
            padding: 12px 20px !important;
            background: linear-gradient(135deg, #FF8C42 0%, #FF7A3D 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 6px !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
            text-align: center !important;
            text-decoration: none !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 2px 8px rgba(255, 140, 66, 0.25) !important;
            margin-bottom: 20px !important;
        }

        .kayarine-btn-primary:hover,
        .kayarine-btn-primary:visited,
        .kayarine-btn-primary:focus {
            color: white !important;
            text-decoration: none !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(255, 140, 66, 0.35) !important;
        }

        /* Secondary Button */
        .kayarine-btn-secondary {
            display: inline-block !important;
            padding: 10px 20px !important;
            background: #f0f0f0 !important;
            color: #333 !important;
            border: 1px solid #ddd !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
            text-decoration: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            margin-right: 10px !important;
            margin-bottom: 10px !important;
        }

        .kayarine-btn-secondary:hover {
            background: #e8e8e8 !important;
            border-color: #FF8C42 !important;
            text-decoration: none !important;
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

        /* Benefits Grid */
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

        /* Links */
        .kayarine-auth-panel-content-orange a {
            color: #FF8C42 !important;
            text-decoration: none !important;
        }

        .kayarine-auth-panel-content-orange a:hover {
            color: #FF7A3D !important;
            text-decoration: underline !important;
        }

        /* Responsive */
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
        </style>

        <script>
        (function() {
            'use strict';
            
            function kayarineInitAuthTabs() {
                var tabButtons = document.querySelectorAll('.kayarine-auth-tab-btn-orange');
                var tabPanels = document.querySelectorAll('.kayarine-auth-panel-orange');
                var switchButtons = document.querySelectorAll('.kayarine-switch-tab-btn');
                
                if (!tabButtons.length || !tabPanels.length) {
                    return;
                }
                
                // Tab button clicks
                tabButtons.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var tabName = this.getAttribute('data-tab');
                        
                        tabButtons.forEach(function(b) { b.classList.remove('active'); });
                        tabPanels.forEach(function(p) { p.classList.remove('active'); });
                        
                        this.classList.add('active');
                        var activePanel = document.getElementById('kayarine-' + tabName + '-tab');
                        if (activePanel) {
                            activePanel.classList.add('active');
                        }
                    });
                });
                
                // Switch tab buttons (register -> login links)
                switchButtons.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var targetTab = this.getAttribute('data-switch-to') || 'login';
                        
                        tabButtons.forEach(function(b) { b.classList.remove('active'); });
                        tabPanels.forEach(function(p) { p.classList.remove('active'); });
                        
                        var targetBtn = document.querySelector('.kayarine-auth-tab-btn-orange[data-tab="' + targetTab + '"]');
                        if (targetBtn) targetBtn.classList.add('active');
                        
                        var targetPanel = document.getElementById('kayarine-' + targetTab + '-tab');
                        if (targetPanel) targetPanel.classList.add('active');
                    });
                });
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', kayarineInitAuthTabs);
            } else {
                kayarineInitAuthTabs();
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Render login form
     */
    private function render_login_form() {
        $redirect_url = isset( $_REQUEST['redirect_to'] ) ? esc_url( $_REQUEST['redirect_to'] ) : home_url( '/member/' );
        ?>
        <h2>登入您的帳戶</h2>
        <p class="kayarine-auth-subtitle">登入以管理您的預約、查看優惠和管理會員檔案。</p>

        <form method="post" action="<?php echo esc_url( wp_login_url( $redirect_url ) ); ?>" class="kayarine-login-form">
            <div class="kayarine-form-group">
                <label for="user_login">電子郵件或用戶名</label>
                <input 
                    type="text" 
                    name="log" 
                    id="user_login" 
                    class="kayarine-form-input"
                    placeholder="請輸入電子郵件或用戶名"
                    required
                >
            </div>

            <div class="kayarine-form-group">
                <label for="user_pass">密碼</label>
                <input 
                    type="password" 
                    name="pwd" 
                    id="user_pass" 
                    class="kayarine-form-input"
                    placeholder="請輸入密碼"
                    required
                >
            </div>

            <div class="kayarine-form-group kayarine-checkbox-group">
                <label for="rememberme">
                    <input type="checkbox" name="rememberme" id="rememberme" value="forever">
                    記住我
                </label>
            </div>

            <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_url ); ?>">
            
            <button type="submit" class="kayarine-btn-submit">登入</button>
        </form>

        <div class="kayarine-auth-footer">
            <p><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">忘記密碼？</a></p>
        </div>
        <?php
    }

    /**
     * Render registration form
     */
    private function render_register_form() {
        $users_can_register = get_option( 'users_can_register' );
        $my_account_url = home_url( '/register/' );
        $woo_registration_enabled = get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes';
        $registration_enabled = $users_can_register || $woo_registration_enabled;
        ?>
        <h2>建立帳戶</h2>
        <p class="kayarine-auth-subtitle">加入 Kayarine 會員社群，享受獨家優惠及積分獎勵</p>

        <!-- Benefits Grid -->
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

        <?php if ( $registration_enabled && ! empty( $my_account_url ) ) : ?>
            <a href="<?php echo esc_url( $my_account_url ); ?>" class="kayarine-btn-primary">
                前往註冊頁面 →
            </a>
        <?php else : ?>
            <div style="background: #fff3cd; border-left: 4px solid #ff9800; padding: 12px 15px; border-radius: 4px; margin: 20px 0; color: #856404;">
                <p style="margin: 0; font-size: 0.9rem;">
                    <strong>提示：</strong>註冊功能需要啟用。請確認 WordPress 設定 → 一般 → 允許任何人註冊。
                </p>
            </div>
        <?php endif; ?>

        <div class="kayarine-auth-footer">
            <p>已有帳戶？<button type="button" class="kayarine-switch-tab-btn" data-switch-to="login">直接登入</button></p>
        </div>
        <?php
    }

    /**
     * Render member dashboard when user is logged in
     */
    public function render_member_dashboard() {
        ob_start();
        ?>
        <div class="kayarine-member-welcome-orange">
            <div class="kayarine-auth-container-orange">
                <div class="kayarine-auth-header-orange">
                    <h1>歡迎回來，<?php echo esc_html( wp_get_current_user()->display_name ); ?></h1>
                    <p>管理您的預約和會員檔案</p>
                </div>

                <div class="kayarine-member-actions-orange" style="padding: 30px;">
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>" class="kayarine-btn-secondary">
                        📅 我的預約
                    </a>
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>" class="kayarine-btn-secondary">
                        ⚙️ 編輯檔案
                    </a>
                    <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="kayarine-btn-secondary">
                        🚪 登出
                    </a>
                </div>
            </div>
        </div>

        <style>
        .kayarine-member-welcome-orange {
            margin: 30px 0;
        }

        .kayarine-member-actions-orange {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .kayarine-member-actions-orange a {
            margin-right: 0 !important;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}

// Initialize the class
new Kayarine_Auth_Integration();
