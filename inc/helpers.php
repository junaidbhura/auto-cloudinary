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
				$class  = preg_match( '/ class="([^"]*)"/', $image, $match_class ) ? $match_class[1] : '';

				if ( ! empty( $class ) ) {
					$size = preg_match( '/size-([a-zA-Z0-9-_]+)?/', $class, $match_size ) ? $match_size[1] : '';
				} else {
					$size = '';
				}

				$updated_image = apply_filters( 'cloudinary_content_image', $image, $attachment_id, $src, $width, $height );

				$cloudinary_args = array(
					'transform' => array(
						'format' => 'auto',
					),
				);

				// Check if filter updated the image.
				if ( $updated_image !== $image ) {
					// Filter updated the image, use this image.
					$content = str_replace( $image, $updated_image, $content );
				} elseif ( ! empty( $src ) ) {
					// Filter hasn't updated the image, let's update it now.
					if ( ! empty( $width ) && ! empty( $height ) ) {
						// Let's see if we can find this image's crop type.
						$hard_crop = false;
						if ( ! empty( $size ) ) {
							$dimensions = JB\Cloudinary\Frontend::get_instance()->get_image_size( $size );
							if ( ! empty( $dimensions ) && (bool) $dimensions['crop'] ) {
								$hard_crop = true;
							}
						}

						$cloudinary_args['transform']['crop']   = cloudinary_default_crop( $hard_crop );
						$cloudinary_args['transform']['width']  = $width;
						$cloudinary_args['transform']['height'] = $height;

						// We have a width and height, let's use them to transform the image.
						$updated_src = cloudinary_url( $attachment_id, $cloudinary_args );
					} else {
						// No width and height from the image, let's default to the full URL.
						$updated_src = cloudinary_url( $src, $cloudinary_args );
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

if ( ! function_exists( 'cloudinary_default_crop' ) ) {
	/**
	 * Helper function to get the correct crop after applying filters.
	 *
	 * @param bool $hard_crop
	 * @return string
	 */
	function cloudinary_default_crop( $hard_crop = false ) {
		$core = JB\Cloudinary\Core::get_instance();
		if ( $hard_crop ) {
			return apply_filters( 'cloudinary_default_hard_crop', apply_filters( 'cloudinary_default_crop', $core->_default_hard_crop ) );
		} else {
			return apply_filters( 'cloudinary_default_soft_crop', apply_filters( 'cloudinary_default_crop', $core->_default_soft_crop ) );
		}
	}
}

/**
 * Get an image HTML tag
 *
 * @param integer $image_id Image ID or URL
 * @param array   $options An array of image options. Any support cloudinary transformation can be passed:
 *
 *   [
 *     'transform' => [
 *        'width'  => 'Image width',
 *        'height' => 'Image height',
 *        'crop'   => 'fill',
 *        'size'   => 'post-thumbnail', // Instead of passing a width and/or height, you can pass a registered WP image size
 *     ]
 *     'srcs' => [ // Corresponds to srcset
 *         '500w' => [
 *            'src' => 'URL or image ID',
 *            'transform' => [
 *              'width' => ....
 *              'crop'  => 'fill',
 *              ...
 *              // All cloudinary arguments can be passed here
 *            ]
 *          ],
 *     ]
 *     'sizes' => [ // Corresponds "sizes" attribute for use with srcet
 *        '(min-width: 1000px) 916px',
 *        '(min-width: 1536px) 1030px',
 *        '100vw',
 *     ],
 *     'atts'  => [ //Extra attributes to be passed to the image tag
 *        'loading' => 'lazy',
 *     ],
 *   ]
 * @return void
 */
function cloudinary_image( $image_id = 0, $options = array() ) {
	// Default args.
	$options = array_replace_recursive(
		array(
			'transform' => [
				'crop'   => 'fill',
				'format' => 'auto',
			],
			'atts'      => [
				'loading' => 'lazy',
			],
		),
		$options
	);

	global $_wp_additional_image_sizes;

	// Just in case width or height were set in the root of the array.

	if ( ! empty( $options['width'] ) ) {
		$options['transform']['width'] = $options['width'];
	}

	if ( ! empty( $options['height'] ) ) {
		$options['transform']['height'] = $options['height'];
	}

	// Check for sizes.
	if ( ! empty( $options['transform']['size'] ) ) {
		$size = JB\Cloudinary\Frontend::get_instance()->get_image_size( $options['transform']['size'] );

		if ( ! empty( $size ) ) {
			$options['transform']['width']  = $size['width'];
			$options['transform']['height'] = $size['height'];

			if ( isset( $size['crop'] ) ) {
				$options['transform']['crop'] = $size['crop'];
			}
		}

		unset( $options['transforms']['size'] );
	}

	// Return.
	$cloudinary_args = array(
		'transform' => $options['transform'],
	);

	if ( ! empty( $options['file_name'] ) ) {
		$cloudinary_args['file_name'] = $options['file_name'];
	}

	$options['original_url'] = $image_id;

	$url = cloudinary_url( $image_id, $cloudinary_args );

	$atts = ! empty( $options['atts'] ) ? $options['atts'] : [];

	$blocklist_atts = [
		'src',
		'width',
		'height',
	];

	foreach ( $blocklist_atts as $key => $value ) {
		if ( isset( $atts[ $key ] ) ) {
			unset( $atts[ $key ] );
		}
	}

	if ( ! empty( $options['transform']['width'] ) ) {
		$atts['width'] = (int) $options['transform']['width'];
	}

	if ( ! empty( $options['transform']['height'] ) ) {
		$atts['height'] = (int) $options['transform']['height'];
	}

	$atts['src'] = $url;

	if ( empty( $atts['alt'] ) && is_numeric( $image_id ) ) {
		$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );

		if ( ! empty( $alt ) ) {
			$atts['alt'] = $alt;
		}
	}

	if ( ! empty( $options['srcs'] ) && ! empty( $options['sizes'] ) ) {
		$srcs = [];

		foreach ( $options['srcs'] as $key => $src_options ) {
			if ( empty( $src_options['src'] ) ) {
				$src_options['src'] = $url;
			}

			$srcs[] = cloudinary_url( $src_options['src'], $src_options ) . ' ' . $key;
		}

		$atts['srcset'] = implode( ', ', $srcs );
		$atts['sizes']  = implode( ', ', $options['sizes'] );
	} else {
		if ( ! empty( $options['transform']['width'] ) && 600 <= $options['transform']['width'] ) {
			$sizes = [
				'100vw',
			];

			for ( $i = 400; $i < $options['transform']['width']; $i += 200 ) {
				$src_options = [
					'transform' => [
						'width'  => $i,
						'format' => 'auto',
					],
				];

				$srcs[] = cloudinary_url( $options['original_url'], $src_options ) . ' ' . $i . 'w';
			}

			$srcs[] = $url . ' ' . $options['transform']['width'] . 'w';

			$atts['srcset'] = implode( ', ', $srcs );
			$atts['sizes']  = implode( ', ', $sizes );
		}
	}

	// Start building the tag.
	$img = '<img';

	// Add attributes.
	foreach ( $atts as $key => $value ) {
		if ( true === $value ) {
			$value = 'true';
		} elseif ( false === $value ) {
			$value = 'false';
		}

		$img .= ' ' . $key . '="' . $value . '"';
	}

	// Finish building the tag.
	$img .= '>';

	// All set, let's return it.
	return $img;
}
