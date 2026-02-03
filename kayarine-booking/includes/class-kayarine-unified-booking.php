<?php
/**
 * Unified Booking Page Shortcode
 * Displays a full booking interface for all equipment on a single page.
 * Usage: [kayarine_unified_booking]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Unified_Booking {

    public function __construct() {
        add_shortcode( 'kayarine_unified_booking', array( $this, 'render_unified_booking' ) );
        add_action( 'wp_ajax_kayarine_add_bundle_to_cart', array( $this, 'ajax_add_bundle_to_cart' ) );
        add_action( 'wp_ajax_nopriv_kayarine_add_bundle_to_cart', array( $this, 'ajax_add_bundle_to_cart' ) );
    }

    public function render_unified_booking() {
        // Enqueue Assets
        wp_enqueue_style( 'flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css' );
        wp_enqueue_script( 'flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true );
        wp_enqueue_style( 'kayarine-booking-css' );
        wp_enqueue_script( 'kayarine-booking-js' );

        // Localize Script for AJAX
        $js_data = Kayarine_Config::get_js_config();
        wp_localize_script( 'kayarine-booking-js', 'kayarine_config', $js_data );
        wp_localize_script( 'kayarine-booking-js', 'kayarine_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
        ));

        // Define Equipment List (Main Rental Items)
        $equipment_ids = array();
        if (defined('Kayarine_Config::ID_SINGLE_KAYAK')) $equipment_ids[] = Kayarine_Config::ID_SINGLE_KAYAK;
        if (defined('Kayarine_Config::ID_DOUBLE_KAYAK')) $equipment_ids[] = Kayarine_Config::ID_DOUBLE_KAYAK;
        if (defined('Kayarine_Config::ID_FAMILY_KAYAK')) $equipment_ids[] = Kayarine_Config::ID_FAMILY_KAYAK;
        if (defined('Kayarine_Config::ID_SUP'))          $equipment_ids[] = Kayarine_Config::ID_SUP;

        // Categorize Add-ons
        $rental_ids = array();
        $sale_ids = array();
        
        $categories = isset($js_data['addon_categories']) ? $js_data['addon_categories'] : array();
        if (isset($categories['rentals'])) $rental_ids = $categories['rentals'];
        if (isset($categories['sales']))   $sale_ids = $categories['sales'];

        $prices = $js_data['prices'];
        $names = $js_data['names'];

        ob_start();
        ?>
        <div id="kayarine-unified-booking" class="unified-booking">
            <h1 class="kb-section-title">預約所有裝備</h1>

            <!-- 1. Date Selection -->
            <div class="kb-subtitle">1. 選擇日期</div>
            <div class="kb-date-wrapper">
                <input type="text" id="kub_date" style="display:none;">
            </div>
            <div class="kb-legend">
                <div><span class="kb-dot weekday"></span>平日</div>
                <div><span class="kb-dot weekend"></span>假日</div>
            </div>

            <!-- 2. Equipment Selection (Main) -->
            <div class="kb-subtitle">2. 選擇裝備</div>
            <div class="kb-grid">
                <?php foreach ( $equipment_ids as $id ) :
                    $product = wc_get_product( $id );
                    if ( ! $product ) continue;
                    
                    $name = $product->get_name();
                    $image_url = get_the_post_thumbnail_url( $id, 'medium' );
                    if (!$image_url) $image_url = wc_placeholder_img_src();

                    $base_price = isset($prices[$id]['weekday']) ? $prices[$id]['weekday'] : 0;
                ?>
                    <div class="kb-card" data-id="<?php echo esc_attr($id); ?>">
                        <div class="kb-card-img" style="background-image:url('<?php echo esc_url($image_url); ?>');"></div>
                        <div class="kb-card-title"><?php echo esc_html($name); ?></div>
                        <div class="kb-card-price">HKD<?php echo $base_price; ?></div>
                        <div class="kb-pill">
                            <button type="button" class="kb-btn minus">-</button>
                            <input class="kb-val kb-qty-input" data-id="<?php echo $id; ?>" value="0" readonly>
                            <button type="button" class="kb-btn plus">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- 3. Add-ons: Rentals -->
            <?php if ( ! empty( $rental_ids ) ) : ?>
            <div class="kb-subtitle">3. 附加租借</div>
            <div class="kb-list-view">
                <?php foreach ( $rental_ids as $id ) :
                    $product = wc_get_product( $id );
                    if ( ! $product ) continue;
                    $name = isset($names[$id]) ? $names[$id] : $product->get_name();
                    $base_price = isset($prices[$id]['weekday']) ? $prices[$id]['weekday'] : 0;
                    $img_url = get_the_post_thumbnail_url( $id, 'thumbnail' );
                    if (!$img_url) $img_url = wc_placeholder_img_src();
                ?>
                    <div class="kb-sale-row" data-id="<?php echo esc_attr($id); ?>">
                        <div class="kb-sale-thumb" style="background-image:url('<?php echo esc_url($img_url); ?>');"></div>
                        <div class="kb-sale-info">
                            <div class="kb-sale-name"><?php echo esc_html($name); ?></div>
                            <div class="kb-sale-price">$<?php echo $base_price; ?></div>
                        </div>
                        <div class="kb-pill" style="width:100px;">
                            <button type="button" class="kb-btn minus">-</button>
                            <input class="kb-val kb-qty-input" data-id="<?php echo $id; ?>" value="0" readonly>
                            <button type="button" class="kb-btn plus">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 4. Add-ons: Sales -->
            <?php if ( ! empty( $sale_ids ) ) : ?>
            <div class="kb-subtitle">4. 加購產品</div>
            <div class="kb-list-view">
                <?php foreach ( $sale_ids as $id ) :
                    $product = wc_get_product( $id );
                    if ( ! $product ) continue;
                    $name = isset($names[$id]) ? $names[$id] : $product->get_name();
                    $base_price = isset($prices[$id]['weekday']) ? $prices[$id]['weekday'] : 0;
                    $img_url = get_the_post_thumbnail_url( $id, 'thumbnail' );
                    if (!$img_url) $img_url = wc_placeholder_img_src();
                ?>
                    <div class="kb-sale-row" data-id="<?php echo esc_attr($id); ?>">
                        <div class="kb-sale-thumb" style="background-image:url('<?php echo esc_url($img_url); ?>');"></div>
                        <div class="kb-sale-info">
                            <div class="kb-sale-name"><?php echo esc_html($name); ?></div>
                            <div class="kb-sale-price">$<?php echo $base_price; ?></div>
                        </div>
                        <div class="kb-pill" style="width:100px;">
                            <button type="button" class="kb-btn minus">-</button>
                            <input class="kb-val kb-qty-input" data-id="<?php echo $id; ?>" value="0" readonly>
                            <button type="button" class="kb-btn plus">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <!-- View More Link -->
                <div style="text-align:center; margin-top:20px;">
                    <a href="https://kayarine.club/%e5%93%81%e7%89%8c%e5%95%86%e5%ba%97/" target="_blank" style="color:#3182ce; text-decoration:none; font-size:0.9rem;">查看更多產品 &rarr;</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="kb-footer">
                <div>總計 Total: <span class="kb-total" id="kub-total-price">$0</span></div>
                <button type="button" id="kub-submit-btn" class="kb-submit">立即預約</button>
            </div>
            <div id="kub-error-msg" class="kb-error-msg" style="display:none; color:red; margin-top:10px; text-align:center;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX Form Submission
     */
    public function ajax_add_bundle_to_cart() {
        $date = sanitize_text_field( $_POST['date'] );
        $items = isset($_POST['items']) ? $_POST['items'] : array();

        if ( empty($date) || empty($items) ) {
            wp_send_json_error( array('message' => 'Missing date or items') );
        }

        // Validate Date (Blackout)
        if ( Kayarine_Config::is_blackout($date) ) {
            wp_send_json_error( array('message' => 'Selected date is unavailable.') );
        }

        $group_id = uniqid(); 
        $added_count = 0;

        foreach ( $items as $item ) {
            $product_id = intval($item['id']);
            $qty = intval($item['qty']);

            if ( $qty > 0 ) {
                $cart_item_data = array(
                    'kayarine_booking_date' => $date,
                    'kayarine_booking_group' => $group_id
                );
                
                WC()->cart->add_to_cart( $product_id, $qty, 0, array(), $cart_item_data );
                $added_count++;
            }
        }

        if ( $added_count > 0 ) {
            wp_send_json_success( array('redirect' => wc_get_cart_url()) );
        } else {
            wp_send_json_error( array('message' => 'No items added.') );
        }
    }
}
