=== ShortcodeGlut - Product Shortcodes for WooCommerce 11 ===
Contributors: shopglut
Tags: woocommerce, products, shortcode, product table, product display
Requires at least: 5.8
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beautiful WooCommerce product shortcodes with grid, list, and table layouts for displaying products, sale items, and category listings.

== Description ==

**ShortcodeGlut** provides a collection of powerful shortcodes for displaying WooCommerce products in beautiful, customizable layouts. Perfect for creating product showcases, sale displays, category listings, and data tables without any coding knowledge.

= AVAILABLE SHORTCODES =

* **[shopglut_product_table]** - Display products in a responsive, sortable, searchable data table
* **[shopglut_woo_category]** - Show products from specific categories with customizable layouts
* **[shopglut_sale_products]** - Display products currently on sale with discount badges

= KEY FEATURES =

**Product Table Shortcode:**
* Responsive table with sortable columns
* Built-in search and filtering
* Category and tag dropdowns
* AJAX-powered pagination
* Customizable columns (title, price, stock, categories, date, add to cart)
* Length selector for items per page
* Mobile-responsive design

**Category Products Shortcode:**
* Display products from one or multiple categories
* Customizable grid layouts for desktop, tablet, and mobile
* Toolbar with search, filter, and sort options
* AJAX pagination for smooth navigation
* Integration with Woo Templates for custom styling
* Support for custom product display templates

**Sale Products Shortcode:**
* Display products currently on sale
* Automatic discount percentage badges
* Customizable grid columns
* Optional pagination and async loading
* Control which elements to show (image, title, price, rating, button)
* Template-based product card customization
* Category filtering support

== Installation ==

**Automatic Installation:**
1. Go to WordPress Admin → Plugins → Add New
2. Search for "ShortcodeGlut"
3. Click "Install Now" and then "Activate"
4. Navigate to ShortcodeGlut → Shortcode Showcase for documentation

**Manual Installation:**
1. Download the plugin zip file
2. Upload to `/wp-content/plugins/shortcodeglut/` directory
3. Activate through the Plugins menu in WordPress
4. Visit ShortcodeGlut → Shortcode Showcase for usage instructions

== Usage Examples ==

**Basic Product Table:**
```
[shopglut_product_table]
```

**Custom Product Table:**
```
[shopglut_product_table cols="title|price|stock|categories|date|add_to_cart" items_per_page="25" paging="1" searching="1" sorting="1"]
```

**Category Products:**
```
[shopglut_woo_category id="electronics" items_per_page="12" cols="3"]
```

**Sale Products:**
```
[shopglut_sale_products limit="12" columns="4" show_rating="1"]
```

== Changelog ==

= 1.0.0 =
* Initial release
* Product table shortcode with AJAX filtering, sorting, and pagination
* Category products shortcode with customizable layouts
* Sale products shortcode with discount badges
* Woo Templates integration for custom styling
* Responsive design for all devices
