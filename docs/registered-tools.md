# Registered Tools Documentation

This document provides a comprehensive list of all tools registered in the WordPress MCP plugin.

## WordPress Core Tools

### Posts Tools

#### wp_posts_search

- **Type**: read
- **Description**: Search and filter WordPress posts with pagination
- **REST Alias**: `/wp/v2/posts` (GET)
- **Parameters**: Standard WordPress post query parameters
- **Returns**: Array of posts matching the search criteria

#### wp_get_post

- **Type**: read
- **Description**: Get a WordPress post by ID
- **REST Alias**: `/wp/v2/posts/(?P<id>[\d]+)` (GET)
- **Parameters**:
  - `id`: Post ID (required)
- **Returns**: Post object

#### wp_add_post

- **Type**: create
- **Description**: Add a new WordPress post
- **REST Alias**: `/wp/v2/posts` (POST)
- **Parameters**: Standard WordPress post creation parameters
- **Returns**: Created post object

#### wp_update_post

- **Type**: update
- **Description**: Update a WordPress post by ID
- **REST Alias**: `/wp/v2/posts/(?P<id>[\d]+)` (PUT)
- **Parameters**:
  - `id`: Post ID (required)
  - Other standard post update parameters
- **Returns**: Updated post object

#### wp_delete_post

- **Type**: delete
- **Description**: Delete a WordPress post by ID
- **REST Alias**: `/wp/v2/posts/(?P<id>[\d]+)` (DELETE)
- **Parameters**:
  - `id`: Post ID (required)
- **Returns**: Deleted post object

### Users Tools

#### wp_users_search

- **Type**: read
- **Description**: Search and filter WordPress users with pagination
- **REST Alias**: `/wp/v2/users` (GET)
- **Parameters**: Standard WordPress user query parameters
- **Returns**: Array of users matching the search criteria

#### wp_get_user

- **Type**: read
- **Description**: Get a WordPress user by ID
- **REST Alias**: `/wp/v2/users/(?P<id>[\d]+)` (GET)
- **Parameters**:
  - `id`: User ID (required)
- **Returns**: User object

#### wp_add_user

- **Type**: create
- **Description**: Add a new WordPress user
- **REST Alias**: `/wp/v2/users` (POST)
- **Parameters**: Standard WordPress user creation parameters
- **Returns**: Created user object

#### wp_update_user

- **Type**: update
- **Description**: Update a WordPress user by ID
- **REST Alias**: `/wp/v2/users/(?P<id>[\d]+)` (PUT)
- **Parameters**:
  - `id`: User ID (required)
  - Other standard user update parameters
- **Returns**: Updated user object

#### wp_delete_user

- **Type**: delete
- **Description**: Delete a WordPress user by ID
- **REST Alias**: `/wp/v2/users/(?P<id>[\d]+)` (DELETE)
- **Parameters**:
  - `id`: User ID (required)
- **Returns**: Deleted user object

## WooCommerce Tools

These tools are only available when WooCommerce is active.

### Products Tools

#### wc_products_search

- **Type**: read
- **Description**: Search and filter WooCommerce products with pagination
- **REST Alias**: `/wc/v3/products` (GET)
- **Parameters**: Standard WooCommerce product query parameters
- **Returns**: Array of products matching the search criteria

#### wc_get_product

- **Type**: read
- **Description**: Get a WooCommerce product by ID
- **REST Alias**: `/wc/v3/products/(?P<id>[\d]+)` (GET)
- **Parameters**:
  - `id`: Product ID (required)
- **Returns**: Product object

#### wc_add_product

- **Type**: create
- **Description**: Add a new WooCommerce product
- **REST Alias**: `/wc/v3/products` (POST)
- **Parameters**: Standard WooCommerce product creation parameters
- **Returns**: Created product object

#### wc_update_product

- **Type**: update
- **Description**: Update a WooCommerce product by ID
- **REST Alias**: `/wc/v3/products/(?P<id>[\d]+)` (PUT)
- **Parameters**:
  - `id`: Product ID (required)
  - Other standard product update parameters
- **Returns**: Updated product object

### Orders Tools

#### wc_orders_search

- **Type**: read
- **Description**: Search and filter WooCommerce orders with pagination
- **REST Alias**: `/wc/v3/orders` (GET)
- **Parameters**: Standard WooCommerce order query parameters
- **Returns**: Array of orders matching the search criteria

#### wc_get_order

- **Type**: read
- **Description**: Get a WooCommerce order by ID
- **REST Alias**: `/wc/v3/orders/(?P<id>[\d]+)` (GET)
- **Parameters**:
  - `id`: Order ID (required)
- **Returns**: Order object

## Site Information Tools

#### wp_get_site_info

- **Type**: read
- **Description**: Get general information about the WordPress site
- **Returns**: Site information including:
  - Site name
  - Site description
  - Site URL
  - WordPress version
  - Active theme
  - Active plugins
