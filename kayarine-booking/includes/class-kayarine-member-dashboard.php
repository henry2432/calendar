<?php
/**
 * Kayarine Member Dashboard
 * Displays member account, bookings, points, and loyalty management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Member_Dashboard {

	public function __construct() {
		// Add shortcode
		add_shortcode( 'kayarine_member_dashboard', array( $this, 'render_dashboard' ) );
		
		// Add AJAX endpoints
		add_action( 'wp_ajax_kayarine_cancel_booking', array( $this, 'ajax_cancel_booking' ) );
		add_action( 'wp_ajax_kayarine_reschedule_booking', array( $this, 'ajax_reschedule_booking' ) );
		
		// ✅ 性能優化：監聽訂單狀態變更，自動清除使用者儀表板緩存
		// 當任何訂單狀態變更時，清除該用戶的緩存以確保最新資料
		add_action( 'woocommerce_order_status_changed', array( $this, 'clear_user_dashboard_cache' ), 10, 3 );
		
		// ✅ 初始化日誌
		error_log( "[Kayarine] Member Dashboard initialized - AJAX hooks registered & cache invalidation enabled" );
	}
	
	/**
	 * ✅ 清除使用者儀表板緩存（當訂單狀態變更時自動調用）
	 */
	public function clear_user_dashboard_cache( $order_id, $old_status, $new_status ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$user_id = $order->get_user_id();
			$cache_key = 'kayarine_dashboard_orders_' . $user_id;
			delete_transient( $cache_key );
			error_log( "[Kayarine] Dashboard cache cleared for user $user_id (Order $order_id: $old_status → $new_status)" );
		}
	}

	/**
	 * Render Member Dashboard - 重設計版本 v2.0
	 * 新佈局：歡迎卡片 → 預約列表 → 忠誠度面板 → 推薦商品
	 * 設計風格：蘋果簡約 + 活力橙品牌色
	 */
	public function render_dashboard() {
		if ( ! is_user_logged_in() ) {
			return '<div class="woocommerce-info">請 <a href="' . get_permalink( get_option('woocommerce_myaccount_page_id') ) . '">登入</a> 查看您的預約。</div>';
		}

		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );
		
		// ✅ 性能優化：使用 WordPress Transient 緩存 15 分鐘
		// 保留所有訂單（確保準確性）但減少重複查詢
		$cache_key = 'kayarine_dashboard_orders_' . $user_id;
		$orders = get_transient( $cache_key );
		
		if ( false === $orders ) {
			// 獲取訂單數據（所有訂單，無限制）
			$orders = wc_get_orders( array(
				'customer_id' => $user_id,
				'status'   => array( 'pending', 'processing', 'on-hold', 'completed', 'refunded', 'cancelled' ),
				'limit'    => -1,
				'orderby'  => 'date',
				'order'    => 'DESC',
			) );
			
			// 緩存 1 小時（性能優化：增加緩存時間）
			set_transient( $cache_key, $orders, 1 * HOUR_IN_SECONDS );
			error_log( "[Kayarine] Dashboard: 首次加載或緩存過期 - 查詢 DB (用戶: $user_id, 訂單數: " . count( $orders ) . ")" );
		} else {
			error_log( "[Kayarine] Dashboard: 使用緩存資料 (用戶: $user_id, 訂單數: " . count( $orders ) . ")" );
		}
		
		// 獲取忠誠度數據
		$points = (int) get_user_meta( $user_id, Kayarine_Membership::META_POINTS, true );
		$tier = Kayarine_Membership::get_tier( $user_id );
		$tier_info = Kayarine_Membership::get_tier_info( $tier );
		
		// 計算成就統計
		$total_bookings = count( $orders );
		$completed_bookings = count( array_filter( $orders, function( $o ) { return $o->get_status() === 'completed'; } ) );

		wp_enqueue_style( 'kayarine-booking-css' );
		wp_enqueue_script( 'kayarine-booking-js' );

		ob_start();
		?>
		<div class="kayarine-member-dashboard kmd-redesign-v2">
			<!-- 1️⃣ 歡迎卡片 -->
			<section class="kmd-welcome-card">
				<div class="kmd-avatar-group">
					<div class="kmd-avatar">
						<?php echo get_avatar( $user_id, 128 ); ?>
						<button class="kmd-avatar-edit" title="編輯頭像">✎</button>
					</div>
				</div>
				
				<div class="kmd-welcome-content">
					<h1 class="kmd-welcome-title">
						歡迎回來，<strong><?php echo esc_html( $user->first_name ?: $user->user_login ); ?></strong>！
					</h1>
					
					<p class="kmd-achievement">
						你已完成了 <strong><?php echo $completed_bookings; ?></strong> 次預約 🏆
					</p>
					
					<div class="kmd-progress-section">
						<div class="kmd-progress-label">
							<span>積分進度</span>
							<span class="kmd-points"><?php echo $points; ?> points</span>
						</div>
						<div class="kmd-progress-bar">
							<div class="kmd-progress-fill" style="width: <?php echo min( 100, max( 0, ( $points / 1000 ) * 100 ) ); ?>%;"></div>
						</div>
						<p class="kmd-progress-hint">
							<?php
								$target_points = 1000;
								$remaining = max( 0, $target_points - $points );
								echo $remaining > 0 ? "還需 $remaining 積分即可升級" : '已達最高等級！';
							?>
						</p>
					</div>
					
					<div class="kmd-button-group">
						<button class="kmd-btn kmd-btn-primary" onclick="window.location.href='<?php echo esc_url( get_permalink( get_option('woocommerce_myaccount_page_id') ) ); ?>'">
							編輯個人資料
						</button>
						<button class="kmd-btn">查看成就徽章</button>
						<button class="kmd-btn">會員等級專享</button>
					</div>
				</div>
			</section>

			<!-- 2️⃣ 我的預約 -->
			<section class="kmd-bookings-section">
				<h2 class="kmd-section-title">我的預約</h2>
				
				<?php if ( empty( $orders ) ) : ?>
					<p class="kmd-no-orders">
						您還沒有預約。 <a href="<?php echo esc_url( home_url( '/kayarine-booking' ) ); ?>" class="kmd-link-primary">立即預約</a>
					</p>
				<?php else : ?>
					<div class="kmd-bookings-list">
						<?php foreach ( $orders as $order ) :
							$this->render_booking_card( $order, $user_id );
						endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<!-- 3️⃣ 忠誠度面板 -->
			<section class="kmd-loyalty-section">
				<h2 class="kmd-section-title">忠誠度面板</h2>
				
				<div class="kmd-loyalty-grid">
					<div class="kmd-loyalty-card">
						<p class="kmd-loyalty-label">積分餘額</p>
						<div class="kmd-loyalty-value"><?php echo $points; ?></div>
						<p class="kmd-loyalty-unit">points</p>
					</div>
					<div class="kmd-loyalty-card">
						<p class="kmd-loyalty-label">會員等級</p>
						<div class="kmd-loyalty-value"><?php echo esc_html( $tier_info['label'] ); ?></div>
						<p class="kmd-loyalty-unit"><?php echo esc_html( $tier_info['label_cn'] ?? '' ); ?></p>
					</div>
				</div>
			</section>

			<!-- Reschedule Modal -->
			<div id="kb-reschedule-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
				<div style="background:white; padding:30px; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1); width:90%; max-width:400px;">
					<h3 style="margin-top:0;">選擇新日期</h3>
					<input type="text" id="kb-reschedule-date" placeholder="選擇日期" style="width:100%; padding:8px; border:1px solid #cbd5e0; border-radius:4px; margin-bottom:15px; box-sizing:border-box;">
					<div style="display:flex; gap:10px;">
						<button onclick="kayarineConfirmReschedule()" style="flex:1; padding:10px; background:#3182ce; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:600;">確認</button>
						<button onclick="kayarineCloseRescheduleModal()" style="flex:1; padding:10px; background:#cbd5e0; color:#2d3748; border:none; border-radius:4px; cursor:pointer; font-weight:600;">取消</button>
					</div>
					<div id="kb-reschedule-error" style="margin-top:10px; color:#c53030; font-size:14px; display:none;"></div>
				</div>
			</div>
		</div>

		<style>
			/* ========================================
			   Kayarine Member Dashboard - 重設計 v2.0
			   設計風格：蘋果簡約 + 活力橙品牌色
			   ======================================== */

			.kayarine-member-dashboard.kmd-redesign-v2 {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
				max-width: 1200px;
				margin: 0 auto;
				padding: 32px 16px;
				background: #FFFFFF;
			}

			/* 歡迎卡片 */
			.kmd-welcome-card {
				background: #FFFFFF;
				border: 1px solid #E8E8E8;
				border-radius: 8px;
				padding: 32px;
				display: grid;
				grid-template-columns: 128px 1fr;
				gap: 24px;
				align-items: flex-start;
				margin-bottom: 60px;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
			}

			.kmd-avatar-group {
				position: relative;
			}

			.kmd-avatar {
				position: relative;
				width: 128px;
				height: 128px;
			}

			.kmd-avatar img {
				width: 100%;
				height: 100%;
				border-radius: 50%;
				object-fit: cover;
			}

			.kmd-avatar-edit {
				position: absolute;
				bottom: 0;
				right: 0;
				width: 36px;
				height: 36px;
				background: #FF7F00;
				border-radius: 50%;
				border: 2px solid white;
				color: white;
				font-size: 18px;
				cursor: pointer;
				transition: all 0.2s ease;
			}

			.kmd-avatar-edit:hover {
				background: #E67E00;
			}

			.kmd-welcome-content {
				display: flex;
				flex-direction: column;
				gap: 16px;
			}

			.kmd-welcome-title {
				font-size: 32px;
				font-weight: 300;
				color: #757575;
				margin: 0;
				line-height: 1.2;
			}

			.kmd-welcome-title strong {
				font-weight: 600;
				color: #1F1F1F;
			}

			.kmd-achievement {
				font-size: 13px;
				color: #757575;
				margin: 0;
			}

			.kmd-achievement strong {
				color: #1F1F1F;
				font-weight: 500;
			}

			.kmd-progress-section {
				display: flex;
				flex-direction: column;
				gap: 8px;
			}

			.kmd-progress-label {
				display: flex;
				justify-content: space-between;
				font-size: 12px;
				color: #757575;
				font-weight: 500;
			}

			.kmd-points {
				color: #FF7F00;
				font-weight: 600;
			}

			.kmd-progress-bar {
				height: 4px;
				background: #E8E8E8;
				border-radius: 2px;
				overflow: hidden;
			}

			.kmd-progress-fill {
				height: 100%;
				background: #FF7F00;
				border-radius: 2px;
				transition: width 0.3s ease;
			}

			.kmd-progress-hint {
				font-size: 12px;
				color: #757575;
				margin: 0;
			}

			.kmd-button-group {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
				margin-top: 8px;
			}

			.kmd-btn {
				padding: 8px 16px;
				font-size: 13px;
				border: 1px solid #E8E8E8;
				background: white;
				border-radius: 4px;
				cursor: pointer;
				transition: all 0.2s ease;
				font-weight: 500;
				color: #1F1F1F;
			}

			.kmd-btn:hover {
				background: #F9F9F9;
				border-color: #D0D0D0;
			}

			.kmd-btn-primary {
				border-color: #FF7F00;
				color: #FF7F00;
			}

			.kmd-btn-primary:hover {
				background: #FFF5EE;
				border-color: #E67E00;
			}

			/* 區塊標題 */
			.kmd-section-title {
				font-size: 24px;
				font-weight: 400;
				color: #1F1F1F;
				margin: 0 0 16px 0;
			}

			/* 預約列表 */
			.kmd-bookings-section {
				margin-bottom: 60px;
			}

			.kmd-no-orders {
				text-align: center;
				color: #757575;
				padding: 40px 20px;
				margin: 0;
			}

			.kmd-link-primary {
				color: #FF7F00;
				text-decoration: none;
				font-weight: 600;
			}

			.kmd-link-primary:hover {
				text-decoration: underline;
			}

			.kmd-bookings-list {
				display: flex;
				flex-direction: column;
				gap: 12px;
			}

			.kmd-booking-card {
				background: #FFFFFF;
				border: 1px solid #E8E8E8;
				border-radius: 8px;
				padding: 20px;
				display: grid;
				grid-template-columns: 1fr 1fr 120px 120px;
				gap: 16px;
				align-items: center;
				position: relative;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
				border-left: 4px solid #999;
				transition: all 0.2s ease;
			}

			.kmd-booking-card:hover {
				box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
			}

			.kmd-booking-info {
				display: flex;
				flex-direction: column;
				gap: 6px;
			}

			.kmd-booking-title {
				font-size: 15px;
				font-weight: 600;
				color: #1F1F1F;
				margin: 0;
			}

			.kmd-booking-detail {
				font-size: 13px;
				color: #757575;
				margin: 0;
			}

			.kmd-booking-amount {
				font-size: 15px;
				font-weight: 600;
				color: #1F1F1F;
				text-align: right;
			}

			.kmd-booking-actions {
				display: flex;
				gap: 8px;
				justify-content: flex-end;
			}

			.kmd-btn-small {
				padding: 6px 12px;
				font-size: 12px;
				border: 1px solid #E8E8E8;
				background: white;
				border-radius: 4px;
				cursor: pointer;
				transition: all 0.2s ease;
				font-weight: 500;
				color: #1F1F1F;
			}

			.kmd-btn-small:hover {
				background: #F9F9F9;
				border-color: #D0D0D0;
			}

			.kmd-btn-danger {
				border-color: #e74c3c;
				color: #e74c3c;
			}

			.kmd-btn-danger:hover {
				background: #fff5f5;
				border-color: #c53030;
			}

			.kmd-booking-status-badge {
				position: absolute;
				top: 12px;
				right: 12px;
				font-size: 11px;
				font-weight: 600;
				padding: 4px 8px;
				border-radius: 4px;
				text-transform: uppercase;
				color: white;
			}

			/* 忠誠度面板 */
			.kmd-loyalty-section {
				margin-bottom: 60px;
			}

			.kmd-loyalty-grid {
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 16px;
			}

			.kmd-loyalty-card {
				background: #FFFFFF;
				border: 1px solid #E8E8E8;
				border-radius: 8px;
				padding: 24px;
				text-align: center;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
				min-height: 140px;
				display: flex;
				flex-direction: column;
				justify-content: center;
			}

			.kmd-loyalty-label {
				font-size: 12px;
				color: #757575;
				font-weight: 500;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				margin-bottom: 12px;
				margin: 0 0 12px 0;
			}

			.kmd-loyalty-value {
				font-size: 32px;
				font-weight: 600;
				color: #1F1F1F;
				margin-bottom: 8px;
			}

			.kmd-loyalty-unit {
				font-size: 12px;
				color: #757575;
				margin: 0;
			}

			/* 推薦商品 */
			.kmd-recommended-section {
				background: #F9F9F9;
				padding: 60px 32px;
				margin-left: -16px;
				margin-right: -16px;
				margin-bottom: -32px;
			}

			.kmd-section-header-center {
				text-align: center;
				margin-bottom: 40px;
			}

			.kmd-section-title {
				font-size: 28px;
				font-weight: 400;
				color: #1F1F1F;
				margin: 0 0 8px 0;
			}

			.kmd-section-subtitle {
				font-size: 13px;
				color: #757575;
				margin: 0;
			}

			.kmd-product-grid {
				display: grid;
				grid-template-columns: repeat(4, 1fr);
				gap: 16px;
				max-width: 1200px;
				margin: 0 auto;
			}

			.kmd-product-card {
				background: white;
				border-radius: 8px;
				overflow: hidden;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
				text-align: center;
				transition: all 0.2s ease;
			}

			.kmd-product-card:hover {
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
				transform: translateY(-2px);
			}

			.kmd-product-image {
				width: 100%;
				height: 200px;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 48px;
			}

			.kmd-product-info {
				padding: 16px;
			}

			.kmd-product-name {
				font-size: 13px;
				font-weight: 500;
				color: #1F1F1F;
				margin-bottom: 8px;
				margin: 0 0 8px 0;
			}

			.kmd-product-price {
				font-size: 14px;
				font-weight: 600;
				color: #FF7F00;
				margin: 0;
			}

			.kmd-original-price {
				text-decoration: line-through;
				color: #999;
				font-weight: 400;
				margin-left: 4px;
			}

			/* 模態框 */
			.kmd-modal {
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				z-index: 9999;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			.kmd-modal-overlay {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background: rgba(0, 0, 0, 0.5);
			}

			.kmd-modal-content {
				position: relative;
				background: white;
				border-radius: 8px;
				box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
				width: 90%;
				max-width: 400px;
				padding: 24px;
			}

			.kmd-modal-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 20px;
			}

			.kmd-modal-header h3 {
				font-size: 18px;
				font-weight: 600;
				margin: 0;
				color: #1F1F1F;
			}

			.kmd-modal-close {
				background: none;
				border: none;
				font-size: 24px;
				color: #999;
				cursor: pointer;
				transition: color 0.2s ease;
			}

			.kmd-modal-close:hover {
				color: #1F1F1F;
			}

			.kmd-modal-body {
				margin-bottom: 20px;
			}

			.kmd-input {
				width: 100%;
				padding: 8px 12px;
				border: 1px solid #E8E8E8;
				border-radius: 4px;
				font-size: 14px;
				font-family: inherit;
			}

			.kmd-input:focus {
				outline: none;
				border-color: #FF7F00;
				box-shadow: 0 0 0 3px rgba(255, 127, 0, 0.1);
			}

			.kmd-modal-footer {
				display: flex;
				gap: 8px;
			}

			.kmd-modal-footer .kmd-btn {
				flex: 1;
			}

			.kmd-error-message {
				color: #e74c3c;
				font-size: 12px;
				margin-top: 12px;
				display: none;
			}

			/* 響應式設計 */
			@media (max-width: 1024px) {
				.kmd-loyalty-grid {
					grid-template-columns: 1fr;
				}

				.kmd-product-grid {
					grid-template-columns: repeat(2, 1fr);
				}

				.kmd-booking-card {
					grid-template-columns: 1fr;
				}
			}

			@media (max-width: 768px) {
				.kayarine-member-dashboard.kmd-redesign-v2 {
					padding: 16px;
				}

				.kmd-welcome-card {
					grid-template-columns: 1fr;
					gap: 16px;
					padding: 16px;
					margin-bottom: 40px;
				}

				.kmd-avatar {
					width: 80px;
					height: 80px;
				}

				.kmd-welcome-title {
					font-size: 24px;
				}

				.kmd-product-grid {
					grid-template-columns: 1fr;
				}

				.kmd-button-group {
					flex-direction: column;
				}

				.kmd-button-group .kmd-btn {
					width: 100%;
				}

				.kmd-recommended-section {
					padding: 40px 16px;
					margin-left: -16px;
					margin-right: -16px;
				}
			}
		</style>

		<script>
		// ========================================
		// 現代化 Member Dashboard JavaScript v2.0
		// 使用 Fetch API + 無刷新 DOM 更新
		// ========================================

		let currentRescheduleOrderId = null;

		// 初始化事件委派
		document.addEventListener('DOMContentLoaded', function() {
			initializeBookingActions();
		});

		function initializeBookingActions() {
			// 改期按鈕
			document.querySelectorAll('[data-action="reschedule"]').forEach(btn => {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					const orderId = this.getAttribute('data-order-id');
					openRescheduleModal(orderId);
				});
			});

			// 取消按鈕
			document.querySelectorAll('[data-action="cancel"]').forEach(btn => {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					const orderId = this.getAttribute('data-order-id');
					if (confirm('確認取消此預約？')) {
						cancelBooking(orderId);
					}
				});
			});
		}

		// 打開改期模態框
		function openRescheduleModal(orderId) {
			currentRescheduleOrderId = orderId;
			const modal = document.getElementById('kmd-reschedule-modal');
			const dateInput = document.getElementById('kmd-reschedule-date');
			const errorDiv = document.getElementById('kmd-reschedule-error');

			modal.style.display = 'flex';
			dateInput.value = '';
			errorDiv.style.display = 'none';

			// 初始化日期選擇器 (flatpickr)
			if (typeof flatpickr !== 'undefined') {
				flatpickr('#kmd-reschedule-date', {
					minDate: 'today',
					disableMobile: true
				});
			}
		}

		// 關閉改期模態框
		function kmdCloseRescheduleModal() {
			const modal = document.getElementById('kmd-reschedule-modal');
			modal.style.display = 'none';
			currentRescheduleOrderId = null;
		}

		// 確認改期
		function confirmReschedule() {
			const orderId = currentRescheduleOrderId;
			const newDate = document.getElementById('kmd-reschedule-date').value;
			const errorDiv = document.getElementById('kmd-reschedule-error');

			if (!newDate) {
				errorDiv.textContent = '請選擇日期';
				errorDiv.style.display = 'block';
				return;
			}

			// 發送 AJAX 請求
			const formData = new FormData();
			formData.append('action', 'kayarine_reschedule_booking');
			formData.append('order_id', orderId);
			formData.append('new_date', newDate);

			fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					showNotification('預約已改期！', 'success');
					kmdCloseRescheduleModal();
					// 2 秒後重新加載
					setTimeout(() => location.reload(), 1500);
				} else {
					errorDiv.textContent = data.data.message || '改期失敗，請重試';
					errorDiv.style.display = 'block';
				}
			})
			.catch(error => {
				console.error('Error:', error);
				errorDiv.textContent = '發生錯誤，請重試';
				errorDiv.style.display = 'block';
			});
		}

		// 取消預約
		function cancelBooking(orderId) {
			const formData = new FormData();
			formData.append('action', 'kayarine_cancel_booking');
			formData.append('order_id', orderId);

			// 顯示 loading 狀態
			const btn = document.querySelector(`[data-action="cancel"][data-order-id="${orderId}"]`);
			if (btn) {
				btn.disabled = true;
				btn.textContent = '處理中...';
			}

			fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					showNotification('預約已取消！', 'success');
					// 2 秒後重新加載
					setTimeout(() => location.reload(), 1500);
				} else {
					alert(data.data.message || '取消失敗，請重試');
					if (btn) {
						btn.disabled = false;
						btn.textContent = '取消';
					}
				}
			})
			.catch(error => {
				console.error('Error:', error);
				alert('發生錯誤，請重試');
				if (btn) {
					btn.disabled = false;
					btn.textContent = '取消';
				}
			});
		}

		// 顯示提示訊息
		function showNotification(message, type = 'info') {
			const notification = document.createElement('div');
			notification.style.cssText = `
				position: fixed;
				top: 20px;
				right: 20px;
				padding: 16px 20px;
				background: ${type === 'success' ? '#27ae60' : '#3498db'};
				color: white;
				border-radius: 4px;
				box-shadow: 0 2px 8px rgba(0,0,0,0.15);
				z-index: 10000;
				font-size: 14px;
				animation: slideIn 0.3s ease;
			`;
			notification.textContent = message;
			document.body.appendChild(notification);

			setTimeout(() => {
				notification.style.animation = 'slideOut 0.3s ease';
				setTimeout(() => notification.remove(), 300);
			}, 2000);
		}

		// 模態框關閉按鈕
		const confirmBtn = document.getElementById('kmd-confirm-reschedule');
		if (confirmBtn) {
			confirmBtn.addEventListener('click', confirmReschedule);
		}

		// 按 ESC 關閉模態框
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				const modal = document.getElementById('kmd-reschedule-modal');
				if (modal && modal.style.display === 'flex') {
					kmdCloseRescheduleModal();
				}
			}
		});

		// 點擊模態框背景關閉
		document.getElementById('kmd-reschedule-modal')?.addEventListener('click', function(e) {
			if (e.target === this) {
				kmdCloseRescheduleModal();
			}
		});
		</script>

		<script>
		// 舊版本兼容函數（防止代碼衝突）
		window.currentRescheduleOrderId = null;

		function kayarineRescheduleBooking(orderId) {
			window.currentRescheduleOrderId = orderId;
			document.getElementById('kb-reschedule-modal').style.display = 'flex';
			document.getElementById('kb-reschedule-date').value = '';
			document.getElementById('kb-reschedule-error').style.display = 'none';
			
			// Initialize date picker
			if (typeof flatpickr !== 'undefined') {
				flatpickr('#kb-reschedule-date', {
					minDate: 'today',
					disableMobile: true
				});
			}
		}

		function kayarineCloseRescheduleModal() {
			document.getElementById('kb-reschedule-modal').style.display = 'none';
			window.currentRescheduleOrderId = null;
		}

		function kayarineConfirmReschedule() {
			var orderId = window.currentRescheduleOrderId;
			var newDate = document.getElementById('kb-reschedule-date').value;

			if (!newDate) {
				document.getElementById('kb-reschedule-error').textContent = '請選擇日期';
				document.getElementById('kb-reschedule-error').style.display = 'block';
				return;
			}

			jQuery.ajax({
				type: 'POST',
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				data: {
					action: 'kayarine_reschedule_booking',
					order_id: orderId,
					new_date: newDate
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						document.getElementById('kb-reschedule-error').textContent = response.data.message;
						document.getElementById('kb-reschedule-error').style.display = 'block';
					}
				}
			});
		}

		function kayarineCancelBooking(orderId) {
			if (!confirm('確認取消此預約？')) {
				return;
			}

			jQuery.ajax({
				type: 'POST',
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				data: {
					action: 'kayarine_cancel_booking',
					order_id: orderId
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert(response.data.message);
					}
				}
			});
		}
		</script>

		<style>
			.kayarine-member-dashboard {
				max-width: 1000px;
				margin: 0 auto;
				padding: 20px;
			}

			.kb-dashboard-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 30px;
				padding-bottom: 15px;
				border-bottom: 2px solid #e2e8f0;
			}

			.kb-dashboard-header h1 {
				margin: 0;
				font-size: 28px;
			}

			.kb-tier-badge {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: white;
				padding: 8px 16px;
				border-radius: 20px;
				font-size: 14px;
				font-weight: 600;
			}

			.kb-loyalty-section {
				margin-bottom: 40px;
			}

			.kb-loyalty-card {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
				gap: 20px;
				margin-bottom: 20px;
			}

			.kb-loyalty-item {
				background: #f7fafc;
				padding: 20px;
				border-radius: 8px;
				text-align: center;
				border: 1px solid #e2e8f0;
			}

			.kb-loyalty-label {
				font-size: 12px;
				color: #718096;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				margin-bottom: 8px;
			}

			.kb-loyalty-value {
				font-size: 24px;
				font-weight: 700;
				color: #2d3748;
			}

			.kb-bookings-section {
				background: #fff;
				padding: 20px;
				border-radius: 8px;
				border: 1px solid #e2e8f0;
			}

			.kb-bookings-section h2 {
				margin-top: 0;
				font-size: 20px;
				margin-bottom: 20px;
			}

			.kb-no-orders {
				text-align: center;
				color: #718096;
				padding: 40px 20px;
			}

			.kb-bookings-list {
				display: flex;
				flex-direction: column;
				gap: 15px;
			}

			.kb-booking-row {
				display: grid;
				grid-template-columns: 1fr 1fr 150px 150px;
				gap: 15px;
				padding: 15px;
				border: 1px solid #e2e8f0;
				border-radius: 6px;
				align-items: center;
			}

			.kb-booking-row.pending {
				background: #fef5e7;
				border-left: 4px solid #f39c12;
			}

			.kb-booking-row.processing {
				background: #eaf2f8;
				border-left: 4px solid #3498db;
			}

			.kb-booking-row.completed {
				background: #eafaf1;
				border-left: 4px solid #27ae60;
			}
	
			.kb-booking-row.on-hold {
				background: #fff8e1;
				border-left: 4px solid #ff9800;
			}
	
			.kb-booking-row.cancelled {
				background: #fadbd8;
				border-left: 4px solid #e74c3c;
			}

			.kb-booking-info {
				flex: 1;
			}
			
			/* ✅ 新增：優化訂單標題顯示 */
			.kb-booking-header {
				display: flex;
				align-items: center;
				gap: 12px;
				margin-bottom: 6px;
				flex-wrap: wrap;
			}

			.kb-booking-order-id {
				font-weight: 700;
				font-size: 14px;
				color: #2d3748;
			}
			
			.kb-booking-quantity {
				display: inline-block;
				padding: 2px 8px;
				background: #edf2f7;
				border-radius: 4px;
				font-size: 12px;
				color: #4a5568;
				font-weight: 500;
			}

			.kb-booking-date-info {
				font-size: 13px;
				color: #4a5568;
				margin-bottom: 4px;
				font-weight: 500;
			}

			.kb-booking-date {
				font-size: 13px;
				color: #718096;
			}

			.kb-booking-items {
				font-size: 13px;
				color: #4a5568;
				margin-top: 4px;
			}

			.kb-booking-status {
				display: inline-block;
				padding: 4px 12px;
				border-radius: 4px;
				font-size: 12px;
				font-weight: 600;
				text-transform: uppercase;
			}

			.kb-booking-status.pending {
				background: #f39c12;
				color: white;
			}

			.kb-booking-status.processing {
				background: #3498db;
				color: white;
			}

			.kb-booking-status.completed {
				background: #27ae60;
				color: white;
			}

			.kb-booking-status.cancelled {
				background: #e74c3c;
				color: white;
			}

			.kb-booking-amount {
				text-align: right;
				font-weight: 600;
			}

			.kb-booking-actions {
				display: flex;
				gap: 10px;
				flex-wrap: wrap;
			}

			.kb-btn-small {
				padding: 6px 12px;
				font-size: 12px;
				border: 1px solid #cbd5e0;
				background: white;
				color: #2d3748;
				border-radius: 4px;
				cursor: pointer;
				text-decoration: none;
				transition: all 0.2s;
			}

			.kb-btn-small:hover {
				background: #f7fafc;
				border-color: #a0aec0;
			}

			.kb-btn-small.danger {
				border-color: #fc8181;
				color: #c53030;
			}

			.kb-btn-small.danger:hover {
				background: #fff5f5;
				border-color: #f56565;
			}

			@media (max-width: 768px) {
				.kb-booking-row {
					grid-template-columns: 1fr;
				}

				.kb-loyalty-card {
					grid-template-columns: 1fr;
				}
			}
		</style>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a single booking card - 新設計版本
	 * 蘋果簡約風格 + 活力橙品牌色
	 */
	private function render_booking_card( $order, $user_id ) {
		$order_id = $order->get_id();
		$status = $order->get_status();
		$total = $order->get_total();
		$date_created = $order->get_date_created();

		// Extract booking items and dates
		$items_summary = array();
		$booking_dates = array();
		$total_qty = 0;

		foreach ( $order->get_items() as $item ) {
			$product_name = $item->get_name();
			$qty = $item->get_quantity();
			$booking_date = $item->get_meta( '_kayarine_booking_date' );

			// Summarize items with quantity
			if ( $qty > 1 ) {
				$items_summary[] = $product_name . ' ×' . $qty;
			} else {
				$items_summary[] = $product_name;
			}
			
			// Track total quantity
			$total_qty += $qty;

			// Collect dates
			if ( $booking_date ) {
				$booking_dates[] = $booking_date;
			}
		}

		$first_date = ! empty( $booking_dates ) ? $booking_dates[0] : $date_created->format( 'Y-m-d' );
		$items_display = implode( ', ', array_unique( $items_summary ) );
		
		// ✅ on-hold 訂單標記為「未確認」
		$status_label = wc_get_order_status_name( $status );
		if ( $status === 'on-hold' ) {
			$status_label = '未確認 (待支付)';
		}
		
		$status_class = str_replace( 'wc-', '', $status );

		// 只允許已付款訂單取消和改期
		$can_cancel = in_array( $status, array( 'completed', 'processing' ) );
		$can_reschedule = in_array( $status, array( 'pending', 'processing', 'on-hold' ) ) && ! empty( $booking_dates );

		// 狀態顏色對應
		$status_color_map = array(
			'completed' => '#27ae60',
			'processing' => '#3498db',
			'pending' => '#f39c12',
			'on-hold' => '#ff9800',
			'cancelled' => '#e74c3c'
		);
		$status_color = isset( $status_color_map[ $status ] ) ? $status_color_map[ $status ] : '#999';
		?>
		<div class="kmd-booking-card kmd-booking-status-<?php echo esc_attr( $status_class ); ?>" style="border-left-color: <?php echo esc_attr( $status_color ); ?>;">
			<div class="kmd-booking-info">
				<h3 class="kmd-booking-title"><?php echo esc_html( $items_display ); ?></h3>
				<p class="kmd-booking-detail">📍 訂單 #<?php echo esc_html( $order_id ); ?></p>
			</div>
			
			<div class="kmd-booking-info">
				<p class="kmd-booking-detail">📅 <?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $first_date ) ) ); ?></p>
				<p class="kmd-booking-detail">🕐 設備: <?php echo intval( $total_qty ); ?> 台</p>
			</div>
			
			<div class="kmd-booking-amount"><?php echo wc_price( $total ); ?></div>
			
			<div class="kmd-booking-actions">
				<?php if ( $can_reschedule ) : ?>
					<button type="button" class="kmd-btn-small" data-order-id="<?php echo $order_id; ?>" data-action="reschedule">改期</button>
				<?php endif; ?>
				<?php if ( $can_cancel ) : ?>
					<button type="button" class="kmd-btn-small kmd-btn-danger" data-order-id="<?php echo $order_id; ?>" data-action="cancel">取消</button>
				<?php endif; ?>
			</div>
			
			<span class="kmd-booking-status-badge kmd-status-<?php echo esc_attr( $status_class ); ?>" style="background-color: <?php echo esc_attr( $status_color ); ?>;">
				<?php echo esc_html( $status_label ); ?>
			</span>
		</div>
		<?php
	}

	/**
	 * AJAX: Cancel Booking
	 * ✅ 修復：
	 * - 只允許已付款（completed、processing）訂單取消
	 * - 自動計算應退還積分（基於應付金額，即訂單總額-已使用積分）
	 * - 取消後保留訂單記錄
	 */
	public function ajax_cancel_booking() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => '請先登入' ) );
		}

		$order_id = intval( $_POST['order_id'] );
		$order = wc_get_order( $order_id );
		$user_id = get_current_user_id();
		
		// ✅ 清除儀表板緩存（訂單變更後需重新計算）
		delete_transient( 'kayarine_dashboard_orders_' . $user_id );
		
		// ✅ 調試日誌
		error_log( "[Kayarine] Cancel: order_id=$order_id, order=" . ($order ? 'found' : 'null') . ", user_id=$user_id, order_user_id=" . ($order ? $order->get_user_id() : 'N/A') );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => '訂單不存在 (ID: ' . $order_id . ')' ) );
		}
		
		if ( intval( $order->get_user_id() ) !== $user_id ) {
			wp_send_json_error( array( 'message' => '無權限操作此訂單' ) );
		}

		// ✅ 修復 5: 只允許已付款訂單取消（completed 或 processing）
		$status = $order->get_status();
		if ( ! in_array( $status, array( 'completed', 'processing' ) ) ) {
			$status_label = wc_get_order_status_name( $status );
			wp_send_json_error( array( 'message' => "只有已付款訂單（$status_label）才可取消" ) );
		}

		// ✅ 修復 4: 自動計算並退還積分
		// 應退積分 = 訂單原始總額（退還用戶支付的原始金額）
		// 計算方式：訂單小計 + 運費 - 任何折扣
		$subtotal = floatval( $order->get_subtotal() );
		$shipping = floatval( $order->get_shipping_total() );
		$refund_amount = intval( $subtotal + $shipping );

		error_log( "[Kayarine Cancel] Order: $order_id, Subtotal: $subtotal, Shipping: $shipping, Refund: $refund_amount" );

		// 標記訂單為已取消（保留記錄）
		$order->set_status( 'cancelled' );
		$order->add_order_note( "訂單已於 " . current_time( 'Y-m-d H:i:s' ) . " 取消，退還 $refund_amount 積分" );
		$order->save();

		// 退還積分給用戶（基於應付金額）
		if ( $refund_amount > 0 ) {
			$membership = new Kayarine_Membership();
			$membership->adjust_points(
				$user_id,
				$refund_amount,
				'refund',
				$order_id,
				"訂單 #{$order_id} 取消 - 退還 {$refund_amount} 積分（應付金額）"
			);
			error_log( "[Kayarine Cancel] Refunded $refund_amount points to user $user_id" );
		}

		// ✅ 修復 4: 立即還原庫存（不需要等待 trash）
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$qty = $item->get_quantity();
			$date = $item->get_meta( '_kayarine_booking_date' );
			
			if ( $date && class_exists( 'Kayarine_Inventory' ) ) {
				// 清除此日期的庫存緩存，使其重新計算可用庫存
				Kayarine_Inventory::clear_cache( $date );
				
				// 記錄庫存還原
				error_log( "[Kayarine Cancel] Inventory restored - Product: $product_id, Qty: $qty, Date: $date" );
			}
		}

		// ✅ 新增：清除待處理使用記錄（防止孤立的 pending 占用庫存）
		if ( class_exists( 'Kayarine_Inventory' ) ) {
			Kayarine_Inventory::clear_pending_usage( $order_id );
			error_log( "[Kayarine Cancel] Cleared pending_usage for order: $order_id" );
		}

		wp_send_json_success( array( 'message' => "訂單已取消，已退還 {$refund_amount} 積分，庫存已還原" ) );
	}

	/**
	 * AJAX: Reschedule Booking
	 */
	public function ajax_reschedule_booking() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => '請先登入' ) );
		}

		$order_id = intval( $_POST['order_id'] );
		$new_date = sanitize_text_field( $_POST['new_date'] );
		$order = wc_get_order( $order_id );
		$user_id = get_current_user_id();
		
		// ✅ 清除儀表板緩存（訂單變更後需重新計算）
		delete_transient( 'kayarine_dashboard_orders_' . $user_id );
		
		// ✅ 調試日誌
		error_log( "[Kayarine] Reschedule: order_id=$order_id, new_date=$new_date, order=" . ($order ? 'found' : 'null') . ", user_id=$user_id, order_user_id=" . ($order ? $order->get_user_id() : 'N/A') );

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => '訂單不存在 (ID: ' . $order_id . ')' ) );
		}
		
		if ( intval( $order->get_user_id() ) !== $user_id ) {
			wp_send_json_error( array( 'message' => '無權限操作此訂單 (所有者: ' . $order->get_user_id() . ', 當前: ' . $user_id . ')' ) );
		}

		// ✅ 修復 5: 驗證 blocking date - 改期不應選擇已封鎖的日期
		if ( class_exists( 'Kayarine_Inventory' ) && Kayarine_Inventory::is_blackout( $new_date ) ) {
			error_log( "[Kayarine Reschedule] Blocked date attempt: $new_date" );
			wp_send_json_error( array( 'message' => '選擇的日期已被封鎖，無法改期' ) );
		}

		// Check inventory for all items
		$needs_inventory = array();
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			$qty = $item->get_quantity();
			if ( ! isset( $needs_inventory[ $product_id ] ) ) {
				$needs_inventory[ $product_id ] = 0;
			}
			$needs_inventory[ $product_id ] += $qty;
		}

		// Validate against inventory
		$limits = Kayarine_Inventory::get_limits();
		$usage = Kayarine_Inventory::get_daily_usage( $new_date );

		foreach ( $needs_inventory as $product_id => $qty ) {
			$limit = isset( $limits[ $product_id ] ) ? $limits[ $product_id ] : 0;
			$used = isset( $usage[ $product_id ] ) ? $usage[ $product_id ] : 0;

			if ( ( $used + $qty ) > $limit ) {
				wp_send_json_error( array( 
					'message' => '選擇日期庫存不足，無法改期' 
				) );
			}
		}

		// ✅ 修復：先讀取舊日期，再更新，最後清除快取
		$old_dates = array(); // Collect old dates BEFORE updating
		foreach ( $order->get_items() as $item ) {
			$old_date = $item->get_meta( '_kayarine_booking_date' );
			if ( $old_date ) {
				$old_dates[] = $old_date;
			}
		}

		// Now update all items to new date
		foreach ( $order->get_items() as $item ) {
			$item->update_meta_data( '_kayarine_booking_date', $new_date );
			$item->save();
		}

		// Clear cache for old dates (collected before update)
		foreach ( array_unique( $old_dates ) as $old_date ) {
			if ( $old_date !== $new_date ) {
				Kayarine_Inventory::clear_cache( $old_date );
			}
		}
		// Clear cache for new date
		Kayarine_Inventory::clear_cache( $new_date );

		// Add order note
		$order->add_order_note( '預約日期已改期至 ' . $new_date );
		$order->save();

		wp_send_json_success( array( 'message' => '預約已改期' ) );
	}
}

// ✅ 已移至 class-kayarine-booking.php 避免雙重初始化
// new Kayarine_Member_Dashboard();
