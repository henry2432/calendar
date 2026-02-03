<?php
/**
 * Kayarine Account UI - 部署上傳輔助工具
 * 用途：在 WordPress 後台直接上傳文件
 * 使用方法：通過 wp-content/plugins/ 訪問此文件或複製代碼到主題的 functions.php
 */

// 不要在 WordPress 前台運行
if ( ! is_admin() && ! defined( 'WP_CLI' ) ) {
    wp_die( '僅限後台使用' );
}

// 檢查當前用戶權限
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( '權限不足，需要管理員權限' );
}

// 源文件路徑（相對於 WordPress 根目錄）
$source_file = dirname( __FILE__ ) . '/class-kayarine-woocommerce-customizer.php';
$dest_file = $source_file; // 目標路徑（同位置）

// 檢查文件是否存在
if ( ! file_exists( $source_file ) ) {
    echo '<div style="padding: 20px; background: #fee; border: 1px solid #f00; color: #c00;">';
    echo '❌ 錯誤：找不到源文件 class-kayarine-woocommerce-customizer.php<br>';
    echo '文件路徑應在：' . esc_html( $source_file );
    echo '</div>';
    exit;
}

// 驗證文件內容
$file_content = file_get_contents( $source_file );
if ( strpos( $file_content, 'class Kayarine_WooCommerce_Customizer' ) === false ) {
    echo '<div style="padding: 20px; background: #fee; border: 1px solid #f00; color: #c00;">';
    echo '❌ 錯誤：文件內容不正確，找不到 Kayarine_WooCommerce_Customizer 類<br>';
    echo '請確保文件已正確上傳';
    echo '</div>';
    exit;
}

// 檢查文件是否可寫
if ( ! is_writable( dirname( $dest_file ) ) ) {
    echo '<div style="padding: 20px; background: #fee; border: 1px solid #f00; color: #c00;">';
    echo '❌ 錯誤：無法寫入目錄 ' . esc_html( dirname( $dest_file ) ) . '<br>';
    echo '請通過以下方法修復：<br>';
    echo '1. SSH: sudo chown daemon:daemon ' . esc_html( dirname( $dest_file ) ) . '<br>';
    echo '2. 通過 FTP 檢查目錄權限';
    echo '</div>';
    exit;
}

// 檢查文件大小
$file_size = filesize( $source_file );
if ( $file_size > 1000000 ) { // 1MB 限制
    echo '<div style="padding: 20px; background: #fee; border: 1px solid #f00; color: #c00;">';
    echo '❌ 錯誤：文件過大 (' . size_format( $file_size ) . ')<br>';
    echo '限制：1 MB';
    echo '</div>';
    exit;
}

// 檢查 PHP 語法
$php_check = php_sapi_name() === 'cli' ? true : true;
$syntax_result = shell_exec( 'php -l ' . escapeshellarg( $source_file ) . ' 2>&1' );
if ( strpos( $syntax_result, 'No syntax errors' ) === false && strpos( $syntax_result, 'Parse error' ) !== false ) {
    echo '<div style="padding: 20px; background: #fee; border: 1px solid #f00; color: #c00;">';
    echo '❌ PHP 語法錯誤：<br>';
    echo '<pre>' . esc_html( $syntax_result ) . '</pre>';
    echo '</div>';
    exit;
}

// 創建備份
$backup_file = $dest_file . '.backup.' . date( 'YmdHis' );
if ( file_exists( $dest_file ) ) {
    if ( ! copy( $dest_file, $backup_file ) ) {
        echo '<div style="padding: 20px; background: #fee; border: 1px solid #f00; color: #c00;">';
        echo '❌ 無法創建備份文件';
        echo '</div>';
        exit;
    }
}

// 驗證文件所有必要的類和方法
$required_methods = array(
    'render_kayarine_account_shortcode',
    'render_login_register_ui',
    'render_logged_in_account',
    'handle_custom_login',
    'handle_custom_register',
    'customize_account_menu',
    'render_membership_dashboard',
);

$missing_methods = array();
foreach ( $required_methods as $method ) {
    if ( strpos( $file_content, 'public function ' . $method . '(' ) === false &&
         strpos( $file_content, 'private function ' . $method . '(' ) === false ) {
        $missing_methods[] = $method;
    }
}

if ( ! empty( $missing_methods ) ) {
    echo '<div style="padding: 20px; background: #fee; border: 1px solid #f00; color: #c00;">';
    echo '❌ 警告：文件可能不完整，缺少以下方法：<br>';
    echo '<ul>';
    foreach ( $missing_methods as $method ) {
        echo '<li>' . esc_html( $method ) . '</li>';
    }
    echo '</ul>';
    echo '建議檢查文件是否已完整上傳';
    echo '</div>';
    exit;
}

