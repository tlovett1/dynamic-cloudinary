<?php
/**
 * Parse images to cloudinary
 *
 * @package DynamicCloudinary
 */

namespace DynamicCloudinary;

use DynamicCloudinary\Utils;
use \IvoPetkov\HTML5DOMDocument;

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

		ob_start( [ $this, 'parse_assets' ] );
	}

	/**
	 * Get type of asset
	 *
	 * @param string $url_or_path URL or path
	 * @return string
	 */
	public function get_asset_type( $url_or_path ) {
		$extension = (string) pathinfo( wp_parse_url( $url_or_path, PHP_URL_PATH ), PATHINFO_EXTENSION );

		$allowed_extensions = Utils\get_allowed_file_extensions();

		foreach ( $allowed_extensions as $type => $type_array ) {
			if ( in_array( strtolower( $extension ), $type_array, true ) ) {
				return $type;
			}
		}

		return null;
	}

	/**
	 * Make sure we can proxy this path through Cloudinary
	 *
	 * @param string $url_or_path URL or path
	 * @return boolean
	 */
	public function is_proxable_path( $url_or_path ) {
		$root_path = trailingslashit( wp_parse_url( home_url(), PHP_URL_PATH ) );

		if ( 0 === strpos( $url_or_path, $root_path ) ) {
			return true;
		} elseif ( 0 === strpos( $url_or_path, home_url() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Parse and replace applicable assets
	 *
	 * @param string $html HTML code
	 * @return string
	 */
	public function parse_assets( $html ) {
		$html_dom = new HTML5DOMDocument();
		$html_dom->loadHTML( $html, HTML5DOMDocument::ALLOW_DUPLICATE_IDS );

		$imgs = $html_dom->getElementsByTagName( 'img' );

		foreach ( $imgs as $img ) {
			$original_html = $img->outerHTML;

			$src    = $img->getAttribute( 'src' );
			$srcset = $img->getAttribute( 'srcset' );
			$width  = $img->getAttribute( 'width' );
			$height = $img->getAttribute( 'height' );

			$transformations = $img->getAttribute( 'data-transformations' );

			$args = [];

			if ( ! empty( $width ) ) {
				$args['width'] = $width;
			}

			if ( ! empty( $height ) ) {
				$args['height'] = $height;
			}

			if ( ! empty( $transformations ) ) {
				$args['transformations'] = $transformations;
			}

			$updated = false;

			if ( 'image' === $this->get_asset_type( $src ) && $this->is_proxable_path( $src ) ) {
				$img->setAttribute( 'src', $this->get_cloudinary_url( $src, $args ) );

				$updated = true;
			}

			if ( ! empty( $srcset ) ) {
				$srcset_tokens = explode( ' ', $srcset );

				foreach ( $srcset_tokens as $token ) {
					if ( 'image' === $this->get_asset_type( $token ) && $this->is_proxable_path( $token ) ) {
						$srcset = str_replace( $token, $this->get_cloudinary_url( $token ), $srcset );

						$img->setAttribute( 'srcset', $srcset );

						$updated = true;
					}
				}
			}

			if ( $updated ) {
				$new_html = $img->outerHTML;

				$html = str_replace( $this->unclose_tag( $original_html ), $this->unclose_tag( $new_html ), $html );
			}
		}

		$sources = $html_dom->getElementsByTagName( 'source' );

		foreach ( $sources as $source ) {
			// We have to do some weird regex because of unexpected HTML5Document results
			$original_html = preg_replace( '#^(<source .*?>).*$#is', '$1', $source->outerHTML );

			$srcset = $source->getAttribute( 'srcset' );
			$src    = $source->getAttribute( 'src' );

			$transformations = $source->getAttribute( 'data-transformations' );

			$args = [];

			if ( ! empty( $transformations ) ) {
				$args['transformations'] = $transformations;
			}

			$updated = false;

			if ( $this->get_asset_type( $srcset ) ) {
				$source->setAttribute( 'srcset', $this->get_cloudinary_url( $srcset, $args ) );

				$updated = true;
			}

			if ( $this->get_asset_type( $src ) ) {
				$source->setAttribute( 'src', $this->get_cloudinary_url( $src, $args ) );

				$updated = true;
			}

			if ( $updated ) {
				$new_html = preg_replace( '#^(<source .*?>).*$#is', '$1', $source->outerHTML );

				$html = str_replace( $this->unclose_tag( $original_html ), $this->unclose_tag( $new_html ), $html );
			}
		}

		return $html;
	}

	/**
	 * Remove /> or > from end of tag
	 *
	 * @param string $str String to strip
	 * @return string
	 */
	public function unclose_tag( $str ) {
		return preg_replace( '#/?>#', '', $str );
	}

	/**
	 * Replace URL with cloudinary version
	 *
	 * Args supports crop, width, height, and transformations. If transformations
	 * is passed, it will override all other transformations
	 *
	 * @param string $url URL for asset
	 * @param array  $args Args for Cloudinary modifiers
	 * @return string
	 */
	public function get_cloudinary_url( $url, $args = [] ) {
		$original_url = $url;

		$type = $this->get_asset_type( $url );

		$settings = Settings::get_instance()->get_settings();

		$root_path = trailingslashit( wp_parse_url( home_url(), PHP_URL_PATH ) );

		$transformations = 'f_auto';

		$args = wp_parse_args(
			$args,
			[
				'crop' => 'fill',
			]
		);

		$transformations = 'c_' . $args['crop'];

		if ( ! empty( $args['width'] ) ) {
			$transformations .= ',w_' . (int) $args['width'];
		}

		if ( ! empty( $args['height'] ) ) {
			$transformations .= ',h_' . (int) $args['height'];
		}

		if ( ! empty( $args['transformations'] ) ) {
			$transformations = $args['transformations'];
		}

		$transformations = apply_filters( 'dc_cloudinary_transformations', $transformations, $original_url, $args );

		$transformations = ltrim( $transformations, ',' );
		$transformations = rtrim( $transformations, '/' );

		if ( ! empty( $transformations ) ) {
			$transformations .= '/';
		}

		$cloudinary_url = apply_filters( 'dc_cloudinary_base_url', 'https://res.cloudinary.com/' ) . $settings['cloud_name'] . '/' . $type . '/upload/' . $transformations . $settings['auto_mapping_folder'];

		if ( 0 === strpos( $url, $root_path ) ) {
			$url = preg_replace( '#^' . $root_path . '#', '/' . $cloudinary_url, $url );
		} else {
			$url = str_replace( home_url(), $cloudinary_url, $url );
		}

		return apply_filters( 'dc_cloudinary_url', $url, $original_url, $args );
	}

}
