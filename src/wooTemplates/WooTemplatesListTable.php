<?php
namespace Shortcodeglut\wooTemplates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Shortcodeglut\wooTemplates\WooTemplatesEntity;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

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
     * Template name column - displays template name with badges
     */
    public function column_template_name($item) {
        $is_default = isset( $item['is_default'] ) && $item['is_default'] == 1;
        $is_default_shortcode = isset( $item['template_id'] ) && $item['template_id'] === 'product_card_basic';

        $badges = '';
        if ($is_default) {
            $badges .= ' <span class="scg-badge scg-badge--default">' . esc_html__( 'Prebuilt', 'shortcodeglut' ) . '</span>';
        }
        if ($is_default_shortcode) {
            $badges .= ' <span class="scg-badge scg-badge--default-shortcode">' . esc_html__( 'Default Shortcode', 'shortcodeglut' ) . '</span>';
        }

        return sprintf(
            '<strong>%s</strong>%s',
            esc_html($item['template_name']),
            $badges
        );
    }

    /**
     * Template ID column with copy button
     */
    public function column_template_id($item) {
        $template_id = esc_html($item['template_id']);
        return '<div class="shortcodeglut-template-id-wrapper">
                <code class="shortcodeglut-template-id-code">' . $template_id . '</code>
                <button type="button" class="shortcodeglut-copy-btn shortcodeglut-copy-id-btn" data-template-id="' . esc_attr($item['template_id']) . '" title="' . esc_attr__('Copy', 'shortcodeglut') . '">
                    <span class="dashicons dashicons-admin-page"></span>
                </button>
            </div>';
    }

    /**
     * Actions column with preview, edit, and delete buttons
     */
    public function column_actions($item) {
        $template_slug = $item['template_id'] ?? ''; // String ID like "product_card_basic", "product_card_horizontal_blue"
        $template_name = esc_html($item['template_name'] ?? 'Template');

        $actions = '<div class="shortcodeglut-template-actions">';

        // Preview button for all templates - use string template_id for AJAX
        $actions .= '<button type="button" class="shortcodeglut-action-btn shortcodeglut-preview-btn"
                        data-template-id="' . esc_attr($template_slug) . '"
                        data-template-name="' . $template_name . '"
                        title="' . esc_attr__('Preview Template', 'shortcodeglut') . '">
                    <span class="dashicons dashicons-visibility"></span>
                    <span>' . esc_html__('Preview', 'shortcodeglut') . '</span>
                </button>';

        $actions .= '</div>';

        return $actions;
    }

    /**
     * Get bulk actions - disabled for prebuilt templates
     */
    public function get_bulk_actions() {
        return [];
    }

    /**
     * No items found text
     */
    public function no_items() {
        esc_html_e('No templates found.', 'shortcodeglut');
    }
}
