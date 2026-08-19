<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ACVL_REST_API {

    const NAMESPACE = 'acvl/v1';

    public static function register_routes() {

        // Site settings
        register_rest_route( self::NAMESPACE, '/settings', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_settings' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);

        // Members
        register_rest_route( self::NAMESPACE, '/members', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_members' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);

        // Sponsors (optional ?tier=platinum|gold|silver)
        register_rest_route( self::NAMESPACE, '/sponsors', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_sponsors' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);

        // Board members
        register_rest_route( self::NAMESPACE, '/board', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_board' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);

        // Committee chairs
        register_rest_route( self::NAMESPACE, '/committees', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_committees' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);

        // Events (optional ?status=upcoming|completed)
        register_rest_route( self::NAMESPACE, '/events', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_events' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);

        // Advisors
        register_rest_route( self::NAMESPACE, '/advisors', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_advisors' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);

        // Page content by slug
        register_rest_route( self::NAMESPACE, '/content/(?P<slug>[a-zA-Z0-9_-]+)', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_page_content' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);

        // All page content blocks
        register_rest_route( self::NAMESPACE, '/content', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_all_content' ],
            'permission_callback' => [ __CLASS__, 'check_api_key' ],
        ]);
    }

    // ── Auth ─────────────────────────────────────────────────
    public static function check_api_key( $request ) {
        $key = get_option( 'acvl_api_key' );
        if ( empty( $key ) ) {
            return true; // no key configured = public access
        }
        return $request->get_param( 'api_key' ) === $key;
    }

    // ── Settings ─────────────────────────────────────────────
    public static function get_settings() {
        return rest_ensure_response([
            'site_name'     => get_option( 'acvl_site_name', 'ACVL' ),
            'tagline'       => get_option( 'acvl_site_tagline' ),
            'contact_email' => get_option( 'acvl_contact_email' ),
            'contact_phone' => get_option( 'acvl_contact_phone' ),
            'contact_address' => get_option( 'acvl_contact_address' ),
            'social' => [
                'facebook'  => get_option( 'acvl_social_facebook' ),
                'twitter'   => get_option( 'acvl_social_twitter' ),
                'linkedin'  => get_option( 'acvl_social_linkedin' ),
                'instagram' => get_option( 'acvl_social_instagram' ),
            ],
            'hero' => [
                'title'       => get_option( 'acvl_hero_title' ),
                'subtitle'    => get_option( 'acvl_hero_subtitle' ),
                'button_text' => get_option( 'acvl_hero_button_text' ),
                'button_url'  => get_option( 'acvl_hero_button_url' ),
            ],
        ]);
    }

    // ── Members ──────────────────────────────────────────────
    public static function get_members() {
        $posts = get_posts([
            'post_type'      => 'acvl_member',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_acvl_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ]);

        $data = array_map( function( $post ) {
            return [
                'id'    => $post->ID,
                'name'  => $post->post_title,
                'logo'  => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
                'url'   => get_post_meta( $post->ID, '_acvl_member_url', true ),
                'order' => (int) get_post_meta( $post->ID, '_acvl_sort_order', true ),
            ];
        }, $posts );

        return rest_ensure_response( $data );
    }

    // ── Sponsors ─────────────────────────────────────────────
    public static function get_sponsors( $request ) {
        $args = [
            'post_type'      => 'acvl_sponsor',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_acvl_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ];

        $tier = $request->get_param( 'tier' );
        if ( $tier && in_array( $tier, [ 'platinum', 'gold', 'silver' ], true ) ) {
            $args['meta_query'] = [
                [
                    'key'   => '_acvl_sponsor_tier',
                    'value' => $tier,
                ],
            ];
        }

        $posts = get_posts( $args );

        $data = array_map( function( $post ) {
            return [
                'id'    => $post->ID,
                'name'  => $post->post_title,
                'logo'  => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
                'tier'  => get_post_meta( $post->ID, '_acvl_sponsor_tier', true ),
                'url'   => get_post_meta( $post->ID, '_acvl_sponsor_url', true ),
                'order' => (int) get_post_meta( $post->ID, '_acvl_sort_order', true ),
            ];
        }, $posts );

        return rest_ensure_response( $data );
    }

    // ── Board ────────────────────────────────────────────────
    public static function get_board() {
        return rest_ensure_response( self::get_people( 'acvl_board' ) );
    }

    // ── Committees ───────────────────────────────────────────
    public static function get_committees() {
        $posts = get_posts([
            'post_type'      => 'acvl_committee',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_acvl_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ]);

        $data = array_map( function( $post ) {
            return [
                'id'             => $post->ID,
                'name'           => $post->post_title,
                'photo'          => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
                'bio'            => apply_filters( 'the_content', $post->post_content ),
                'job_title'      => get_post_meta( $post->ID, '_acvl_job_title', true ),
                'company'        => get_post_meta( $post->ID, '_acvl_company', true ),
                'committee_name' => get_post_meta( $post->ID, '_acvl_committee_name', true ),
                'email'          => get_post_meta( $post->ID, '_acvl_email', true ),
                'order'          => (int) get_post_meta( $post->ID, '_acvl_sort_order', true ),
            ];
        }, $posts );

        return rest_ensure_response( $data );
    }

    // ── Events ───────────────────────────────────────────────
    public static function get_events( $request ) {
        $args = [
            'post_type'      => 'acvl_event',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_acvl_event_start',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        ];

        $status = $request->get_param( 'status' );
        if ( $status ) {
            $args['meta_query'] = [
                [
                    'key'   => '_acvl_event_status',
                    'value' => sanitize_text_field( $status ),
                ],
            ];
        }

        $posts = get_posts( $args );

        $data = array_map( function( $post ) {
            return [
                'id'          => $post->ID,
                'title'       => $post->post_title,
                'description' => apply_filters( 'the_content', $post->post_content ),
                'image'       => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: null,
                'start_date'  => get_post_meta( $post->ID, '_acvl_event_start', true ),
                'end_date'    => get_post_meta( $post->ID, '_acvl_event_end', true ),
                'location'    => get_post_meta( $post->ID, '_acvl_event_location', true ),
                'venue'       => get_post_meta( $post->ID, '_acvl_event_venue', true ),
                'url'         => get_post_meta( $post->ID, '_acvl_event_url', true ),
                'status'      => get_post_meta( $post->ID, '_acvl_event_status', true ),
                'order'       => (int) get_post_meta( $post->ID, '_acvl_sort_order', true ),
            ];
        }, $posts );

        return rest_ensure_response( $data );
    }

    // ── Advisors ─────────────────────────────────────────────
    public static function get_advisors() {
        return rest_ensure_response( self::get_people( 'acvl_advisor' ) );
    }

    // ── Page Content by slug ─────────────────────────────────
    public static function get_page_content( $request ) {
        $slug  = sanitize_text_field( $request['slug'] );
        $posts = get_posts([
            'post_type'      => 'acvl_page_content',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_key'       => '_acvl_content_slug',
            'meta_value'     => $slug,
        ]);

        if ( empty( $posts ) ) {
            return new WP_Error( 'not_found', 'Content block not found', [ 'status' => 404 ] );
        }

        $post = $posts[0];
        return rest_ensure_response([
            'id'      => $post->ID,
            'title'   => $post->post_title,
            'slug'    => $slug,
            'content' => apply_filters( 'the_content', $post->post_content ),
        ]);
    }

    // ── All content blocks ───────────────────────────────────
    public static function get_all_content() {
        $posts = get_posts([
            'post_type'      => 'acvl_page_content',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ]);

        $data = array_map( function( $post ) {
            return [
                'id'      => $post->ID,
                'title'   => $post->post_title,
                'slug'    => get_post_meta( $post->ID, '_acvl_content_slug', true ),
                'content' => apply_filters( 'the_content', $post->post_content ),
            ];
        }, $posts );

        return rest_ensure_response( $data );
    }

    // ── Helper: get people (board, advisors) ─────────────────
    private static function get_people( $post_type ) {
        $posts = get_posts([
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_key'       => '_acvl_sort_order',
            'orderby'        => 'meta_value_num',
            'order'          => 'ASC',
        ]);

        return array_map( function( $post ) {
            return [
                'id'        => $post->ID,
                'name'      => $post->post_title,
                'photo'     => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
                'bio'       => apply_filters( 'the_content', $post->post_content ),
                'job_title' => get_post_meta( $post->ID, '_acvl_job_title', true ),
                'company'   => get_post_meta( $post->ID, '_acvl_company', true ),
                'email'     => get_post_meta( $post->ID, '_acvl_email', true ),
                'linkedin'  => get_post_meta( $post->ID, '_acvl_linkedin', true ),
                'order'     => (int) get_post_meta( $post->ID, '_acvl_sort_order', true ),
            ];
        }, $posts );
    }
}
