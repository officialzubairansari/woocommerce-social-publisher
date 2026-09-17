<?php
/**
 * Unified Multi-Platform Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Publisher {

	/**
	 * Publish a single product to specified platform ('facebook', 'instagram', or 'both')
	 *
	 * @param int $product_id
	 * @param string $platform 'facebook', 'instagram', or 'both'
	 * @param array $options [ 'caption' => '', 'image_url' => '', 'gallery_urls' => [], 'publish_again' => false ]
	 * @return array [ 'facebook' => array|null, 'instagram' => array|null ]
	 */
	public static function publish_product( $product_id, $platform, $options = [] ) {
		$results = [
			'facebook'  => null,
			'instagram' => null,
		];

		if ( in_array( $platform, [ 'facebook', 'both' ], true ) ) {
			$results['facebook'] = self::dispatch_single( $product_id, 'facebook', $options );
		}

		if ( in_array( $platform, [ 'instagram', 'both' ], true ) ) {
			$results['instagram'] = self::dispatch_single( $product_id, 'instagram', $options );
		}

		return $results;
	}

	/**
	 * Dispatch publishing job for a specific platform
	 *
	 * @param int $product_id
	 * @param string $platform 'facebook' or 'instagram'
	 * @param array $options
	 * @return array [ 'success' => bool, 'db_id' => int, 'platform' => string, 'post_id' => string, 'error' => string ]
	 */
	protected static function dispatch_single( $product_id, $platform, $options = [] ) {
		$allow_duplicate = ! empty( $options['publish_again'] );

		// 1. Duplicate check
		$dup_check = WSP_Duplicate_Checker::is_allowed( $product_id, $platform, $allow_duplicate );
		if ( ! $dup_check['allowed'] ) {
			return [
				'success'  => false,
				'db_id'    => 0,
				'platform' => $platform,
				'post_id'  => '',
				'error'    => $dup_check['warning'],
			];
		}

		// 2. Resolve Product Data & Caption
		$product_data = new WSP_Product_Data( $product_id );
		if ( ! $product_data->is_valid() ) {
			return [
				'success'  => false,
				'db_id'    => 0,
				'platform' => $platform,
				'post_id'  => '',
				'error'    => sprintf( __( 'Invalid WooCommerce product ID %d.', 'woocommerce-social-publisher' ), $product_id ),
			];
		}

		$caption = ! empty( $options['caption'] )
			? sanitize_textarea_field( $options['caption'] )
			: WSP_Caption_Builder::build( $product_id );

		// 3. Resolve Media URLs (Publish all images: Featured + Product Gallery)
		$gallery_urls = ! empty( $options['gallery_urls'] ) && is_array( $options['gallery_urls'] )
			? array_values( array_unique( array_filter( array_map( 'esc_url_raw', $options['gallery_urls'] ) ) ) )
			: [];

		// If no gallery list explicitly passed, automatically retrieve all images (featured + gallery)
		if ( empty( $gallery_urls ) ) {
			$all_images = $product_data->get_all_image_urls();
			if ( ! empty( $all_images ) ) {
				$gallery_urls = $all_images;
			}
		}

		// Single primary image URL (fallback or first of gallery)
		$image_url = ! empty( $gallery_urls )
			? $gallery_urls[0]
			: ( ! empty( $options['image_url'] ) ? esc_url_raw( $options['image_url'] ) : $product_data->get_featured_image_url() );

		$post_type = count( $gallery_urls ) > 1 ? 'carousel' : ( ! empty( $image_url ) ? 'single_image' : 'text_link' );

		// 4. Validate Media
		if ( ! empty( $gallery_urls ) ) {
			foreach ( $gallery_urls as $img_item_url ) {
				$validation = WSP_Image_Validator::validate( $img_item_url, $platform );
				if ( ! $validation['valid'] ) {
					return [
						'success'  => false,
						'db_id'    => 0,
						'platform' => $platform,
						'post_id'  => '',
						'error'    => sprintf( __( 'Image error (%s): %s', 'woocommerce-social-publisher' ), esc_url( $img_item_url ), implode( '; ', $validation['errors'] ) ),
					];
				}
			}
		} elseif ( ! empty( $image_url ) ) {
			$validation = WSP_Image_Validator::validate( $image_url, $platform );
			if ( ! $validation['valid'] ) {
				return [
					'success'  => false,
					'db_id'    => 0,
					'platform' => $platform,
					'post_id'  => '',
					'error'    => implode( '; ', $validation['errors'] ),
				];
			}
		} elseif ( 'instagram' === $platform ) {
			return [
				'success'  => false,
				'db_id'    => 0,
				'platform' => $platform,
				'post_id'  => '',
				'error'    => __( 'Product has no image. Instagram requires an image to create a post.', 'woocommerce-social-publisher' ),
			];
		}

		// 5. Create History Database Record (Status: Processing)
		$db_id = WSP_Database::insert_post( [
			'product_id' => $product_id,
			'platform'   => $platform,
			'post_type'  => $post_type,
			'caption'    => $caption,
			'media_url'  => $image_url,
			'status'     => 'processing',
			'attempts'   => 1,
		] );

		// 6. Execute Meta Publishing Call
		$payload = [
			'caption'      => $caption,
			'image_url'    => $image_url,
			'gallery_urls' => $gallery_urls,
			'product_url'  => $product_data->get_permalink(),
		];

		if ( 'facebook' === $platform ) {
			$res = WSP_Facebook::publish( $payload );
		} else {
			$res = WSP_Instagram::publish( $payload );
		}

		// 7. Update History Database Record with Result
		if ( $res['success'] ) {
			WSP_Database::update_post( $db_id, [
				'status'                => 'published',
				'published_at'          => current_time( 'mysql' ),
				'external_post_id'      => $res['post_id'],
				'external_container_id' => isset( $res['container_id'] ) ? $res['container_id'] : null,
				'error_message'         => null,
			] );

			return [
				'success'  => true,
				'db_id'    => $db_id,
				'platform' => $platform,
				'post_id'  => $res['post_id'],
				'error'    => '',
			];
		}

		// On Failure
		WSP_Database::update_post( $db_id, [
			'status'        => 'failed',
			'error_message' => $res['error'],
		] );

		return [
			'success'  => false,
			'db_id'    => $db_id,
			'platform' => $platform,
			'post_id'  => '',
			'error'    => $res['error'],
		];
	}

	/**
	 * Retry a previously failed publishing job
	 *
	 * @param int $db_id
	 * @return array [ 'success' => bool, 'error' => string ]
	 */
	public static function retry( $db_id ) {
		$record = WSP_Database::get_post( $db_id );
		if ( ! $record ) {
			return [ 'success' => false, 'error' => __( 'Log record not found.', 'woocommerce-social-publisher' ) ];
		}

		$product_data = new WSP_Product_Data( $record->product_id );
		$product_url  = $product_data->is_valid() ? $product_data->get_permalink() : '';

		// Update attempts & set to processing
		WSP_Database::update_post( $db_id, [
			'status'   => 'processing',
			'attempts' => $record->attempts + 1,
		] );

		$payload = [
			'caption'     => $record->caption,
			'image_url'   => $record->media_url,
			'product_url' => $product_url,
		];

		if ( 'facebook' === $record->platform ) {
			$res = WSP_Facebook::publish( $payload );
		} else {
			$res = WSP_Instagram::publish( $payload );
		}

		if ( $res['success'] ) {
			WSP_Database::update_post( $db_id, [
				'status'                => 'published',
				'published_at'          => current_time( 'mysql' ),
				'external_post_id'      => $res['post_id'],
				'external_container_id' => isset( $res['container_id'] ) ? $res['container_id'] : null,
				'error_message'         => null,
			] );
			return [ 'success' => true, 'error' => '' ];
		}

		WSP_Database::update_post( $db_id, [
			'status'        => 'failed',
			'error_message' => $res['error'],
		] );

		return [ 'success' => false, 'error' => $res['error'] ];
	}
}
