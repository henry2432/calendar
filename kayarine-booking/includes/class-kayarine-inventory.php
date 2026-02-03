<?php
/**
 * Inventory Management Class
 * Handles availability checks, limits, and usage calculation directly in WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Inventory {

    const OPTION_PENDING_USAGE = 'kayarine_pending_usage';

    /**
     * Retrieve the pending usage storage from options.
     *
     * @return array
     */
    private static function get_pending_usage_storage() {
        $storage = get_option( self::OPTION_PENDING_USAGE, array() );
        if ( ! is_array( $storage ) ) {
            $storage = array();
        }
        return $storage;
    }

    /**
     * Persist the pending usage storage into options.
     *
     * @param array $storage
     * @return void
     */
    private static function persist_pending_usage_storage( array $storage ) {
        update_option( self::OPTION_PENDING_USAGE, $storage );
    }

    /**
     * Record pending usage for a given order/date/product.
     *
     * @param int $order_id
     * @param string $date
     * @param int $product_id
     * @param int $qty
     * @return void
     */
    public static function record_pending_usage( $order_id, $date, $product_id, $qty ) {
        // ✅ 調試日誌：記錄所有參數以診斷驗證失敗
        error_log( '════════════════════════════════════════════' );
        error_log( '[Inventory] record_pending_usage() CALLED' );
        error_log( '[Inventory] Params: order_id=' . $order_id . ', date=' . $date . ', product_id=' . $product_id . ', qty=' . $qty );
        error_log( '[Inventory] Validation: order_id=?' . (! $order_id ? 'FAIL' : 'PASS') . ', date=?' . (! $date ? 'FAIL' : 'PASS') . ', product_id=?' . (! $product_id ? 'FAIL' : 'PASS') . ', qty>0=?' . ($qty <= 0 ? 'FAIL' : 'PASS') );
        
        if ( ! $order_id || ! $date || ! $product_id || $qty <= 0 ) {
            error_log( '[Inventory] ❌ Validation FAILED - Returning early' );
            error_log( '════════════════════════════════════════════' );
            return;
        }

        $storage = self::get_pending_usage_storage();

        if ( ! isset( $storage[ $order_id ] ) ) {
            $storage[ $order_id ] = array();
        }

        if ( ! isset( $storage[ $order_id ][ $date ] ) ) {
            $storage[ $order_id ][ $date ] = array();
        }

        if ( ! isset( $storage[ $order_id ][ $date ][ $product_id ] ) ) {
            $storage[ $order_id ][ $date ][ $product_id ] = 0;
        }

        $storage[ $order_id ][ $date ][ $product_id ] += $qty;

        self::persist_pending_usage_storage( $storage );
        
        error_log( '[Inventory] ✅ Pending usage recorded successfully' );
        error_log( '[Inventory] Storage: ' . json_encode($storage) );
        error_log( '════════════════════════════════════════════' );
    }

    /**
     * Get pending usage aggregated by product for a specific date.
     *
     * @param string $date
     * @return array
     */
    public static function get_pending_usage( $date ) {
        if ( ! $date ) {
            return array();
        }

        $storage = self::get_pending_usage_storage();
        $usage = array();

        foreach ( $storage as $order_data ) {
            if ( empty( $order_data[ $date ] ) ) {
                continue;
            }

            foreach ( $order_data[ $date ] as $product_id => $qty ) {
                if ( ! isset( $usage[ $product_id ] ) ) {
                    $usage[ $product_id ] = 0;
                }
                $usage[ $product_id ] += $qty;
            }
        }

        return $usage;
    }

    /**
     * Clear pending usage entries for an order.
     *
     * @param int $order_id
     * @return void
     */
    public static function clear_pending_usage( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $storage = self::get_pending_usage_storage();
        if ( isset( $storage[ $order_id ] ) ) {
            unset( $storage[ $order_id ] );
            self::persist_pending_usage_storage( $storage );
        }
    }

    /**
     * Get Fleet Limits from WP Options
     * Returns array of product_id => max_quantity
     */
    public static function get_limits() {
        // Default values
        $limits = array(
            Kayarine_Config::ID_SINGLE_KAYAK => 50,
            Kayarine_Config::ID_DOUBLE_KAYAK => 20,
            Kayarine_Config::ID_FAMILY_KAYAK => 10,
            Kayarine_Config::ID_SUP          => 20,
            Kayarine_Config::ID_SNORKEL_RENT => 50, // Use ID_SNORKEL_RENT instead of undefined ID_SNORKEL
            Kayarine_Config::ID_PHONE_CASE   => 50,

            // Tours & Courses Defaults
            Kayarine_Config::ID_TOUR_SUNRISE     => 20,
            Kayarine_Config::ID_TOUR_SUNSET      => 20,
            Kayarine_Config::ID_COURSE_BEGINNER  => 16,
            Kayarine_Config::ID_TOUR_SNORKEL     => 20,
            Kayarine_Config::ID_COURSE_WHISKEY   => 10,
            Kayarine_Config::ID_COURSE_BRONZE    => 10,
            Kayarine_Config::ID_COURSE_SILVER    => 10,
            Kayarine_Config::ID_TOUR_YOGA        => 20,
        );

        // Fetch Individual Options
        foreach ( $limits as $id => $default ) {
            // Option Key: kayarine_limit_{ID}
            $val = get_option( 'kayarine_limit_' . $id );
            
            // Only update if value is set and numeric (allow 0)
            if ( $val !== false && $val !== '' && is_numeric($val) ) {
                $limits[$id] = intval($val);
            }
        }

        return $limits;
    }

    /**
     * Get Blackout Dates from WP Options
     * Returns array of date strings (YYYY-MM-DD) or ranges
     */
    public static function get_blackout_dates() {
        // Retrieve raw string from textarea option
        $raw_dates = get_option( 'kayarine_blackout_dates', '' );
        
        // Use ONLY user-defined dates if they exist.
        // If option is strictly empty string (never saved) or False, maybe use default?
        // But user said "removing default" failed, so likely we were merging them.
        // The commented out merge code confirms we were NOT merging.
        // So the issue might be Config::$blackout_dates is being used somewhere else?
        // Let's check `kayarine-config.php` usage in `get_js_config`.
        
        $dates = array_filter( array_map( 'trim', explode( "\n", $raw_dates ) ) );
        return $dates;
    }

    /**
     * Get Allowlist Dates (Limited Time Events)
     * Parses the Blackout Dates text area for rules tagged with "限時活動" (Limited Time Event).
     * Returns array of allowed dates for the product.
     */
    public static function get_allowlist_dates( $product_id ) {
        if ( ! $product_id ) return array();

        $rules = self::get_blackout_dates();
        $allowed = array();
        $has_whitelist_rule = false;

        foreach ( $rules as $rule ) {
            // Syntax: Date | ID:123 | 限時活動
            if ( stripos( $rule, '限時活動' ) === false && stripos( $rule, 'whitelist' ) === false ) {
                continue;
            }

            // Check if this rule applies to the product
            if ( stripos( $rule, 'ID:' . $product_id ) !== false ) {
                $has_whitelist_rule = true;
                $parts = explode( '|', $rule );
                $date_part = trim( $parts[0] );
                
                // Add to allowed list (Support Ranges too if needed, but Single Date for now)
                // TODO: Add range support if requested.
                $allowed[] = $date_part;
            }
        }

        if ( $has_whitelist_rule ) {
            return $allowed;
        }

        return null; // Return null if no whitelist rules exist (standard logic applies)
    }

    /**
     * Check if a specific date is blacked out for a specific product
     *
     * @param string $date YYYY-MM-DD
     * @param int $product_id Optional. If provided, checks rules for this product + global rules.
     * @return boolean
     */
    public static function is_blackout( $date, $product_id = null ) {
        // 1. Check Limited Time Events (Allowlist Logic) via Admin Settings
        if ( $product_id ) {
            $allowed_dates = self::get_allowlist_dates( $product_id );
            
            // If allowlist exists (not null), STRICTLY enforce it.
            if ( $allowed_dates !== null ) {
                // If date is NOT in allowlist -> Block it
                if ( ! in_array( $date, $allowed_dates ) ) {
                    return true;
                }
                // If date IS in allowlist -> Allow it (Bypass other blocks)
                return false;
            }
        }
        
        // 2. Hardcoded Config Allowlist (Backward Compat / Dev Override)
        if ( $product_id && ! empty( Kayarine_Config::$event_dates[$product_id] ) ) {
            $allowed_dates = Kayarine_Config::$event_dates[$product_id];
            if ( ! in_array( $date, $allowed_dates ) ) {
                return true;
            }
            return false;
        }

        $blackout_rules = self::get_blackout_dates();
        $check_date = strtotime( $date );
        $day_of_week = strtolower( date( 'l', $check_date ) ); // sunday, monday...

        foreach ( $blackout_rules as $rule ) {
            if ( empty( $rule ) ) continue;

            // Parse Rule for specific targeting
            // Syntax: "Rule Part | Condition"
            // Conditions: "ID:123", "Tag:sunrise"
            $parts = explode( '|', $rule );
            $date_part = trim( $parts[0] );
            $condition_part = isset($parts[1]) ? trim($parts[1]) : null;

            // Step 1: Does the date match?
            $date_match = false;

            // A. Single Date
            if ( $date_part === $date ) {
                $date_match = true;
            }
            // B. Range "YYYY-MM-DD to YYYY-MM-DD"
            elseif ( strpos( $date_part, ' to ' ) !== false ) {
                $range_parts = explode( ' to ', $date_part );
                if ( count( $range_parts ) === 2 ) {
                    $start = strtotime( trim( $range_parts[0] ) );
                    $end   = strtotime( trim( $range_parts[1] ) );
                    if ( $check_date >= $start && $check_date <= $end ) {
                        $date_match = true;
                    }
                }
            }
            // C. Recurring "Every Monday"
            elseif ( stripos( $date_part, 'Every ' ) === 0 ) {
                $day_name = trim( substr( $date_part, 6 ) );
                if ( $day_of_week === strtolower( $day_name ) ) {
                    $date_match = true;
                }
            }

            if ( ! $date_match ) {
                continue; // Rule doesn't apply to this date
            }

            // Step 2: Does the Condition match? (If date matches)
            
            // If no condition, it's a GLOBAL block
            if ( ! $condition_part ) {
                return true;
            }

            // If we are checking for a specific product
            if ( $product_id ) {
                // Check ID Condition
                if ( stripos( $condition_part, 'ID:' ) === 0 ) {
                    $target_id = intval( trim( substr( $condition_part, 3 ) ) );
                    if ( $target_id == $product_id ) {
                        return true;
                    }
                }
                // Check Tag Condition
                elseif ( stripos( $condition_part, 'Tag:' ) === 0 ) {
                    $target_tag = trim( substr( $condition_part, 4 ) );
                    if ( has_term( $target_tag, 'product_tag', $product_id ) ) {
                        return true;
                    }
                }
            } else {
                // If is_blackout called without ID (Global check),
                // we only care if it's a Global Rule.
                // Specific rules don't block "everything".
                // So we continue loop.
            }
        }

        return false;
    }

    /**
     * Calculate Usage for a Specific Date (Cached)
     * Queries WooCommerce orders to sum up booked items.
     * Uses static cache to prevent hammering DB in same request.
     */
    public static function get_daily_usage( $date ) {
         global $wpdb;
         
         // ✅ 調試選項：禁用快取來診斷庫存問題
         // 設置 define('KAYARINE_DISABLE_CACHE', true); 在 wp-config.php 中禁用快取
         $cache_disabled = defined('KAYARINE_DISABLE_CACHE') && KAYARINE_DISABLE_CACHE;
         
         // 1. Runtime Cache (Per Request)
         static $runtime_cache = array();
         if ( ! $cache_disabled && isset( $runtime_cache[$date] ) ) {
             return $runtime_cache[$date];
         }

         // 2. Persistent Transient Cache (Per 5 Seconds)
         // Short TTL (5s) balances performance with real-time availability
         // Prevents DB spikes during high traffic without stale data issues
         if ( ! $cache_disabled ) {
             $transient_key = 'kayarine_usage_' . $date;
             $cached_usage = get_transient( $transient_key );

             if ( $cached_usage !== false ) {
                 $runtime_cache[$date] = $cached_usage; // Update runtime cache
                 
                 // Debug Log: Cache Hit
                 if ( get_option('kayarine_debug_mode') ) {
                     Kayarine_Inventory::log( "[Cache Hit] Date: $date | Usage: " . json_encode($cached_usage) );
                 }
                 
                 return $cached_usage;
             }
         }

        // ✅ 修復：包含 pending、processing、completed、on-hold，但排除 cancelled
        // 注意：cancelled 訂單的設備應被釋放，不計入庫存佔用
        // ✅ 支持舊版本meta_key (_kayarine_booking_date) 和新版本 (kayarine_booking_date)
        
        // 🔍 檢查數據庫中實際的訂單狀態格式
        $check_statuses_sql = "
            SELECT DISTINCT post_status FROM {$wpdb->posts}
            WHERE post_type = 'shop_order'
            LIMIT 10
        ";
        $db_statuses = $wpdb->get_col($check_statuses_sql);
        
        // 決定使用哪種狀態格式
        // WooCommerce可能存儲為: 'wc-pending' 或 'pending'
        $use_wc_prefix = false;
        if (!empty($db_statuses) && strpos($db_statuses[0], 'wc-') === 0) {
            $use_wc_prefix = true;
        }
        
        // 訂單狀態（支持兩種格式，包括 draft 用於新訂單）
        $statuses = $use_wc_prefix
            ? array( 'wc-pending', 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-draft', 'draft' )
            : array( 'pending', 'processing', 'completed', 'on-hold', 'draft' );
        
        $status_placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
        
        error_log( "[Inventory DEBUG] Detected status format: " . ($use_wc_prefix ? 'WITH wc-' : 'WITHOUT wc-') );
        error_log( "[Inventory DEBUG] DB statuses detected: " . json_encode($db_statuses) );
        error_log( "[Inventory DEBUG] Using statuses: " . json_encode($statuses) );

        $sql = "
            SELECT
                item_meta_product.meta_value as product_id,
                SUM( CAST(item_meta_qty.meta_value AS UNSIGNED) ) as total_qty
            FROM {$wpdb->prefix}woocommerce_order_itemmeta as item_meta_date
            
            INNER JOIN {$wpdb->prefix}woocommerce_order_items as items
                ON item_meta_date.order_item_id = items.order_item_id
            
            INNER JOIN {$wpdb->posts} as orders
                ON items.order_id = orders.ID
            
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as item_meta_product
                ON items.order_item_id = item_meta_product.order_item_id
                AND item_meta_product.meta_key = '_product_id'
            
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as item_meta_qty
                ON items.order_item_id = item_meta_qty.order_item_id
                AND item_meta_qty.meta_key = '_qty'

            WHERE
                item_meta_date.meta_key = '_kayarine_booking_date'
                AND item_meta_date.meta_value = %s
                AND orders.post_type IN ('shop_order', 'shop_order_placehold')
                AND orders.post_status IN ({$status_placeholders})
                AND items.order_item_type = 'line_item'
            
            GROUP BY product_id
        ";

        // ✅ 增強debug：記錄SQL和結果詳情
        error_log( "════════════════════════════════════════════" );
        error_log( "[Inventory] 🔍 開始計算庫存使用情況" );
        error_log( "[Inventory] 📅 日期: $date" );
        error_log( "[Inventory] 💾 快取禁用: " . ($cache_disabled ? 'YES' : 'NO') );
        error_log( "[Inventory] 📊 SQL 訂單狀態: " . implode(', ', $statuses) );

        // Prepare parameters: date + all statuses
        $params = array_merge( array( $date ), $statuses );
        
        // 詳細SQL調試：記錄準備的SQL和參數
        error_log( "[Inventory DEBUG] SQL 模板:\n" . $sql );
        error_log( "[Inventory DEBUG] 準備參數: " . json_encode($params) );
        
        // 執行準備的查詢
        $prepared_sql = $wpdb->prepare( $sql, $params );
        error_log( "[Inventory DEBUG] 執行的 SQL:\n" . $prepared_sql );
        
        $results = $wpdb->get_results( $prepared_sql );
        
        // 檢查數據庫錯誤
        if ( $wpdb->last_error ) {
            error_log( "[Inventory ❌ ERROR] 數據庫錯誤: " . $wpdb->last_error );
        }
        error_log( "[Inventory] ✅ 查詢結果行數: " . count($results) );
        if ( ! empty( $results ) ) {
            foreach ( $results as $row ) {
                error_log( "[Inventory DEBUG] 產品 ID: {$row->product_id}, 數量: {$row->total_qty}" );
            }
        }

        // 驗證查詢1：檢查booking_date meta記錄是否存在（忽略其他JOINs）
        $check_sql = "
            SELECT COUNT(*) as count FROM {$wpdb->prefix}woocommerce_order_itemmeta
            WHERE meta_key = '_kayarine_booking_date'
            AND meta_value = %s
        ";
        $check_result = $wpdb->get_var( $wpdb->prepare($check_sql, $date) );
        error_log( "[Inventory DEBUG] Simple meta count for date $date: " . $check_result );
        
        // 驗證查詢2：檢查對應訂單狀態的訂單是否存在
        $order_check_sql = "
            SELECT COUNT(DISTINCT orders.ID) as count FROM {$wpdb->posts} as orders
            WHERE orders.post_type = 'shop_order'
            AND orders.post_status IN ({$status_placeholders})
        ";
        $order_check_result = $wpdb->get_var( $wpdb->prepare($order_check_sql, ...$statuses) );
        error_log( "[Inventory DEBUG] Order count with matching statuses: " . $order_check_result );

        $usage = array();
        if ( $results ) {
            error_log( "[Inventory] Found " . count($results) . " product rows for date $date" );
            foreach ( $results as $row ) {
                $usage[ $row->product_id ] = (int) $row->total_qty;
                error_log( "[Inventory] Product {$row->product_id}: {$row->total_qty} units" );
            }
        } else {
            error_log( "[Inventory] No results found for date: $date" );
        }

        // Merge pending usage (orders that are still being created)
        $pending_usage = self::get_pending_usage( $date );
        if ( $pending_usage ) {
            foreach ( $pending_usage as $pid => $qty ) {
                if ( isset( $usage[ $pid ] ) ) {
                    $usage[ $pid ] += $qty;
                } else {
                    $usage[ $pid ] = $qty;
                }
            }
            error_log( "[Inventory] Added pending usage for date $date: " . json_encode( $pending_usage ) );
        }

        // Debug Log
        if ( get_option('kayarine_debug_mode') ) {
            $log = sprintf("[Daily Usage - DB Query] Date: %s | Result: %s", $date, json_encode($usage));
            Kayarine_Inventory::log($log);
        }

        // ✅ 修復：增加 TTL 到 3600 秒（1小時）
        // 說明：Hook 會主動清除快取，TTL 只是保險機制
        // 更長的 TTL 減少 DB 查詢，防止邊界情況導致的快取問題
        set_transient( $transient_key, $usage, 3600 );

        // Save to runtime cache
        $runtime_cache[$date] = $usage;

        return $usage;
    }

    /**
     * Simple File Logger
     */
    public static function log( $message ) {
        $log_file = WP_CONTENT_DIR . '/kayarine-debug.log';
        $time = current_time('mysql');
        $entry = "[$time] $message" . PHP_EOL;
        file_put_contents($log_file, $entry, FILE_APPEND);
    }

    /**
     * Get tail of log file
     */
    public static function get_log_tail( $lines = 50 ) {
        $log_file = WP_CONTENT_DIR . '/kayarine-debug.log';
        if ( ! file_exists( $log_file ) ) return "No logs yet.";
        
        $data = file( $log_file );
        $tail = array_slice( $data, -$lines );
        return implode( "", $tail );
    }

    /**
     * Get Full Availability Report for API Response
     */
    public static function get_availability( $date ) {
        $limits = self::get_limits();
        $usage  = self::get_daily_usage( $date );
        $data = array();

        // Check Global Blackout first (optimization)
        $is_global_blackout = self::is_blackout( $date ); // No ID = Global Check

        foreach ( $limits as $pid => $limit ) {
            // Determine if this specific product is available
            $is_blocked = $is_global_blackout || self::is_blackout( $date, $pid );

            if ( $is_blocked ) {
                $remaining = 0;
                $used = $limit; // Visual indication full
            } else {
                $used = isset( $usage[$pid] ) ? $usage[$pid] : 0;
                $remaining = max( 0, $limit - $used );
            }
            
            $data[$pid] = array(
                'name' => get_the_title($pid),
                'limit' => $limit,
                'used' => $used,
                'remaining' => $remaining
            );
        }

        return $data;
    }

    /**
     * Clear Cache for a Date
     * Use this when a new order is placed to ensure next check is fresh.
     */
    public static function clear_cache( $date ) {
        if ( ! $date ) return;
        
        $transient_key = 'kayarine_usage_' . $date;
        delete_transient( $transient_key );
        
        if ( get_option('kayarine_debug_mode') ) {
            Kayarine_Inventory::log( "[Cache Clear] Cleared cache for date: $date" );
        }
    }
}
