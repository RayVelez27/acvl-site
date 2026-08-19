# Headless WordPress Architecture for ACVL Website

## Overview

This document describes how to build a **headless WordPress theme** that serves as a content management backend for the existing ACVL static HTML website. WordPress handles content storage and admin editing. The HTML frontend fetches content from the WordPress REST API and renders it in the browser.

---

## Architecture Diagram

```
┌─────────────────────────┐         ┌─────────────────────────────┐
│   WordPress Backend     │         │   Static HTML Frontend      │
│   (e.g. cms.acvl.com)   │         │   (e.g. www.acvlonline.com) │
│                         │  REST   │                             │
│  - Admin Dashboard      │◄───────►│  - index.html               │
│  - Custom Post Types    │  API    │  - mission.html             │
│  - REST API Endpoints   │  JSON   │  - board.html               │
│  - ACF / Meta Fields    │         │  - committee.html           │
│  - Theme (functions.php)│         │  - events.html              │
│  - No frontend rendering│         │  - advisors.html            │
│                         │         │  - why-sponsor.html         │
│                         │         │  - contact.html             │
│                         │         │                             │
│                         │         │  JavaScript fetches JSON    │
│                         │         │  from WP REST API and       │
│                         │         │  populates DOM elements     │
└─────────────────────────┘         └─────────────────────────────┘
```

---

## Part 1: WordPress Theme (The Zip File)

### 1.1 Theme File Structure

```
acvl-headless/
├── style.css                  # Theme metadata (required by WordPress)
├── index.php                  # Minimal template (redirects to admin)
├── functions.php              # All backend logic lives here
├── screenshot.png             # Theme thumbnail for WP admin (optional)
└── inc/
    ├── custom-post-types.php  # CPT registration
    ├── rest-api.php           # Custom REST endpoints & CORS
    ├── admin-columns.php      # Admin list table customization
    └── meta-boxes.php         # Custom field definitions
```

### 1.2 style.css (Theme Metadata)

```css
/*
Theme Name: ACVL Headless CMS
Theme URI: https://www.acvlonline.com
Description: Headless WordPress theme for ACVL. Provides REST API endpoints for the static HTML frontend. No frontend rendering.
Version: 1.0.0
Author: ACVL
Text Domain: acvl-headless
*/
```

### 1.3 index.php (Minimal Frontend)

Since this is headless, the theme's frontend should redirect to the admin dashboard or show a simple message:

```php
<?php
/**
 * Headless theme - no frontend rendering.
 * Redirects all frontend requests to the WP admin.
 */
if ( ! is_admin() && ! wp_doing_ajax() && ! defined( 'REST_REQUEST' ) ) {
    wp_redirect( admin_url() );
    exit;
}
```

### 1.4 functions.php (Main Bootstrap)

```php
<?php
/**
 * ACVL Headless Theme - functions.php
 *
 * Registers custom post types, meta boxes, REST API customizations,
 * and CORS headers for the static HTML frontend.
 */

define( 'ACVL_THEME_VERSION', '1.0.0' );

// Load modules
require_once get_template_directory() . '/inc/custom-post-types.php';
require_once get_template_directory() . '/inc/meta-boxes.php';
require_once get_template_directory() . '/inc/rest-api.php';
require_once get_template_directory() . '/inc/admin-columns.php';

/**
 * Disable the WordPress frontend theme entirely.
 * Redirect all non-API, non-admin requests to wp-admin.
 */
add_action( 'template_redirect', function () {
    if ( ! is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        wp_redirect( admin_url() );
        exit;
    }
});

/**
 * Remove unnecessary frontend features to reduce overhead.
 */
add_action( 'after_setup_theme', function () {
    // We still want featured images for potential logo uploads
    add_theme_support( 'post-thumbnails' );
});

/**
 * Clean up <head> output (not needed for headless, but good hygiene).
 */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

/**
 * Customize the admin menu order for ACVL-specific content types.
 */
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', function ( $menu_order ) {
    return array(
        'index.php',                    // Dashboard
        'separator1',
        'edit.php?post_type=acvl_event',
        'edit.php?post_type=acvl_board_member',
        'edit.php?post_type=acvl_committee',
        'edit.php?post_type=acvl_member_company',
        'edit.php?post_type=acvl_advisor',
        'edit.php?post_type=acvl_sponsor',
        'edit.php?post_type=acvl_benefit',
        'separator2',
        'edit.php?post_type=page',      // Pages (for mission statement, etc.)
    );
});

/**
 * Add an ACVL settings page for global content (mission statement, hero text, etc.)
 */
add_action( 'admin_menu', function () {
    add_menu_page(
        'ACVL Settings',
        'ACVL Settings',
        'manage_options',
        'acvl-settings',
        'acvl_render_settings_page',
        'dashicons-admin-generic',
        3
    );
    add_action( 'admin_init', 'acvl_register_settings' );
});

function acvl_register_settings() {
    // Hero Section
    register_setting( 'acvl_settings_group', 'acvl_hero_title' );
    register_setting( 'acvl_settings_group', 'acvl_hero_description' );
    register_setting( 'acvl_settings_group', 'acvl_hero_button_text' );
    register_setting( 'acvl_settings_group', 'acvl_hero_button_url' );

    // Mission Statement
    register_setting( 'acvl_settings_group', 'acvl_mission_statement' );

    // Contact Info
    register_setting( 'acvl_settings_group', 'acvl_contact_email' );
    register_setting( 'acvl_settings_group', 'acvl_contact_website' );

    // Counter Stats
    register_setting( 'acvl_settings_group', 'acvl_stat_business_solutions' );
    register_setting( 'acvl_settings_group', 'acvl_stat_members' );
    register_setting( 'acvl_settings_group', 'acvl_stat_industry_experts' );
}

function acvl_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>ACVL Site Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'acvl_settings_group' ); ?>
            <?php do_settings_sections( 'acvl_settings_group' ); ?>

            <h2>Hero Section</h2>
            <table class="form-table">
                <tr>
                    <th><label for="acvl_hero_title">Hero Title</label></th>
                    <td><input type="text" name="acvl_hero_title" value="<?php echo esc_attr( get_option( 'acvl_hero_title', 'Association of Consumer Vehicle Lessors' ) ); ?>" class="regular-text" style="width:100%;"></td>
                </tr>
                <tr>
                    <th><label for="acvl_hero_description">Hero Description</label></th>
                    <td><textarea name="acvl_hero_description" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'acvl_hero_description' ) ); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="acvl_hero_button_text">Hero Button Text</label></th>
                    <td><input type="text" name="acvl_hero_button_text" value="<?php echo esc_attr( get_option( 'acvl_hero_button_text', 'Our Mission' ) ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="acvl_hero_button_url">Hero Button URL</label></th>
                    <td><input type="url" name="acvl_hero_button_url" value="<?php echo esc_attr( get_option( 'acvl_hero_button_url', 'mission.html' ) ); ?>" class="regular-text"></td>
                </tr>
            </table>

            <h2>Mission Statement</h2>
            <table class="form-table">
                <tr>
                    <th><label for="acvl_mission_statement">Mission Statement</label></th>
                    <td><textarea name="acvl_mission_statement" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'acvl_mission_statement', 'To promote high standards in vehicle leasing and to keep members well-informed on trends, changes, and key issues in the leasing industry with high expertise and integrity.' ) ); ?></textarea></td>
                </tr>
            </table>

            <h2>Contact Information</h2>
            <table class="form-table">
                <tr>
                    <th><label for="acvl_contact_email">Contact Email</label></th>
                    <td><input type="email" name="acvl_contact_email" value="<?php echo esc_attr( get_option( 'acvl_contact_email', 'HelloACVL@gmail.com' ) ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="acvl_contact_website">Website URL</label></th>
                    <td><input type="url" name="acvl_contact_website" value="<?php echo esc_attr( get_option( 'acvl_contact_website', 'https://www.acvlonline.com' ) ); ?>" class="regular-text"></td>
                </tr>
            </table>

            <h2>Counter Stats</h2>
            <table class="form-table">
                <tr>
                    <th><label for="acvl_stat_business_solutions">Business Solutions Count</label></th>
                    <td><input type="number" name="acvl_stat_business_solutions" value="<?php echo esc_attr( get_option( 'acvl_stat_business_solutions', '1240' ) ); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="acvl_stat_members">Members Count</label></th>
                    <td><input type="number" name="acvl_stat_members" value="<?php echo esc_attr( get_option( 'acvl_stat_members', '625' ) ); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th><label for="acvl_stat_industry_experts">Industry Experts Count</label></th>
                    <td><input type="number" name="acvl_stat_industry_experts" value="<?php echo esc_attr( get_option( 'acvl_stat_industry_experts', '110' ) ); ?>" class="small-text"></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
```

