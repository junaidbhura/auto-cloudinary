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
	// Don't care about WP admin.
	if ( is_admin() ) {
		return;
	}

	// First, set up core.
	Core::get_instance()->setup();

	// Check if we need to replace content images on the front-end.
	$replace_content = false;
	if ( '1' === get_option( 'cloudinary_content_images' ) && apply_filters( 'cloudinary_content_images', true ) ) {
		$replace_content = true;
	}

	if ( apply_filters( 'cloudinary_filter_the_content', $replace_content ) ) {
		add_filter( 'the_content', 'cloudinary_update_content_images', 999 );
	}
	if ( apply_filters( 'cloudinary_filter_wp_get_attachment_url', $replace_content ) ) {
		add_filter( 'wp_get_attachment_url', __NAMESPACE__ . '\\filter_wp_get_attachment_url', 999, 2 );
	}
	add_filter( 'image_downsize', __NAMESPACE__ . '\\filter_image_downsize', 999, 3 );
	if ( apply_filters( 'cloudinary_filter_wp_calculate_image_srcset', $replace_content ) ) {
		add_filter( 'wp_calculate_image_srcset', __NAMESPACE__ . '\\filter_wp_calculate_image_srcset', 999, 5 );
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
 * Filter wp_get_attachment_url to use Cloudinary.
 *
 * @param  string $url
 * @param  int    $post_id
 * @return string
 */
function filter_wp_get_attachment_url( $url, $post_id ) {
	if ( ! apply_filters( 'cloudinary_ignore', false ) ) {
		return cloudinary_url( $url );
	}
	return $url;
}

/**
 * Filter image_downsize to use Cloudinary.
 *
 * @param  bool  $downsize
 * @param  int   $id
 * @param  mixed $size
 * @return array|bool
 */
function filter_image_downsize( $downsize, $id, $size ) {
	if ( 'full' === $size || is_array( $size ) || apply_filters( 'cloudinary_ignore', false ) ) {
		return false;
	}

	$dimensions = get_image_size( $size );
	if ( empty( $dimensions ) ) {
		return false;
	}

	return array(
		cloudinary_url( $id, array(
			'transform' => array(
				'width'  => $dimensions['width'],
				'height' => $dimensions['height'],
			),
		) ),
		$dimensions['width'],
		$dimensions['height'],
		true, // Is intermediate.
	);
}

/**
 * Filter wp_calculate_image_srcset to use Cloudinary.
 *
 * @param  array  $sources
 * @param  array  $size_array
 * @param  string $image_src
 * @param  array  $image_meta
 * @param  int    $attachment_id
 * @return array
 */
function filter_wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	if ( ! apply_filters( 'cloudinary_ignore', false ) ) {
		if ( ! empty( $sources ) ) {
			$original_url = cloudinary_get_original_url( $attachment_id );
			foreach ( $sources as $key => $source ) {
				$dimensions = get_srcset_dimensions( $image_meta, $source );
				$transform = array();
				if ( ! empty( $dimensions ) ) {
					$transform = array(
						'transform' => $dimensions,
					);
				}
				$transform = apply_filters( 'cloudinary_image_srcset_transform', $transform, $original_url, $attachment_id );

				if ( ! empty( $transform ) ) {
					$sources[ $key ]['url'] = cloudinary_url( $original_url, $transform );
				}
			}
		}
	}
	return $sources;
}

/**
 * Get dimensions from image meta which matches a descriptor.
 *
 * @param  array $image_meta
 * @param  array $source
 * @return array
 */
function get_srcset_dimensions( $image_meta = array(), $source = array() ) {
	$dimension = 'w' === $source['descriptor'] ? 'width' : 'height';
	foreach ( $image_meta['sizes'] as $key => $size ) {
		if ( $size[ $dimension ] === $source['value'] ) {
			return array(
				'width'  => $size['width'],
				'height' => $size['height'],
			);
		}
	}
	return array(
		$dimension => $source['value'],
	);
}

/**
 * Get size information for all currently-registered image sizes.
 *
 * @global $_wp_additional_image_sizes
 * @uses   get_intermediate_image_sizes()
 * @return array $sizes Data for all currently-registered image sizes.
 *
 * @see    https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
 */
function get_image_sizes() {
	global $_wp_additional_image_sizes;

	$sizes = array();

	foreach ( get_intermediate_image_sizes() as $_size ) {
		if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
			$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
			$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
			$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array(
				'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
				'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
			);
		}
	}

	return $sizes;
}

/**
 * Get size information for a specific image size.
 *
 * @uses   get_image_sizes()
 * @param  string $size The image size for which to retrieve data.
 * @return bool|array $size Size data about an image size or false if the size doesn't exist.
 *
 * @see    https://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes
 */
function get_image_size( $size ) {
	$sizes = get_image_sizes();

	if ( ! empty( $sizes ) && isset( $sizes[ $size ] ) ) {
		return $sizes[ $size ];
	}

	return false;
}
