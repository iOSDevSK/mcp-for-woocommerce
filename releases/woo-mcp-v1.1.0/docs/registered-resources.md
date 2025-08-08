# Registered Resources Documentation

This document provides a comprehensive list of all resources registered in the WordPress MCP plugin.

## General Site Information

### WordPress://site-info

- **Name**: site-info
- **Description**: Provides general information about the WordPress site
- **MIME Type**: application/json
- **Data Structure**:
  ```json
  {
    "name": "string",
    "description": "string",
    "url": "string",
    "version": "string",
    "language": "string",
    "timezone": "string",
    "date_format": "string",
    "time_format": "string",
    "start_of_week": "number"
  }
  ```

## Plugin Information

### WordPress://plugin-info

- **Name**: plugin-info
- **Description**: Provides detailed information about active WordPress plugins
- **MIME Type**: application/json
- **Data Structure**:
  ```json
  {
    "plugins": [
      {
        "name": "string",
        "version": "string",
        "description": "string",
        "author": "string",
        "author_uri": "string",
        "plugin_uri": "string",
        "text_domain": "string",
        "domain_path": "string",
        "network": "boolean",
        "requires_wp": "string",
        "requires_php": "string",
        "update_available": "boolean",
        "update_version": "string"
      }
    ]
  }
  ```

## Theme Information

### WordPress://theme-info

- **Name**: theme-info
- **Description**: Provides information about the active WordPress theme
- **MIME Type**: application/json
- **Data Structure**:
  ```json
  {
    "name": "string",
    "version": "string",
    "description": "string",
    "author": "string",
    "author_uri": "string",
    "theme_uri": "string",
    "text_domain": "string",
    "domain_path": "string",
    "template": "string",
    "status": "string",
    "tags": ["string"],
    "theme_root": "string",
    "theme_root_uri": "string",
    "parent_theme": "string",
    "custom_background": "boolean",
    "custom_header": "boolean",
    "custom_logo": "boolean",
    "customize_selective_refresh": "boolean",
    "starter_content": "boolean"
  }
  ```

## User Information

### WordPress://user-info

- **Name**: user-info
- **Description**: Provides information about the current user
- **MIME Type**: application/json
- **Data Structure**:
  ```json
  {
    "id": "number",
    "username": "string",
    "email": "string",
    "first_name": "string",
    "last_name": "string",
    "nickname": "string",
    "display_name": "string",
    "roles": ["string"],
    "capabilities": ["string"],
    "locale": "string",
    "registered_date": "string",
    "last_login": "string"
  }
  ```

## Site Settings

### WordPress://site-settings

- **Name**: site-settings
- **Description**: Provides access to WordPress site settings
- **MIME Type**: application/json
- **Data Structure**:
  ```json
  {
    "general": {
      "site_title": "string",
      "tagline": "string",
      "wp_address": "string",
      "site_address": "string",
      "admin_email": "string",
      "membership": "boolean",
      "default_role": "string"
    },
    "writing": {
      "default_category": "number",
      "default_post_format": "string",
      "post_via_email": "boolean",
      "mailserver_url": "string",
      "mailserver_login": "string",
      "mailserver_pass": "string",
      "default_mail_category": "number",
      "update_services": ["string"]
    },
    "reading": {
      "show_on_front": "string",
      "page_on_front": "number",
      "page_for_posts": "number",
      "posts_per_page": "number",
      "posts_per_rss": "number",
      "rss_use_excerpt": "boolean",
      "blog_public": "boolean"
    },
    "discussion": {
      "default_pingback_flag": "boolean",
      "default_ping_status": "string",
      "default_comment_status": "string",
      "require_name_email": "boolean",
      "comment_registration": "boolean",
      "close_comments_for_old_posts": "boolean",
      "close_comments_days_old": "number",
      "thread_comments": "boolean",
      "thread_comments_depth": "number",
      "page_comments": "boolean",
      "comments_per_page": "number",
      "default_comments_page": "string",
      "comment_order": "string",
      "comments_notify": "boolean",
      "moderation_notify": "boolean",
      "comment_moderation": "boolean",
      "comment_whitelist": "boolean",
      "comment_max_links": "number",
      "moderation_keys": "string",
      "blacklist_keys": "string",
      "show_avatars": "boolean",
      "avatar_rating": "string",
      "avatar_default": "string"
    },
    "media": {
      "thumbnail_size_w": "number",
      "thumbnail_size_h": "number",
      "thumbnail_crop": "boolean",
      "medium_size_w": "number",
      "medium_size_h": "number",
      "large_size_w": "number",
      "large_size_h": "number",
      "uploads_use_yearmonth_folders": "boolean"
    },
    "permalink": {
      "permalink_structure": "string",
      "category_base": "string",
      "tag_base": "string"
    }
  }
  ```
