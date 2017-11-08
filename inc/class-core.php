<?php

namespace JB\Cloudinary;

class Core {

	private static $_instance    = null;
	public $_setup               = false;
	public $_cloud_name          = '';
	public $_auto_mapping_folder = '';
	public $_options             = array();
	private $_domain_counter     = 0;

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
		if ( defined( 'CLOUDINARY_CLOUD_NAME' ) && defined( 'CLOUDINARY_AUTO_MAPPING_FOLDER' ) ) {
			$this->_setup               = true;
			$this->_cloud_name          = CLOUDINARY_CLOUD_NAME;
			$this->_auto_mapping_folder = CLOUDINARY_AUTO_MAPPING_FOLDER;
		}

		$this->_options = apply_filters( 'cloudinary_options', array(
			'total_domains' => 1,
			'content_url'   => content_url(),
		) );
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
			$original_url = wp_get_attachment_url( intval( $identifier ) );
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
		return $url;
	}

	/**
	 * Get Cloudinary domain based on total domains.
	 *
	 * @return string
	 */
	public function get_domain() {
		if ( 1 === $this->_options['total_domains'] ) {
			return 'https://res.cloudinary.com';
		}

		$this->_domain_counter ++;
		if ( $this->_domain_counter > $this->_options['total_domains'] ) {
			$this->_domain_counter = 1;
		}

		return "https://res-$this->_domain_counter.cloudinary.com";
	}

}