---

## Part 2: Custom Post Types

### 2.1 File: inc/custom-post-types.php

Each content type from the HTML site becomes a Custom Post Type (CPT) in WordPress.

```php
<?php
/**
 * Register all ACVL Custom Post Types.
 */
add_action( 'init', 'acvl_register_post_types' );

function acvl_register_post_types() {

    // ─── 1. Board Members ───
    register_post_type( 'acvl_board_member', array(
        'labels' => array(
            'name'               => 'Board Members',
            'singular_name'      => 'Board Member',
            'add_new_item'       => 'Add New Board Member',
            'edit_item'          => 'Edit Board Member',
            'all_items'          => 'All Board Members',
            'search_items'       => 'Search Board Members',
            'not_found'          => 'No board members found',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,       // CRITICAL: Exposes to REST API
        'rest_base'    => 'board-members',
        'menu_icon'    => 'dashicons-groups',
        'supports'     => array( 'title', 'thumbnail' ),
        'has_archive'  => false,
    ));

    // ─── 2. Committee Chairs ───
    register_post_type( 'acvl_committee', array(
        'labels' => array(
            'name'               => 'Committees',
            'singular_name'      => 'Committee',
            'add_new_item'       => 'Add New Committee',
            'edit_item'          => 'Edit Committee',
            'all_items'          => 'All Committees',
            'search_items'       => 'Search Committees',
            'not_found'          => 'No committees found',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rest_base'    => 'committees',
        'menu_icon'    => 'dashicons-clipboard',
        'supports'     => array( 'title' ),
        'has_archive'  => false,
    ));

    // ─── 3. Events ───
    register_post_type( 'acvl_event', array(
        'labels' => array(
            'name'               => 'Events',
            'singular_name'      => 'Event',
            'add_new_item'       => 'Add New Event',
            'edit_item'          => 'Edit Event',
            'all_items'          => 'All Events',
            'search_items'       => 'Search Events',
            'not_found'          => 'No events found',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rest_base'    => 'events',
        'menu_icon'    => 'dashicons-calendar-alt',
        'supports'     => array( 'title', 'editor' ),
        'has_archive'  => false,
    ));

    // ─── 4. Member Companies ───
    register_post_type( 'acvl_member_company', array(
        'labels' => array(
            'name'               => 'Member Companies',
            'singular_name'      => 'Member Company',
            'add_new_item'       => 'Add New Member Company',
            'edit_item'          => 'Edit Member Company',
            'all_items'          => 'All Member Companies',
            'search_items'       => 'Search Member Companies',
            'not_found'          => 'No member companies found',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rest_base'    => 'member-companies',
        'menu_icon'    => 'dashicons-building',
        'supports'     => array( 'title', 'thumbnail' ),
        'has_archive'  => false,
    ));

    // ─── 5. Advisors ───
    register_post_type( 'acvl_advisor', array(
        'labels' => array(
            'name'               => 'Advisors',
            'singular_name'      => 'Advisor',
            'add_new_item'       => 'Add New Advisor',
            'edit_item'          => 'Edit Advisor',
            'all_items'          => 'All Advisors',
            'search_items'       => 'Search Advisors',
            'not_found'          => 'No advisors found',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rest_base'    => 'advisors',
        'menu_icon'    => 'dashicons-businessperson',
        'supports'     => array( 'title', 'thumbnail' ),
        'has_archive'  => false,
    ));

    // ─── 6. Sponsors ───
    register_post_type( 'acvl_sponsor', array(
        'labels' => array(
            'name'               => 'Sponsors',
            'singular_name'      => 'Sponsor',
            'add_new_item'       => 'Add New Sponsor',
            'edit_item'          => 'Edit Sponsor',
            'all_items'          => 'All Sponsors',
            'search_items'       => 'Search Sponsors',
            'not_found'          => 'No sponsors found',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rest_base'    => 'sponsors',
        'menu_icon'    => 'dashicons-star-filled',
        'supports'     => array( 'title', 'thumbnail' ),
        'has_archive'  => false,
    ));

    // ─── 7. Membership Benefits ───
    register_post_type( 'acvl_benefit', array(
        'labels' => array(
            'name'               => 'Benefits',
            'singular_name'      => 'Benefit',
            'add_new_item'       => 'Add New Benefit',
            'edit_item'          => 'Edit Benefit',
            'all_items'          => 'All Benefits',
            'search_items'       => 'Search Benefits',
            'not_found'          => 'No benefits found',
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_rest' => true,
        'rest_base'    => 'benefits',
        'menu_icon'    => 'dashicons-awards',
        'supports'     => array( 'title', 'editor' ),
        'has_archive'  => false,
    ));
}
```

---

## Part 3: Custom Meta Fields (Meta Boxes)

### 3.1 File: inc/meta-boxes.php

Each CPT needs custom fields. These are registered as post meta exposed to the REST API.

