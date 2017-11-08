<?php

if ( ! function_exists( 'cloudinary_url' ) ) {
	/**
	 * Get a Cloudinary URL for an image.
	 *
	 * @param  int|string $identifier
	 * @param  array      $args
	 * @return string
	 */
	function cloudinary_url( $identifier = 0, $args = array() ) {
		return JB\Cloudinary\Core::get_instance()->get_url( $identifier, $args );
	}
}
