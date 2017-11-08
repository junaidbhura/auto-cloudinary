<?php

namespace JB\Cloudinary;

class Core {

	private static $_instance    = null;
	public $_setup               = false;
	public $_cloud_name          = '';
	public $_auto_mapping_folder = '';
	public $_options             = array();
	public $_capability          = 'manage_options';
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
		add_action( 'admin_menu', array( $this, 'admin_menu_item' ) );

		$this->_capability               = apply_filters( 'cloudinary_user_capability', $this->_capability );
		$this->_cloud_name               = get_option( 'cloudinary_cloud_name' );
		$this->_auto_mapping_folder      = get_option( 'cloudinary_auto_mapping_folder' );
		$this->_options['total_domains'] = intval( get_option( 'cloudinary_total_domains' ) );
		$this->_options['content_url']   = apply_filters( 'cloudinary_content_url', content_url() );

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
			$total_domains = intval( sanitize_text_field( $_POST['cloudinary_total_domains'] ) );
			update_option( 'cloudinary_total_domains', $total_domains < 1 ? 1 : $total_domains );

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