```php
<?php
/**
 * Register custom meta fields for each CPT.
 * All fields use 'show_in_rest' => true so the REST API exposes them.
 */
add_action( 'init', 'acvl_register_meta_fields' );

function acvl_register_meta_fields() {

    // ─── Board Member Fields ───
    $board_fields = array(
        'role'    => 'string',   // "President", "Director", "Managing Director", "Financial Director"
        'company' => 'string',   // "GM Financial", "Ally", etc.
        'order'   => 'integer',  // Display order (1 = first)
    );
    foreach ( $board_fields as $key => $type ) {
        register_post_meta( 'acvl_board_member', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ));
    }

    // ─── Committee Fields ───
    // Title = committee name (e.g. "Tax Committee")
    $committee_fields = array(
        'chair_1_name'    => 'string',  // "Marisa Hayes"
        'chair_1_title'   => 'string',  // "AVP Tax"
        'chair_1_company' => 'string',  // "GM Financial"
        'chair_2_name'    => 'string',  // Second co-chair (optional)
        'chair_2_title'   => 'string',
        'chair_2_company' => 'string',
        'icon_class'      => 'string',  // FontAwesome class e.g. "fas fa-balance-scale"
        'order'           => 'integer',
    );
    foreach ( $committee_fields as $key => $type ) {
        register_post_meta( 'acvl_committee', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ));
    }

    // ─── Event Fields ───
    // Title = event name, Editor = description
    $event_fields = array(
        'event_type'   => 'string',   // "Conference", "Webinar", "Recurring Meeting"
        'start_date'   => 'string',   // ISO date "2026-04-23"
        'end_date'     => 'string',   // ISO date "2026-04-24" (optional)
        'time'         => 'string',   // "12:00 PM - 1:30 PM EST" (optional)
        'location'     => 'string',   // "Plano, TX" or "Virtual/Online"
        'status'       => 'string',   // "Upcoming", "Recurring Monthly", etc.
        'icon_class'   => 'string',   // "fas fa-calendar-alt"
        'order'        => 'integer',
    );
    foreach ( $event_fields as $key => $type ) {
        register_post_meta( 'acvl_event', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ));
    }

    // ─── Member Company Fields ───
    // Title = company name, Thumbnail = logo
    $member_fields = array(
        'website_url' => 'string',
        'category'    => 'string',   // "Finance Company", "Bank", "Credit Union", "Leasing Company"
        'order'       => 'integer',
    );
    foreach ( $member_fields as $key => $type ) {
        register_post_meta( 'acvl_member_company', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ));
    }

    // ─── Advisor Fields ───
    // Title = org name, Thumbnail = logo
    $advisor_fields = array(
        'organization_type' => 'string',  // "Law Firm", "Consultant", etc.
        'website_url'       => 'string',
        'order'             => 'integer',
    );
    foreach ( $advisor_fields as $key => $type ) {
        register_post_meta( 'acvl_advisor', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ));
    }

    // ─── Sponsor Fields ───
    // Title = sponsor name, Thumbnail = logo
    $sponsor_fields = array(
        'tier'        => 'string',   // "Platinum", "Gold", "Silver"
        'website_url' => 'string',
        'order'       => 'integer',
    );
    foreach ( $sponsor_fields as $key => $type ) {
        register_post_meta( 'acvl_sponsor', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ));
    }

    // ─── Benefit Fields ───
    // Title = benefit title, Editor = benefit description
    $benefit_fields = array(
        'benefit_number' => 'string',   // "01", "02", "03"
        'icon_class'     => 'string',   // FontAwesome class
        'order'          => 'integer',
    );
    foreach ( $benefit_fields as $key => $type ) {
        register_post_meta( 'acvl_benefit', $key, array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'auth_callback' => '__return_true',
        ));
    }
}

/**
 * Add meta box UI to the post editor screens.
 */
add_action( 'add_meta_boxes', 'acvl_add_meta_boxes' );

function acvl_add_meta_boxes() {

    // Board Member meta box
    add_meta_box( 'acvl_board_member_fields', 'Board Member Details', 'acvl_render_board_member_meta_box', 'acvl_board_member', 'normal', 'high' );

    // Committee meta box
    add_meta_box( 'acvl_committee_fields', 'Committee Details', 'acvl_render_committee_meta_box', 'acvl_committee', 'normal', 'high' );

    // Event meta box
    add_meta_box( 'acvl_event_fields', 'Event Details', 'acvl_render_event_meta_box', 'acvl_event', 'normal', 'high' );

    // Member Company meta box
    add_meta_box( 'acvl_member_company_fields', 'Company Details', 'acvl_render_member_company_meta_box', 'acvl_member_company', 'normal', 'high' );

    // Advisor meta box
    add_meta_box( 'acvl_advisor_fields', 'Advisor Details', 'acvl_render_advisor_meta_box', 'acvl_advisor', 'normal', 'high' );

    // Sponsor meta box
    add_meta_box( 'acvl_sponsor_fields', 'Sponsor Details', 'acvl_render_sponsor_meta_box', 'acvl_sponsor', 'normal', 'high' );

    // Benefit meta box
    add_meta_box( 'acvl_benefit_fields', 'Benefit Details', 'acvl_render_benefit_meta_box', 'acvl_benefit', 'normal', 'high' );
}

/**
 * Helper: render a text input in a meta box.
 */
function acvl_meta_text_field( $post_id, $key, $label, $type = 'text' ) {
    $value = get_post_meta( $post_id, $key, true );
    printf(
        '<p><label><strong>%s</strong><br><input type="%s" name="%s" value="%s" style="width:100%%;"></label></p>',
        esc_html( $label ),
        esc_attr( $type ),
        esc_attr( $key ),
        esc_attr( $value )
    );
}

/**
 * Helper: render a select dropdown in a meta box.
 */
function acvl_meta_select_field( $post_id, $key, $label, $options ) {
    $value = get_post_meta( $post_id, $key, true );
    printf( '<p><label><strong>%s</strong><br><select name="%s" style="width:100%%;">', esc_html( $label ), esc_attr( $key ) );
    printf( '<option value="">— Select —</option>' );
    foreach ( $options as $opt ) {
        printf( '<option value="%s" %s>%s</option>', esc_attr( $opt ), selected( $value, $opt, false ), esc_html( $opt ) );
    }
    echo '</select></label></p>';
}

// ─── Render functions for each meta box ───

function acvl_render_board_member_meta_box( $post ) {
    wp_nonce_field( 'acvl_save_meta', 'acvl_meta_nonce' );
    acvl_meta_select_field( $post->ID, 'role', 'Role', array( 'President', 'Director', 'Managing Director', 'Financial Director' ) );
    acvl_meta_text_field( $post->ID, 'company', 'Company' );
    acvl_meta_text_field( $post->ID, 'order', 'Display Order', 'number' );
}

function acvl_render_committee_meta_box( $post ) {
    wp_nonce_field( 'acvl_save_meta', 'acvl_meta_nonce' );
    echo '<h4>Chair 1</h4>';
    acvl_meta_text_field( $post->ID, 'chair_1_name', 'Name' );
    acvl_meta_text_field( $post->ID, 'chair_1_title', 'Job Title' );
    acvl_meta_text_field( $post->ID, 'chair_1_company', 'Company' );
    echo '<h4>Chair 2 (Co-Chair, optional)</h4>';
    acvl_meta_text_field( $post->ID, 'chair_2_name', 'Name' );
    acvl_meta_text_field( $post->ID, 'chair_2_title', 'Job Title' );
    acvl_meta_text_field( $post->ID, 'chair_2_company', 'Company' );
    echo '<hr>';
    acvl_meta_text_field( $post->ID, 'icon_class', 'Icon Class (e.g. fas fa-balance-scale)' );
    acvl_meta_text_field( $post->ID, 'order', 'Display Order', 'number' );
}

function acvl_render_event_meta_box( $post ) {
    wp_nonce_field( 'acvl_save_meta', 'acvl_meta_nonce' );
    acvl_meta_select_field( $post->ID, 'event_type', 'Event Type', array( 'Conference', 'Webinar', 'Recurring Meeting' ) );
    acvl_meta_text_field( $post->ID, 'start_date', 'Start Date', 'date' );
    acvl_meta_text_field( $post->ID, 'end_date', 'End Date (optional)', 'date' );
    acvl_meta_text_field( $post->ID, 'time', 'Time (e.g. 12:00 PM - 1:30 PM EST)' );
    acvl_meta_text_field( $post->ID, 'location', 'Location' );
    acvl_meta_select_field( $post->ID, 'status', 'Status', array( 'Upcoming', 'Recurring Monthly', 'Completed', 'Cancelled' ) );
    acvl_meta_text_field( $post->ID, 'icon_class', 'Icon Class (e.g. fas fa-calendar-alt)' );
    acvl_meta_text_field( $post->ID, 'order', 'Display Order', 'number' );
}

function acvl_render_member_company_meta_box( $post ) {
    wp_nonce_field( 'acvl_save_meta', 'acvl_meta_nonce' );
    acvl_meta_select_field( $post->ID, 'category', 'Category', array( 'Finance Company', 'Bank', 'Credit Union', 'Leasing Company' ) );
    acvl_meta_text_field( $post->ID, 'website_url', 'Website URL', 'url' );
    acvl_meta_text_field( $post->ID, 'order', 'Display Order', 'number' );
}

function acvl_render_advisor_meta_box( $post ) {
    wp_nonce_field( 'acvl_save_meta', 'acvl_meta_nonce' );
    acvl_meta_select_field( $post->ID, 'organization_type', 'Organization Type', array( 'Law Firm', 'Consultant', 'Financial Services', 'Technology', 'Other' ) );
    acvl_meta_text_field( $post->ID, 'website_url', 'Website URL', 'url' );
    acvl_meta_text_field( $post->ID, 'order', 'Display Order', 'number' );
}

function acvl_render_sponsor_meta_box( $post ) {
    wp_nonce_field( 'acvl_save_meta', 'acvl_meta_nonce' );
    acvl_meta_select_field( $post->ID, 'tier', 'Sponsor Tier', array( 'Platinum', 'Gold', 'Silver' ) );
    acvl_meta_text_field( $post->ID, 'website_url', 'Website URL', 'url' );
    acvl_meta_text_field( $post->ID, 'order', 'Display Order', 'number' );
}

function acvl_render_benefit_meta_box( $post ) {
    wp_nonce_field( 'acvl_save_meta', 'acvl_meta_nonce' );
    acvl_meta_text_field( $post->ID, 'benefit_number', 'Benefit Number (e.g. 01, 02, 03)' );
    acvl_meta_text_field( $post->ID, 'icon_class', 'Icon Class (e.g. fas fa-handshake)' );
    acvl_meta_text_field( $post->ID, 'order', 'Display Order', 'number' );
}

/**
 * Save all custom meta fields on post save.
 */
add_action( 'save_post', 'acvl_save_meta_fields' );

function acvl_save_meta_fields( $post_id ) {
    if ( ! isset( $_POST['acvl_meta_nonce'] ) || ! wp_verify_nonce( $_POST['acvl_meta_nonce'], 'acvl_save_meta' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // All possible meta keys across all CPTs
    $all_keys = array(
        'role', 'company', 'order',
        'chair_1_name', 'chair_1_title', 'chair_1_company',
        'chair_2_name', 'chair_2_title', 'chair_2_company',
        'icon_class',
        'event_type', 'start_date', 'end_date', 'time', 'location', 'status',
        'website_url', 'category',
        'organization_type',
        'tier',
        'benefit_number',
    );

    foreach ( $all_keys as $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
        }
    }
}
```

