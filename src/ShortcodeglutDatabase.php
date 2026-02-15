<?php

namespace Shortcodeglut;

class ShortcodeglutDatabase {
	
	private static $initialized = false;

	public static function table_showcase_filters() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_enhancement_filters';
	}

	public static function table_user_actions() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_user_actions';
	}

	public static function table_shop_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_shop_layouts';
	}

	public static function table_archive_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_archive_layouts';
	}

	public static function table_shopg_wishlist() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_wishlist';
	}

	public static function table_single_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_single_product_layout';
	}

	public static function table_cartpage_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_cartpage_layouts';
	}

	public static function table_ordercomplete_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_ordercomplete_layouts';
	}

	public static function table_accountpage_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_accountpage_layouts';
	}

	public static function table_shortcodes_showcase() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_shortcodes_showcase';
	}

	public static function table_gallery_shortcode() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_gallery_shortcode';
	}

	public static function table_tabs_showcase() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_tabs_showcase';
	}

	public static function table_badges_showcase() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_badges_showcase';
	}
    
	public static function table_banners_showcase() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_banners_showcase';
	}

	public static function table_shop_banner_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_shopbanner_layouts';
	}

	public static function table_slider_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_slider_layouts';
	}

	public static function table_tab_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_tab_layouts';
	}

	public static function table_accordion_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_accordion_layouts';
	}

	public static function table_gallery_layouts() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_gallery_layouts';
	}

	public static function table_mega_menu_showcase() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_mega_menus';
	}

	public static function table_quickview_enhancement() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_quickview_layouts';
	}

	public static function table_comparison_enhancement() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_comparison_layouts';
	}

	public static function table_lock_settings() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_lock_settings';
	}

	public static function table_woo_templates() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_woo_templates';
	}

	public static function table_product_custom_field_settings() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_product_custom_field_settings';
	}

	public static function table_product_badges() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_product_badge_layouts';
	}

	public static function table_product_swatches() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_productswatches_layout';
	}


	public static function table_showcase_filters1() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_enhancements_filters';
	}


	/**
	 * Check if table exists
	 */
	private static function table_exists( $table_name ) {
		global $wpdb;

		// Check cache first
		$cache_key = 'shopglut_table_exists_' . md5( $table_name );
		$cached_result = wp_cache_get( $cache_key, 'shopglut_db_schema' );

		if ( false !== $cached_result ) {
			return (bool) $cached_result;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table existence check, safe table name from internal function
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;

		// Cache for 1 hour (schema checks don't change frequently)
		wp_cache_set( $cache_key, $exists, 'shopglut_db_schema', HOUR_IN_SECONDS );

		return $exists;
	}

	/**
	 * Check if column exists in table
	 */
	private static function column_exists( $table_name, $column_name ) {
		global $wpdb;

		// Check cache first
		$cache_key = 'shopglut_column_exists_' . md5( $table_name . '_' . $column_name );
		$cached_result = wp_cache_get( $cache_key, 'shopglut_db_schema' );

		if ( false !== $cached_result ) {
			return (bool) $cached_result;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder -- Column existence check, safe table and column names from internal function
        $results = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `%1s` LIKE %s", $table_name, $column_name ) );
		$exists = ! empty( $results );

		// Cache for 1 hour (schema checks don't change frequently)
		wp_cache_set( $cache_key, $exists, 'shopglut_db_schema', HOUR_IN_SECONDS );

		return $exists;
	}

	public static function create_user_actions() {
		global $wpdb;

		$table_name = self::table_user_actions();
		if ( self::table_exists( $table_name ) ) {
			return; // Table already exists
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            product_id mediumint(9) NOT NULL,
            action_type varchar(255) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
         ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_shop_layouts() {
		global $wpdb;

		$table_name = self::table_shop_layouts();
		if ( self::table_exists( $table_name ) ) {
			return; // Table already exists
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            layout_name varchar(255) NOT NULL DEFAULT 'Layout One',
            layout_template varchar(255) NOT NULL DEFAULT 'template1',
            status varchar(50) NOT NULL DEFAULT 'not-active',
			layout_settings longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_archive_layouts() {
		global $wpdb;

		$table_name = self::table_archive_layouts();
		if ( self::table_exists( $table_name ) ) {
			return; // Table already exists
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            arlayout_name varchar(255) NOT NULL DEFAULT 'Layout One',
            arlayout_template varchar(255) NOT NULL DEFAULT 'template1',
			arlayout_settings longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_single_layouts() {
		global $wpdb;

		$table_name = self::table_single_layouts();
		if ( self::table_exists( $table_name ) ) {
			return; // Table already exists
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
             id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
             layout_name varchar(255) NOT NULL,
             layout_template varchar(255) NOT NULL,
             layout_settings text NOT NULL,
			 created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
             updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
             PRIMARY KEY (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

    public static function create_cartpage_layouts() {
		global $wpdb;

		$table_name = self::table_cartpage_layouts();
		if ( self::table_exists( $table_name ) ) {
			return; // Table already exists
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
             id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
             layout_name varchar(255) NOT NULL,
             layout_template varchar(255) NOT NULL,
             layout_settings text NOT NULL,
			 created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
             updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
             PRIMARY KEY (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	 public static function create_ordercomplete_layouts() {
		global $wpdb;

		$table_name = self::table_ordercomplete_layouts();
		if ( self::table_exists( $table_name ) ) {
			return; // Table already exists
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
             id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
             layout_name varchar(255) NOT NULL,
             layout_template varchar(255) NOT NULL,
             layout_settings text NOT NULL,
			 created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
             updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
             PRIMARY KEY (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}
	
	public static function create_accountpage_layouts() {
		global $wpdb;

		$table_name = self::table_accountpage_layouts();
		if ( self::table_exists( $table_name ) ) {
			return; // Table already exists
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
             id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
             layout_name varchar(255) NOT NULL,
             layout_template varchar(255) NOT NULL,
             layout_settings text NOT NULL,
			 created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
             updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
             PRIMARY KEY (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_showcase_filters() {
		global $wpdb;

		$table_name = self::table_showcase_filters();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            filter_name varchar(255) NOT NULL,
            filter_settings longtext,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );

	}

	public static function create_showcase_badges() {
		global $wpdb;

		$table_name = self::table_badges_showcase();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            badge_name varchar(255) NOT NULL,
            badge_settings longtext,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_showcase_banners() {
		global $wpdb;

		$table_name = self::table_banners_showcase();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			banner_name varchar(255) NOT NULL,
			banner_template varchar(255) NOT NULL,
			banner_settings longtext,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_mega_menu_showcase() {
		global $wpdb;

		$table_name = self::table_mega_menu_showcase();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			menu_name varchar(255) NOT NULL,
			menu_template varchar(255) NOT NULL,
			menu_settings longtext,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_shortcodes_showcase() {
		global $wpdb;

		$table_name = self::table_shortcodes_showcase();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_name varchar(255) NOT NULL,
            template_id varchar(255) NOT NULL,
            template_html longtext,
            template_css longtext,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_gallery_shortcode() {
		// Create gallery shortcode tables
		if (class_exists('\Shopglut\galleryShortcode\GalleryDataTables')) {
			\Shopglut\galleryShortcode\GalleryDataTables::create_tables();
		}
	}

	public static function create_showcase_quickview() {
		global $wpdb;

		$table_name = self::table_quickview_enhancement();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			layout_name varchar(255) NOT NULL,
			layout_template varchar(255) NOT NULL,
			layout_settings longtext,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_showcase_comparison() {
		global $wpdb;

		$table_name = self::table_comparison_enhancement();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			layout_name varchar(255) NOT NULL,
			layout_template varchar(255) NOT NULL,
			layout_settings longtext,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_tabs_showcase() {
		global $wpdb;

		$table_name = self::table_tabs_showcase();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            tab_name varchar(255) NOT NULL,
            tab_settings longtext,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_wishlist_table() {
			global $wpdb;

			$table_name = self::table_shopg_wishlist();
			if ( self::table_exists( $table_name ) ) {
				return;
			}

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				wish_user_id varchar(255) NOT NULL,
				username varchar(255) NOT NULL,
				useremail varchar(255) NOT NULL,
				product_ids text NOT NULL,
				product_meta longtext DEFAULT NULL,
				wishlist_notifications text NOT NULL,
				product_added_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				product_individual_dates longtext DEFAULT NULL,
				share_data text DEFAULT NULL,
				PRIMARY KEY (id)
			) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
			dbDelta( $sql );

			// Clear cache after table creation
			wp_cache_delete( 'shopglut_table_exists_' . md5( $table_name ) );
    }
	public static function create_lock_settings() {
		global $wpdb;

		$table_name = self::table_lock_settings();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT NULL,
			email_subscribe varchar(255) NOT NULL,
			name_subscribe varchar(255) NOT NULL,
			subscription_status varchar(50) DEFAULT 'pending',
			lock_type varchar(50) DEFAULT 'email',
			expiry_date datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY email_subscribe (email_subscribe),
			KEY subscription_status (subscription_status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function shopglut_woo_subscription_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		// Subscription Tables
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shopglut_woo_subscriptions (
            subscription_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            variation_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            billing_period varchar(20) NOT NULL,
            billing_interval int(11) NOT NULL DEFAULT 1,
            trial_period varchar(20) DEFAULT NULL,
            trial_interval int(11) DEFAULT NULL,
            initial_amount decimal(19,4) NOT NULL DEFAULT 0,
            recurring_amount decimal(19,4) NOT NULL DEFAULT 0,
            start_date datetime NOT NULL,
            trial_end_date datetime DEFAULT NULL,
            next_payment_date datetime DEFAULT NULL,
            end_date datetime DEFAULT NULL,
            last_payment_date datetime DEFAULT NULL,
            payment_method varchar(100) DEFAULT NULL,
            payment_method_title varchar(100) DEFAULT NULL,
            total_payments int(11) DEFAULT 0,
            completed_payments int(11) DEFAULT 0,
            failed_payments int(11) DEFAULT 0,
            suspension_count int(11) DEFAULT 0,
            cancelled_date datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (subscription_id),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY next_payment_date (next_payment_date)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shopglut_subscription_items (
            item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned NOT NULL,
            order_item_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            variation_id bigint(20) unsigned DEFAULT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            subtotal decimal(19,4) NOT NULL DEFAULT 0,
            subtotal_tax decimal(19,4) NOT NULL DEFAULT 0,
            total decimal(19,4) NOT NULL DEFAULT 0,
            total_tax decimal(19,4) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (item_id),
            KEY subscription_id (subscription_id),
            KEY product_id (product_id)
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shopglut_subscription_meta (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned NOT NULL,
            meta_key varchar(255) DEFAULT NULL,
            meta_value longtext,
            PRIMARY KEY (meta_id),
            KEY subscription_id (subscription_id),
            KEY meta_key (meta_key(191))
        ) {$charset_collate};";

		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shopglut_subscription_schedule (
            schedule_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned NOT NULL,
            action varchar(50) NOT NULL,
            scheduled_date datetime NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            last_attempt datetime DEFAULT NULL,
            completed_date datetime DEFAULT NULL,
            args longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (schedule_id),
            KEY subscription_id (subscription_id),
            KEY action (action),
            KEY scheduled_date (scheduled_date),
            KEY status (status)
        ) {$charset_collate};";

		foreach ( $sql as $query ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
			dbDelta( $query );
		}
	}

	public static function shopglut_create_slider_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_sliders';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slider_name varchar(255) NOT NULL,
            slider_template varchar(255) NOT NULL,
            slider_settings longtext,
            date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function shopglut_create_tab_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_tabs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            tab_name varchar(255) NOT NULL,
            tab_template varchar(255) NOT NULL,
            tab_settings longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function shopglut_create_accordion_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_accordions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            accordion_name varchar(255) NOT NULL,
            accordion_template varchar(255) NOT NULL,
            accordion_settings longtext,
            date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function shopglut_create_gallery_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'shopglut_gallerys';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            gallery_name varchar(255) NOT NULL,
            gallery_template varchar(255) NOT NULL,
            gallery_settings longtext,
            date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_woo_templates() {
		global $wpdb;

		$table_name = self::table_woo_templates();

		// Check if column exists, if not add it (for existing installations)
		$was_existing = self::table_exists( $table_name );
		$added_column = false;
		if ( $was_existing && ! self::column_exists( $table_name, 'is_default' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- ALTER TABLE for adding column to existing table, safe table name from internal function
			$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN is_default tinyint(1) DEFAULT 0 AFTER template_tags" );
			$added_column = true;
		}

		if ( self::table_exists( $table_name ) && ! $added_column ) {
			// Table exists and we didn't just add the column - check if we need to insert defaults
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Direct query for count, safe table name from internal function
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
			if ( $count == 0 ) {
				// Table is empty, insert default templates
				\Shortcodeglut\wooTemplates\WooTemplatesEntity::insert_default_templates();
			}
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            template_name varchar(255) NOT NULL,
            template_id varchar(255) NOT NULL,
            template_html longtext,
            template_css longtext,
            template_tags longtext,
            is_default tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY template_id (template_id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );

		// Insert default templates after table creation
		\Shortcodeglut\wooTemplates\WooTemplatesEntity::insert_default_templates();
	}

	public static function create_product_custom_field_settings() {
		global $wpdb;

		$table_name = self::table_product_custom_field_settings();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            field_name varchar(255) NOT NULL,
            field_settings longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );

	}

	public static function create_product_badges() {
		global $wpdb;

		$table_name = self::table_product_badges();
		$charset_collate = $wpdb->get_charset_collate();

		// First, check if table exists and needs migration from old structure

		$sql = "CREATE TABLE {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            layout_name varchar(255) NOT NULL,
            layout_template varchar(255) NOT NULL DEFAULT 'template1',
            layout_settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );

		// Clear cache after table creation
	}

	public static function create_product_swatches() {
		global $wpdb;

		$table_name = self::table_product_swatches();
		$table_existed = self::table_exists( $table_name );

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            layout_name varchar(255) NOT NULL,
            layout_template varchar(255) NOT NULL DEFAULT 'template1',
            layout_settings longtext,
            assigned_attributes text DEFAULT NULL,
            assignment_type varchar(20) DEFAULT 'legacy',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY assignment_type (assignment_type)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );

		// Add new columns to existing tables for attribute-based assignment
		if ( $table_existed ) {
			self::migrate_product_swatches_table();
		}
	}

	/**
	 * Migrate existing product swatches table to support attribute-based assignment
	 */
	public static function migrate_product_swatches_table() {
		global $wpdb;

		$table_name = self::table_product_swatches();

		// Check if assigned_attributes column exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema migration check
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema migration check
		$column_exists = $wpdb->get_var(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = '" . esc_sql( $table_name ) . "'
			AND COLUMN_NAME = 'assigned_attributes'"
		);

		if ( ! $column_exists ) {
			// Add assigned_attributes column
			
			$wpdb->query( "ALTER TABLE `" . esc_sql( $table_name ) . "` ADD COLUMN assigned_attributes text DEFAULT NULL AFTER layout_settings"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema migration, safe SQL with esc_sql
		}

		// Check if assignment_type column exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema migration check
		$column_exists = $wpdb->get_var(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE()
			AND TABLE_NAME = '" . esc_sql( $table_name ) . "'
			AND COLUMN_NAME = 'assignment_type'"
		);

		if ( ! $column_exists ) {
			// Add assignment_type column
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema migration, safe SQL with esc_sql
			$wpdb->query("ALTER TABLE `" . esc_sql( $table_name ) . "`ADD COLUMN assignment_type varchar(20) DEFAULT 'legacy' AFTER assigned_attributes" );// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema migration, safe SQL with esc_sql

			// Add index for assignment_type
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema migration, safe SQL with esc_sql
			$wpdb->query("ALTER TABLE `" . esc_sql( $table_name ) . "`ADD KEY assignment_type (assignment_type)"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema migration, safe SQL with esc_sql
		}
	}

	public static function create_product_comparisons() {
		global $wpdb;

		$table_name = self::table_product_comparisons();
		$table_existed = self::table_exists( $table_name );
		
		if ( $table_existed ) {
			
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            comparison_name varchar(255) NOT NULL,
            comparison_data longtext,
            comparison_settings longtext,
            status varchar(20) DEFAULT 'inactive',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
		
		// Insert pre-built comparisons
		self::insert_prebuilt_comparisons( $table_name );
	}
	
	private static function insert_prebuilt_comparisons( $table_name ) {
		global $wpdb;
		
		$prebuilt_comparisons = array(
			array(
				'comparison_name' => 'Basic Product Comparison',
				'comparison_data' => json_encode(array(
					'compare_fields' => array('price', 'description', 'rating'),
					'display_type' => 'table',
					'max_products' => 3
				)),
				'comparison_settings' => json_encode(array(
					'style' => array(
						'table_style' => 'bordered',
						'header_bg_color' => '#f8f9fa',
						'border_color' => '#dee2e6',
						'text_color' => '#212529',
						'highlight_color' => '#007bff'
					),
					'behavior' => array(
						'auto_remove' => false,
						'sticky_header' => true,
						'responsive' => true
					)
				))
			),
			array(
				'comparison_name' => 'Advanced Feature Comparison',
				'comparison_data' => json_encode(array(
					'compare_fields' => array('price', 'features', 'specifications', 'rating', 'reviews'),
					'display_type' => 'detailed',
					'max_products' => 4
				)),
				'comparison_settings' => json_encode(array(
					'style' => array(
						'table_style' => 'striped',
						'header_bg_color' => '#343a40',
						'border_color' => '#495057',
						'text_color' => '#ffffff',
						'highlight_color' => '#28a745'
					),
					'behavior' => array(
						'auto_remove' => true,
						'sticky_header' => true,
						'responsive' => true
					)
				))
			),
			array(
				'comparison_name' => 'Quick Compare View',
				'comparison_data' => json_encode(array(
					'compare_fields' => array('price', 'rating'),
					'display_type' => 'compact',
					'max_products' => 2
				)),
				'comparison_settings' => json_encode(array(
					'style' => array(
						'table_style' => 'minimal',
						'header_bg_color' => '#ffffff',
						'border_color' => '#e9ecef',
						'text_color' => '#495057',
						'highlight_color' => '#17a2b8'
					),
					'behavior' => array(
						'auto_remove' => false,
						'sticky_header' => false,
						'responsive' => true
					)
				))
			),
			array(
				'comparison_name' => 'Detailed Specification Compare',
				'comparison_data' => json_encode(array(
					'compare_fields' => array('price', 'specifications', 'attributes', 'dimensions', 'weight'),
					'display_type' => 'specification',
					'max_products' => 3
				)),
				'comparison_settings' => json_encode(array(
					'style' => array(
						'table_style' => 'detailed',
						'header_bg_color' => '#6f42c1',
						'border_color' => '#6f42c1',
						'text_color' => '#ffffff',
						'highlight_color' => '#fd7e14'
					),
					'behavior' => array(
						'auto_remove' => false,
						'sticky_header' => true,
						'responsive' => true
					)
				))
			),
			array(
				'comparison_name' => 'Visual Product Compare',
				'comparison_data' => json_encode(array(
					'compare_fields' => array('image', 'price', 'rating', 'description'),
					'display_type' => 'visual',
					'max_products' => 3
				)),
				'comparison_settings' => json_encode(array(
					'style' => array(
						'table_style' => 'card',
						'header_bg_color' => '#e83e8c',
						'border_color' => '#e83e8c',
						'text_color' => '#ffffff',
						'highlight_color' => '#ffc107'
					),
					'behavior' => array(
						'auto_remove' => true,
						'sticky_header' => false,
						'responsive' => true
					)
				))
			)
		);
		
		foreach ( $prebuilt_comparisons as $comparison ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table_name,
				array(
					'comparison_name' => $comparison['comparison_name'],
					'comparison_data' => $comparison['comparison_data'],
					'comparison_settings' => $comparison['comparison_settings'],
					'status' => 'inactive',
					'created_at' => current_time('mysql'),
					'updated_at' => current_time('mysql')
				),
				array('%s', '%s', '%s', '%s', '%s', '%s')
			);
		}
	}

	public static function table_product_comparisons() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_product_comparisons';
	}

	public static function create_product_quickview() {
		global $wpdb;

		$table_name = self::table_product_quickview();
		$table_existed = self::table_exists( $table_name );
		
		if ( $table_existed ) {
			// Check if table has any quickview configs, if not insert prebuilt ones
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$quickview_count = $wpdb->get_var( "SELECT COUNT(*) FROM " . esc_sql( $table_name ) );
			if ( $quickview_count == 0 ) {
				self::insert_prebuilt_quickview( $table_name );
			}
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            quickview_name varchar(255) NOT NULL,
            quickview_data longtext,
            quickview_settings longtext,
            status varchar(20) DEFAULT 'inactive',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
		
		// Insert pre-built quickview configurations
		self::insert_prebuilt_quickview( $table_name );
	}
	
	private static function insert_prebuilt_quickview( $table_name ) {
		global $wpdb;
		
		$prebuilt_quickview = array(
			array(
				'quickview_name' => 'Minimal Quick View',
				'quickview_data' => json_encode(array(
					'display_fields' => array('image', 'title', 'price', 'add_to_cart'),
					'layout' => 'minimal',
					'image_size' => 'medium'
				)),
				'quickview_settings' => json_encode(array(
					'style' => array(
						'modal_width' => '600px',
						'modal_height' => 'auto',
						'background_color' => '#ffffff',
						'text_color' => '#333333',
						'button_color' => '#007cba',
						'overlay_color' => 'rgba(0,0,0,0.8)'
					),
					'behavior' => array(
						'close_on_overlay' => true,
						'show_close_button' => true,
						'auto_focus' => true,
						'keyboard_navigation' => true
					)
				))
			),
			array(
				'quickview_name' => 'Standard Product Quick View',
				'quickview_data' => json_encode(array(
					'display_fields' => array('image', 'gallery', 'title', 'price', 'description', 'add_to_cart', 'rating'),
					'layout' => 'standard',
					'image_size' => 'large'
				)),
				'quickview_settings' => json_encode(array(
					'style' => array(
						'modal_width' => '800px',
						'modal_height' => 'auto',
						'background_color' => '#ffffff',
						'text_color' => '#333333',
						'button_color' => '#28a745',
						'overlay_color' => 'rgba(0,0,0,0.7)'
					),
					'behavior' => array(
						'close_on_overlay' => true,
						'show_close_button' => true,
						'auto_focus' => true,
						'keyboard_navigation' => true
					)
				))
			),
			array(
				'quickview_name' => 'Detailed Product View',
				'quickview_data' => json_encode(array(
					'display_fields' => array('image', 'gallery', 'title', 'price', 'description', 'short_description', 'add_to_cart', 'rating', 'meta', 'variations'),
					'layout' => 'detailed',
					'image_size' => 'large'
				)),
				'quickview_settings' => json_encode(array(
					'style' => array(
						'modal_width' => '900px',
						'modal_height' => '80vh',
						'background_color' => '#ffffff',
						'text_color' => '#333333',
						'button_color' => '#dc3545',
						'overlay_color' => 'rgba(0,0,0,0.9)'
					),
					'behavior' => array(
						'close_on_overlay' => false,
						'show_close_button' => true,
						'auto_focus' => true,
						'keyboard_navigation' => true
					)
				))
			),
			array(
				'quickview_name' => 'Compact Quick View',
				'quickview_data' => json_encode(array(
					'display_fields' => array('image', 'title', 'price', 'add_to_cart'),
					'layout' => 'compact',
					'image_size' => 'thumbnail'
				)),
				'quickview_settings' => json_encode(array(
					'style' => array(
						'modal_width' => '400px',
						'modal_height' => 'auto',
						'background_color' => '#f8f9fa',
						'text_color' => '#495057',
						'button_color' => '#6f42c1',
						'overlay_color' => 'rgba(0,0,0,0.6)'
					),
					'behavior' => array(
						'close_on_overlay' => true,
						'show_close_button' => false,
						'auto_focus' => false,
						'keyboard_navigation' => true
					)
				))
			),
			array(
				'quickview_name' => 'Gallery Focus Quick View',
				'quickview_data' => json_encode(array(
					'display_fields' => array('image', 'gallery', 'title', 'price', 'variations', 'add_to_cart'),
					'layout' => 'gallery',
					'image_size' => 'full'
				)),
				'quickview_settings' => json_encode(array(
					'style' => array(
						'modal_width' => '1000px',
						'modal_height' => '90vh',
						'background_color' => '#ffffff',
						'text_color' => '#212529',
						'button_color' => '#fd7e14',
						'overlay_color' => 'rgba(0,0,0,0.95)'
					),
					'behavior' => array(
						'close_on_overlay' => true,
						'show_close_button' => true,
						'auto_focus' => true,
						'keyboard_navigation' => true
					)
				))
			)
		);
		
		foreach ( $prebuilt_quickview as $quickview ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$table_name,
				array(
					'quickview_name' => $quickview['quickview_name'],
					'quickview_data' => $quickview['quickview_data'],
					'quickview_settings' => $quickview['quickview_settings'],
					'status' => 'inactive',
					'created_at' => current_time('mysql'),
					'updated_at' => current_time('mysql')
				),
				array('%s', '%s', '%s', '%s', '%s', '%s')
			);
		}
	}

	public static function table_product_quickview() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_product_quickview';
	}

	// Add this function to define the table name
	public static function table_checkout_fields() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_checkout_fields';
	}

	// Add this function to create the checkout fields table
	public static function create_checkout_fields_table() {
		global $wpdb;

		$table_name = self::table_checkout_fields();
		if ( self::table_exists( $table_name ) ) {
			// Check if the block_checkout column exists
			if ( ! self::column_exists( $table_name, 'block_checkout' ) ) {
				// Add the column if it doesn't exist
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema modification operation, caching not appropriate
                $wpdb->query( $wpdb->prepare( "ALTER TABLE `%1s` ADD COLUMN block_checkout tinyint(1) DEFAULT 0", $table_name ) );
			}
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        section varchar(50) NOT NULL,
        field_id varchar(100) NOT NULL,
        type varchar(50) NOT NULL,
        label varchar(255) NOT NULL,
        placeholder varchar(255),
        class varchar(255),
        required tinyint(1) DEFAULT 0,
        priority int(11) DEFAULT 10,
        options longtext,
        validation varchar(255),
        enabled tinyint(1) DEFAULT 1,
        custom tinyint(1) DEFAULT 0,
        block_checkout tinyint(1) DEFAULT 0,
        display_in_emails tinyint(1) DEFAULT 1,
        display_in_order tinyint(1) DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY field_id_section (field_id, section)
     ) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	// Add this function to define the table name
	public static function table_checkout_field_values() {
		global $wpdb;
		return $wpdb->prefix . 'shopglut_checkout_field_values';
	}

	// Add this function to create the checkout field values table
	public static function create_checkout_field_values_table() {
		global $wpdb;

		$table_name = self::table_checkout_field_values();
		if ( self::table_exists( $table_name ) ) {
			return; // Table already exists
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		order_id bigint(20) unsigned NOT NULL,
		field_id varchar(100) NOT NULL,
		section varchar(50) NOT NULL,
		value longtext,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (id),
		KEY order_id (order_id),
		KEY field_id (field_id),
		UNIQUE KEY order_field_section (order_id, field_id, section)
	) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}


	public static function create_showcase_filters1() {
		global $wpdb;

		$table_name = self::table_showcase_filters1();
		if ( self::table_exists( $table_name ) ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            filter_name varchar(255) NOT NULL,
            filter_settings longtext,
            PRIMARY KEY  (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}


	public static function create_shop_banner_layouts() {
		global $wpdb;
		$table_name = self::table_shop_banner_layouts();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            layout_name varchar(255) NOT NULL,
            layout_template varchar(255) NOT NULL,
            layout_settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_slider_layouts() {
		global $wpdb;
		$table_name = self::table_slider_layouts();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            layout_name varchar(255) NOT NULL,
            layout_template varchar(255) NOT NULL,
            layout_settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_tab_layouts() {
		global $wpdb;
		$table_name = self::table_tab_layouts();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            layout_name varchar(255) NOT NULL,
            layout_template varchar(255) NOT NULL,
            layout_settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_accordion_layouts() {
		global $wpdb;
		$table_name = self::table_accordion_layouts();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            layout_name varchar(255) NOT NULL,
            layout_template varchar(255) NOT NULL,
            layout_settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function create_gallery_layouts() {
		global $wpdb;
		$table_name = self::table_gallery_layouts();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            layout_name varchar(255) NOT NULL,
            layout_template varchar(255) NOT NULL,
            layout_settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- dbDelta for table creation, safe SQL with placeholders
		dbDelta( $sql );
	}

	public static function shortcodeglut_initialize() {
		if ( self::$initialized ) {
			return;
		}

		// Create core tables for shortcodeglut
		self::create_user_actions();
		self::create_shortcodes_showcase();
		self::create_woo_templates();

		self::$initialized = true;
	}
}