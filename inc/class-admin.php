<?php

namespace JB\Cloudinary;

class Admin {

	private static $_instance = null;

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
	 * Setup hooks and filters.
	 *
	 * @return void
	 */
	public function setup() {
		add_action( 'admin_menu', array( $this, 'admin_menu_item' ) );
	}

	/**
	 * Add admin menu item.
	 *
	 * @return void
	 */
	public function admin_menu_item() {
		add_management_page(
			__( 'Cloudinary', 'cloudinary' ),
			__( 'Cloudinary', 'cloudinary' ),
			apply_filters( 'cloudinary_user_capability', 'manage_options' ),
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
			update_option( 'cloudinary_default_hard_crop', sanitize_text_field( $_POST['cloudinary_default_hard_crop'] ) );
			update_option( 'cloudinary_default_soft_crop', sanitize_text_field( $_POST['cloudinary_default_soft_crop'] ) );
			$urls = trim( sanitize_textarea_field( $_POST['cloudinary_urls'] ) );
			if ( empty( $urls ) ) {
				$urls = 'https://res.cloudinary.com';
			}
			update_option( 'cloudinary_urls', $urls );
			if ( empty( $_POST['cloudinary_content_images'] ) ) {
				$content_images = '';
			} else {
				$content_images = sanitize_text_field( $_POST['cloudinary_content_images'] );
			}
			update_option( 'cloudinary_content_images', $content_images );

			echo '<div class="updated"><p>' . esc_html__( 'Options saved.', 'fly-images' ) . '</p></div>';
		}

		// Load template.
		load_template( JB_CLOUDINARY_PATH . '/admin/options.php' );
	}

}
