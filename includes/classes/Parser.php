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
	 * Get transformations for a given HTML element node
	 *
	 * @param * $node HTML node
	 * @return array
	 */
	public function get_transformations_from_node( $node ) {
		$available_transformations = $this->get_allowed_cloudinary_transformations();

		$transformations = [];

		foreach ( $available_transformations as $key => $value ) {
			$data = $node->getAttribute( 'data-' . $key );

			if ( ! empty( $data ) ) {
				$transformations[ $key ] = $data;
			}
		}

		return $transformations;
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
			} else {
				$args = $this->get_transformations_from_node( $img );
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
			} else {
				$args = $this->get_transformations_from_node( $source );
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
	 * See supported transformations in `build_transformation_slug;
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

		$args = wp_parse_args(
			$args,
			[
				'format' => 'auto',
				'crop'   => 'fill',
			]
		);

		$t = $this->build_transformation_slug( $args );

		$transformations = $this->build_transformation_slug( $args );

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

	/**
	 * Check if the value is valid.
	 *
	 * Code copied and extended from https://github.com/junaidbhura/auto-cloudinary/blob/master/inc/class-core.php#L191
	 *
	 * @param string $key Value key
	 * @param string $value Value
	 * @return bool
	 */
	public function is_valid_value( $key = '', $value = '' ) {
		if ( ( 'w' === $key || 'h' === $key ) && empty( $value ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Get allowed Cloudinary transformations
	 *
	 * @return array
	 */
	public function get_allowed_cloudinary_transformations() {
		$cloudinary_params = [
			'format'               => 'f',
			'angle'                => 'a',
			'aspect_ratio'         => 'ar',
			'background'           => 'b',
			'border'               => 'bo',
			'crop'                 => 'c',
			'color'                => 'co',
			'dpr'                  => 'dpr',
			'duration'             => 'du',
			'effect'               => 'e',
			'end_offset'           => 'eo',
			'flags'                => 'fl',
			'height'               => 'h',
			'overlay'              => 'l',
			'opacity'              => 'o',
			'quality'              => 'q',
			'radius'               => 'r',
			'start_offset'         => 'so',
			'named_transformation' => 't',
			'underlay'             => 'u',
			'video_codec'          => 'vc',
			'width'                => 'w',
			'x'                    => 'x',
			'y'                    => 'y',
			'zoom'                 => 'z',
			'audio_codec'          => 'ac',
			'audio_frequency'      => 'af',
			'bit_rate'             => 'br',
			'color_space'          => 'cs',
			'default_image'        => 'd',
			'delay'                => 'dl',
			'density'              => 'dn',
			'fetch_format'         => 'f',
			'gravity'              => 'g',
			'prefix'               => 'p',
			'page'                 => 'pg',
			'video_sampling'       => 'vs',
			'progressive'          => 'fl_progressive',
		];

		return $cloudinary_params;
	}

	/**
	 * Build a Cloudinary transformation slug from arguments.
	 *
	 * Code copied and extended from https://github.com/junaidbhura/auto-cloudinary/blob/master/inc/class-core.php#L191
	 *
	 * @param  array $args Transformation arguments
	 * @return string
	 */
	public function build_transformation_slug( $args = array() ) {
		if ( empty( $args ) ) {
			return '';
		}

		$cloudinary_params = $this->get_allowed_cloudinary_transformations();

		$slug = array();
		foreach ( $args as $key => $value ) {
			if ( array_key_exists( $key, $cloudinary_params ) && $this->is_valid_value( $cloudinary_params[ $key ], $value ) ) {
				switch ( $key ) {
					case 'progressive':
						if ( true === $value ) {
							$slug[] = $cloudinary_params[ $key ];
						} else {
							$slug[] = $cloudinary_params[ $key ] . ':' . $value;
						}
						break;
					default:
						$slug[] = $cloudinary_params[ $key ] . '_' . $value;
				}
			}
		}
		return implode( ',', $slug );
	}
}