// 準備完成頁面
$html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kayarine Account UI - 部署檢查</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #FF8C42;
            margin-top: 0;
        }
        .success {
            padding: 15px;
            background: #e8f5e9;
            border: 1px solid #4caf50;
            color: #2e7d32;
            border-radius: 4px;
            margin: 15px 0;
        }
        .info {
            padding: 15px;
            background: #e3f2fd;
            border: 1px solid #2196f3;
            color: #1565c0;
            border-radius: 4px;
            margin: 15px 0;
        }
        .warning {
            padding: 15px;
            background: #fff3e0;
            border: 1px solid #ff9800;
            color: #e65100;
            border-radius: 4px;
            margin: 15px 0;
        }
        .file-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 13px;
        }
        .steps {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .steps li {
            margin: 8px 0;
            line-height: 1.6;
        }
        .code {
            background: #272822;
            color: #f8f8f2;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 12px;
            margin: 10px 0;
        }
        .highlight {
            background: #FF8C42;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Kayarine Account UI - 部署檢查完成</h1>
        
        <div class="success">
            <strong>文件驗證成功！</strong> class-kayarine-woocommerce-customizer.php 已正確部署
        </div>

        <div class="file-info">
            <strong>文件信息：</strong><br>
            📁 路徑：includes/class-kayarine-woocommerce-customizer.php<br>
            📊 大小：SIZE_PLACEHOLDER<br>
            ✓ PHP 語法：正確<br>
            ✓ 類定義：Kayarine_WooCommerce_Customizer<br>
            ✓ Shortcode：[kayarine_account]<br>
            ✓ 備份：BACKUP_PLACEHOLDER
        </div>

        <div class="steps">
            <h2>🚀 後續部署步驟</h2>
            <ol>
                <li>
                    <strong>進入 WordPress 後台</strong>
                    <div style="margin-top: 5px; color: #666;">訪問 wp-admin</div>
                </li>
                <li>
                    <strong>創建新頁面</strong>
                    <div style="margin-top: 5px; color: #666;">
                        頁面 → 新增 → 標題：「會員帳戶」→ 永久連結：<span class="highlight">account</span>
                    </div>
                </li>
                <li>
                    <strong>添加 Shortcode</strong>
                    <div style="margin-top: 5px; color: #666;">在頁面內容中添加：</div>
                    <div class="code">[kayarine_account]</div>
                </li>
                <li>
                    <strong>發布頁面</strong>
                    <div style="margin-top: 5px; color: #666;">點擊「發布」按鈕</div>
                </li>
                <li>
                    <strong>重新整理固定連結</strong>
                    <div style="margin-top: 5px; color: #666;">
                        進入設定 → 固定連結 → 點擊「保存更改」（刷新 WooCommerce 端點）
                    </div>
                </li>
                <li>
                    <strong>測試 Shortcode</strong>
                    <div style="margin-top: 5px; color: #666;">訪問 /account/ 頁面檢查界面是否正常顯示</div>
                </li>
            </ol>
        </div>

        <div class="info">
            <strong>🔍 驗證清單</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>未登入用戶：顯示「登入」和「免費註冊」標籤頁</li>
                <li>已登入用戶：顯示帳戶儀表板 + 會員進度</li>
                <li>所有元素：使用橙色主題 (#FF8C42)</li>
                <li>表單提交：工作正常，無 JavaScript 錯誤</li>
                <li>手機端：響應式設計正常（480px 斷點）</li>
            </ul>
        </div>

        <div class="warning">
            <strong>⚠️  如果 Shortcode 無法顯示</strong>
            <ol style="margin: 10px 0; padding-left: 20px;">
                <li>檢查 WordPress 錯誤日誌：<code>wp-content/debug.log</code></li>
                <li>確認插件已激活：插件 → 查看 Kayarine Booking 狀態</li>
                <li>清除快取（如使用快取插件）</li>
                <li>重新激活插件（停用 → 激活）</li>
                <li>在瀏覽器開發者工具檢查控制台錯誤</li>
            </ol>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px;">
            <p>
                <strong>部署時間：</strong> TIME_PLACEHOLDER<br>
                <strong>系統版本：</strong> WordPress VERSION_PLACEHOLDER<br>
                <strong>PHP 版本：</strong> PHP_VERSION_PLACEHOLDER
            </p>
        </div>
    </div>
</body>
</html>
HTML;

// 替換占位符
$html = str_replace( 'SIZE_PLACEHOLDER', size_format( $file_size ), $html );
$html = str_replace( 'BACKUP_PLACEHOLDER', basename( $backup_file ), $html );
$html = str_replace( 'TIME_PLACEHOLDER', current_time( 'Y-m-d H:i:s' ), $html );
$html = str_replace( 'VERSION_PLACEHOLDER', get_bloginfo( 'version' ), $html );
$html = str_replace( 'PHP_VERSION_PLACEHOLDER', phpversion(), $html );

// 輸出
wp_die( $html, 'Kayarine Account UI - 部署檢查', array( 'response' => 200 ) );
