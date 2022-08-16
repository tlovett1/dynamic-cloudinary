<?php
/**
 * Create settings screen
 *
 * @package DynamicCloudinary
 */

namespace DynamicCloudinary;

/**
 * Settings class
 */
class Settings {
	/**
	 * Singleton instance
	 *
	 * @var $instance Plugin Singleton plugin instance
	 */
	public static $instance = null;

	/**
	 * Lazy initialize the plugin
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Setup class
	 */
	public function setup() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_init', [ $this, 'setup_fields_sections' ] );
	}

	/**
	 * Output setting menu option
	 */
	public function admin_menu() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_submenu_page( 'options-general.php', esc_html__( 'Dynamic Cloudinary', 'dynamic-cloudinary' ), esc_html__( 'Dynamic Cloudinary', 'dynamic-cloudinary' ), 'manage_options', 'dynamic-cloudinary-settings', [ $this, 'settings_screen' ] );
	}

	/**
	 * Admin settings screen output
	 */
	public function settings_screen() {
		?>
		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Dynamic Cloudinary Settings', 'dynamic-cloudinary' ); ?>
			</h1>

			<form action="options.php" method="post">

			<?php settings_fields( 'dc_settings' ); ?>
			<?php do_settings_sections( 'dynamic-cloudinary' ); ?>

			<?php submit_button(); ?>

			</form>
		</div>
		<?php
	}

	/**
	 * Register settings for options table
	 */
	public function register_settings() {
		register_setting( 'dc_settings', 'dc_settings', __NAMESPACE__ . '\sanitize_settings' );
	}

	/**
	 * Sanitize settings for DB
	 *
	 * @param  array $settings Array of settings.
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		$new_settings = $this->get_settings();

		foreach ( $settings as $key => $value ) {
			$new_settings[ $key ] = sanitize_text_field( $value );
		}

		return $new_settings;
	}

	/**
	 * Register setting fields and sections
	 */
	public function setup_fields_sections() {
		add_settings_section( 'dc-section-1', '', '', 'dynamic-cloudinary' );

		add_settings_field( 'cloud-name', esc_html__( 'Cloud Name', 'dynamic-cloudinary' ), [ $this, 'cloud_name_callback' ], 'dynamic-cloudinary', 'dc-section-1' );
		add_settings_field( 'auto-mapping-folder', esc_html__( 'Auto Mapping Folder', 'dynamic-cloudinary' ), [ $this, 'auto_mapping_folder_callback' ], 'dynamic-cloudinary', 'dc-section-1' );

	}

	/**
	 * Cloud name field output
	 */
	public function cloud_name_callback() {
		$settings = $this->get_settings();
		?>
		<label for="dc_cloud_name">
			<input id="dc_cloud_name" type="text" value="<?php echo esc_attr( $settings['cloud_name'] ); ?>" name="dc_settings[cloud_name]">
			<p class="description"><?php _e( 'This can be found in your <a href="https://cloudinary.com/">Cloudinary</a> dashboard homepage.', 'dynamic-cloudinary' ); ?></p>
		</label>
		<?php
	}

	/**
	 * Auto mapping folder field output
	 */
	public function auto_mapping_folder_callback() {
		$settings = $this->get_settings();
		?>
		<label for="dc_auto_mapping_folder">
			<input id="dc_auto_mapping_folder" type="text" value="<?php echo esc_attr( $settings['auto_mapping_folder'] ); ?>" name="dc_settings[auto_mapping_folder]">
			<p class="description"><?php _e( 'This can be found in your <a href="https://cloudinary.com/">Cloudinary</a> dashboard settings upload section.', 'dynamic-cloudinary' ); ?></p>
		</label>
		<?php
	}

	/**
	 * Get plugin settings
	 *
	 * @return array
	 */
	public function get_settings() {
		$defaults = [
			'cloud_name'          => '',
			'auto_mapping_folder' => '',
		];

		$settings = get_option( 'dc_settings', [] );
		$settings = wp_parse_args( $settings, $defaults );

		return $settings;
	}
}
