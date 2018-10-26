<?php

namespace JB\Cloudinary;

class Core {

	private static $_instance    = null;
	public $_setup               = false;
	public $_cloud_name          = '';
	public $_auto_mapping_folder = '';
	public $_default_hard_crop   = '';
	public $_default_soft_crop   = '';
	public $_options             = array();
	public $_urls                = array();
	private $_url_counter        = 0;

	/**
	 * Get current instance.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Setup plugin.
	 *
	 * @return void
	 */
	public function setup() {
		$this->_cloud_name            = apply_filters( 'cloudinary_cloud_name', get_option( 'cloudinary_cloud_name' ) );
		$this->_auto_mapping_folder   = apply_filters( 'cloudinary_auto_mapping_folder', get_option( 'cloudinary_auto_mapping_folder' ) );
		$this->_default_hard_crop     = get_option( 'cloudinary_default_hard_crop' );
		$this->_default_soft_crop     = get_option( 'cloudinary_default_soft_crop' );
		$this->_options['urls']       = get_option( 'cloudinary_urls' );
		$upload_dir                   = wp_upload_dir();
		$this->_options['upload_url'] = apply_filters( 'cloudinary_upload_url', $upload_dir['baseurl'] );

		if ( ! empty( $this->_cloud_name ) && ! empty( $this->_auto_mapping_folder ) ) {
			$this->_setup = true;
		}

		/**
		 * Users before 1.1.0 might not have updated their options.
		 * This is to support them.
		 */
		if ( empty( $this->_default_hard_crop ) ) {
			$this->_default_hard_crop = apply_filters( 'cloudinary_default_hard_crop', apply_filters( 'cloudinary_default_crop', 'fill' ) );
		}
		if ( empty( $this->_default_soft_crop ) ) {
			$this->_default_soft_crop = apply_filters( 'cloudinary_default_hard_crop', apply_filters( 'cloudinary_default_crop', 'fill' ) );
		}
	}

	/**
	 * Get a Cloudinary URL for an image.
	 *
	 * @param  int|string $identifier
	 * @param  array      $args
	 * @return string
	 */
	public function get_url( $identifier = 0, $args = array() ) {
		if ( is_numeric( $identifier ) ) {
			// Identifier is numeric, let's assume it's an ID and get the original URL.
			$original_url = cloudinary_get_original_url( intval( $identifier ) );
		} elseif ( is_string( $identifier ) ) {
			// Identifier is a string, let's assume it's the original URL.
			$original_url = $identifier;
		}

		// Check if we have an original URL.
		if ( empty( $original_url ) ) {
			return '';
		}

		// If the plugin isn't set up correctly, default to the original URL.
		if ( ! $this->_setup ) {
			return $original_url;
		}

		// Validate URL.
		if ( 0 !== strpos( $original_url, $this->_options['upload_url'] ) ) {
			return $original_url;
		}

		// Default args.
		$default_args = apply_filters( 'cloudinary_default_args', array() );
		if ( ! empty( $default_args ) ) {
			$args = array_replace_recursive( $default_args, $args );
		}

		// Filter args.
		$args = apply_filters( 'cloudinary_args', $args, $identifier );

		// Start building the URL.
		$url = $this->get_domain() . '/' . $this->_cloud_name;

		// If file name is present, add the "images" prefix.
		if ( ! empty( $args['file_name'] ) ) {
			$url .= '/images';
		}

		// Transformations.
		if ( ! empty( $args['transform'] ) ) {
			$transformations_slug = $this->build_transformation_slug( $args['transform'] );
			if ( ! empty( $transformations_slug ) ) {
				$url .= '/' . $transformations_slug;
			}
		}

		// Finish building the URL.
		$url .= '/' . $this->_auto_mapping_folder . str_replace( $this->_options['upload_url'], '', $original_url );

		// Modify last bit of the URL if file name is present.
		if ( ! empty( $args['file_name'] ) ) {
			$path_info = pathinfo( $url );
			$url       = str_replace( $path_info['filename'], $path_info['filename'] . '/' . $args['file_name'], $url );
		}

		// All done, let's return it.
		return apply_filters( 'cloudinary_url', $url, $identifier, $args );
	}

	/**
	 * Get Cloudinary domain based on total domains.
	 *
	 * @return string
	 */
	public function get_domain() {
		// Get our URLs the first time this function is called.
		if ( empty( $this->_urls ) ) {
			if ( ! empty( $this->_options['urls'] ) ) {
				$this->_urls = array_map( function( $url ) {
					return rtrim( $url, '/' );
				}, array_map( 'trim', explode( "\n", $this->_options['urls'] ) ) );
			}

			$this->_urls        = apply_filters( 'cloudinary_urls', $this->_urls );
			$this->_url_counter = 0;

			// Something went wrong, fallback to default URL.
			if ( empty( $this->_urls ) ) {
				$this->_urls[] = 'https://res.cloudinary.com';
			}
		}

		// Cycle through domains and get the current one.
		$total_urls = count( $this->_urls );
		$this->_url_counter ++;
		if ( $this->_url_counter > $total_urls ) {
			$this->_url_counter = 1;
		}

		// Return current domain.
		return $this->_urls[ $this->_url_counter - 1 ];
	}

	/**
	 * Check if the value is valid.
	 *
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	public function valid_value( $key = '', $value = '' ) {
		if ( ( 'w' === $key || 'h' === $key ) && empty( $value ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Build a Cloudinary transformation slug from arguments.
	 *
	 * @param  array $args
	 * @return string
	 */
	public function build_transformation_slug( $args = array() ) {
		if ( empty( $args ) ) {
			return '';
		}

		$cloudinary_params = array(
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
		);

		$slug = array();
		foreach ( $args as $key => $value ) {
			if ( array_key_exists( $key, $cloudinary_params ) && $this->valid_value( $cloudinary_params[ $key ], $value ) ) {
				$slug[] = $cloudinary_params[ $key ] . '_' . $value;
			}
		}
		return implode( ',', $slug );
	}

}