---

## Part 4: REST API & CORS Configuration

### 4.1 File: inc/rest-api.php

```php
<?php
/**
 * REST API customizations and CORS headers.
 */

/**
 * CORS: Allow the static HTML frontend to call the WordPress REST API.
 *
 * IMPORTANT: Replace 'https://www.acvlonline.com' with the actual frontend domain.
 * During development, you can use '*' but this should be locked down in production.
 */
add_action( 'rest_api_init', function () {

    // Remove default CORS and add our own
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

    add_filter( 'rest_pre_serve_request', function ( $value ) {
        $allowed_origins = array(
            'https://www.acvlonline.com',
            'http://localhost',            // Development
            'http://127.0.0.1',            // Development
            'null',                        // Local file:// protocol
        );

        $origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';

        if ( in_array( $origin, $allowed_origins, true ) || $origin === '' ) {
            $allow = $origin ?: '*';
            header( 'Access-Control-Allow-Origin: ' . $allow );
            header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
            header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
            header( 'Access-Control-Allow-Credentials: true' );
        }

        return $value;
    });
});

/**
 * Register a custom combined endpoint that returns ALL site data in one request.
 * This reduces the number of HTTP calls the frontend needs to make.
 *
 * Endpoint: GET /wp-json/acvl/v1/site-data
 *
 * Returns JSON:
 * {
 *   "settings": { hero_title, hero_description, mission_statement, contact_email, ... },
 *   "board_members": [ { id, name, role, company, image_url, order }, ... ],
 *   "committees": [ { id, name, chair_1_name, ..., chair_2_name, ..., icon_class, order }, ... ],
 *   "events": [ { id, title, description, event_type, start_date, end_date, time, location, status, order }, ... ],
 *   "member_companies": [ { id, name, logo_url, category, website_url, order }, ... ],
 *   "advisors": [ { id, name, logo_url, organization_type, website_url, order }, ... ],
 *   "sponsors": [ { id, name, logo_url, tier, website_url, order }, ... ],
 *   "benefits": [ { id, title, description, benefit_number, icon_class, order }, ... ]
 * }
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'acvl/v1', '/site-data', array(
        'methods'             => 'GET',
        'callback'            => 'acvl_get_site_data',
        'permission_callback' => '__return_true',  // Public endpoint
    ));

    // Individual endpoints for partial updates
    register_rest_route( 'acvl/v1', '/settings', array(
        'methods'             => 'GET',
        'callback'            => 'acvl_get_settings',
        'permission_callback' => '__return_true',
    ));
});

/**
 * Return all ACVL settings (options table).
 */
function acvl_get_settings() {
    return new WP_REST_Response( array(
        'hero_title'              => get_option( 'acvl_hero_title', 'Association of Consumer Vehicle Lessors' ),
        'hero_description'        => get_option( 'acvl_hero_description', '' ),
        'hero_button_text'        => get_option( 'acvl_hero_button_text', 'Our Mission' ),
        'hero_button_url'         => get_option( 'acvl_hero_button_url', 'mission.html' ),
        'mission_statement'       => get_option( 'acvl_mission_statement', '' ),
        'contact_email'           => get_option( 'acvl_contact_email', 'HelloACVL@gmail.com' ),
        'contact_website'         => get_option( 'acvl_contact_website', 'https://www.acvlonline.com' ),
        'stat_business_solutions' => get_option( 'acvl_stat_business_solutions', '1240' ),
        'stat_members'            => get_option( 'acvl_stat_members', '625' ),
        'stat_industry_experts'   => get_option( 'acvl_stat_industry_experts', '110' ),
    ), 200 );
}

/**
 * Return ALL site data in a single API call.
 */
function acvl_get_site_data() {
    return new WP_REST_Response( array(
        'settings'         => acvl_get_settings()->get_data(),
        'board_members'    => acvl_get_cpt_data( 'acvl_board_member', array( 'role', 'company', 'order' ) ),
        'committees'       => acvl_get_cpt_data( 'acvl_committee', array( 'chair_1_name', 'chair_1_title', 'chair_1_company', 'chair_2_name', 'chair_2_title', 'chair_2_company', 'icon_class', 'order' ) ),
        'events'           => acvl_get_cpt_data( 'acvl_event', array( 'event_type', 'start_date', 'end_date', 'time', 'location', 'status', 'icon_class', 'order' ), true ),
        'member_companies' => acvl_get_cpt_data( 'acvl_member_company', array( 'website_url', 'category', 'order' ) ),
        'advisors'         => acvl_get_cpt_data( 'acvl_advisor', array( 'organization_type', 'website_url', 'order' ) ),
        'sponsors'         => acvl_get_cpt_data( 'acvl_sponsor', array( 'tier', 'website_url', 'order' ) ),
        'benefits'         => acvl_get_cpt_data( 'acvl_benefit', array( 'benefit_number', 'icon_class', 'order' ), true ),
    ), 200 );
}

/**
 * Helper: query a CPT and return formatted array with meta fields.
 *
 * @param string $post_type      The CPT slug.
 * @param array  $meta_keys      Meta keys to include.
 * @param bool   $include_content Whether to include post_content as 'description'.
 * @return array
 */
function acvl_get_cpt_data( $post_type, $meta_keys, $include_content = false ) {
    $posts = get_posts( array(
        'post_type'      => $post_type,
        'posts_per_page' => 100,
        'post_status'    => 'publish',
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'order',
        'order'          => 'ASC',
    ));

    $results = array();
    foreach ( $posts as $post ) {
        $item = array(
            'id'   => $post->ID,
            'name' => $post->post_title,
        );

        if ( $include_content ) {
            $item['description'] = apply_filters( 'the_content', $post->post_content );
        }

        // Include featured image URL if set
        $thumb_id = get_post_thumbnail_id( $post->ID );
        if ( $thumb_id ) {
            $item['image_url'] = wp_get_attachment_image_url( $thumb_id, 'medium' );
        }

        // Include all requested meta fields
        foreach ( $meta_keys as $key ) {
            $item[ $key ] = get_post_meta( $post->ID, $key, true );
        }

        $results[] = $item;
    }

    return $results;
}
```

