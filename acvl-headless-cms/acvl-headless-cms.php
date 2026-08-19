<?php
/**
 * Plugin Name: ACVL Headless CMS
 * Plugin URI:  https://acvl.org
 * Description: Headless CMS engine for the ACVL static HTML frontend. Manages members, sponsors, board, committees, events, advisors, and site-wide settings via the WordPress REST API.
 * Version:     1.0.0
 * Author:      ACVL
 * License:     GPL-2.0+
 * Text Domain: acvl-headless
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ACVL_VERSION', '1.0.0' );
define( 'ACVL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ACVL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ACVL_PLUGIN_DIR . 'includes/class-post-types.php';
require_once ACVL_PLUGIN_DIR . 'includes/class-meta-boxes.php';
require_once ACVL_PLUGIN_DIR . 'includes/class-settings.php';
require_once ACVL_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once ACVL_PLUGIN_DIR . 'includes/class-cors.php';

// Boot everything up.
add_action( 'init',           [ 'ACVL_Post_Types', 'register' ] );
add_action( 'add_meta_boxes', [ 'ACVL_Meta_Boxes',  'register' ] );
add_action( 'save_post',      [ 'ACVL_Meta_Boxes',  'save' ] );
add_action( 'admin_menu',     [ 'ACVL_Settings',    'add_pages' ] );
add_action( 'admin_init',     [ 'ACVL_Settings',    'register_settings' ] );
add_action( 'rest_api_init',  [ 'ACVL_REST_API',    'register_routes' ] );
add_action( 'init',           [ 'ACVL_CORS',        'handle' ] );

// Admin styles.
add_action( 'admin_enqueue_scripts', function () {
    wp_enqueue_style( 'acvl-admin', ACVL_PLUGIN_URL . 'admin/css/admin-style.css', [], ACVL_VERSION );
});

// Activation: flush rewrite rules so CPT permalinks work.
register_activation_hook( __FILE__, function () {
    ACVL_Post_Types::register();
    flush_rewrite_rules();
});

register_deactivation_hook( __FILE__, function () {
    flush_rewrite_rules();
});
