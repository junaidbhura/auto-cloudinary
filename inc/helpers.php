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
				$src    = preg_match( '/ src="([^"]*)"/', $image, $match_src ) ? $match_src[1] : '';
				$width  = preg_match( '/ width="([0-9]+)"/', $image, $match_width ) ? (int) $match_width[1] : 0;
				$height = preg_match( '/ height="([0-9]+)"/', $image, $match_height ) ? (int) $match_height[1] : 0;

				$updated_image = apply_filters( 'cloudinary_content_image', $image, $attachment_id, $src, $width, $height );

				// Check if filter updated the image.
				if ( $updated_image !== $image ) {
					// Filter updated the image, use this image.
					$content = str_replace( $image, $updated_image, $content );
				} elseif ( ! empty( $src ) ) {
					// Filter hasn't updated the image, let's update it now.
					if ( ! empty( $width ) && ! empty( $height ) ) {
						// We have a width and height, let's use them to transform the image.
						$updated_src = cloudinary_url( $attachment_id, array(
							'transform' => array(
								'width'  => $width,
								'height' => $height,
								'crop'   => apply_filters( 'cloudinary_default_crop', 'fill' ),
							),
						) );
					} else {
						// No width and height from the image, let's default to the full URL.
						$updated_src = cloudinary_url( $src );
					}

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

if ( ! function_exists( 'cloudinary_get_original_url' ) ) {
	/**
	 * Get the original URL of an image without modifying it.
	 *
	 * @param  int $id
	 * @return false|string
	 */
	function cloudinary_get_original_url( $id = 0 ) {
		/**
		 * wp_get_attachment_url() does not get modified right now.
		 *
		 * It might in a future version, hence this helper function to
		 * future-proof the code.
		 */
		return wp_get_attachment_url( $id );
	}
}