---

## Part 5: Admin Column Customization

### 5.1 File: inc/admin-columns.php

```php
<?php
/**
 * Customize admin list table columns for better usability.
 */

// Board Members columns
add_filter( 'manage_acvl_board_member_posts_columns', function ( $columns ) {
    return array(
        'cb'      => $columns['cb'],
        'title'   => 'Name',
        'role'    => 'Role',
        'company' => 'Company',
        'order'   => 'Order',
        'date'    => 'Date',
    );
});

add_action( 'manage_acvl_board_member_posts_custom_column', function ( $column, $post_id ) {
    echo esc_html( get_post_meta( $post_id, $column, true ) );
}, 10, 2 );

// Events columns
add_filter( 'manage_acvl_event_posts_columns', function ( $columns ) {
    return array(
        'cb'         => $columns['cb'],
        'title'      => 'Event Name',
        'event_type' => 'Type',
        'start_date' => 'Start Date',
        'location'   => 'Location',
        'status'     => 'Status',
        'date'       => 'Date',
    );
});

add_action( 'manage_acvl_event_posts_custom_column', function ( $column, $post_id ) {
    echo esc_html( get_post_meta( $post_id, $column, true ) );
}, 10, 2 );

// Sponsors columns
add_filter( 'manage_acvl_sponsor_posts_columns', function ( $columns ) {
    return array(
        'cb'    => $columns['cb'],
        'title' => 'Sponsor Name',
        'tier'  => 'Tier',
        'order' => 'Order',
        'date'  => 'Date',
    );
});

add_action( 'manage_acvl_sponsor_posts_custom_column', function ( $column, $post_id ) {
    echo esc_html( get_post_meta( $post_id, $column, true ) );
}, 10, 2 );
```

---

## Part 6: Frontend JavaScript Integration

### 6.1 How the HTML Pages Consume the API

Add a single configuration script and a data-fetching module to the HTML site. This goes in every HTML page that needs dynamic content.

**Configuration (add before closing `</body>` tag in each HTML file):**

```html
<script>
/**
 * ACVL API Configuration
 * Change ACVL_API_BASE to your WordPress installation URL.
 */
const ACVL_API_BASE = 'https://cms.acvlonline.com/wp-json';
</script>
<script src="assets/js/acvl-api.js"></script>
```

### 6.2 File: assets/js/acvl-api.js (New file for the HTML frontend)

