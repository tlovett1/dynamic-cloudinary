<?php
/**
 * Parse images to cloudinary
 *
 * @package DynamicCloudinary
 */

namespace DynamicCloudinary;

use \DOMDocument;

/**
 * Parser class
 */
class Parser {
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
		add_action( 'template_redirect', [ $this, 'maybe_buffer_output' ], 3 );
	}

	/**
	 * Check if current page is bufferable
	 *
	 * @return boolean
	 */
	protected function is_bufferable() {
		return ( ! is_admin() );
	}

	/**
	 * Start buffer
	 */
	public function maybe_buffer_output() {
		if ( ! $this->is_bufferable() ) {
			return;
		}

		ob_start( [ $this, 'parse_images' ] );
	}

	/**
	 * Check if asset can be proxied through Cloudinary
	 *
	 * @param string $url_or_path URL or path
	 * @return boolean
	 */
	public function is_valid_asset( $url_or_path ) {
		if ( 0 === strpos( $url_or_path, '/' ) ) {
			if ( 0 === strpos( $url_or_path, '' ) ) {
				return true;
			}
		} elseif ( 0 === strpos( $url_or_path, home_url() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Parse and replace applicable images
	 *
	 * @param string $html HTML code
	 * @return string
	 */
	public function parse_images( $html ) {
		$html_dom = new DOMDocument();
		$html_dom->loadHTML( $html );

		$imgs = $html_dom->getElementsByTagName( 'img' );

		foreach ( $imgs as $img ) {
			$original_img_html = $img->ownerDocument->saveHTML( $img );

			$src    = $img->getAttribute( 'src' );
			$srcset = $img->getAttribute( 'srcset' );

			$updated = false;

			if ( $this->is_valid_asset( $src ) ) {
				$img->setAttribute( 'src', $this->cloudinary_url( $src ) );

				$html .= ' ' . $this->cloudinary_url( $src );

				$updated = true;
			}

			if ( $updated ) {
				$new_img_html = $img->ownerDocument->saveHTML( $img );

				$html = str_replace( $original_img_html, $new_img_html, $html );
			}
		}

		return $html;
	}

	/**
	 * Replace URL with cloudinary version
	 *
	 * @param string $url URL for asset
	 * @param array  $args Args for Cloudinary modifiers
	 * @return string
	 */
	public function cloudinary_url( $url, $args = [] ) {
		$settings = Settings::get_instance()->get_settings();

		$args = wp_parse_args(
			$args,
			[
				'crop' => 'fill',
			]
		);

		$mutations = 'f_auto,c_' . $args['crop'];

		if ( ! empty( $args['width'] ) ) {
			$mutations .= ',w_' . (int) $args['width'];
		}

		if ( ! empty( $args['height'] ) ) {
			$mutations .= ',h_' . (int) $args['height'];
		}

		$cloudinary_url = 'https://res.cloudinary.com/' . $settings['cloud_name'] . '/image/upload/' . $mutations . '/' . $settings['auto_mapping_folder'];

		if ( 0 === strpos( $url, '/' ) ) {

		} else {
			$url = str_replace( home_url(), $cloudinary_url, $url );
		}

		return $url;
	}
}
