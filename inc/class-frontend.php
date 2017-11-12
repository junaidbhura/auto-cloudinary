<?php

namespace JB\Cloudinary;

class Frontend {

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
		// Check if we need to replace content images on the front-end.
		$replace_content = false;
		if ( '1' === get_option( 'cloudinary_content_images' ) && apply_filters( 'cloudinary_content_images', true ) ) {
			$replace_content = true;
		}

		if ( apply_filters( 'cloudinary_filter_the_content', $replace_content ) ) {
			add_filter( 'the_content', 'cloudinary_update_content_images', 999 );
		}
		if ( apply_filters( 'cloudinary_filter_image_downsize', $replace_content ) ) {
			add_filter( 'image_downsize', array( $this, 'filter_image_downsize' ), 999, 3 );
		}
		if ( apply_filters( 'cloudinary_filter_wp_calculate_image_srcset', $replace_content ) ) {
			add_filter( 'wp_calculate_image_srcset', array( $this, 'filter_wp_calculate_image_srcset' ), 999, 5 );
		}
	}

	/**
	 * Filter image_downsize to use Cloudinary.
	 *
	 * @param  bool  $downsize
	 * @param  int   $id
	 * @param  mixed $size
	 * @return array|bool
	 */
	public function filter_image_downsize( $downsize, $id, $size ) {
		if ( 'full' === $size || is_array( $size ) ) {
			return false;
		}

		$dimensions = $this->get_image_size( $size );
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
	public function filter_wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( ! empty( $sources ) ) {
			foreach ( $sources as $key => $source ) {
				$dimensions = $this->get_srcset_dimensions( $image_meta, $source );
				$transform  = array();
				if ( ! empty( $dimensions ) ) {
					$transform = array(
						'transform' => $dimensions,
					);
				}
				$transform = apply_filters( 'cloudinary_image_srcset_transform', $transform, $image_src, $attachment_id );

				if ( ! empty( $transform ) ) {
					$sources[ $key ]['url'] = cloudinary_url( $image_src, $transform );
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
	public function get_srcset_dimensions( $image_meta = array(), $source = array() ) {
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
	public function get_image_sizes() {
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
	public function get_image_size( $size ) {
		$sizes = $this->get_image_sizes();

		if ( ! empty( $sizes ) && isset( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		}

		return false;
	}

}
