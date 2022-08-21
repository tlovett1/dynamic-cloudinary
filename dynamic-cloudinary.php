<?php
/**
 * Plugin Name:       Dynamic Cloudinary
 * Plugin URI:        https://github.com/tlovett1/dynamic-cloudinary
 * Description:       Automatically serve all your images optimized from the cloud.
 * Version:           1.1.34
 * Requires PHP:      7.0
 * Author:            Taylor Lovett
 * Author URI:        https://taylorlovett.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dynamic-cloudinary
 * Domain Path:       /languages
 *
 * @package dynamic-cloudinary
 *
 * This plugin derives work from Auto Cloudinary: https://github.com/junaidbhura/auto-cloudinary
 */

namespace DynamicCloudinary;

define( 'DYNAMIC_CLOUDINARY_VERSION', '1.1.4' );
define( 'DYNAMIC_CLOUDINARY_URL', plugin_dir_url( __FILE__ ) );
define( 'DYNAMIC_CLOUDINARY_PATH', plugin_dir_path( __FILE__ ) );

// Require Composer autoloader if it exists.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} elseif ( ! class_exists( Settings::class ) ) {
	require_once __DIR__ . '/vendor/ivopetkov/html5-dom-document-php/autoload.php';

	/**
	 * PSR-4 autoloading
	 */
	spl_autoload_register(
		function( $class ) {
				// Project-specific namespace prefix.
				$prefix = 'DynamicCloudinary\\';
				// Base directory for the namespace prefix.
				$base_dir = __DIR__ . '/includes/classes/';
				// Does the class use the namespace prefix?
				$len = strlen( $prefix );
			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
			}
				$relative_class = substr( $class, $len );
				$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
				// If the file exists, require it.
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

require_once DYNAMIC_CLOUDINARY_PATH . 'includes/utils.php';

Settings::get_instance();
Parser::get_instance();
