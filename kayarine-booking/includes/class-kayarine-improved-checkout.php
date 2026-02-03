<?php
/**
 * Kayarine Improved Checkout Manager
 * 改進版本：不依賴 Session，改用訂單元數據和表單欄位
 * 主要改進：
 * - 積分請求通過表單隱藏欄位提交，不依賴 AJAX
 * - 所有積分信息記錄到訂單元數據，確保持久化
 * - 多重 Hook 觸發，確保積分扣除和回饋
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Improved_Checkout {

	public function __construct() {
		// 1️⃣ 結帳頁面：顯示積分選項和隱藏欄位
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_points_form' ) );
		
		// 2️⃣ 訂單建立時：記錄積分請求
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'record_points_request' ), 10, 3 );
		
		// 3️⃣ 費用添加：直接添加到訂單而非依賴 Cart 費用
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_points_fee_to_order' ), 20, 3 );
		
		// 4️⃣ 訂單支付完成時：扣除積分（多重 Hook）
		add_action( 'woocommerce_order_status_pending_to_processing', array( $this, 'deduct_points_on_payment' ) );
		add_action( 'woocommerce_order_status_pending_to_completed', array( $this, 'deduct_points_on_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'deduct_points_on_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'deduct_points_on_payment' ) );
		
		// 5️⃣ 訂單完成時：獲得回饋積分
		add_action( 'woocommerce_order_status_completed', array( $this, 'add_reward_points' ), 20 );
		
		// 6️⃣ 訂單取消時：退還積分
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'refund_points_on_cancellation' ) );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'refund_points_on_cancellation' ) );
		
		error_log( "[Kayarine Improved Checkout] Class initialized with all hooks registered" );
	}

	/**
	 * 1️⃣ 結帳頁面：顯示積分選項和隱藏欄位
	 * 不依賴 AJAX，改用表單隱藏欄位
	 */
	public function render_points_form() {
		if ( ! is_user_logged_in() ) {
			echo '<div class="woocommerce-info">登入後可使用會員積分。</div>';
			return;
		}

		$user_id = get_current_user_id();
		$points = (int) get_user_meta( $user_id, 'kayarine_points_balance', true );
		$tier = Kayarine_Membership::get_tier( $user_id );
		$tier_info = Kayarine_Membership::get_tier_info( $tier );
		
		// 計算可用積分
		$cart_total = WC()->cart->get_subtotal() + WC()->cart->get_shipping_total();
		$can_use_points = $points > 0 && $cart_total > 0;
		$auto_points = $can_use_points ? min( $points, intval( $cart_total ) ) : 0;
		
		?>
		<div class="kayarine-checkout-points" style="padding: 15px; margin-bottom: 20px; border: 1px solid #e0e0e0; border-radius: 8px; background: #f9f9f9;">
			<h4 style="margin-top: 0;">會員積分 (<?php echo esc_html( $tier_info['label'] ); ?>)</h4>
			
			<?php if ( $can_use_points ) : ?>
				<div style="margin-bottom: 15px;">
					<label style="display: flex; align-items: center; gap: 10px;">
						<input type="checkbox" id="kayarine_use_points" name="kayarine_use_points" value="1" checked style="width: auto;">
						<span>自動使用積分折抵</span>
					</label>
				</div>
				
				<!-- ✅ 關鍵改進：使用隱藏欄位而非 AJAX -->
				<input type="hidden" id="kayarine_points_request" name="kayarine_points_request" value="<?php echo esc_attr( $auto_points ); ?>">
				
				<div id="kayarine_points_display" style="padding: 10px; background: white; border-radius: 4px; border-left: 4px solid #4CAF50;">
					<p style="margin: 0; font-weight: bold;">
						將折抵: <span id="kayarine_points_amount"><?php echo esc_html( $auto_points ); ?></span> 分 = HK$<span id="kayarine_points_dollar"><?php echo esc_html( $auto_points ); ?></span>
					</p>
					<?php if ( $points > $auto_points ) : ?>
						<p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
							剩餘積分: <?php echo esc_html( $points - $auto_points ); ?> 分
						</p>
					<?php endif; ?>
				</div>
				
				<p style="font-size: 12px; color: #666; margin-top: 8px;">
					每 1 積分可折抵 HK$1。系統將自動使用最多積分，不會超過帳單金額。
				</p>
				
				<script>
				jQuery(document).ready(function($) {
					var maxPoints = <?php echo esc_attr( $auto_points ); ?>;
					
					// 複選框改變時更新隱藏欄位
					$('#kayarine_use_points').change(function() {
						var usePoints = this.checked ? maxPoints : 0;
						$('#kayarine_points_request').val(usePoints);
						
						// 更新顯示
						$('#kayarine_points_amount').text(usePoints);
						$('#kayarine_points_dollar').text(usePoints);
						$('#kayarine_points_display').css('opacity', usePoints > 0 ? '1' : '0.5');
						
						console.log('[Kayarine Checkout] Points request updated:', usePoints);
					});
					
					// 頁面加載時，如果複選框已勾選，確保隱藏欄位值正確
					if ($('#kayarine_use_points').is(':checked')) {
						$('#kayarine_points_request').val(maxPoints);
					}
				});
				</script>
			<?php else : ?>
				<p style="color: #666; margin: 0;">
					<?php
					if ( $points <= 0 ) {
						echo '目前沒有可用積分';
					} else {
						echo '購物車為空，無法使用積分';
					}
					?>
				</p>
				<input type="hidden" id="kayarine_points_request" name="kayarine_points_request" value="0">
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * 2️⃣ 訂單建立時：記錄積分請求
	 */
	public function record_points_request( $order_id, $posted_data, $order ) {
		// 從 POST 數據獲取積分請求值
		$points_requested = isset( $_POST['kayarine_points_request'] ) 
			? intval( $_POST['kayarine_points_request'] ) 
			: 0;
		
		error_log( "[Kayarine Checkout] Order $order_id created. POST data - Points requested: $points_requested" );
		
		if ( $points_requested <= 0 ) {
			error_log( "[Kayarine Checkout] No points requested for order $order_id" );
			return;
		}
		
		$user_id = $order->get_user_id();
		
		// 驗證：用戶是否有足夠的積分
		$user_points = (int) get_user_meta( $user_id, 'kayarine_points_balance', true );
		
		if ( $points_requested > $user_points ) {
			error_log( "[Kayarine Checkout] Insufficient points - Requested: $points_requested, Available: $user_points" );
			$points_requested = $user_points;
		}
		
		// 驗證：積分不能超過訂單總額
		$order_total = (float) $order->get_total();
		if ( $points_requested > $order_total ) {
			error_log( "[Kayarine Checkout] Points exceed order total - Requested: $points_requested, Total: $order_total" );
			$points_requested = intval( $order_total );
		}
		
		// 記錄到訂單元數據
		$order->update_meta_data( '_kayarine_points_requested', $points_requested );
		$order->update_meta_data( '_kayarine_points_status', 'requested' );
		$order->save();
		
		error_log( "[Kayarine Checkout] Order $order_id - Points request recorded: $points_requested" );
	}

	/**
	 * 3️⃣ 費用添加：直接添加到訂單而非依賴 Cart 費用
	 * 確保費用被持久化到訂單
	 */
	public function add_points_fee_to_order( $order_id, $posted_data, $order ) {
		$points_requested = (int) $order->get_meta( '_kayarine_points_requested' );
		
		if ( ! $points_requested || $points_requested <= 0 ) {
			error_log( "[Kayarine Fee] No fee needed for order $order_id" );
			return;
		}
		
		error_log( "[Kayarine Fee] Adding fee to order $order_id for $points_requested points" );
		
		// 檢查是否已添加過費用
		$has_fee = false;
		foreach ( $order->get_items( 'fee' ) as $fee_item ) {
			if ( strpos( $fee_item->get_name(), '會員積分' ) !== false ) {
				$has_fee = true;
				error_log( "[Kayarine Fee] Fee already exists, skipping" );
				break;
			}
		}
		
		if ( ! $has_fee ) {
			// 添加費用項目（負值 = 折扣）
			$fee = new WC_Order_Item_Fee();
			$fee->set_name( '會員積分折抵' );
			$fee->set_amount( -$points_requested );  // 負值表示折扣
			$fee->set_tax_class( '' );
			$fee->set_tax_status( 'none' );
			
			$order->add_item( $fee );
			$order->save();
			
			error_log( "[Kayarine Fee] Fee added successfully" );
		}
	}

	/**
	 * 4️⃣ 訂單支付完成時：扣除積分
	 * 多重 Hook 確保至少一個會被觸發
	 */
	public function deduct_points_on_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;
		
		// 檢查是否已扣除
		if ( $order->get_meta( '_kayarine_points_deducted' ) ) {
			error_log( "[Kayarine Deduct] Order $order_id already processed" );
			return;
		}
		
		$points_requested = (int) $order->get_meta( '_kayarine_points_requested' );
		if ( ! $points_requested || $points_requested <= 0 ) {
			error_log( "[Kayarine Deduct] No points to deduct for order $order_id" );
			return;
		}
		
		$user_id = $order->get_user_id();
		
		error_log( "[Kayarine Deduct] Processing deduction for order $order_id - User: $user_id, Points: $points_requested" );
		
		// 再次驗證用戶積分充足
		$user_points = (int) get_user_meta( $user_id, 'kayarine_points_balance', true );
		$actual_deduction = min( $points_requested, $user_points );
		
		if ( $actual_deduction <= 0 ) {
			error_log( "[Kayarine Deduct] Insufficient points at deduction time" );
			$order->update_meta_data( '_kayarine_points_status', 'failed_insufficient_points' );
			$order->save();
			return;
		}
		
		// 執行扣除
		$membership = new Kayarine_Membership();
		$result = $membership->adjust_points(
			$user_id,
			-$actual_deduction,
			'redeem',
			$order_id,
			"訂單 #{$order_id} - 積分折抵"
		);
		
		if ( $result ) {
			$order->update_meta_data( '_kayarine_points_deducted', $actual_deduction );
			$order->update_meta_data( '_kayarine_points_status', 'deducted' );
			$order->save();
			
			error_log( "[Kayarine Deduct] Successfully deducted $actual_deduction points" );
		} else {
			error_log( "[Kayarine Deduct] Deduction failed" );
			$order->update_meta_data( '_kayarine_points_status', 'failed' );
			$order->save();
		}
	}

	/**
	 * 5️⃣ 訂單完成時：獲得回饋積分
	 */
	public function add_reward_points( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;
		
		// 檢查是否已獎勵
		if ( $order->get_meta( '_kayarine_points_awarded' ) ) {
			error_log( "[Kayarine Reward] Order $order_id already awarded" );
			return;
		}
		
		$user_id = $order->get_user_id();
		if ( ! $user_id ) return;
		
		// 計算回饋基礎：訂單總額 + 使用的積分（視為「收入」）
		$order_total = (float) $order->get_total();
		$points_used = (int) $order->get_meta( '_kayarine_points_deducted' );
		$earning_base = $order_total + $points_used;
		
		// 獲得等級和回饋率
		$tier = Kayarine_Membership::get_tier( $user_id );
		$tier_info = Kayarine_Membership::get_tier_info( $tier );
		$rate = $tier_info['rate'];
		
		$points_earned = floor( $earning_base * $rate );
		
		error_log( "[Kayarine Reward] Order $order_id - Base: $earning_base, Rate: $rate, Earned: $points_earned, Tier: $tier" );
		
		if ( $points_earned <= 0 ) {
			$order->update_meta_data( '_kayarine_points_awarded', 0 );
			$order->save();
			return;
		}
		
		// 新增積分
		$membership = new Kayarine_Membership();
		$result = $membership->adjust_points(
			$user_id,
			$points_earned,
			'earn',
			$order_id,
			"訂單 #{$order_id} 回饋 ({$tier}級)"
		);
		
		if ( $result ) {
			$order->update_meta_data( '_kayarine_points_awarded', $points_earned );
			$order->save();
			error_log( "[Kayarine Reward] Successfully awarded $points_earned points" );
		}
	}

	/**
	 * 6️⃣ 訂單取消時：退還積分
	 */
	public function refund_points_on_cancellation( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;
		
		// 檢查是否已退款
		if ( $order->get_meta( '_kayarine_points_refunded' ) ) {
			error_log( "[Kayarine Refund] Order $order_id already refunded" );
			return;
		}
		
		$user_id = $order->get_user_id();
		if ( ! $user_id ) return;
		
		$points_deducted = (int) $order->get_meta( '_kayarine_points_deducted' );
		$points_awarded = (int) $order->get_meta( '_kayarine_points_awarded' );
		
		error_log( "[Kayarine Refund] Order $order_id - Deducted: $points_deducted, Awarded: $points_awarded" );
		
		$membership = new Kayarine_Membership();
		
		// 1. 退還已扣除的積分
		if ( $points_deducted > 0 ) {
			$membership->adjust_points(
				$user_id,
				$points_deducted,
				'refund',
				$order_id,
				"訂單 #{$order_id} 取消 - 退還扣除的積分"
			);
		}
		
		// 2. 扣除已獲得的回饋積分
		if ( $points_awarded > 0 ) {
			$membership->adjust_points(
				$user_id,
				-$points_awarded,
				'adjust',
				$order_id,
				"訂單 #{$order_id} 取消 - 扣除回饋積分"
			);
		}
		
		$order->update_meta_data( '_kayarine_points_refunded', 1 );
		$order->save();
		
		error_log( "[Kayarine Refund] Refund completed" );
	}
}
