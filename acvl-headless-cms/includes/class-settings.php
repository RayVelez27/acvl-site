<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ACVL_Settings {

    public static function add_pages() {
        add_menu_page(
            'ACVL Settings',
            'ACVL Settings',
            'manage_options',
            'acvl-settings',
            [ __CLASS__, 'render_page' ],
            'dashicons-admin-generic',
            3
        );
    }

    public static function register_settings() {
        // Site identity
        register_setting( 'acvl_settings', 'acvl_site_name' );
        register_setting( 'acvl_settings', 'acvl_site_tagline' );
        register_setting( 'acvl_settings', 'acvl_contact_email' );
        register_setting( 'acvl_settings', 'acvl_contact_phone' );
        register_setting( 'acvl_settings', 'acvl_contact_address' );

        // Social links
        register_setting( 'acvl_settings', 'acvl_social_facebook' );
        register_setting( 'acvl_settings', 'acvl_social_twitter' );
        register_setting( 'acvl_settings', 'acvl_social_linkedin' );
        register_setting( 'acvl_settings', 'acvl_social_instagram' );

        // Homepage hero
        register_setting( 'acvl_settings', 'acvl_hero_title' );
        register_setting( 'acvl_settings', 'acvl_hero_subtitle' );
        register_setting( 'acvl_settings', 'acvl_hero_button_text' );
        register_setting( 'acvl_settings', 'acvl_hero_button_url' );

        // Frontend URL (where the static HTML site lives)
        register_setting( 'acvl_settings', 'acvl_frontend_url' );

        // API Key for optional write protection
        register_setting( 'acvl_settings', 'acvl_api_key' );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap acvl-settings-wrap">
            <h1>ACVL Site Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'acvl_settings' ); ?>

                <h2 class="title">Site Identity</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="acvl_site_name">Site Name</label></th>
                        <td><input type="text" id="acvl_site_name" name="acvl_site_name" value="<?php echo esc_attr( get_option( 'acvl_site_name', 'Association of Consumer Vehicle Lessors' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_site_tagline">Tagline</label></th>
                        <td><input type="text" id="acvl_site_tagline" name="acvl_site_tagline" value="<?php echo esc_attr( get_option( 'acvl_site_tagline' ) ); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2 class="title">Contact Information</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="acvl_contact_email">Email</label></th>
                        <td><input type="email" id="acvl_contact_email" name="acvl_contact_email" value="<?php echo esc_attr( get_option( 'acvl_contact_email', 'helloacvl@gmail.com' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_contact_phone">Phone</label></th>
                        <td><input type="text" id="acvl_contact_phone" name="acvl_contact_phone" value="<?php echo esc_attr( get_option( 'acvl_contact_phone' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_contact_address">Address</label></th>
                        <td><textarea id="acvl_contact_address" name="acvl_contact_address" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'acvl_contact_address' ) ); ?></textarea></td>
                    </tr>
                </table>

                <h2 class="title">Social Media</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="acvl_social_facebook">Facebook URL</label></th>
                        <td><input type="url" id="acvl_social_facebook" name="acvl_social_facebook" value="<?php echo esc_attr( get_option( 'acvl_social_facebook' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_social_twitter">Twitter / X URL</label></th>
                        <td><input type="url" id="acvl_social_twitter" name="acvl_social_twitter" value="<?php echo esc_attr( get_option( 'acvl_social_twitter' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_social_linkedin">LinkedIn URL</label></th>
                        <td><input type="url" id="acvl_social_linkedin" name="acvl_social_linkedin" value="<?php echo esc_attr( get_option( 'acvl_social_linkedin' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_social_instagram">Instagram URL</label></th>
                        <td><input type="url" id="acvl_social_instagram" name="acvl_social_instagram" value="<?php echo esc_attr( get_option( 'acvl_social_instagram' ) ); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2 class="title">Homepage Hero Section</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="acvl_hero_title">Hero Title</label></th>
                        <td><input type="text" id="acvl_hero_title" name="acvl_hero_title" value="<?php echo esc_attr( get_option( 'acvl_hero_title', 'Association of Consumer Vehicle Lessors' ) ); ?>" class="large-text"></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_hero_subtitle">Hero Subtitle</label></th>
                        <td><textarea id="acvl_hero_subtitle" name="acvl_hero_subtitle" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'acvl_hero_subtitle' ) ); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_hero_button_text">Button Text</label></th>
                        <td><input type="text" id="acvl_hero_button_text" name="acvl_hero_button_text" value="<?php echo esc_attr( get_option( 'acvl_hero_button_text', 'Our Mission' ) ); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="acvl_hero_button_url">Button URL</label></th>
                        <td><input type="text" id="acvl_hero_button_url" name="acvl_hero_button_url" value="<?php echo esc_attr( get_option( 'acvl_hero_button_url', 'mission.html' ) ); ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2 class="title">Headless Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="acvl_frontend_url">Frontend URL</label></th>
                        <td>
                            <input type="url" id="acvl_frontend_url" name="acvl_frontend_url" value="<?php echo esc_attr( get_option( 'acvl_frontend_url' ) ); ?>" class="regular-text" placeholder="https://acvl.org">
                            <p class="description">The URL where the static HTML site is hosted. Used for CORS.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="acvl_api_key">API Key (optional)</label></th>
                        <td>
                            <input type="text" id="acvl_api_key" name="acvl_api_key" value="<?php echo esc_attr( get_option( 'acvl_api_key' ) ); ?>" class="regular-text">
                            <p class="description">If set, frontend requests must include <code>?api_key=...</code> to access the API.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Save Settings' ); ?>
            </form>
        </div>
        <?php
    }
}
