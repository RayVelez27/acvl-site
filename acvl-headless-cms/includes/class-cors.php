<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ACVL_CORS {

    public static function handle() {
        // Only apply to REST API requests.
        add_action( 'rest_api_init', function () {
            remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
            add_filter( 'rest_pre_serve_request', [ __CLASS__, 'send_cors_headers' ] );
        }, 15 );
    }

    public static function send_cors_headers( $value ) {
        $frontend_url = get_option( 'acvl_frontend_url' );
        $origin       = isset( $_SERVER['HTTP_ORIGIN'] ) ? $_SERVER['HTTP_ORIGIN'] : '';

        // Allow the configured frontend URL, or fall back to wildcard for dev.
        if ( $frontend_url && rtrim( $origin, '/' ) === rtrim( $frontend_url, '/' ) ) {
            header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
        } elseif ( empty( $frontend_url ) ) {
            header( 'Access-Control-Allow-Origin: *' );
        }

        header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
        header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
        header( 'Access-Control-Allow-Credentials: true' );

        return $value;
    }
}
