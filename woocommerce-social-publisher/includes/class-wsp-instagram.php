<?php
/**
 * Instagram Professional Content Publisher using Meta Graph API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Instagram {

	const MAX_POLL_ATTEMPTS = 6;
	const POLL_DELAY_SECS   = 2;

	/**
	 * Publish a post to connected Instagram Business or Creator account
	 *
	 * @param array $args [ 'caption' => '', 'image_url' => '', 'gallery_urls' => [] ]
	 * @return array [ 'success' => bool, 'post_id' => string, 'container_id' => string, 'error' => string, 'raw' => mixed ]
	 */
	public static function publish( $args ) {
		$ig_id      = WSP_Settings::get( 'ig_account_id' );
		$page_token = WSP_Settings::get( 'fb_page_access_token' );

		if ( empty( $ig_id ) || empty( $page_token ) ) {
			return [
				'success'      => false,
				'post_id'      => '',
				'container_id' => '',
				'error'        => __( 'Instagram account is not connected or missing permissions. An Instagram Professional (Business or Creator) account linked to a Facebook Page is required.', 'woocommerce-social-publisher' ),
				'raw'          => null,
			];
		}

		$caption      = isset( $args['caption'] ) ? $args['caption'] : '';
		$image_url    = isset( $args['image_url'] ) ? $args['image_url'] : '';
		$gallery_urls = isset( $args['gallery_urls'] ) && is_array( $args['gallery_urls'] ) ? array_filter( $args['gallery_urls'] ) : [];

		if ( empty( $image_url ) && empty( $gallery_urls ) ) {
			return [
				'success'      => false,
				'post_id'      => '',
				'container_id' => '',
				'error'        => __( 'Instagram requires an image. Products without images cannot be published to Instagram.', 'woocommerce-social-publisher' ),
				'raw'          => null,
			];
		}

		$api = new WSP_Meta_API();

		// Handle Carousel if multiple images provided
		if ( count( $gallery_urls ) > 1 ) {
			return self::publish_carousel( $ig_id, $page_token, $gallery_urls, $caption, $api );
		}

		// Single image container creation
		$create_res = $api->post( $ig_id . '/media', [
			'image_url' => $image_url,
			'caption'   => $caption,
		], $page_token );

		if ( ! $create_res['success'] || empty( $create_res['data']['id'] ) ) {
			return [
				'success'      => false,
				'post_id'      => '',
				'container_id' => '',
				'error'        => sprintf( __( 'Failed to create Instagram container: %s', 'woocommerce-social-publisher' ), $create_res['error'] ),
				'raw'          => $create_res['raw'],
			];
		}

		$container_id = (string) $create_res['data']['id'];

		// Wait and verify container readiness
		$status_res = self::wait_for_container( $container_id, $page_token, $api );
		if ( ! $status_res['success'] ) {
			return [
				'success'      => false,
				'post_id'      => '',
				'container_id' => $container_id,
				'error'        => $status_res['error'],
				'raw'          => $status_res['raw'],
			];
		}

		// Publish the container
		$publish_res = $api->post( $ig_id . '/media_publish', [
			'creation_id' => $container_id,
		], $page_token );

		if ( ! $publish_res['success'] || empty( $publish_res['data']['id'] ) ) {
			return [
				'success'      => false,
				'post_id'      => '',
				'container_id' => $container_id,
				'error'        => sprintf( __( 'Failed to publish Instagram media: %s', 'woocommerce-social-publisher' ), $publish_res['error'] ),
				'raw'          => $publish_res['raw'],
			];
		}

		return [
			'success'      => true,
			'post_id'      => (string) $publish_res['data']['id'],
			'container_id' => $container_id,
			'error'        => '',
			'raw'          => $publish_res['data'],
		];
	}

	/**
	 * Publish multi-image carousel to Instagram
	 *
	 * @param string $ig_id
	 * @param string $page_token
	 * @param array $images
	 * @param string $caption
	 * @param WSP_Meta_API $api
	 * @return array
	 */
	protected static function publish_carousel( $ig_id, $page_token, $images, $caption, $api ) {
		// Limit to max 10 images as per Meta Instagram specs
		$images = array_slice( $images, 0, 10 );
		$child_container_ids = [];

		foreach ( $images as $img_url ) {
			$item_res = $api->post( $ig_id . '/media', [
				'image_url'        => $img_url,
				'is_carousel_item' => 'true',
			], $page_token );

			if ( $item_res['success'] && ! empty( $item_res['data']['id'] ) ) {
				$child_id = (string) $item_res['data']['id'];
				$child_status = self::wait_for_container( $child_id, $page_token, $api );
				if ( $child_status['success'] ) {
					$child_container_ids[] = $child_id;
				}
			}
		}

		if ( count( $child_container_ids ) < 2 ) {
			// Fall back to single image publish if carousel item containers failed
			$single_url = ! empty( $images[0] ) ? $images[0] : '';
			return self::publish( [ 'caption' => $caption, 'image_url' => $single_url ] );
		}

		// Create Parent Carousel Container
		$carousel_res = $api->post( $ig_id . '/media', [
			'media_type' => 'CAROUSEL',
			'children'   => implode( ',', $child_container_ids ),
			'caption'    => $caption,
		], $page_token );

		if ( ! $carousel_res['success'] || empty( $carousel_res['data']['id'] ) ) {
			return [
				'success'      => false,
				'post_id'      => '',
				'container_id' => '',
				'error'        => sprintf( __( 'Failed to create Instagram carousel container: %s', 'woocommerce-social-publisher' ), $carousel_res['error'] ),
				'raw'          => $carousel_res['raw'],
			];
		}

		$parent_container_id = (string) $carousel_res['data']['id'];

		$parent_status = self::wait_for_container( $parent_container_id, $page_token, $api );
		if ( ! $parent_status['success'] ) {
			return [
				'success'      => false,
				'post_id'      => '',
				'container_id' => $parent_container_id,
				'error'        => $parent_status['error'],
				'raw'          => $parent_status['raw'],
			];
		}

		// Publish carousel container
		$publish_res = $api->post( $ig_id . '/media_publish', [
			'creation_id' => $parent_container_id,
		], $page_token );

		if ( ! $publish_res['success'] || empty( $publish_res['data']['id'] ) ) {
			return [
				'success'      => false,
				'post_id'      => '',
				'container_id' => $parent_container_id,
				'error'        => sprintf( __( 'Failed to publish Instagram carousel: %s', 'woocommerce-social-publisher' ), $publish_res['error'] ),
				'raw'          => $publish_res['raw'],
			];
		}

		return [
			'success'      => true,
			'post_id'      => (string) $publish_res['data']['id'],
			'container_id' => $parent_container_id,
			'error'        => '',
			'raw'          => $publish_res['data'],
		];
	}

	/**
	 * Poll container status until ready (FINISHED) or error
	 *
	 * @param string $container_id
	 * @param string $page_token
	 * @param WSP_Meta_API $api
	 * @return array [ 'success' => bool, 'status' => string, 'error' => string, 'raw' => mixed ]
	 */
	protected static function wait_for_container( $container_id, $page_token, $api ) {
		$attempts = 0;

		while ( $attempts < self::MAX_POLL_ATTEMPTS ) {
			$res = $api->get( $container_id, [ 'fields' => 'status_code,status' ], $page_token );

			if ( $res['success'] && ! empty( $res['data']['status_code'] ) ) {
				$status = strtoupper( $res['data']['status_code'] );

				if ( 'FINISHED' === $status ) {
					return [ 'success' => true, 'status' => $status, 'error' => '', 'raw' => $res['data'] ];
				}

				if ( 'ERROR' === $status || 'EXPIRED' === $status ) {
					return [
						'success' => false,
						'status'  => $status,
						'error'   => sprintf( __( 'Instagram media processing failed with status: %s', 'woocommerce-social-publisher' ), $status ),
						'raw'     => $res['data'],
					];
				}
			}

			$attempts++;
			if ( $attempts < self::MAX_POLL_ATTEMPTS ) {
				sleep( self::POLL_DELAY_SECS );
			}
		}

		// In some Meta versions, images finish instantaneously without status_code field. Proceed if no error was returned.
		return [ 'success' => true, 'status' => 'ASSUMED_READY', 'error' => '', 'raw' => null ];
	}
}
