<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ACVL_Post_Types {

    public static function register() {

        // --- Member Companies ---
        register_post_type( 'acvl_member', [
            'labels' => self::labels( 'Member Company', 'Member Companies' ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-building',
            'supports'     => [ 'title', 'thumbnail' ],
            'has_archive'  => false,
        ]);

        // --- Sponsors ---
        register_post_type( 'acvl_sponsor', [
            'labels' => self::labels( 'Sponsor', 'Sponsors' ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-star-filled',
            'supports'     => [ 'title', 'thumbnail' ],
            'has_archive'  => false,
        ]);

        // --- Board Members ---
        register_post_type( 'acvl_board', [
            'labels' => self::labels( 'Board Member', 'Board Members' ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-groups',
            'supports'     => [ 'title', 'thumbnail', 'editor' ],
            'has_archive'  => false,
        ]);

        // --- Committee Chairs ---
        register_post_type( 'acvl_committee', [
            'labels' => self::labels( 'Committee Chair', 'Committee Chairs' ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-businessman',
            'supports'     => [ 'title', 'thumbnail', 'editor' ],
            'has_archive'  => false,
        ]);

        // --- Events ---
        register_post_type( 'acvl_event', [
            'labels' => self::labels( 'Event', 'Events' ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-calendar-alt',
            'supports'     => [ 'title', 'thumbnail', 'editor' ],
            'has_archive'  => false,
        ]);

        // --- Advisors ---
        register_post_type( 'acvl_advisor', [
            'labels' => self::labels( 'Advisor', 'Advisors' ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-welcome-learn-more',
            'supports'     => [ 'title', 'thumbnail', 'editor' ],
            'has_archive'  => false,
        ]);

        // --- Page Content (reusable content blocks) ---
        register_post_type( 'acvl_page_content', [
            'labels' => self::labels( 'Page Content', 'Page Content' ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-media-text',
            'supports'     => [ 'title', 'editor' ],
            'has_archive'  => false,
        ]);
    }

    private static function labels( $singular, $plural ) {
        return [
            'name'               => $plural,
            'singular_name'      => $singular,
            'add_new'            => "Add New {$singular}",
            'add_new_item'       => "Add New {$singular}",
            'edit_item'          => "Edit {$singular}",
            'new_item'           => "New {$singular}",
            'view_item'          => "View {$singular}",
            'search_items'       => "Search {$plural}",
            'not_found'          => "No {$plural} found",
            'not_found_in_trash' => "No {$plural} found in Trash",
            'all_items'          => "All {$plural}",
            'menu_name'          => $plural,
        ];
    }
}
