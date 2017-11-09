<?php

namespace JB\Cloudinary;

spl_autoload_register( __NAMESPACE__ . '\\autoload' );
add_action( 'init', __NAMESPACE__ . '\\bootstrap' );
add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu_item' );

/**
 * Autoloader.
 *
 * @param  string $class
 * @return void
 */
function autoload( $class = '' ) {
	if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
		return;
	}

	$path          = JB_CLOUDINARY_PATH . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR;
	$prefix_length = strlen( __NAMESPACE__ );
	$class         = substr( $class, $prefix_length + 1 );
	$class         = strtolower( $class );
	$file          = '';
	$last_ns_pos   = strripos( $class, '\\' );

	if ( false !== $last_ns_pos ) {
		$namespace = substr( $class, 0, $last_ns_pos );
		$class     = substr( $class, $last_ns_pos + 1 );
		$file      = str_replace( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
	}

	$file .= 'class-' . str_replace( '_', '-', $class ) . '.php';
	$path .= $file;
	if ( file_exists( $path ) ) {
		require_once $path;
	}
}

/**
 * Bootstrap.
 *
 * @return void
 */
function bootstrap() {
	// First, set up core.
	Core::get_instance()->setup();

	// Check if we need to replace content images.
	if ( '1' === get_option( 'cloudinary_content_images' ) && apply_filters( 'cloudinary_content_images', true ) ) {
		add_filter( 'the_content', 'cloudinary_update_content_images', 999 );
		add_filter( 'wp_get_attachment_url', 'cloudinary_url', 999 );
		add_filter( 'wp_get_attachment_image_src', __NAMESPACE__ . '\\filter_wp_get_attachment_image_src', 999 );
		add_filter( 'wp_calculate_image_srcset', __NAMESPACE__ . '\\filter_wp_calculate_image_srcset', 999 );
	}
}

/**
 * Add admin menu item.
 *
 * @return void
 */
function admin_menu_item() {
	add_management_page(
		__( 'Cloudinary', 'cloudinary' ),
		__( 'Cloudinary', 'cloudinary' ),
		apply_filters( 'cloudinary_user_capability', 'manage_options' ),
		'auto-cloudinary',
		__NAMESPACE__ . '\\options_page'
	);
}

/**
 * Options page.
 *
 * @return void
 */
function options_page() {
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

/**
 * Filter wp_get_attachment_image_src to use Cloudinary.
 *
 * @param  array $image
 * @return array
 */
function filter_wp_get_attachment_image_src( $image ) {
	$image[0] = cloudinary_url( $image[0] );
	return $image;
}

/**
 * Filter wp_calculate_image_srcset to use Cloudinary.
 *
 * @param  array $sources
 * @return array
 */
function filter_wp_calculate_image_srcset( $sources ) {
	if ( ! empty( $sources ) ) {
		foreach ( $sources as $key => $source ) {
			$sources[ $key ]['url'] = cloudinary_url( $sources[ $key ]['url'] );
		}
	}
	return $sources;
}
