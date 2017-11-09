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

if ( ! function_exists( 'cloudinary_update_content_images' ) ) {
	/**
	 * Parse content and update images to use Cloudinary.
	 *
	 * @param  string $content
	 * @return string
	 */
	function cloudinary_update_content_images( $content = '' ) {
		$all_images = array();
		if ( preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
			$all_images = $matches[0];
		}

		if ( empty( $all_images ) ) {
			return $content;
		}

		foreach ( $all_images as $image ) {
			// Only look for images with an attachment ID.
			if ( preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) && ( $attachment_id = absint( $class_id[1] ) ) ) { // @codingStandardsIgnoreLine
				preg_match( '/src="([^"]*)"/', $image, $src );
				if ( ! empty( $src ) ) {
					$src = $src[1];
				} else {
					$src = '';
				}

				$updated_image = apply_filters( 'cloudinary_content_image', $image, $attachment_id, $src );
				if ( $updated_image !== $image ) {
					$content = str_replace( $image, $updated_image, $content );
				} elseif ( ! empty( $src ) ) {
					$updated_src = cloudinary_url( $src );
					if ( ! empty( $updated_src ) ) {
						$updated_image = str_replace( $src, $updated_src, $image );
						$content       = str_replace( $image, $updated_image, $content );
					}
				}
			}
		}

		return $content;
	}
}

if ( ! function_exists( 'cloudinary_ignore_start' ) ) {
	/**
	 * Helper function to add a filter.
	 *
	 * @return void
	 */
	function cloudinary_ignore_start() {
		add_filter( 'cloudinary_ignore', '__return_true', 10 );
	}
}

if ( ! function_exists( 'cloudinary_ignore_end' ) ) {
	/**
	 * Helper function to remove a filter.
	 *
	 * @return void
	 */
	function cloudinary_ignore_end() {
		remove_filter( 'cloudinary_ignore', '__return_true', 10 );
	}
}