```javascript
/**
 * ACVL Frontend API Client
 *
 * Fetches content from the headless WordPress backend and populates
 * the static HTML pages with dynamic data.
 *
 * Dependencies: None (vanilla JS). jQuery optional for DOM manipulation.
 *
 * Usage:
 *   Each HTML page calls the specific render function it needs.
 *   The API client caches the full site-data response in sessionStorage
 *   so subsequent page loads within the same session are instant.
 */

const ACVL = {

    /**
     * Fetch all site data from the combined endpoint.
     * Caches in sessionStorage for the browser session.
     * @returns {Promise<Object>} The full site-data JSON object.
     */
    async fetchSiteData() {
        const cacheKey = 'acvl_site_data';
        const cacheTime = 'acvl_site_data_time';
        const maxAge = 5 * 60 * 1000; // 5 minutes

        const cached = sessionStorage.getItem(cacheKey);
        const cachedAt = sessionStorage.getItem(cacheTime);

        if (cached && cachedAt && (Date.now() - parseInt(cachedAt)) < maxAge) {
            return JSON.parse(cached);
        }

        try {
            const response = await fetch(`${ACVL_API_BASE}/acvl/v1/site-data`);
            if (!response.ok) throw new Error(`API error: ${response.status}`);
            const data = await response.json();
            sessionStorage.setItem(cacheKey, JSON.stringify(data));
            sessionStorage.setItem(cacheTime, Date.now().toString());
            return data;
        } catch (error) {
            console.error('ACVL API fetch failed:', error);
            // Return null so pages can fall back to hardcoded content
            return null;
        }
    },

    /**
     * Populate the hero section on index.html.
     */
    async renderHero() {
        const data = await this.fetchSiteData();
        if (!data) return;
        const s = data.settings;

        const titleEl = document.querySelector('.banner-one-inner .title');
        const discEl = document.querySelector('.banner-one-inner .disc');
        const btnEl = document.querySelector('.banner-one-inner .rts-btn');

        if (titleEl && s.hero_title) titleEl.innerHTML = s.hero_title;
        if (discEl && s.hero_description) discEl.innerHTML = s.hero_description;
        if (btnEl && s.hero_button_text) {
            btnEl.textContent = s.hero_button_text;
            btnEl.href = s.hero_button_url || 'mission.html';
        }
    },

    /**
     * Populate the members carousel on index.html.
     */
    async renderMemberCarousel() {
        const data = await this.fetchSiteData();
        if (!data || !data.member_companies.length) return;

        const wrapper = document.querySelector('.mySwiper-members-carousel .swiper-wrapper');
        if (!wrapper) return;

        wrapper.innerHTML = data.member_companies.map(company => `
            <div class="swiper-slide">
                <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px 15px; text-align: center; min-height: 70px; display: flex; align-items: center; justify-content: center;">
                    ${company.image_url
                        ? `<img src="${company.image_url}" alt="${company.name}" style="max-height: 50px; max-width: 100%;">`
                        : `<span style="font-weight: 600; color: #333; font-size: 14px;">${company.name}</span>`
                    }
                </div>
            </div>
        `).join('');

        // Re-initialize Swiper after DOM update
        new Swiper(".mySwiper-members-carousel", {
            slidesPerView: 5,
            spaceBetween: 20,
            loop: true,
            autoplay: { delay: 2500, disableOnInteraction: false },
            breakpoints: {
                1200: { slidesPerView: 5 },
                992: { slidesPerView: 4 },
                768: { slidesPerView: 3 },
                480: { slidesPerView: 2 },
                0: { slidesPerView: 2 },
            },
        });
    },

    /**
     * Populate the events section on index.html or events.html.
     */
    async renderEvents(containerSelector) {
        const data = await this.fetchSiteData();
        if (!data || !data.events.length) return;

        const container = document.querySelector(containerSelector);
        if (!container) return;

        container.innerHTML = data.events.map(event => `
            <div class="col-lg-6 col-md-6 mb--30">
                <div style="background: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 5px 30px rgba(0,0,0,0.08); height: 100%; border-left: 4px solid var(--color-primary);">
                    <div style="margin-bottom: 15px;">
                        <i class="${event.icon_class || 'fas fa-calendar-alt'}" style="font-size: 30px; color: var(--color-primary);"></i>
                    </div>
                    <h4 style="margin-bottom: 10px;">${event.name}</h4>
                    ${event.start_date ? `<p style="color: var(--color-primary); font-weight: 600;">${event.start_date}${event.end_date ? ' - ' + event.end_date : ''}</p>` : ''}
                    ${event.location ? `<p><i class="fas fa-map-marker-alt"></i> ${event.location}</p>` : ''}
                    ${event.time ? `<p><i class="fas fa-clock"></i> ${event.time}</p>` : ''}
                    ${event.description || ''}
                </div>
            </div>
        `).join('');
    },

    /**
     * Populate the board members on board.html.
     */
    async renderBoardMembers(containerSelector) {
        const data = await this.fetchSiteData();
        if (!data || !data.board_members.length) return;

        const container = document.querySelector(containerSelector);
        if (!container) return;

        const president = data.board_members.find(m => m.role === 'President');
        const directors = data.board_members.filter(m => m.role === 'Director');
        const leadership = data.board_members.filter(m => m.role === 'Managing Director' || m.role === 'Financial Director');

        let html = '';

        // President card
        if (president) {
            html += `
            <div class="col-lg-12 mb--40">
                <div style="background: linear-gradient(135deg, #010066 0%, #0b4df5 100%); padding: 40px; border-radius: 20px; text-align: center;">
                    <h4 style="color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px;">President</h4>
                    <h3 style="color: #fff; font-size: 28px;">${president.name}</h3>
                    <p style="color: rgba(255,255,255,0.8); font-size: 18px;">${president.company}</p>
                </div>
            </div>`;
        }

        // Directors
        if (directors.length) {
            html += `<div class="col-lg-12 mb--20"><h3 style="text-align:center;">Directors</h3></div>`;
            directors.forEach(d => {
                html += `
                <div class="col-lg-3 col-md-6 mb--30">
                    <div style="background: #f8f9fa; padding: 30px; border-radius: 15px; text-align: center; height: 100%;">
                        <i class="fas fa-user-tie" style="font-size: 40px; color: var(--color-primary); margin-bottom: 15px;"></i>
                        <h5>${d.name}</h5>
                        <p style="color: var(--color-primary); font-weight: 600;">${d.company}</p>
                    </div>
                </div>`;
            });
        }

        // Leadership
        if (leadership.length) {
            html += `<div class="col-lg-12 mb--20 mt--30"><h3 style="text-align:center;">ACVL Leadership</h3></div>`;
            leadership.forEach(l => {
                html += `
                <div class="col-lg-6 col-md-6 mb--30">
                    <div style="background: #f8f9fa; padding: 30px; border-radius: 15px; text-align: center; height: 100%;">
                        <h5>${l.name}</h5>
                        <p style="color: var(--color-primary);">${l.role}</p>
                        <p>${l.company}</p>
                    </div>
                </div>`;
            });
        }

        container.innerHTML = html;
    },

    /**
     * Populate committees on committee.html.
     */
    async renderCommittees(containerSelector) {
        const data = await this.fetchSiteData();
        if (!data || !data.committees.length) return;

        const container = document.querySelector(containerSelector);
        if (!container) return;

        container.innerHTML = data.committees.map(c => `
            <div class="col-lg-6 col-md-6 mb--30">
                <div style="background: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 5px 30px rgba(0,0,0,0.08); height: 100%;">
                    <div style="margin-bottom: 15px;">
                        <i class="${c.icon_class || 'fas fa-users'}" style="font-size: 30px; color: var(--color-primary);"></i>
                    </div>
                    <h4 style="margin-bottom: 20px;">${c.name}</h4>
                    <div style="margin-bottom: 10px;">
                        <strong>${c.chair_1_name}</strong><br>
                        <span style="color: #666;">${c.chair_1_title}, ${c.chair_1_company}</span>
                    </div>
                    ${c.chair_2_name ? `
                    <div>
                        <strong>${c.chair_2_name}</strong><br>
                        <span style="color: #666;">${c.chair_2_title}, ${c.chair_2_company}</span>
                    </div>` : ''}
                </div>
            </div>
        `).join('');
    },

    /**
     * Populate advisors on advisors.html.
     */
    async renderAdvisors(containerSelector) {
        const data = await this.fetchSiteData();
        if (!data || !data.advisors.length) return;

        const container = document.querySelector(containerSelector);
        if (!container) return;

        container.innerHTML = data.advisors.map(a => `
            <div class="col-lg-3 col-md-4 col-sm-6 mb--30">
                <div style="background: #fff; border-radius: 15px; padding: 30px; text-align: center; box-shadow: 0 5px 30px rgba(0,0,0,0.08); height: 100%;">
                    ${a.image_url
                        ? `<img src="${a.image_url}" alt="${a.name}" style="max-height: 60px; margin-bottom: 15px;">`
                        : `<i class="fas fa-building" style="font-size: 40px; color: var(--color-primary); margin-bottom: 15px;"></i>`
                    }
                    <h5>${a.name}</h5>
                    ${a.organization_type ? `<p style="color: #666; font-size: 14px;">${a.organization_type}</p>` : ''}
                </div>
            </div>
        `).join('');
    },

    /**
     * Populate sponsors on index.html.
     */
    async renderSponsors(containerSelector) {
        const data = await this.fetchSiteData();
        if (!data || !data.sponsors.length) return;

        const container = document.querySelector(containerSelector);
        if (!container) return;

        // Group by tier
        const grouped = { Platinum: [], Gold: [], Silver: [] };
        data.sponsors.forEach(s => {
            if (grouped[s.tier]) grouped[s.tier].push(s);
        });

        let html = '';
        for (const [tier, sponsors] of Object.entries(grouped)) {
            if (!sponsors.length) continue;
            html += `<div class="col-lg-12 mb--20"><h4 style="text-align:center;">${tier} Sponsors</h4></div>`;
            sponsors.forEach(s => {
                html += `
                <div class="col-lg-4 col-md-6 mb--30">
                    <div style="background: #fff; border-radius: 15px; padding: 30px; text-align: center; box-shadow: 0 5px 30px rgba(0,0,0,0.08);">
                        ${s.image_url
                            ? `<img src="${s.image_url}" alt="${s.name}" style="max-height: 60px; margin-bottom: 15px;">`
                            : `<h5>${s.name}</h5>`
                        }
                    </div>
                </div>`;
            });
        }
        container.innerHTML = html;
    },

    /**
     * Populate the mission statement on mission.html.
     */
    async renderMission() {
        const data = await this.fetchSiteData();
        if (!data) return;

        const missionEl = document.querySelector('.mission-box p');
        if (missionEl && data.settings.mission_statement) {
            missionEl.textContent = data.settings.mission_statement;
        }
    },

    /**
     * Populate counter stats on index.html.
     */
    async renderStats() {
        const data = await this.fetchSiteData();
        if (!data) return;
        const s = data.settings;

        // These target the odometer elements by their data or class
        // Implementation depends on the specific HTML structure
        const counters = document.querySelectorAll('.odometer');
        if (counters.length >= 3) {
            counters[0].setAttribute('data-count', s.stat_business_solutions);
            counters[1].setAttribute('data-count', s.stat_members);
            counters[2].setAttribute('data-count', s.stat_industry_experts);
        }
    },
};
```

