<?php
/**
 * Frontend Display Handler
 * Injects booking fields into WooCommerce Product Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Booking_Display {

    public function __construct() {
        // Hook into WooCommerce Add to Cart form (Standard Theme)
        add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_booking_fields' ), 10 );
        
        // Add Shortcode for Elementor / Custom Builders
        add_shortcode( 'kayarine_booking', array( $this, 'render_booking_fields_shortcode' ) );
    }

    /**
     * Shortcode Wrapper for Elementor
     */
    public function render_booking_fields_shortcode( $atts ) {
        // Enqueue assets explicitly for Shortcode usage
        wp_enqueue_style( 'flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css' );
        wp_enqueue_script( 'flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true );
        wp_enqueue_style( 'kayarine-booking-css' );
        wp_enqueue_script( 'kayarine-booking-js' );

        $atts = shortcode_atts( array(
            'id'     => null,
            'layout' => 'card', // 'card' (default) or 'full'
        ), $atts );

        $product_id = $atts['id'] ? intval($atts['id']) : get_the_ID();

        ob_start();

        if ( $atts['layout'] === 'full' ) {
            // Full Page Layout (Image Left, Booking Form Right)
            $img_url = get_the_post_thumbnail_url( $product_id, 'large' );
            if (!$img_url) $img_url = wc_placeholder_img_src();
            
            // Get Gallery Images
            $product = wc_get_product($product_id);
            $attachment_ids = $product ? $product->get_gallery_image_ids() : array();
            
            // Prepare Slides: Main Image + Gallery Images
            $slides = array();
            $slides[] = $img_url; // First Slide is Main Image
            
            foreach ( $attachment_ids as $att_id ) {
                $slides[] = wp_get_attachment_image_url( $att_id, 'large' );
            }
            
            // Wrap in FORM to ensure "Add to Cart" works via Shortcode
            $action_url = esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', get_permalink( $product_id ) ) );
            ?>
            <form class="cart" action="<?php echo $action_url; ?>" method="post" enctype="multipart/form-data">
                <div class="product-page-layout">
                    <!-- Left: Product Image Slider (Main + Gallery Combined) -->
                    <div class="left-col">
                        <div class="product-image-slider">
                            <?php foreach ( $slides as $slide_url ) : ?>
                                <div class="product-image-slide" style="background-image:url('<?php echo esc_url( $slide_url ); ?>');"></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Thumbnails below for quick jump -->
                        <?php if ( count($slides) > 1 ) : ?>
                        <div class="gallery-carousel" style="margin-top:10px;">
                            <?php foreach( $slides as $index => $slide_url ) : ?>
                                <div class="gallery-item <?php echo $index === 0 ? 'active' : ''; ?>"
                                     data-index="<?php echo $index; ?>"
                                     style="background-image:url('<?php echo esc_url($slide_url); ?>');">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right: Booking Form -->
                    <div class="booking-sidebar">
                        <?php $this->render_booking_fields( $product_id ); ?>
                    </div>
                </div>
                <!-- Hidden inputs required for WC -->
                <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>" />
            </form>
            <?php
        } else {
            // Standard Card Only
            // Also wrap in form if standalone
            $action_url = esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', get_permalink( $product_id ) ) );
            ?>
            <form class="cart" action="<?php echo $action_url; ?>" method="post" enctype="multipart/form-data">
                <?php $this->render_booking_fields( $product_id ); ?>
                <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>" />
            </form>
            <?php
        }

        return ob_get_clean();
    }

    public function render_booking_fields( $force_product_id = null ) {
        global $product;
        $product_id = 0;

        if ( $force_product_id ) {
            $product_id = intval( $force_product_id );
        } elseif ( $product ) {
            $product_id = $product->get_id();
        } else {
            // Try fallback
            $product_id = get_the_ID();
        }

        if ( ! $product_id ) {
            return; // No ID found
        }
        
        $rules = Kayarine_Config::$product_rules;

        // Check if this product is configured for booking
        if ( ! isset( $rules[ $product_id ] ) ) {
            return;
        }

        $config = $rules[ $product_id ];
        $addons = isset( $config['addons'] ) ? $config['addons'] : array();
        
        // Categorize Add-ons
        $rental_addons = array();
        $sale_addons = array();
        $config_addons = Kayarine_Config::get_js_config();
        $categories = isset($config_addons['addon_categories']) ? $config_addons['addon_categories'] : array();
        
        // Check add-ons against categories
        // If not categorized, assume rental for backward compat unless mapped otherwise
        foreach ( $addons as $addon_id ) {
            $is_sale = false;
            if ( isset($categories['sales']) && in_array($addon_id, $categories['sales']) ) {
                $is_sale = true;
            }
            
            if ($is_sale) {
                $sale_addons[] = $addon_id;
            } else {
                $rental_addons[] = $addon_id;
            }
        }

        // Enqueue Assets
        wp_enqueue_style( 'flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css' );
        wp_enqueue_script( 'flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true );
        wp_enqueue_style( 'kayarine-booking-css' );
        wp_enqueue_script( 'kayarine-booking-js' );

        ?>
        <div id="kayarine-booking-fields" class="kayarine-booking-card" data-product-id="<?php echo esc_attr( $product_id ); ?>">
            <h3 class="kb-title">預約行程</h3>

            <!-- 1. Calendar Section -->
            <div class="kb-date-wrapper">
                <input type="text" id="kayarine_booking_date" name="kayarine_booking_date" class="kb-input-date" placeholder="選擇日期" required readonly style="display:none;">
            </div>
            
            <div class="kb-legend">
                 <div><span class="kb-dot weekday"></span>平日</div>
                 <div><span class="kb-dot weekend"></span>假日</div>
            </div>

            <!-- 2. Quantity Section -->
            <div class="kb-qty-row">
                <span style="font-weight:bold; color:#4a5568;">參加人數</span>
                <div class="kb-pill">
                    <button type="button" class="kb-btn minus">-</button>
                    <input type="number" id="kb-main-qty-display" class="kb-val" value="1" min="1" readonly>
                    <button type="button" class="kb-btn plus">+</button>
                </div>
            </div>

            <!-- 3. Add-ons: Rentals (Text Only List) -->
            <?php if ( ! empty( $rental_addons ) ) : ?>
                <div class="kb-section-header kb-toggle-header" data-target="kb-rental-list">附加租借</div>
                <div id="kb-rental-list" class="kb-toggle-content">
                <?php
                $js_config = Kayarine_Config::get_js_config();
                $names = $js_config['names'];
                $prices = $js_config['prices'];
                
                foreach ( $rental_addons as $addon_id ) :
                    $name = isset($names[$addon_id]) ? $names[$addon_id] : "";
                    $base_price = isset($prices[$addon_id]['weekday']) ? $prices[$addon_id]['weekday'] : 0;
                ?>
                    <div class="kb-rental-item">
                        <div>
                            <div style="font-weight:600;"><?php echo esc_html( $name ); ?></div>
                            <div style="font-size:0.8rem; color:#a0aec0;">+$<?php echo $base_price; ?></div>
                        </div>
                        <div class="kb-pill">
                            <button type="button" class="kb-btn minus">-</button>
                            <input type="number"
                                   name="kayarine_addon[<?php echo $addon_id; ?>]"
                                   class="kb-val kb-addon-qty"
                                   min="0"
                                   value="0"
                                   readonly
                                   data-addon-id="<?php echo $addon_id; ?>">
                            <button type="button" class="kb-btn plus">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div> <!-- End Toggle Content -->
            <?php endif; ?>

            <!-- 4. Add-ons: Sales (Row View with Thumb) -->
            <?php if ( ! empty( $sale_addons ) ) : ?>
                <div class="kb-section-header kb-toggle-header" data-target="kb-sale-list">加購產品</div>
                <div id="kb-sale-list" class="kb-toggle-content">
                <?php
                $js_config = Kayarine_Config::get_js_config();
                $names = $js_config['names'];
                $prices = $js_config['prices'];

                foreach ( $sale_addons as $addon_id ) :
                    $name = isset($names[$addon_id]) ? $names[$addon_id] : "";
                    $base_price = isset($prices[$addon_id]['weekday']) ? $prices[$addon_id]['weekday'] : 0;
                    // Get Product Image
                    $img_url = get_the_post_thumbnail_url( $addon_id, 'thumbnail' );
                    if (!$img_url) $img_url = wc_placeholder_img_src();
                    
                ?>
                    <div class="kb-sale-row">
                        <div class="kb-sale-thumb" style="background-image:url('<?php echo esc_url($img_url); ?>');"></div>
                        <div class="kb-sale-info">
                            <div class="kb-sale-name"><?php echo esc_html( $name ); ?></div>
                            <div class="kb-sale-price">$<?php echo $base_price; ?></div>
                        </div>
                        <div class="kb-pill">
                            <button type="button" class="kb-btn minus">-</button>
                            <input type="number"
                                   name="kayarine_addon[<?php echo $addon_id; ?>]"
                                   class="kb-val kb-addon-qty"
                                   min="0"
                                   value="0"
                                   readonly
                                   data-addon-id="<?php echo $addon_id; ?>">
                            <button type="button" class="kb-btn plus">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- View More Link -->
                <div style="text-align:center; margin-top:-10px; margin-bottom:20px;">
                    <a href="https://kayarine.club/%e5%93%81%e7%89%8c%e5%95%86%e5%ba%97/" target="_blank" style="color:#3182ce; text-decoration:none; font-size:0.9rem;">查看更多產品 &rarr;</a>
                </div>
                </div> <!-- End Toggle Content -->
            <?php endif; ?>

            <!-- 5. Footer Section -->
            <div class="kb-footer">
                <div style="font-weight:bold; margin-bottom:10px;">總計: <span id="kb-total-price" style="color:#ed8936; font-size:1.3rem;">$0</span></div>
                
                <button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>" class="kb-submit">
                    立即預約
                </button>
            </div>

            <!-- Hidden input to pass product type for JS validation -->
            <input type="hidden" id="kb_product_type" value="<?php echo esc_attr($config['type']); ?>">

        </div>
        
        <style>
            /* Hide Default WooCommerce Elements */
            form.cart .single_add_to_cart_button.button.alt:not(.kb-submit-button) { display: none !important; }
            form.cart .quantity { display: none !important; }
        </style>

        <script>
            jQuery(document).ready(function($) {
                var $realQty = $('form.cart input[name="quantity"]');
                var $displayQty = $('#kb-main-qty-display');
                
                if ($realQty.length === 0) {
                    $('<input type="hidden" name="quantity" value="1">').appendTo('form.cart');
                    $realQty = $('form.cart input[name="quantity"]');
                }

                $displayQty.on('change', function() {
                    var val = $(this).val();
                    $realQty.val(val).trigger('change');
                });
            });
        </script>
        <?php
    }
}
