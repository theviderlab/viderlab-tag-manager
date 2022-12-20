<?php
/**	 
 * Plugin Name: Simple Google Tag Manager Implementation
 * Plugin URI: https://viderlab.com
 * Description: Adds GTM snippet
 * Version: 1.5.0
 * Author: ViderLab
 * Author URI: https://viderlab.com
 * License: GPL+2
 * Text Domain: viderlab-gtm
**/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class ViderLab_GTM {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'settings_init' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        add_action('wp_head', [ $this, 'gtm_head' ] );
        add_action('wp_footer', [ $this, 'gtm_body' ] );
    }

    public function settings_init() {
        // Register a new setting for "viderlab-gtm-settings" page.
        register_setting( 'viderlab-gtm-settings', 'viderlab-gtm-settings_options' );

        // Register a new section in the "viderlab-gtm-settings" page.
        add_settings_section(
            'viderlab-gtm-settings_tag',
            __( 'GTM Tag', 'viderlab-gtm' ), 
            [ $this, 'settings_tag' ],
            'viderlab-gtm-settings'
        );

        // Register a new field in the "viderlab-gtm-settings_tag" section, inside the "viderlab-gtm-settings" page.
        add_settings_field(
            'viderlab-gtm-settings_tag-code', // As of WP 4.6 this value is used only internally.
                                    // Use $args' label_for to populate the id inside the callback.
                __( 'Google Tag Manager Tag', 'viderlab-gtm' ),
            [ $this, 'tag_code_field' ],
            'viderlab-gtm-settings',
            'viderlab-gtm-settings_tag',
            array(
                'label_for'         => 'viderlab-gtm-settings_tag-code',
                'class'             => 'viderlab-gtm-settings_row',
                'custom_data'   => 'custom',
            )
        );
    }

    /**
     * Filter section callback function.
     *
     * @param array $args  The settings array, defining title, id, callback.
     */
    public function settings_tag( $args ) {
        ?>
        <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Google Tag Manager Tag ID (GTM-XXXXXXX).', 'viderlab-gtm' ); ?></p>
        <?php
    }

    /**
     * Pill field callbakc function.
     *
     * WordPress has magic interaction with the following keys: label_for, class.
     * - the "label_for" key value is used for the "for" attribute of the <label>.
     * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
     * Note: you can add custom key value pairs to be used inside your callbacks.
     *
     * @param array $args
     */
    public function tag_code_field( $args ) {
        // Get the value of the setting we've registered with register_setting()
        $options = get_option( 'viderlab-gtm-settings_options' );
        ?>
        <input type="text" 
                id="<?php echo esc_attr( $args['label_for'] ); ?>"
                data-custom="<?php echo esc_attr( $args['custom_data'] ); ?>"
                name="viderlab-gtm-settings_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
                value="<?php echo $options[ $args['label_for'] ]; ?>">
        <p class="description">
            <?php esc_html_e( 'Insert Google Tag Manager code.', 'viderlab-gtm' ); ?>
        </p>
        <?php
    }

	public function admin_menu() {
		$hook = add_management_page( 'Google Tag manager', 'Google Tag manager', 'manage_options', 'viderlab-gtm', [ $this, 'admin_page' ], '' );
        add_action( "load-$hook",  [ $this, 'admin_page_load' ] );
	}

	public function admin_page_load() {
		// ...
	}

	public function admin_page() {
        // check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // WordPress will add the "settings-updated" $_GET parameter to the url
        if ( isset( $_GET['settings-updated'] ) ) {
            // add settings saved message with the class of "updated"
            add_settings_error( 'viderlab-gtm-settings_messages', 'viderlab-gtm-settings_message', __( 'Settings Saved', 'viderlab-gtm' ), 'updated' );
        }

        // show error/update messages
        settings_errors( 'viderlab-gtm-settings_messages' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                // output security fields for the registered setting "viderlab-gtm-settings"
                settings_fields( 'viderlab-gtm-settings' );
                // output setting sections and their fields
                // (sections are registered for "viderlab-gtm-settings", each field is registered to a specific section)
                do_settings_sections( 'viderlab-gtm-settings' );
                // output save settings button
                submit_button( __('Save Settings', 'viderlab-gtm') );
                ?>
            </form>
        </div>
        <?php
	}

    public function gtm_head() {
        $settings = get_option( 'viderlab-gtm-settings_options' );
        $tag = $settings['viderlab-gtm-settings_tag-code'];
        ?>
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','<?php echo $tag; ?>');</script>
        <!-- End Google Tag Manager -->
        <?php
    }

    public function gtm_body() {
        $settings = get_option( 'viderlab-gtm-settings_options' );
        $tag = $settings['viderlab-gtm-settings_tag-code'];
        ?>
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $tag; ?>"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        <?php
    }
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.5.0
 */
function run_viderlab_gtm() {

	$plugin = new ViderLab_GTM();

}
run_viderlab_gtm();
?>