### 6.3 Page-Specific Initialization

Each HTML page adds a small script block to call the render functions it needs:

**index.html:**
```html
<script>
document.addEventListener('DOMContentLoaded', () => {
    ACVL.renderHero();
    ACVL.renderMemberCarousel();
    ACVL.renderEvents('#events-container');
    ACVL.renderSponsors('#sponsors-container');
    ACVL.renderStats();
});
</script>
```

**board.html:**
```html
<script>
document.addEventListener('DOMContentLoaded', () => {
    ACVL.renderBoardMembers('#board-container');
});
</script>
```

**committee.html:**
```html
<script>
document.addEventListener('DOMContentLoaded', () => {
    ACVL.renderCommittees('#committees-container');
});
</script>
```

**events.html:**
```html
<script>
document.addEventListener('DOMContentLoaded', () => {
    ACVL.renderEvents('#events-container');
});
</script>
```

**advisors.html:**
```html
<script>
document.addEventListener('DOMContentLoaded', () => {
    ACVL.renderAdvisors('#advisors-container');
});
</script>
```

**mission.html:**
```html
<script>
document.addEventListener('DOMContentLoaded', () => {
    ACVL.renderMission();
});
</script>
```

---

## Part 7: REST API Endpoint Reference

All endpoints are public (read-only, no authentication required).

### Combined Endpoint (preferred)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/wp-json/acvl/v1/site-data` | Returns ALL content types and settings in one response |
| `GET` | `/wp-json/acvl/v1/settings` | Returns only the global settings |

### Standard WordPress REST Endpoints (auto-generated by `show_in_rest`)
| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/wp-json/wp/v2/board-members` | List all board members |
| `GET` | `/wp-json/wp/v2/board-members/{id}` | Single board member |
| `GET` | `/wp-json/wp/v2/committees` | List all committees |
| `GET` | `/wp-json/wp/v2/events` | List all events |
| `GET` | `/wp-json/wp/v2/member-companies` | List all member companies |
| `GET` | `/wp-json/wp/v2/advisors` | List all advisors |
| `GET` | `/wp-json/wp/v2/sponsors` | List all sponsors |
| `GET` | `/wp-json/wp/v2/benefits` | List all benefits |

### Example API Response (GET /wp-json/acvl/v1/site-data)

```json
{
  "settings": {
    "hero_title": "Association of Consumer Vehicle Lessors",
    "hero_description": "The Association of Consumer Vehicle Lessors is the national association...",
    "hero_button_text": "Our Mission",
    "hero_button_url": "mission.html",
    "mission_statement": "To promote high standards in vehicle leasing...",
    "contact_email": "HelloACVL@gmail.com",
    "contact_website": "https://www.acvlonline.com",
    "stat_business_solutions": "1240",
    "stat_members": "625",
    "stat_industry_experts": "110"
  },
  "board_members": [
    {
      "id": 12,
      "name": "Marisa Hayes",
      "role": "President",
      "company": "GM Financial",
      "image_url": null,
      "order": "1"
    },
    {
      "id": 13,
      "name": "Aaron Reynolds",
      "role": "Director",
      "company": "Ally",
      "image_url": null,
      "order": "2"
    }
  ],
  "committees": [
    {
      "id": 20,
      "name": "Tax Committee",
      "chair_1_name": "Marisa Hayes",
      "chair_1_title": "AVP Tax",
      "chair_1_company": "GM Financial",
      "chair_2_name": "Debbie Piearce",
      "chair_2_title": "Senior Tax Analyst",
      "chair_2_company": "Nissan North America",
      "icon_class": "fas fa-file-invoice-dollar",
      "order": "1"
    }
  ],
  "events": [
    {
      "id": 30,
      "name": "Tax Conference",
      "description": "<p>Annual tax conference for ACVL members.</p>",
      "event_type": "Conference",
      "start_date": "2026-04-23",
      "end_date": "2026-04-24",
      "time": "",
      "location": "Plano, TX",
      "status": "Upcoming",
      "icon_class": "fas fa-landmark",
      "order": "2"
    }
  ],
  "member_companies": [
    {
      "id": 40,
      "name": "Ally",
      "image_url": null,
      "category": "Finance Company",
      "website_url": "",
      "order": "1"
    }
  ],
  "advisors": [],
  "sponsors": [],
  "benefits": []
}
```

---

## Part 8: Deployment Instructions

### 8.1 WordPress Backend Setup

1. **Install WordPress** on a subdomain (e.g., `cms.acvlonline.com`)
2. **Upload theme**: Go to Appearance > Themes > Add New > Upload Theme > select `acvl-headless.zip`
3. **Activate the theme**
4. **Populate content**: Go to each CPT in the admin sidebar and add entries matching the current hardcoded data
5. **Configure settings**: Go to ACVL Settings and fill in the hero text, mission statement, contact info, and counter stats
6. **Verify API**: Visit `https://cms.acvlonline.com/wp-json/acvl/v1/site-data` in a browser — you should see JSON output

