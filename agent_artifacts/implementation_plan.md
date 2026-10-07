# Phase D: Product Variations Implementation Plan

We are ready to implement **Product Variations** (e.g., sizes, colors, material types) for the MotoTrack inventory system. 

## Approach: Parent-Child Inventory Strategy
Instead of creating a massive structural overhaul with entirely new database tables that would break existing Sales, Pre-Orders, and POS logic, we will use a **Parent-Child relationship** within the existing `inventory_items` table. 

- **Parent Items:** Represent the base product (e.g., "ProTaper Grips"). They will appear in the catalog.
- **Child Items (Variations):** Represent the specific variation (e.g., "Red", "Blue"). They hold the actual `stock_quantity`, `price` overrides, and unique `sku` (e.g., `PTG-RED`).

This strategy is highly elegant because it means all existing Cart, Sales, and Pre-Order logic will continue to work seamlessly (since they just point to an `inventory_item_id`, which will now map directly to the specific variation).

## Proposed Changes

### 1. Database Schema
#### [NEW] Migration: `add_variation_columns_to_inventory_items_table.php`
- Add `parent_id` (foreign key referencing `inventory_items.id`, nullable).
- Add `variation_name` (string, nullable) to store the variation label (e.g., "Size L, Red").

### 2. Models
#### [MODIFY] [InventoryItem.php](file:///e:/laragon/www/mototrack/app/Models/InventoryItem.php)
- Add `parent_id` and `variation_name` to `$fillable`.
- Add `parent()` and `variations()` Eloquent relationships.

### 3. Administrator Inventory Management
#### [MODIFY] Admin Inventory Controller & Views
- Update the **Admin Inventory Dashboard** so owners can click a "Manage Variations" button on any product.
- Create a UI to add, edit, or remove variations (setting specific stock and price for each).
- The POS system search will automatically pick up variations since they are valid inventory items, meaning owners can instantly search and sell specific variations (e.g., "Grip Red").

### 4. Customer eCommerce Catalog
#### [MODIFY] [PublicBrowseController.php](file:///e:/laragon/www/mototrack/app/Http/Controllers/Inventory/PublicBrowseController.php)
- Update the public catalog query to only show **Parent** items (`whereNull('parent_id')`) to prevent cluttering the storefront.
- Eager load variations (`with('variations')`).

#### [MODIFY] [browse.blade.php](file:///e:/laragon/www/mototrack/resources/views/inventory/browse.blade.php)
- If a product has variations, change the "Add to Cart" button to an interactive **Select Variation** dropdown or modal.
- Customers must select a variation (e.g., Red or Blue) before the item is added to their cart. The selected variation dictates the price and stock limits checked.

## User Review Required
> [!IMPORTANT]
> Because variations are essentially just unique inventory items linked to a parent, if a parent product currently has a stock of 50, and you create 2 variations, you will need to specify how that stock is distributed (e.g., 25 Red, 25 Blue). The base parent stock will effectively become a display aggregate of its variations.

**Does this plan align with your vision for managing product variations? If you approve, click Proceed and I will execute the changes on the server and local codebase.**
