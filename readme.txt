=== ShortcodeGlut - Product Shortcodes for WooCommerce ===
Contributors: appglut
Tags: woocommerce, products, shortcode, product table, product display
Requires at least: 5.8
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beautiful WooCommerce product shortcodes with grid, list, and table layouts for displaying products, sale items, and category listings.

== Description ==

**ShortcodeGlut** provides a collection of powerful shortcodes for displaying WooCommerce products in beautiful, customizable layouts. Perfect for creating product showcases, sale displays, category listings, and interactive data tables without any coding knowledge.

= AVAILABLE SHORTCODES =

* **[shopglut_product_table]** - Interactive product table with search, filters, column sorting, and pagination
* **[shopglut_woo_category]** - Show products from specific categories with customizable layouts
* **[shopglut_sale_products]** - Display products currently on sale with discount badges

= KEY FEATURES =

**Product Table Shortcode [shopglut_product_table]:**
* Interactive table with click-to-sort columns (ascending/descending)
* Real-time search across all product data
* Dynamic category, tag, and stock status dropdowns (populated from table data)
* Client-side pagination with items per page selector (10, 25, 50, 100)
* AJAX add-to-cart with "View Cart" button after adding
* Two design styles: classic (default) and modern (gradient header)
* Fully responsive - converts to card layout on mobile
* Customizable columns: title, price, stock, categories, tags, SKU, date, thumbnail, rating, sales, and more
* Visual sort indicators (↑ ↓) on column headers
* Results count showing "Showing X to Y of Z entries"
* Show/hide controls with shortcode parameters

**Available Column Fields:**
- `title` - Product name with link
- `price` - Formatted price with sale pricing
- `stock` - Stock status (In Stock / Out of Stock)
- `categories` - Product category links
- `tags` - Product tag links
- `date` - Product creation date
- `modified_date` - Last modified date
- `thumbnail` - Product image
- `sku` - Product SKU
- `rating` - Star rating display
- `sales` - Total sales count
- `add_to_cart` - Add to Cart button
- `view` - View product button

**Available Shortcode Parameters:**
- `design` - Design style: "classic" or "modern" (default: classic)
- `cols` - Column definition with | separator (default: title|price|stock|categories|date|add_to_cart)
- `colheads` - Column headers with | separator (default: Product|Price|Stock|Categories|Date|Action)
- `title` - Table title for modern design
- `description` - Table description for modern design
- `show_items_per_page` - Show items dropdown: 1 or 0 (default: 1)
- `items_per_page` - Default items per page: 10, 25, 50, 100 (default: 10)
- `show_search` - Show search field: 1 or 0 (default: 1)
- `show_category_filter` - Show category dropdown: 1 or 0 (default: 1)
- `show_tag_filter` - Show tag dropdown: 1 or 0 (default: 0)
- `show_stock_filter` - Show stock dropdown: 1 or 0 (default: 0)
- `orderby` - Order field: date, title, price, modified (default: date)
- `order` - Sort direction: ASC or DESC (default: DESC)
- `categories` - Filter by category slugs (comma-separated)
- `include` - Include only specific product IDs
- `exclude` - Exclude specific product IDs
- `thumb` - Show thumbnail: 1 or 0 (default: 0)
- `thumb_width` - Thumbnail width in pixels (default: 48)
- `sorting` - Enable column sorting: 1 or 0 (default: 1)
- `responsive` - Enable responsive layout: 1 or 0 (default: 1)

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

**Basic Product Table (Classic Design):**
```
[shopglut_product_table]
```

**Modern Product Table with All Features:**
```
[shopglut_product_table design="modern" title="Our Products" description="Browse our catalog" items_per_page="25" show_category_filter="1" show_tag_filter="1" show_stock_filter="1"]
```

**Minimal Table - Search Only:**
```
[shopglut_product_table show_items_per_page="0" show_category_filter="0" show_search="1"]
```

**Custom Columns:**
```
[shopglut_product_table cols="title|thumbnail|price|stock|rating|add_to_cart" colheads="Product|Image|Price|Stock|Rating|Action"]
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
* Interactive product table with custom sorting, filtering, and pagination
* Category and tag dropdowns dynamically populated from table data
* AJAX add-to-cart with View Cart button
* Two design styles: classic and modern
* Category products shortcode with customizable layouts
* Sale products shortcode with discount badges
* Woo Templates integration for custom styling
* Fully responsive design for all devices
