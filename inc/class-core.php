<?php

namespace JB\Cloudinary;

class Core {

	private static $_instance    = null;
	public $_setup               = false;
	public $_cloud_name          = '';
	public $_auto_mapping_folder = '';
	public $_options             = array();
	public $_capability          = 'manage_options';
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
		add_action( 'admin_menu', array( $this, 'admin_menu_item' ) );

		$this->_capability             = apply_filters( 'cloudinary_user_capability', $this->_capability );
		$this->_cloud_name             = get_option( 'cloudinary_cloud_name' );
		$this->_auto_mapping_folder    = get_option( 'cloudinary_auto_mapping_folder' );
		$this->_options['urls']        = get_option( 'cloudinary_urls' );
		$this->_options['content_url'] = apply_filters( 'cloudinary_content_url', content_url() );

		if ( ! empty( $this->_cloud_name ) && ! empty( $this->_auto_mapping_folder ) ) {
			$this->_setup = true;
		}
	}

	/**
	 * Add admin menu item.
	 *
	 * @return void
	 */
	public function admin_menu_item() {
		add_management_page(
			__( 'Auto Cloudinary', 'cloudinary' ),
			__( 'Auto Cloudinary', 'cloudinary' ),
			$this->_capability,
			'auto-cloudinary',
			array( $this, 'options_page' )
		);
	}

	/**
	 * Options page.
	 *
	 * @return void
	 */
	public function options_page() {
		// Check for POST.
		if (
			isset( $_POST['cloudinary_nonce'] ) // Input var okay.
			&& wp_verify_nonce( sanitize_key( $_POST['cloudinary_nonce'] ), 'cloudinary_options' ) // Input var okay.
		) {
			update_option( 'cloudinary_cloud_name', sanitize_text_field( $_POST['cloudinary_cloud_name'] ) );
			update_option( 'cloudinary_auto_mapping_folder', sanitize_text_field( $_POST['cloudinary_auto_mapping_folder'] ) );
			$urls = trim( sanitize_textarea_field( $_POST['cloudinary_urls'] ) );
			if ( empty( $urls ) ) {
				$urls = 'https://res.cloudinary.com';
			}
			update_option( 'cloudinary_urls', $urls );

			echo '<div class="updated"><p>' . esc_html__( 'Options saved.', 'fly-images' ) . '</p></div>';
		}

		// Load template.
		load_template( JB_CLOUDINARY_PATH . '/admin/options.php' );
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
		if ( empty( $this->_urls ) ) {
			if ( ! empty( $this->_options['urls'] ) ) {
				$this->_urls = array_map( function( $url ) {
					return rtrim( $url, '/' );
				}, array_map( 'trim', explode( "\n", $this->_options['urls'] ) ) );
			}

			if ( empty( $this->_urls ) ) {
				$this->_urls[] = 'https://res.cloudinary.com';
			}
		}

		$total_urls = count( $this->_urls );
		$this->_url_counter ++;
		if ( $this->_url_counter > $total_urls ) {
			$this->_url_counter = 1;
		}

		return $this->_urls[ $this->_url_counter - 1 ];
	}

}
