<?php
/**
 * Facebook Page Publisher using Meta Graph API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Facebook {

	/**
	 * Publish a post to the connected Facebook Page
	 *
	 * @param array $args [ 'caption' => '', 'image_url' => '', 'gallery_urls' => [], 'product_url' => '' ]
	 * @return array [ 'success' => bool, 'post_id' => string, 'error' => string, 'raw' => mixed ]
	 */
	public static function publish( $args ) {
		$page_id    = WSP_Settings::get( 'fb_page_id' );
		$page_token = WSP_Settings::get( 'fb_page_access_token' );

		if ( empty( $page_id ) || empty( $page_token ) ) {
			return [
				'success' => false,
				'post_id' => '',
				'error'   => __( 'Facebook Page is not connected. Please configure Facebook settings.', 'woocommerce-social-publisher' ),
				'raw'     => null,
			];
		}

		$caption      = isset( $args['caption'] ) ? $args['caption'] : '';
		$image_url    = isset( $args['image_url'] ) ? $args['image_url'] : '';
		$gallery_urls = isset( $args['gallery_urls'] ) && is_array( $args['gallery_urls'] ) ? array_filter( $args['gallery_urls'] ) : [];
		$product_url  = isset( $args['product_url'] ) ? $args['product_url'] : '';

		$api = new WSP_Meta_API();

		// Case 1: Multi-image carousel / multi-photo post
		if ( count( $gallery_urls ) > 1 ) {
			return self::publish_multi_photo( $page_id, $page_token, $gallery_urls, $caption, $api );
		}

		// Case 2: Single Photo post
		if ( ! empty( $image_url ) ) {
			$res = $api->post( $page_id . '/photos', [
				'url'     => $image_url,
				'message' => $caption,
			], $page_token );

			if ( $res['success'] && ! empty( $res['data']['id'] ) ) {
				$post_id = ! empty( $res['data']['post_id'] ) ? $res['data']['post_id'] : $res['data']['id'];
				return [
					'success' => true,
					'post_id' => (string) $post_id,
					'error'   => '',
					'raw'     => $res['data'],
				];
			}

			return [
				'success' => false,
				'post_id' => '',
				'error'   => $res['error'],
				'raw'     => $res['raw'],
			];
		}

		// Case 3: Text + Link post
		$body = [ 'message' => $caption ];
		if ( ! empty( $product_url ) ) {
			$body['link'] = $product_url;
		}

		$res = $api->post( $page_id . '/feed', $body, $page_token );

		if ( $res['success'] && ! empty( $res['data']['id'] ) ) {
			return [
				'success' => true,
				'post_id' => (string) $res['data']['id'],
				'error'   => '',
				'raw'     => $res['data'],
			];
		}

		return [
			'success' => false,
			'post_id' => '',
			'error'   => $res['error'],
			'raw'     => $res['raw'],
		];
	}

	/**
	 * Publish multiple photos attached to a single Facebook Page feed post
	 *
	 * @param string $page_id
	 * @param string $page_token
	 * @param array $images
	 * @param string $caption
	 * @param WSP_Meta_API $api
	 * @return array
	 */
	protected static function publish_multi_photo( $page_id, $page_token, $images, $caption, $api ) {
		$attached_media = [];

		foreach ( $images as $img_url ) {
			$upload_res = $api->post( $page_id . '/photos', [
				'url'       => $img_url,
				'published' => 'false',
			], $page_token );

			if ( $upload_res['success'] && ! empty( $upload_res['data']['id'] ) ) {
				$attached_media[] = [ 'media_fbid' => $upload_res['data']['id'] ];
			}
		}

		if ( empty( $attached_media ) ) {
			return [
				'success' => false,
				'post_id' => '',
				'error'   => __( 'Failed to upload photos for multi-photo post to Facebook.', 'woocommerce-social-publisher' ),
				'raw'     => null,
			];
		}

		$feed_body = [
			'message' => $caption,
		];

		// Meta Graph API accepts indexed attached_media[0]={"media_fbid":"123"}
		foreach ( $attached_media as $idx => $item ) {
			$feed_body[ "attached_media[{$idx}]" ] = wp_json_encode( $item );
		}

		$feed_res = $api->post( $page_id . '/feed', $feed_body, $page_token );

		if ( $feed_res['success'] && ! empty( $feed_res['data']['id'] ) ) {
			return [
				'success' => true,
				'post_id' => (string) $feed_res['data']['id'],
				'error'   => '',
				'raw'     => $feed_res['data'],
			];
		}

		return [
			'success' => false,
			'post_id' => '',
			'error'   => $feed_res['error'],
			'raw'     => $feed_res['raw'],
		];
	}
}
