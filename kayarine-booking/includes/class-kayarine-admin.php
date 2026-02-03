<?php
/**
 * Admin Settings Class
 * Creates a settings page in WP Admin to manage inventory limits and blackout dates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Kayarine Booking Settings',
            'Kayarine Booking',
            'manage_options',
            'kayarine-booking-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-calendar-alt',
            56
        );
    }

    public function register_settings() {
        // We will manually handle saving in render_settings_page to avoid options.php array issues
        // But we still register them to whitelist them
        register_setting( 'kayarine_settings_group', 'kayarine_inventory_limits' );
        register_setting( 'kayarine_settings_group', 'kayarine_blackout_dates' );
    }

    public function render_settings_page() {
        // Manual Save Logic
        if ( isset( $_POST['kayarine_save_settings'] ) && check_admin_referer( 'kayarine_save_action', 'kayarine_nonce' ) ) {
            
            // 1. Save Limits (Individual Options)
            if ( isset( $_POST['kayarine_inventory_limits'] ) && is_array( $_POST['kayarine_inventory_limits'] ) ) {
                foreach ( $_POST['kayarine_inventory_limits'] as $id => $val ) {
                    // Save as: kayarine_limit_{ID}
                    $key = 'kayarine_limit_' . intval($id);
                    $clean_val = intval($val);
                    update_option( $key, $clean_val );
                }
            }

            // 2. Save Blackout Dates
            if ( isset( $_POST['kayarine_blackout_dates'] ) ) {
                update_option( 'kayarine_blackout_dates', sanitize_textarea_field( $_POST['kayarine_blackout_dates'] ) );
            }

            // 3. Save Debug Mode
            $debug_mode = isset( $_POST['kayarine_debug_mode'] ) ? 1 : 0;
            update_option( 'kayarine_debug_mode', $debug_mode );

            echo '<div class="notice notice-success is-dismissible"><p>Settings Saved Successfully!</p></div>';
        }

        $limits = Kayarine_Inventory::get_limits();
        $blackout_dates = get_option( 'kayarine_blackout_dates', '' );
        $debug_mode = get_option( 'kayarine_debug_mode', 0 );
        ?>
        <div class="wrap">
            <h1>Kayarine Booking Settings</h1>
            <!-- Point to current page instead of options.php for manual handling -->
            <form method="post" action="">
                <?php wp_nonce_field( 'kayarine_save_action', 'kayarine_nonce' ); ?>
                
                <h2>🛶 Inventory Limits (Total Fleet Size)</h2>
                <p>Set the maximum number of items available for booking per day.</p>
                <table class="form-table">
                    <?php 
                    $products = array(
                        Kayarine_Config::ID_SINGLE_KAYAK => 'Single Kayak',
                        Kayarine_Config::ID_DOUBLE_KAYAK => 'Double Kayak',
                        Kayarine_Config::ID_FAMILY_KAYAK => 'Family Kayak',
                        Kayarine_Config::ID_SUP          => 'Stand Up Paddle (SUP)',
                        Kayarine_Config::ID_SNORKEL_RENT => 'Snorkel Mask',
                        Kayarine_Config::ID_PHONE_CASE   => 'Phone Case',
                    );

                    foreach ( $products as $id => $name ) : 
                        $val = isset( $limits[$id] ) ? $limits[$id] : 0;
                    ?>
                        <tr valign="top">
                            <th scope="row"><?php echo esc_html( $name ); ?> (ID: <?php echo $id; ?>)</th>
                            <td>
                                <input type="number" name="kayarine_inventory_limits[<?php echo $id; ?>]" value="<?php echo esc_attr( $val ); ?>" class="small-text" min="0" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                
                <hr>

                <h2>📅 Blackout Dates (No Booking)</h2>
                <p>Enter dates where booking is disabled. One rule per line.</p>
                <p>Supported formats:</p>
                <ul style="list-style:disc; margin-left:20px;">
                    <li><strong>Global Block:</strong> <code>2026-05-20</code> (Blocks EVERYTHING)</li>
                    <li><strong>Range:</strong> <code>2026-05-20 to 2026-05-25</code></li>
                    <li><strong>Recurring:</strong> <code>Every Monday</code> (Blocks EVERYTHING on Mondays)</li>
                    <li><strong>Specific Product/Tour:</strong> <code>Every Sunday | ID:6958</code> (Blocks product 6958 on Sundays)</li>
                    <li><strong>By Tag:</strong> <code>Every Monday | Tag:sunrise-tour</code> (Blocks all products with tag 'sunrise-tour')</li>
                </ul>
                <textarea name="kayarine_blackout_dates" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( $blackout_dates ); ?></textarea>
                
                <input type="hidden" name="kayarine_save_settings" value="1">
                <hr>
                
                <h2>🐞 System Tools</h2>
                <label>
                    <input type="checkbox" name="kayarine_debug_mode" value="1" <?php checked( $debug_mode, 1 ); ?>>
                    Enable Debug Logging
                </label>
                <p class="description">Logs will be saved to <code>wp-content/kayarine-debug.log</code></p>

                <?php if ( $debug_mode ) : ?>
                    <div style="background:#f0f0f1; padding:10px; margin-top:10px; border:1px solid #ccd0d4; max-height:300px; overflow-y:auto;">
                        <strong>Debug Log (Last 50 lines):</strong>
                        <pre style="font-size:11px;"><?php echo esc_html( Kayarine_Inventory::get_log_tail() ); ?></pre>
                    </div>
                <?php endif; ?>

                <input type="hidden" name="kayarine_save_settings" value="1">
                <?php submit_button( 'Save Changes' ); ?>
            </form>
        </div>
        <?php
    }
}
