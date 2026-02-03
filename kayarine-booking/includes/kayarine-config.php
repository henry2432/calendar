<?php
/**
 * Configuration for Kayarine Booking
 * 
 * Edit this file to change prices, blackout dates, and product IDs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Kayarine_Config {

    // Product IDs (WooCommerce Product IDs)
    const ID_SINGLE_KAYAK = 6954;
    const ID_DOUBLE_KAYAK = 6955;
    const ID_SUP          = 6956;
    const ID_FAMILY_KAYAK = 6963;
    
    // Tours & Courses
    const ID_TOUR_SUNRISE      = 6957;
    const ID_TOUR_SUNSET       = 6958;
    const ID_COURSE_BEGINNER   = 6959;
    const ID_TOUR_SNORKEL      = 6960;
    const ID_COURSE_WHISKEY    = 6961;
    const ID_COURSE_BRONZE     = 6962;
    const ID_COURSE_SILVER     = 6964;
    const ID_TOUR_YOGA         = 6965;

    // Add-ons (Rentals)
    const ID_SNORKEL_RENT = 6966; // 租借浮潛面罩

    // Add-ons (Sales)
    const ID_PHONE_CASE   = 6967; // 購買手機防水袋
    const ID_SNORKEL_BUY  = 7075; // 購買浮潛面罩

    // Base Prices (Weekday)
    const PRICE_SINGLE_KAYAK = 100;
    const PRICE_DOUBLE_KAYAK = 200;
    const PRICE_SUP          = 150;
    const PRICE_FAMILY_KAYAK = 200;
    
    const PRICE_SNORKEL_RENT = 30;
    const PRICE_PHONE_CASE   = 50;
    const PRICE_SNORKEL_BUY  = 50; // Placeholder price

    // Tour & Course Prices (Weekday) - Placeholder Values
    const PRICE_TOUR_SUNRISE     = 580;
    const PRICE_TOUR_SUNSET      = 580;
    const PRICE_COURSE_BEGINNER  = 800;
    const PRICE_TOUR_SNORKEL     = 450;
    const PRICE_COURSE_WHISKEY   = 1200;
    const PRICE_COURSE_BRONZE    = 1500;
    const PRICE_COURSE_SILVER    = 1800;
    const PRICE_TOUR_YOGA        = 600;

    // Weekend/Holiday Prices
    const PRICE_SINGLE_KAYAK_WEEKEND = 150;
    const PRICE_DOUBLE_KAYAK_WEEKEND = 300;
    const PRICE_SUP_WEEKEND          = 200;
    const PRICE_FAMILY_KAYAK_WEEKEND = 300;
    
    const PRICE_SNORKEL_RENT_WEEKEND = 30;
    const PRICE_PHONE_CASE_WEEKEND   = 50;
    const PRICE_SNORKEL_BUY_WEEKEND  = 50;

    const PRICE_TOUR_SUNRISE_WEEKEND     = 680;
    const PRICE_TOUR_SUNSET_WEEKEND      = 680;
    const PRICE_COURSE_BEGINNER_WEEKEND  = 900;
    const PRICE_TOUR_SNORKEL_WEEKEND     = 550;
    const PRICE_COURSE_WHISKEY_WEEKEND   = 1300;
    const PRICE_COURSE_BRONZE_WEEKEND    = 1600;
    const PRICE_COURSE_SILVER_WEEKEND    = 1900;
    const PRICE_TOUR_YOGA_WEEKEND        = 700;

    // Configuration for Product-Based Booking (Redesign)
    // Map main Product IDs to their allowed Add-ons and Rules
    public static $product_rules = array(
        // Single Kayak
        self::ID_SINGLE_KAYAK => array(
            'addons' => array(self::ID_SNORKEL_RENT, self::ID_PHONE_CASE, self::ID_SNORKEL_BUY),
            'type'   => 'rental'
        ),
        // Double Kayak
        self::ID_DOUBLE_KAYAK => array(
            'addons' => array(self::ID_SNORKEL_RENT, self::ID_PHONE_CASE, self::ID_SNORKEL_BUY),
            'type'   => 'rental'
        ),
        // Family Kayak
        self::ID_FAMILY_KAYAK => array(
            'addons' => array(self::ID_SNORKEL_RENT, self::ID_PHONE_CASE, self::ID_SNORKEL_BUY),
            'type'   => 'rental'
        ),
        // SUP (Stand Up Paddle)
        self::ID_SUP => array(
            'addons' => array(self::ID_SNORKEL_RENT, self::ID_PHONE_CASE, self::ID_SNORKEL_BUY),
            'type'   => 'rental'
        ),

        // Tours & Courses (Enable Date Picker)
        self::ID_TOUR_SUNRISE     => array( 'type' => 'tour', 'addons' => array(self::ID_PHONE_CASE) ),
        self::ID_TOUR_SUNSET      => array( 'type' => 'tour', 'addons' => array(self::ID_PHONE_CASE) ),
        self::ID_COURSE_BEGINNER  => array( 'type' => 'course', 'addons' => array(self::ID_PHONE_CASE) ),
        self::ID_TOUR_SNORKEL     => array( 'type' => 'tour', 'addons' => array(self::ID_PHONE_CASE) ),
        self::ID_COURSE_WHISKEY   => array( 'type' => 'course', 'addons' => array(self::ID_PHONE_CASE) ),
        self::ID_COURSE_BRONZE    => array( 'type' => 'course', 'addons' => array(self::ID_PHONE_CASE) ),
        self::ID_COURSE_SILVER    => array( 'type' => 'course', 'addons' => array(self::ID_PHONE_CASE) ),
        self::ID_TOUR_YOGA        => array( 'type' => 'tour', 'addons' => array(self::ID_PHONE_CASE) ),
    );

    // Backend API URL
    const API_BASE_URL = 'internal';

    // Blackout Dates (YYYY-MM-DD) - Default values
    public static $blackout_dates = array(
        '2024-02-10', // CNY
        '2024-02-11',
        '2024-12-25', // Christmas
    );

    // Blocked Date Ranges (Inclusive)
    public static $blocked_ranges = array(
        // array( 'from' => '2025-12-25', 'to' => '2026-03-01' ),
    );

    // Limited Time Events (Whitelist Dates)
    // Product ID => array of allowed dates (YYYY-MM-DD)
    public static $event_dates = array(
        // Moved to Admin Settings "Blackout Dates" with syntax: YYYY-MM-DD | ID:xx | 限時活動
    );

    // Public Holidays (Treat as Weekend)
    public static $holidays = array(
        '2024-01-01',
        '2024-05-01',
    );

    /**
     * Check if a date is Weekend or Holiday
     */
    public static function is_high_season( $date ) {
        $timestamp = strtotime( $date );
        $day_of_week = date( 'N', $timestamp ); // 1 (Mon) to 7 (Sun)
        
        // Saturday (6) or Sunday (7)
        if ( $day_of_week >= 6 ) {
            return true;
        }

        // Check Holidays
        if ( in_array( $date, self::$holidays ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check if date is blacked out
     */
    public static function is_blackout( $date ) {
        if ( in_array( $date, self::$blackout_dates ) ) {
            return true;
        }

        // Check ranges
        $ts = strtotime($date);
        foreach ( self::$blocked_ranges as $range ) {
            $start = strtotime( $range['from'] );
            $end   = strtotime( $range['to'] );
            if ( $ts >= $start && $ts <= $end ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Price for Product on Date
     */
    public static function get_price( $product_id, $date ) {
        // Check VIP Status (Force Weekday Price)
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $is_vip = get_user_meta( $user_id, 'kayarine_is_vip', true );
            if ( $is_vip ) {
                $is_weekend = false;
            } else {
                $is_weekend = self::is_high_season( $date );
            }
        } else {
            $is_weekend = self::is_high_season( $date );
        }

        switch ( $product_id ) {
            case self::ID_SINGLE_KAYAK:
                return $is_weekend ? self::PRICE_SINGLE_KAYAK_WEEKEND : self::PRICE_SINGLE_KAYAK;
            case self::ID_DOUBLE_KAYAK:
                return $is_weekend ? self::PRICE_DOUBLE_KAYAK_WEEKEND : self::PRICE_DOUBLE_KAYAK;
            case self::ID_FAMILY_KAYAK:
                return $is_weekend ? self::PRICE_FAMILY_KAYAK_WEEKEND : self::PRICE_FAMILY_KAYAK;
            case self::ID_SUP:
                return $is_weekend ? self::PRICE_SUP_WEEKEND : self::PRICE_SUP;
            case self::ID_SNORKEL_RENT:
                return $is_weekend ? self::PRICE_SNORKEL_RENT_WEEKEND : self::PRICE_SNORKEL_RENT;
            case self::ID_PHONE_CASE:
                return $is_weekend ? self::PRICE_PHONE_CASE_WEEKEND : self::PRICE_PHONE_CASE;
            case self::ID_SNORKEL_BUY:
                return $is_weekend ? self::PRICE_SNORKEL_BUY_WEEKEND : self::PRICE_SNORKEL_BUY;
        }
        return 0; // Default or fallback
    }

    /**
     * Get All Pricing Rules for JS (Frontend)
     */
    public static function get_js_config() {
        return array(
            'prices' => array(
                // Rentals
                self::ID_SINGLE_KAYAK => array('weekday' => self::PRICE_SINGLE_KAYAK, 'weekend' => self::PRICE_SINGLE_KAYAK_WEEKEND),
                self::ID_DOUBLE_KAYAK => array('weekday' => self::PRICE_DOUBLE_KAYAK, 'weekend' => self::PRICE_DOUBLE_KAYAK_WEEKEND),
                self::ID_FAMILY_KAYAK => array('weekday' => self::PRICE_FAMILY_KAYAK, 'weekend' => self::PRICE_FAMILY_KAYAK_WEEKEND),
                self::ID_SUP          => array('weekday' => self::PRICE_SUP, 'weekend' => self::PRICE_SUP_WEEKEND),
                
                // Add-ons
                self::ID_SNORKEL_RENT => array('weekday' => self::PRICE_SNORKEL_RENT, 'weekend' => self::PRICE_SNORKEL_RENT_WEEKEND),
                self::ID_PHONE_CASE   => array('weekday' => self::PRICE_PHONE_CASE, 'weekend' => self::PRICE_PHONE_CASE_WEEKEND),
                self::ID_SNORKEL_BUY  => array('weekday' => self::PRICE_SNORKEL_BUY, 'weekend' => self::PRICE_SNORKEL_BUY_WEEKEND),

                // Tours & Courses
                self::ID_TOUR_SUNRISE     => array('weekday' => self::PRICE_TOUR_SUNRISE, 'weekend' => self::PRICE_TOUR_SUNRISE_WEEKEND),
                self::ID_TOUR_SUNSET      => array('weekday' => self::PRICE_TOUR_SUNSET, 'weekend' => self::PRICE_TOUR_SUNSET_WEEKEND),
                self::ID_COURSE_BEGINNER  => array('weekday' => self::PRICE_COURSE_BEGINNER, 'weekend' => self::PRICE_COURSE_BEGINNER_WEEKEND),
                self::ID_TOUR_SNORKEL     => array('weekday' => self::PRICE_TOUR_SNORKEL, 'weekend' => self::PRICE_TOUR_SNORKEL_WEEKEND),
                self::ID_COURSE_WHISKEY   => array('weekday' => self::PRICE_COURSE_WHISKEY, 'weekend' => self::PRICE_COURSE_WHISKEY_WEEKEND),
                self::ID_COURSE_BRONZE    => array('weekday' => self::PRICE_COURSE_BRONZE, 'weekend' => self::PRICE_COURSE_BRONZE_WEEKEND),
                self::ID_COURSE_SILVER    => array('weekday' => self::PRICE_COURSE_SILVER, 'weekend' => self::PRICE_COURSE_SILVER_WEEKEND),
                self::ID_TOUR_YOGA        => array('weekday' => self::PRICE_TOUR_YOGA, 'weekend' => self::PRICE_TOUR_YOGA_WEEKEND),
            ),
            'holidays' => self::$holidays,
            // Use Admin Settings ONLY. Ignore Config defaults to ensure user control.
            'blackout_dates' => Kayarine_Inventory::get_blackout_dates(),
            'blocked_ranges' => self::$blocked_ranges,
            'event_dates'    => self::$event_dates,
            'product_rules'  => self::$product_rules,
            'names' => array(
                self::ID_SNORKEL_RENT => '租借浮潛面罩',
                self::ID_PHONE_CASE   => '手機防水袋',
                self::ID_SNORKEL_BUY  => '浮潛面罩'
            ),
            'addon_categories' => array(
                'rentals' => array(self::ID_SNORKEL_RENT),
                'sales'   => array(self::ID_PHONE_CASE, self::ID_SNORKEL_BUY)
            ),
            // Internal WP AJAX Endpoint
            'api_url' => admin_url( 'admin-ajax.php?action=kayarine_proxy_check' )
        );
    }
}
