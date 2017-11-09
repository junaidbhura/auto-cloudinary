<?php

namespace JB\Cloudinary;

class Core {

	private static $_instance    = null;
	public $_setup               = false;
	public $_cloud_name          = '';
	public $_auto_mapping_folder = '';
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
		$this->_cloud_name             = apply_filters( 'cloudinary_cloud_name', get_option( 'cloudinary_cloud_name' ) );
		$this->_auto_mapping_folder    = apply_filters( 'cloudinary_auto_mapping_folder', get_option( 'cloudinary_auto_mapping_folder' ) );
		$this->_options['urls']        = get_option( 'cloudinary_urls' );
		$this->_options['content_url'] = apply_filters( 'cloudinary_content_url', content_url() );

		if ( ! empty( $this->_cloud_name ) && ! empty( $this->_auto_mapping_folder ) ) {
			$this->_setup = true;
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
			cloudinary_ignore_start();
			$original_url = wp_get_attachment_url( intval( $identifier ) );
			cloudinary_ignore_end();
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

		// Start building the URL.
		$url = $this->get_domain() . '/' . $this->_cloud_name;

		// If file name is present, add the "images" prefix.
		if ( ! empty( $args['file_name'] ) ) {
			$url .= '/images';
		}

		// Transformations.
		if ( ! empty( $args['transform'] ) ) {
			$img_options = array();
			foreach ( $args['transform'] as $key => $value ) {
				$img_options[] = $key[0] . '_' . $value;
			}
			$url .= '/' . implode( ',', $img_options );
		}

		// Finish building the URL.
		$url .= '/' . $this->_auto_mapping_folder . str_replace( $this->_options['content_url'], '', $original_url );

		// Modify last bit of the URL if file name is present.
		if ( ! empty( $args['file_name'] ) ) {
			$path_info = pathinfo( $url );
			$url       = str_replace( $path_info['filename'], $path_info['filename'] . '/' . $args['file_name'], $url );
		}

		// All done, let's return it.
		return apply_filters( 'cloudinary_url', $url );
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

			$this->_urls = apply_filters( 'cloudinary_urls', $this->_urls );
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

}
