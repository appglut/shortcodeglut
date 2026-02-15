<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\ShortcodeglutDatabase;

class WooTemplatesEntity {

	protected static function getTable() {
		return ShortcodeglutDatabase::table_woo_templates();
	}

	/**
	 * Check if a column exists in the table
	 */
	private static function column_exists( $table, $column_name ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder, WordPress.DB.DirectDatabaseQuery.NoCaching -- Column existence check, safe table name from internal function
		$results = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `%1s` LIKE %s", $table, $column_name ) );
		return ! empty( $results );
	}

	public static function retrieveAll($limit = 0, $current_page = 1) {
		global $wpdb;

		$table = self::getTable();
		if ( empty( $table ) ) {
			return [];
		}

		// Check if is_default column exists
		$has_default_column = self::column_exists( $table, 'is_default' );

		// Cache key for this query
		$cache_key = 'shopglut_woo_templates_all_' . md5( $limit . '_' . $current_page );
		$result = wp_cache_get( $cache_key );

		if ( false === $result ) {
			// Validate and sanitize current_page and limit parameters
			$current_page = is_numeric($current_page) ? absint($current_page) : 1;
			$limit = is_numeric($limit) ? absint($limit) : 10;

			// Order by clause based on whether is_default column exists
			$order_by = $has_default_column ? 'is_default DESC, id DESC' : 'id DESC';

			if ($limit > 0) {
				if ($current_page > 1) {
					$result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Direct query for custom table, safe internal table/order by values
						$wpdb->prepare(
							"SELECT * FROM `{$table}` WHERE 1=%d ORDER BY {$order_by} LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							1, $limit, ($current_page - 1) * $limit
						), 'ARRAY_A'
					);
				} else {
					$result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Direct query for custom table, safe internal table/order by values
						$wpdb->prepare(
							"SELECT * FROM `{$table}` WHERE 1=%d ORDER BY {$order_by} LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							1, $limit
						), 'ARRAY_A'
					);
				}
			} else {
				$result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Direct query for custom table, safe internal table/order by values
					$wpdb->prepare(
						"SELECT * FROM `{$table}` WHERE 1=%d ORDER BY {$order_by}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						1
					), 'ARRAY_A'
				);
			}

			// Cache the result for 1 hour
			wp_cache_set( $cache_key, $result, '', 3600 );
		}

		$output = [];

		if (is_array($result) && !empty($result)) {
			foreach ($result as $item) {
				$output[] = $item;
			}
		}

		return $output;
	}

	public static function retrieveAllCount() {
		global $wpdb;

		$table = self::getTable();
		if ( empty( $table ) ) {
			return 0;
		}

		// Cache key for count query
		$cache_key = 'shopglut_woo_templates_count';
		$count = wp_cache_get( $cache_key );

		if ( false === $count ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Direct query required for custom table operation, caching handled manually, safe table name from internal function
			$count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM `{$table}`" );

			// Cache the result for 1 hour
			wp_cache_set( $cache_key, $count, '', 3600 );
		}

		return $count;
	}

	public static function get_template($template_id) {
		global $wpdb;
		
		$table = self::getTable();
		if ( empty( $table ) || ! $template_id ) {
			return null;
		}

		$template_id = absint($template_id);

		// Cache key for this template
		$cache_key = 'shopglut_woo_template_' . $template_id;
		$result = wp_cache_get( $cache_key );
		
		if ( false === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query required for custom table operation, caching handled manually
			$result = $wpdb->get_row( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using sprintf with escaped table name, direct query required for custom table operation
				sprintf("SELECT * FROM `%s` WHERE id = %d", esc_sql($table), absint($template_id)), ARRAY_A // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder -- Direct query required for custom table operation, using sprintf with escaped table name for compatibility
			);
			
			// Cache the result for 1 hour
			wp_cache_set( $cache_key, $result, '', 3600 );
		}

		return $result;
	}

	public static function get_template_by_template_id($template_id) {
		global $wpdb;
		
		$table = self::getTable();
		if ( empty( $table ) || ! $template_id ) {
			return null;
		}

		$template_id = sanitize_text_field($template_id);

		// Cache key for this template lookup
		$cache_key = 'shopglut_woo_template_by_id_' . md5($template_id);
		$result = wp_cache_get( $cache_key );
		
		if ( false === $result ) {
			$result = $wpdb->get_row(sprintf("SELECT * FROM `%s` WHERE template_id = %s", esc_sql($table), $wpdb->prepare("%s", $template_id)), ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Direct query required for custom table operation, caching handled manually
			
			// Cache the result for 1 hour
			wp_cache_set( $cache_key, $result, '', 3600 );
		}

		return $result;
	}

	public static function delete_template($template_id) {
		global $wpdb;

		$table = self::getTable();
		if ( empty( $table ) || ! $template_id ) {
			return false;
		}

		$template_id = absint($template_id);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table delete operation, cache cleared below
		$result = $wpdb->delete( $table, ['id' => $template_id], ['%d'] );

		// Clear related cache after deletion
		if ( $result ) {
			wp_cache_delete( 'shopglut_woo_templates_count' );
			wp_cache_delete( 'shopglut_woo_template_' . $template_id );
			// Clear listing cache with pattern matching (simplified approach)
			wp_cache_flush(); // Or implement more targeted cache clearing
		}

		return $result;
	}

	/**
	 * Insert default/prebuilt templates
	 */
	public static function insert_default_templates() {
		global $wpdb;

		$table = self::getTable();
		if ( empty( $table ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ShopGlut: insert_default_templates() - table is empty' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for development
			}
			return;
		}

		// Check if table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Direct query for table existence check, safe table name from internal function
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );
		if ( ! $table_exists ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ShopGlut: insert_default_templates() - table does not exist: ' . $table ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for development
			}
			return;
		}

		// All available default templates
		$all_templates = array(
			'classic_card' => array(
				'template_id' => 'classic_card',
				'template_name' => 'Classic Card',
				'template_html' => '<div class="scg-tmpl-card">
    <div class="scg-tmpl-image">
        <a href="[product_permalink]">[product_image]</a>
    </div>
    <div class="scg-tmpl-content">
        <h3 class="scg-tmpl-title"><a href="[product_permalink]">[product_title]</a></h3>
        <div class="scg-tmpl-price">[product_price]</div>
        <div class="scg-tmpl-rating">[product_rating]</div>
        <div class="scg-tmpl-desc">[product_short_description]</div>
        <div class="scg-tmpl-actions">
            [btn_cart]
            [btn_view]
        </div>
    </div>
</div>',
				'template_css' => '.scg-tmpl-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}
.scg-tmpl-card:hover {
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.scg-tmpl-image {
    position: relative;
}
.scg-tmpl-image a {
    display: block;
}
.scg-tmpl-image img {
    width: 100%;
    height: auto;
}
.scg-tmpl-content {
    padding: 20px;
}
.scg-tmpl-title {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 12px 0;
    line-height: 1.3;
}
.scg-tmpl-title a {
    color: #111827;
    text-decoration: none;
}
.scg-tmpl-title a:hover {
    color: #0284c7;
}
.scg-tmpl-price {
    font-size: 20px;
    font-weight: 700;
    color: #0284c7;
    margin-bottom: 8px;
}
.scg-tmpl-rating {
    margin-bottom: 12px;
}
.scg-tmpl-desc {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 16px;
}
.scg-tmpl-actions {
    display: flex;
    gap: 10px;
}',
				'is_default' => 1,
			),
			'minimal_list' => array(
				'template_id' => 'minimal_list',
				'template_name' => 'Minimal List',
				'template_html' => '<div class="scg-tmpl-list">
    <div class="scg-tmpl-list-thumb">
        <a href="[product_permalink]">[product_image]</a>
    </div>
    <div class="scg-tmpl-list-info">
        <h3><a href="[product_permalink]">[product_title]</a></h3>
        <p class="scg-tmpl-list-price">[product_price]</p>
        <p class="scg-tmpl-list-meta">[product_stock]</p>
        <div class="scg-tmpl-list-actions">
            [btn_cart]
        </div>
    </div>
</div>',
				'template_css' => '.scg-tmpl-list {
    display: flex;
    gap: 20px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    align-items: center;
}
.scg-tmpl-list-thumb {
    flex-shrink: 0;
}
.scg-tmpl-list-thumb a {
    display: block;
}
.scg-tmpl-list-thumb img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
}
.scg-tmpl-list-info {
    flex: 1;
    min-width: 0;
}
.scg-tmpl-list-info h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px 0;
}
.scg-tmpl-list-info h3 a {
    color: #111827;
    text-decoration: none;
}
.scg-tmpl-list-info h3 a:hover {
    color: #0284c7;
}
.scg-tmpl-list-price {
    font-size: 20px;
    font-weight: 700;
    color: #0284c7;
    margin: 8px 0;
}
.scg-tmpl-list-meta {
    color: #6b7280;
    font-size: 14px;
    margin: 4px 0 12px 0;
}
.scg-tmpl-list-actions {
    display: flex;
    gap: 8px;
}',
				'is_default' => 1,
			),
			'featured_badge' => array(
				'template_id' => 'featured_badge',
				'template_name' => 'Featured Badge',
				'template_html' => '<div class="scg-tmpl-featured">
    <div class="scg-tmpl-featured-image">
        <a href="[product_permalink]">[product_image]</a>
    </div>
    <div class="scg-tmpl-featured-content">
        <h3><a href="[product_permalink]">[product_title]</a></h3>
        <p class="scg-tmpl-featured-price">[product_price]</p>
        <p class="scg-tmpl-featured-cats">[product_categories]</p>
        <div class="scg-tmpl-featured-actions">
            [btn_cart]
        </div>
    </div>
</div>',
				'template_css' => '.scg-tmpl-featured {
    position: relative;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.scg-tmpl-featured-image a {
    display: block;
}
.scg-tmpl-featured-image img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}
.scg-tmpl-featured-content {
    padding: 24px;
}
.scg-tmpl-featured-content h3 {
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 12px 0;
}
.scg-tmpl-featured-content h3 a {
    color: #111827;
    text-decoration: none;
}
.scg-tmpl-featured-content h3 a:hover {
    color: #0284c7;
}
.scg-tmpl-featured-price {
    font-size: 24px;
    font-weight: 700;
    color: #0284c7;
    margin: 12px 0;
}
.scg-tmpl-featured-cats {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 16px;
}
.scg-tmpl-featured-actions {
    display: flex;
    gap: 8px;
}',
				'is_default' => 1,
			),
			'clean_simple' => array(
				'template_id' => 'clean_simple',
				'template_name' => 'Clean Simple',
				'template_html' => '<div class="scg-tmpl-clean">
    <div class="scg-tmpl-clean-img">
        <a href="[product_permalink]">[product_image]</a>
    </div>
    <div class="scg-tmpl-clean-body">
        <p class="scg-tmpl-clean-cats">[product_categories]</p>
        <h3><a href="[product_permalink]">[product_title]</a></h3>
        <p class="scg-tmpl-clean-price">[product_price]</p>
        <div class="scg-tmpl-clean-actions">
            [btn_cart]
        </div>
    </div>
</div>',
				'template_css' => '.scg-tmpl-clean {
    text-align: center;
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s ease;
}
.scg-tmpl-clean:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
.scg-tmpl-clean-img a {
    display: block;
    margin-bottom: 16px;
}
.scg-tmpl-clean-img img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}
.scg-tmpl-clean-cats {
    color: #0284c7;
    font-size: 13px;
    margin-bottom: 8px;
}
.scg-tmpl-clean-cats a {
    color: #0284c7;
    text-decoration: none;
}
.scg-tmpl-clean-body h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 8px 0;
}
.scg-tmpl-clean-body h3 a {
    color: #111827;
    text-decoration: none;
}
.scg-tmpl-clean-body h3 a:hover {
    color: #0284c7;
}
.scg-tmpl-clean-price {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin: 8px 0 16px 0;
}
.scg-tmpl-clean-actions {
    display: flex;
    gap: 8px;
    justify-content: center;
}',
				'is_default' => 1,
			),
			'modern_card' => array(
				'template_id' => 'modern_card',
				'template_name' => 'Modern Card',
				'template_html' => '<div class="scg-tmpl-modern">
    <div class="scg-tmpl-modern-header">
        <a href="[product_permalink]">[product_image]</a>
        <span class="scg-tmpl-modern-badge">[product_stock]</span>
    </div>
    <div class="scg-tmpl-modern-body">
        <h3><a href="[product_permalink]">[product_title]</a></h3>
        <p class="scg-tmpl-modern-excerpt">[product_short_description]</p>
        <div class="scg-tmpl-modern-footer">
            <span class="scg-tmpl-modern-price">[product_price]</span>
            <div class="scg-tmpl-modern-actions">
                [btn_cart]
            </div>
        </div>
    </div>
</div>',
				'template_css' => '.scg-tmpl-modern {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.scg-tmpl-modern-header {
    position: relative;
}
.scg-tmpl-modern-header a {
    display: block;
}
.scg-tmpl-modern-header img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}
.scg-tmpl-modern-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(0,0,0,0.7);
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
}
.scg-tmpl-modern-body {
    padding: 20px;
}
.scg-tmpl-modern-body h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 12px 0;
}
.scg-tmpl-modern-body h3 a {
    color: #111827;
    text-decoration: none;
}
.scg-tmpl-modern-body h3 a:hover {
    color: #0284c7;
}
.scg-tmpl-modern-excerpt {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 16px;
}
.scg-tmpl-modern-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.scg-tmpl-modern-price {
    font-size: 20px;
    font-weight: 700;
    color: #0284c7;
}
.scg-tmpl-modern-actions {
    display: flex;
    gap: 8px;
}',
				'is_default' => 1,
			),
		);

		// Default template IDs that should exist
		$default_template_ids = array_keys( $all_templates );

		// Check which default templates already exist
		$placeholders = implode( ',', array_fill( 0, count( $default_template_ids ), '%s' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Direct query required for custom table operation with variable placeholders, safe table name from internal function
		$existing_ids = $wpdb->get_col( $wpdb->prepare( "SELECT template_id FROM {$table} WHERE template_id IN ({$placeholders})", $default_template_ids ) );

		// Log for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ShopGlut: Existing template IDs: ' . implode( ', ', $existing_ids ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for development
		}

		// Only insert templates that don't exist yet
		$templates_to_insert = array_diff( $default_template_ids, $existing_ids );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ShopGlut: Templates to insert: ' . implode( ', ', $templates_to_insert ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for development
		}

		$inserted = false;
		$inserted_count = 0;
		foreach ( $templates_to_insert as $template_id ) {
			if ( isset( $all_templates[ $template_id ] ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Insert operation for custom table
				$result = $wpdb->insert( $table, $all_templates[ $template_id ] );
				if ( $result ) {
					$inserted = true;
					$inserted_count++;
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'ShopGlut: Successfully inserted template: ' . $template_id ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for development
					}
				} else {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'ShopGlut: Failed to insert template: ' . $template_id . ' - ' . $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for development
					}
				}
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'ShopGlut: Total templates inserted: ' . $inserted_count ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for development
		}

		// Clear cache if any templates were inserted
		if ( $inserted ) {
			wp_cache_delete( 'shopglut_woo_templates_count' );
			wp_cache_flush(); // Clear all listing cache
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ShopGlut: Cache cleared after template insertion' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging for development
			}
		}
	}
}