### 8.2 HTML Frontend Setup

1. **Set the API base URL**: In each HTML file (or a shared config), set `ACVL_API_BASE` to your WordPress URL:
   ```javascript
   const ACVL_API_BASE = 'https://cms.acvlonline.com/wp-json';
   ```
2. **Add container IDs**: Add `id` attributes to the HTML elements that will be populated dynamically:
   - `#events-container` on the events row in index.html and events.html
   - `#board-container` on the board members row in board.html
   - `#committees-container` on the committees row in committee.html
   - `#advisors-container` on the advisors grid in advisors.html
   - `#sponsors-container` on the sponsors row in index.html
3. **Include the scripts**: Add `acvl-api.js` and the page-specific init script to each HTML file
4. **Deploy** the HTML site to any static host (Netlify, Vercel, S3, shared hosting, etc.)

### 8.3 CORS Checklist

- The WordPress theme's `inc/rest-api.php` must include the HTML frontend's domain in the `$allowed_origins` array
- If using `file://` protocol locally for testing, `'null'` is already included as an allowed origin
- Test with browser DevTools Network tab — look for `Access-Control-Allow-Origin` header on API responses

---

## Part 9: Content Type to Page Mapping

This maps which WordPress content type populates which section on which HTML page:

| WordPress Content | HTML Page | Section |
|---|---|---|
| ACVL Settings > Hero | `index.html` | Hero banner title, description, button |
| ACVL Settings > Mission | `mission.html` | Mission statement blue box |
| ACVL Settings > Contact | `contact.html`, footer | Email, website |
| ACVL Settings > Stats | `index.html` | Odometer counters |
| Board Members CPT | `board.html` | President card, directors grid, leadership |
| Committees CPT | `committee.html` | Committee cards with chair details |
| Events CPT | `index.html`, `events.html` | Event cards |
| Member Companies CPT | `index.html` | Members carousel |
| Advisors CPT | `advisors.html` | Advisor organization grid |
| Sponsors CPT | `index.html` | Sponsors section (grouped by tier) |
| Benefits CPT | `index.html` | Membership benefits section |

---

## Part 10: Current Hardcoded Data to Seed into WordPress

When first setting up WordPress, create posts for each of these. This is the complete current content from the HTML files:

### Board Members (6 total)
| Name | Role | Company | Order |
|---|---|---|---|
| Marisa Hayes | President | GM Financial | 1 |
| Aaron Reynolds | Director | Ally | 2 |
| Rene Abdalah-Herrera | Director | Stellantis Financial Services | 3 |
| Joe Dyes | Director | US Bank | 4 |
| Debbie Piearce | Director | Nissan North America | 5 |
| Tammy Ternullo | Managing Director | ACVL | 6 |
| Joe Ternullo | Financial Director | ACVL | 7 |

### Committees (4 total)
| Committee | Chair 1 | Chair 1 Title | Chair 1 Company | Chair 2 | Chair 2 Title | Chair 2 Company |
|---|---|---|---|---|---|---|
| Tax Committee | Marisa Hayes | AVP Tax | GM Financial | Debbie Piearce | Senior Tax Analyst | Nissan North America |
| Operations Committee | Aaron Reynolds | Manager | Ally | — | — | — |
| Residual Committee | Rene Abdalah-Herrera | Managing Director | Stellantis Financial Services | Jeffery Keys | SVP Risk Management | US Bank |
| Legal Committee | Irma Leon-Gonzalez | Assistant General Counsel | Porsche Financial Services | Will Doby | Managing Counsel | Toyota Financial Services |

### Events (4 total)
| Title | Type | Start | End | Location | Time | Status |
|---|---|---|---|---|---|---|
| Monthly Committee Meetings | Recurring Meeting | — | — | — | — | Recurring Monthly |
| Tax Conference | Conference | 2026-04-23 | 2026-04-24 | Plano, TX | — | Upcoming |
| Residual & Operations Webinar | Webinar | 2026-05-14 | — | Virtual/Online | 12:00 PM - 1:30 PM EST | Upcoming |
| Annual Conference | Conference | 2026-10-11 | 2026-10-13 | Asheville, NC | — | Upcoming |

### Member Companies (15 total)
Ally, American Honda Finance, Bank of America, BMW Financial Services, Credit Union Leasing of America, GM Financial, Hyundai Capital America, Mercedes-Benz Financial Services, Nissan North America, Porsche Financial Services, Southeast Toyota Finance, Stellantis Financial Services, Toyota Financial Services, US Bank, Volvo Car Financial Services US LLC

### Advisors (8 total)
Eversheds Sutherland, Husch Blackwell LLP, Morgan Lewis & Bockius LLP, Nisen Elliott LLC, Recurrent, Tax Lease Consultants, Troutman Pepper Locke LLP, Womble Bond Dickinson (US) LLP

### Settings
| Key | Value |
|---|---|
| Hero Title | Association of Consumer Vehicle Lessors |
| Hero Description | The Association of Consumer Vehicle Lessors is the national association representing members that are the nation's largest consumer vehicle lessors, including captive and non-captive finance companies, banks, credit unions and leasing companies. Our members currently originate most of the consumer vehicle leases in the country. |
| Hero Button Text | Our Mission |
| Hero Button URL | mission.html |
| Mission Statement | To promote high standards in vehicle leasing and to keep members well-informed on trends, changes, and key issues in the leasing industry with high expertise and integrity. |
| Contact Email | HelloACVL@gmail.com |
| Contact Website | https://www.acvlonline.com |
| Stat Business Solutions | 1240 |
| Stat Members | 625 |
| Stat Industry Experts | 110 |

---

## Summary

**WordPress side**: A zip-installable theme with 7 Custom Post Types, custom meta fields with admin UI, a combined REST endpoint (`/acvl/v1/site-data`), CORS headers, and a settings page for global content. No frontend rendering — all requests redirect to wp-admin.

**HTML side**: A single `acvl-api.js` file with an `ACVL` object that fetches from the WordPress REST API, caches responses in sessionStorage, and provides per-section render functions. Each HTML page calls the render functions it needs on `DOMContentLoaded`. The existing hardcoded HTML serves as a fallback if the API is unreachable.

**Workflow**: Content editors log into WordPress, update a board member or add an event, and the static HTML site reflects the change on the next page load (within the 5-minute cache window).
