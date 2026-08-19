<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ACVL_Meta_Boxes {

    public static function register() {

        // Sponsor tier
        add_meta_box( 'acvl_sponsor_meta', 'Sponsor Details', [ __CLASS__, 'render_sponsor' ], 'acvl_sponsor', 'side' );

        // Board member details
        add_meta_box( 'acvl_board_meta', 'Board Member Details', [ __CLASS__, 'render_person' ], 'acvl_board', 'normal' );

        // Committee chair details
        add_meta_box( 'acvl_committee_meta', 'Committee Chair Details', [ __CLASS__, 'render_committee' ], 'acvl_committee', 'normal' );

        // Event details
        add_meta_box( 'acvl_event_meta', 'Event Details', [ __CLASS__, 'render_event' ], 'acvl_event', 'normal' );

        // Advisor details
        add_meta_box( 'acvl_advisor_meta', 'Advisor Details', [ __CLASS__, 'render_person' ], 'acvl_advisor', 'normal' );

        // Member company details
        add_meta_box( 'acvl_member_meta', 'Member Details', [ __CLASS__, 'render_member' ], 'acvl_member', 'normal' );

        // Page content slug identifier
        add_meta_box( 'acvl_page_content_meta', 'Content Block Settings', [ __CLASS__, 'render_page_content' ], 'acvl_page_content', 'side' );
    }

    // ── Sponsor ──────────────────────────────────────────────
    public static function render_sponsor( $post ) {
        wp_nonce_field( 'acvl_meta', 'acvl_meta_nonce' );
        $tier    = get_post_meta( $post->ID, '_acvl_sponsor_tier', true );
        $url     = get_post_meta( $post->ID, '_acvl_sponsor_url', true );
        $order   = get_post_meta( $post->ID, '_acvl_sort_order', true );
        ?>
        <p>
            <label><strong>Tier</strong></label><br>
            <select name="_acvl_sponsor_tier" style="width:100%">
                <option value="platinum" <?php selected( $tier, 'platinum' ); ?>>Platinum</option>
                <option value="gold"     <?php selected( $tier, 'gold' ); ?>>Gold</option>
                <option value="silver"   <?php selected( $tier, 'silver' ); ?>>Silver</option>
            </select>
        </p>
        <p>
            <label><strong>Website URL</strong></label><br>
            <input type="url" name="_acvl_sponsor_url" value="<?php echo esc_attr( $url ); ?>" style="width:100%">
        </p>
        <p>
            <label><strong>Sort Order</strong></label><br>
            <input type="number" name="_acvl_sort_order" value="<?php echo esc_attr( $order ); ?>" style="width:100%">
        </p>
        <?php
    }

    // ── Person (Board / Advisor) ─────────────────────────────
    public static function render_person( $post ) {
        wp_nonce_field( 'acvl_meta', 'acvl_meta_nonce' );
        $job_title = get_post_meta( $post->ID, '_acvl_job_title', true );
        $company   = get_post_meta( $post->ID, '_acvl_company', true );
        $email     = get_post_meta( $post->ID, '_acvl_email', true );
        $linkedin  = get_post_meta( $post->ID, '_acvl_linkedin', true );
        $order     = get_post_meta( $post->ID, '_acvl_sort_order', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label>Job Title / Role</label></th>
                <td><input type="text" name="_acvl_job_title" value="<?php echo esc_attr( $job_title ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Company / Organization</label></th>
                <td><input type="text" name="_acvl_company" value="<?php echo esc_attr( $company ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Email</label></th>
                <td><input type="email" name="_acvl_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>LinkedIn URL</label></th>
                <td><input type="url" name="_acvl_linkedin" value="<?php echo esc_attr( $linkedin ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Sort Order</label></th>
                <td><input type="number" name="_acvl_sort_order" value="<?php echo esc_attr( $order ); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    // ── Committee Chair ──────────────────────────────────────
    public static function render_committee( $post ) {
        wp_nonce_field( 'acvl_meta', 'acvl_meta_nonce' );
        $job_title      = get_post_meta( $post->ID, '_acvl_job_title', true );
        $company        = get_post_meta( $post->ID, '_acvl_company', true );
        $committee_name = get_post_meta( $post->ID, '_acvl_committee_name', true );
        $email          = get_post_meta( $post->ID, '_acvl_email', true );
        $order          = get_post_meta( $post->ID, '_acvl_sort_order', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label>Committee Name</label></th>
                <td><input type="text" name="_acvl_committee_name" value="<?php echo esc_attr( $committee_name ); ?>" class="regular-text" placeholder="e.g. Legislative Committee"></td>
            </tr>
            <tr>
                <th><label>Job Title / Role</label></th>
                <td><input type="text" name="_acvl_job_title" value="<?php echo esc_attr( $job_title ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Company / Organization</label></th>
                <td><input type="text" name="_acvl_company" value="<?php echo esc_attr( $company ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Email</label></th>
                <td><input type="email" name="_acvl_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Sort Order</label></th>
                <td><input type="number" name="_acvl_sort_order" value="<?php echo esc_attr( $order ); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    // ── Event ────────────────────────────────────────────────
    public static function render_event( $post ) {
        wp_nonce_field( 'acvl_meta', 'acvl_meta_nonce' );
        $start_date = get_post_meta( $post->ID, '_acvl_event_start', true );
        $end_date   = get_post_meta( $post->ID, '_acvl_event_end', true );
        $location   = get_post_meta( $post->ID, '_acvl_event_location', true );
        $venue      = get_post_meta( $post->ID, '_acvl_event_venue', true );
        $url        = get_post_meta( $post->ID, '_acvl_event_url', true );
        $status     = get_post_meta( $post->ID, '_acvl_event_status', true );
        $order      = get_post_meta( $post->ID, '_acvl_sort_order', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label>Start Date</label></th>
                <td><input type="date" name="_acvl_event_start" value="<?php echo esc_attr( $start_date ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>End Date</label></th>
                <td><input type="date" name="_acvl_event_end" value="<?php echo esc_attr( $end_date ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Location (City, State)</label></th>
                <td><input type="text" name="_acvl_event_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Venue</label></th>
                <td><input type="text" name="_acvl_event_venue" value="<?php echo esc_attr( $venue ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Registration / Info URL</label></th>
                <td><input type="url" name="_acvl_event_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Status</label></th>
                <td>
                    <select name="_acvl_event_status">
                        <option value="upcoming"  <?php selected( $status, 'upcoming' ); ?>>Upcoming</option>
                        <option value="ongoing"   <?php selected( $status, 'ongoing' ); ?>>Ongoing</option>
                        <option value="completed" <?php selected( $status, 'completed' ); ?>>Completed</option>
                        <option value="cancelled" <?php selected( $status, 'cancelled' ); ?>>Cancelled</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label>Sort Order</label></th>
                <td><input type="number" name="_acvl_sort_order" value="<?php echo esc_attr( $order ); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    // ── Member Company ───────────────────────────────────────
    public static function render_member( $post ) {
        wp_nonce_field( 'acvl_meta', 'acvl_meta_nonce' );
        $url   = get_post_meta( $post->ID, '_acvl_member_url', true );
        $order = get_post_meta( $post->ID, '_acvl_sort_order', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label>Website URL</label></th>
                <td><input type="url" name="_acvl_member_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label>Sort Order</label></th>
                <td><input type="number" name="_acvl_sort_order" value="<?php echo esc_attr( $order ); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }

    // ── Page Content Block ───────────────────────────────────
    public static function render_page_content( $post ) {
        wp_nonce_field( 'acvl_meta', 'acvl_meta_nonce' );
        $slug = get_post_meta( $post->ID, '_acvl_content_slug', true );
        ?>
        <p>
            <label><strong>Content Slug</strong></label><br>
            <input type="text" name="_acvl_content_slug" value="<?php echo esc_attr( $slug ); ?>" style="width:100%" placeholder="e.g. homepage-hero, mission-statement">
            <br><small>Used by the frontend to fetch this content block.</small>
        </p>
        <?php
    }

    // ── Save all meta ────────────────────────────────────────
    public static function save( $post_id ) {
        if ( ! isset( $_POST['acvl_meta_nonce'] ) || ! wp_verify_nonce( $_POST['acvl_meta_nonce'], 'acvl_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
            '_acvl_sponsor_tier',
            '_acvl_sponsor_url',
            '_acvl_job_title',
            '_acvl_company',
            '_acvl_email',
            '_acvl_linkedin',
            '_acvl_committee_name',
            '_acvl_event_start',
            '_acvl_event_end',
            '_acvl_event_location',
            '_acvl_event_venue',
            '_acvl_event_url',
            '_acvl_event_status',
            '_acvl_member_url',
            '_acvl_content_slug',
            '_acvl_sort_order',
        ];

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}
