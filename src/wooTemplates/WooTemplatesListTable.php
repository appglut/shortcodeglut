<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\wooTemplates\WooTemplatesEntity;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

// Prevent class redeclaration when both ShortcodeGlut and ShopGlut plugins are active
if ( ! class_exists( 'Shortcodeglut\\wooTemplates\\WooTemplatesListTable' ) ) {

class WooTemplatesListTable extends \WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'template',
            'plural'   => 'templates',
            'ajax'     => false
        ]);
    }

    /**
     * Get columns for the table
     */
    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'template_name' => esc_html__('Template Name', 'shortcodeglut'),
            'template_id'   => esc_html__('Template ID', 'shortcodeglut'),
            'actions'       => esc_html__('Actions', 'shortcodeglut')
        ];
    }

    /**
     * Prepare items for the table
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        // Get templates from database
        $per_page = 10;
        $current_page = $this->get_pagenum();

        $total_items = WooTemplatesEntity::retrieveAllCount();
        $this->items = WooTemplatesEntity::retrieveAll($per_page, $current_page);

        // Set pagination args
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        return [
            'template_name' => ['template_name', false],
            'template_id'   => ['template_id', false]
        ];
    }

    /**
     * Column default
     */
    public function column_default($item, $column_name) {
        return esc_html($item[$column_name] ?? '');
    }

    /**
     * Checkbox column
     */
    public function column_cb($item) {
        $is_default = isset( $item['is_default'] ) && $item['is_default'] == 1;

        if ( $is_default ) {
            return '';
        }

        return sprintf(
            '<input type="checkbox" name="template_ids[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * Template name column with actions
     */
    public function column_template_name($item) {
        $is_default = isset( $item['is_default'] ) && $item['is_default'] == 1;
        $default_badge = $is_default ? ' <span class="scg-badge scg-badge--default">' . esc_html__( 'Default', 'shortcodeglut' ) . '</span>' : '';

        $actions = [];

        if ( ! $is_default ) {
            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=shortcodeglut&editor=woo_template&template_id=' . $item['id'])),
                esc_html__('Edit', 'shortcodeglut')
            );
        } else {
            $actions['view_only'] = sprintf(
                '<span style="color:#999;cursor:not-allowed">%s</span>',
                esc_html__('Prebuilt - View Only', 'shortcodeglut')
            );
        }

        if ( ! $is_default ) {
            $actions['delete'] = sprintf(
                '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin.php?page=shortcodeglut&view=woo_templates&action=delete&template_id=' . $item['id']), 'delete_template_' . $item['id'])),
                esc_html__('Are you sure you want to delete this template?', 'shortcodeglut'),
                esc_html__('Delete', 'shortcodeglut')
            );
        }

        $name_link = $is_default ? esc_html($item['template_name']) : sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=shortcodeglut&editor=woo_template&template_id=' . $item['id'])),
            esc_html($item['template_name'])
        );

        return sprintf(
            '<strong>%s</strong>%s%s',
            $name_link,
            $default_badge,
            $this->row_actions($actions)
        );
    }

    /**
     * Template ID column with copy button
     */
    public function column_template_id($item) {
        $template_id = esc_html($item['template_id']);
        return '<div class="shopglut-template-id-wrapper">
                <code class="shopglut-template-id-code">' . $template_id . '</code>
                <button type="button" class="shopglut-copy-btn shopglut-copy-id-btn" data-template-id="' . esc_attr($item['template_id']) . '" title="' . esc_attr__('Copy', 'shortcodeglut') . '">
                    <span class="dashicons dashicons-admin-page"></span>
                </button>
            </div>';
    }

    /**
     * Actions column with preview and duplicate buttons
     */
    public function column_actions($item) {
        $template_id = intval($item['id']);
        $template_name = esc_html($item['template_name'] ?? 'Template');
        $template_html = esc_attr($item['template_html'] ?? '');
        $template_css = esc_attr($item['template_css'] ?? '');

        return '<div class="shopglut-template-actions">
                <button type="button" class="shopglut-action-btn shopglut-preview-btn"
                        data-template-id="' . $template_id . '"
                        data-template-html="' . $template_html . '"
                        data-template-css="' . $template_css . '"
                        data-template-name="' . $template_name . '"
                        title="' . esc_attr__('Preview Template', 'shortcodeglut') . '">
                    <span class="dashicons dashicons-visibility"></span>
                    <span>' . esc_html__('Preview', 'shortcodeglut') . '</span>
                </button>
                <button type="button" class="shopglut-action-btn shopglut-duplicate-btn"
                        data-template-id="' . $template_id . '"
                        title="' . esc_attr__('Duplicate Template', 'shortcodeglut') . '">
                    <span class="dashicons dashicons-admin-page"></span>
                    <span>' . esc_html__('Duplicate', 'shortcodeglut') . '</span>
                </button>
            </div>';
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        return [
            'delete' => esc_html__('Delete', 'shortcodeglut')
        ];
    }

    /**
     * No items found text
     */
    public function no_items() {
        esc_html_e('No templates found.', 'shortcodeglut');
    }
}

} // End if class_exists check
