<?php
/**
 * Kayarine Inventory Admin Page
 * 
 * WordPress 後台庫存管理界面
 * 允許管理員設置產品庫存限制和黑名單日期
 * 
 * @package Kayarine_Booking
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

class Kayarine_Inventory_Admin {
    
    /**
     * 初始化管理頁面
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_post_kayarine_save_inventory_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }

    /**
     * 添加管理選單
     */
    public static function add_admin_menu() {
        add_menu_page(
            'Kayarine 庫存管理',
            'Kayarine 庫存',
            'manage_options',
            'kayarine-inventory',
            array(__CLASS__, 'render_admin_page'),
            'dashicons-calendar-alt',
            30
        );
    }

    /**
     * 載入管理頁面樣式
     */
    public static function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_kayarine-inventory') {
            return;
        }

        wp_enqueue_style(
            'kayarine-inventory-admin',
            plugin_dir_url(__FILE__) . '../assets/css/inventory-admin.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script('jquery');
    }

    /**
     * 獲取所有產品及其當前限制
     */
    private static function get_products_with_limits() {
        // 從 kayarine-config.php 獲取產品 ID 常量
        require_once KAYARINE_BOOKING_PATH . 'includes/kayarine-config.php';

        $products = array();
        $limits = Kayarine_Inventory::get_limits();

        // 獲取產品名稱
        foreach ($limits as $product_id => $limit) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'limit' => $limit,
                    'type' => $product->get_type()
                );
            }
        }

        return $products;
    }

    /**
     * 渲染管理頁面
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('您沒有權限訪問此頁面');
        }

        // 獲取當前設置
        $products = self::get_products_with_limits();
        $blackout_dates = get_option('kayarine_blackout_dates', '');

        // 顯示成功訊息
        if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>設置已成功保存！</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-calendar-alt" style="font-size: 32px; width: 32px; height: 32px;"></span>
                Kayarine 庫存管理
            </h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('kayarine_inventory_settings', 'kayarine_inventory_nonce'); ?>
                <input type="hidden" name="action" value="kayarine_save_inventory_settings">

                <!-- 標籤頁 -->
                <h2 class="nav-tab-wrapper">
                    <a href="#limits" class="nav-tab nav-tab-active" onclick="switchTab(event, 'limits')">產品庫存限制</a>
                    <a href="#blackout" class="nav-tab" onclick="switchTab(event, 'blackout')">黑名單日期</a>
                    <a href="#usage" class="nav-tab" onclick="switchTab(event, 'usage')">使用報表</a>
                </h2>

                <!-- Tab 1: 產品庫存限制 -->
                <div id="limits" class="tab-content">
                    <h2>設置每日庫存限制</h2>
                    <p class="description">設置每個產品每天可租借的最大數量。留空則使用預設值。</p>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 60px;">產品 ID</th>
                                <th>產品名稱</th>
                                <th>類型</th>
                                <th style="width: 150px;">每日限制</th>
                                <th style="width: 100px;">狀態</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><code><?php echo esc_html($product['id']); ?></code></td>
                                    <td><strong><?php echo esc_html($product['name']); ?></strong></td>
                                    <td><?php echo esc_html($product['type']); ?></td>
                                    <td>
                                        <input 
                                            type="number" 
                                            name="limits[<?php echo esc_attr($product['id']); ?>]" 
                                            value="<?php echo esc_attr($product['limit']); ?>"
                                            min="0"
                                            max="999"
                                            class="small-text"
                                        >
                                    </td>
                                    <td>
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="description" style="margin-top: 15px;">
                        <strong>提示：</strong>修改後會立即生效。庫存系統使用 5 秒快取，最多 5 秒後更新。
                    </p>
                </div>

                <!-- Tab 2: 黑名單日期 -->
                <div id="blackout" class="tab-content" style="display: none;">
                    <h2>管理黑名單日期</h2>
                    <p class="description">設置不可預訂的日期。支援單一日期、日期範圍、循環日期。</p>
                    
                    <textarea 
                        name="blackout_dates" 
                        rows="15" 
                        class="large-text code"
                        placeholder="範例：
2026-02-15 | | 春節假期
2026-02-16 to 2026-02-20 | | 春節假期
Every Monday | ID:6954 | 單人獨木舟週一不可租
2026-03-01 | ID:6957 | 限時活動（白名單）
2026-03-15 | Tag:sunrise | 日出團不可預訂"
                    ><?php echo esc_textarea($blackout_dates); ?></textarea>

                    <div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border-left: 4px solid #0073aa;">
                        <h3 style="margin-top: 0;">規則語法說明</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row">單一日期</th>
                                <td><code>2026-02-15 | | 描述</code></td>
                            </tr>
                            <tr>
                                <th scope="row">日期範圍</th>
                                <td><code>2026-02-15 to 2026-02-20 | | 描述</code></td>
                            </tr>
                            <tr>
                                <th scope="row">循環日期</th>
                                <td><code>Every Monday | | 描述</code><br>
                                    <code>Every Friday | | 描述</code></td>
                            </tr>
                            <tr>
                                <th scope="row">產品特定</th>
                                <td><code>2026-02-15 | ID:6954 | 只對產品 6954 適用</code></td>
                            </tr>
                            <tr>
                                <th scope="row">標籤特定</th>
                                <td><code>2026-02-15 | Tag:sunrise | 只對帶 sunrise 標籤的產品</code></td>
                            </tr>
                            <tr>
                                <th scope="row">白名單模式</th>
                                <td><code>2026-03-01 | ID:6957 | 限時活動</code><br>
                                    <small>使用「限時活動」標籤時，只有白名單日期可預訂</small></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Tab 3: 使用報表 -->
                <div id="usage" class="tab-content" style="display: none;">
                    <h2>庫存使用報表</h2>
                    <p class="description">查看特定日期的庫存使用情況</p>
                    
                    <div style="margin-bottom: 20px;">
                        <label for="report_date"><strong>選擇日期：</strong></label>
                        <input type="date" id="report_date" value="<?php echo date('Y-m-d'); ?>" />
                        <button type="button" class="button" onclick="loadUsageReport()">查詢</button>
                    </div>

                    <div id="usage-report-container">
                        <p class="description">請選擇日期並點擊「查詢」按鈕</p>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="保存設置">
                    <a href="<?php echo admin_url('admin.php?page=kayarine-inventory'); ?>" class="button">取消</a>
                </p>
            </form>
        </div>

        <script>
        function switchTab(event, tabName) {
            event.preventDefault();
            
            // 隱藏所有 tabs
            var contents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < contents.length; i++) {
                contents[i].style.display = 'none';
            }
            
            // 移除所有 active 狀態
            var tabs = document.getElementsByClassName('nav-tab');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('nav-tab-active');
            }
            
            // 顯示選中的 tab
            document.getElementById(tabName).style.display = 'block';
            event.target.classList.add('nav-tab-active');
        }

        function loadUsageReport() {
            var date = document.getElementById('report_date').value;
            var container = document.getElementById('usage-report-container');
            
            container.innerHTML = '<p>載入中...</p>';
            
            // 調用 REST API
            fetch('<?php echo rest_url('kayarine/v1/inventory/availability'); ?>?date=' + date)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        var html = '<table class="wp-list-table widefat fixed striped">';
                        html += '<thead><tr><th>產品名稱</th><th>限制</th><th>已用</th><th>剩餘</th><th>使用率</th></tr></thead>';
                        html += '<tbody>';
                        
                        for (var productId in data.data) {
                            var item = data.data[productId];
                            var usage = Math.round((item.used / item.limit) * 100);
                            var statusColor = usage >= 100 ? '#dc3232' : usage >= 80 ? '#ffb900' : '#46b450';
                            
                            html += '<tr>';
                            html += '<td><strong>' + item.name + '</strong></td>';
                            html += '<td>' + item.limit + '</td>';
                            html += '<td>' + item.used + '</td>';
                            html += '<td style="color: ' + statusColor + '"><strong>' + item.remaining + '</strong></td>';
                            html += '<td><div style="background: #e0e0e0; height: 20px; border-radius: 3px; overflow: hidden;">';
                            html += '<div style="background: ' + statusColor + '; width: ' + usage + '%; height: 100%;"></div>';
                            html += '</div>' + usage + '%</td>';
                            html += '</tr>';
                        }
                        
                        html += '</tbody></table>';
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = '<p class="description">查詢失敗或該日期無預訂數據</p>';
                    }
                })
                .catch(error => {
                    container.innerHTML = '<p style="color: #dc3232;">錯誤：' + error.message + '</p>';
                });
        }
        </script>

        <style>
        .tab-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-top: none;
            margin-bottom: 20px;
        }
        .nav-tab-wrapper {
            border-bottom: 1px solid #ccd0d4;
            margin: 20px 0 0 0;
        }
        #usage-report-container {
            margin-top: 20px;
        }
        </style>
        <?php
    }

    /**
     * 保存設置
     */
    public static function save_settings() {
        // 驗證權限
        if (!current_user_can('manage_options')) {
            wp_die('您沒有權限執行此操作');
        }

        // 驗證 nonce
        if (!isset($_POST['kayarine_inventory_nonce']) || 
            !wp_verify_nonce($_POST['kayarine_inventory_nonce'], 'kayarine_inventory_settings')) {
            wp_die('安全驗證失敗');
        }

        // 保存庫存限制
        if (isset($_POST['limits']) && is_array($_POST['limits'])) {
            foreach ($_POST['limits'] as $product_id => $limit) {
                $product_id = absint($product_id);
                $limit = absint($limit);
                
                if ($product_id > 0 && $limit > 0) {
                    update_option('kayarine_limit_' . $product_id, $limit, true);
                }
            }
        }

        // 保存黑名單日期
        if (isset($_POST['blackout_dates'])) {
            $blackout_dates = sanitize_textarea_field($_POST['blackout_dates']);
            update_option('kayarine_blackout_dates', $blackout_dates, true);
        }

        // 清除庫存快取
        Kayarine_Inventory::clear_cache();

        // 重定向回管理頁面
        wp_redirect(admin_url('admin.php?page=kayarine-inventory&updated=true'));
        exit;
    }
}

// 初始化管理頁面
add_action('plugins_loaded', array('Kayarine_Inventory_Admin', 'init'), 20);
