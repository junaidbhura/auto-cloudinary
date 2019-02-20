<?php

namespace JB\Cloudinary;

spl_autoload_register( __NAMESPACE__ . '\\autoload' );
add_action( 'init', __NAMESPACE__ . '\\bootstrap' );

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
	$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX; // wp_doing_ajax() is only available from WP 4.7

	if ( ! $doing_ajax && is_admin() ) {
		// Admin stuff.
		Admin::get_instance()->setup();
	} else {
		// Front-end stuff.
		Core::get_instance()->setup();
		Frontend::get_instance()->setup();
	}
}
