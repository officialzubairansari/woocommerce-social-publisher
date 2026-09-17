<?php
/**
 * Official Meta Graph API HTTP Client
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Meta_API {

	const BASE_URL = 'https://graph.facebook.com';

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * Constructor
	 *
	 * @param string|null $version
	 */
	public function __construct( $version = null ) {
		if ( null !== $version && ! empty( $version ) ) {
			$this->version = $version;
		} else {
			$this->version = WSP_Settings::get( 'meta_api_version', defined( 'WSP_DEFAULT_META_API_VERSION' ) ? WSP_DEFAULT_META_API_VERSION : 'v21.0' );
		}
	}

	/**
	 * Get API Base Endpoint URL with version
	 *
	 * @param string $endpoint
	 * @return string
	 */
	public function get_url( $endpoint ) {
		$endpoint = ltrim( $endpoint, '/' );
		return sprintf( '%s/%s/%s', self::BASE_URL, $this->version, $endpoint );
	}

	/**
	 * Perform GET request to Meta Graph API
	 *
	 * @param string $endpoint
	 * @param array $params
	 * @param string|null $access_token
	 * @return array [ 'success' => bool, 'data' => array, 'error' => string, 'code' => int ]
	 */
	public function get( $endpoint, $params = [], $access_token = null ) {
		if ( ! empty( $access_token ) ) {
			$params['access_token'] = $access_token;
		}

		$url = add_query_arg( $params, $this->get_url( $endpoint ) );

		$response = wp_remote_get( $url, [
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent'  => 'WooCommerce-Social-Publisher/' . WSP_VERSION,
		] );

		return $this->parse_response( $response );
	}

	/**
	 * Perform POST request to Meta Graph API
	 *
	 * @param string $endpoint
	 * @param array $body
	 * @param string|null $access_token
	 * @return array [ 'success' => bool, 'data' => array, 'error' => string, 'code' => int ]
	 */
	public function post( $endpoint, $body = [], $access_token = null ) {
		if ( ! empty( $access_token ) ) {
			$body['access_token'] = $access_token;
		}

		$url = $this->get_url( $endpoint );

		$response = wp_remote_post( $url, [
			'timeout'     => 60,
			'redirection' => 5,
			'httpversion' => '1.1',
			'user-agent'  => 'WooCommerce-Social-Publisher/' . WSP_VERSION,
			'body'        => $body,
		] );

		return $this->parse_response( $response );
	}

	/**
	 * Parse and normalize WP HTTP response
	 *
	 * @param array|WP_Error $response
	 * @return array [ 'success' => bool, 'data' => array, 'error' => string, 'code' => int, 'raw' => mixed ]
	 */
	protected function parse_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'data'    => [],
				'error'   => sprintf( __( 'Network / HTTP Error: %s', 'woocommerce-social-publisher' ), $response->get_error_message() ),
				'code'    => 0,
				'raw'     => null,
			];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$raw_body    = wp_remote_retrieve_body( $response );
		$data        = json_decode( $raw_body, true );

		if ( $status_code >= 200 && $status_code < 300 && is_array( $data ) && ! isset( $data['error'] ) ) {
			return [
				'success' => true,
				'data'    => $data,
				'error'   => '',
				'code'    => $status_code,
				'raw'     => $data,
			];
		}

		// Handle Meta Graph API Error Envelope
		$error_msg = __( 'Unknown Meta API error.', 'woocommerce-social-publisher' );
		$meta_code = 0;

		if ( isset( $data['error'] ) && is_array( $data['error'] ) ) {
			$err = $data['error'];
			$meta_code     = isset( $err['code'] ) ? (int) $err['code'] : $status_code;
			$subcode       = isset( $err['error_subcode'] ) ? (int) $err['error_subcode'] : 0;
			$type          = isset( $err['type'] ) ? $err['type'] : 'OAuthException';
			$base_msg      = isset( $err['message'] ) ? $err['message'] : 'An error occurred with Meta API.';
			$user_msg      = ! empty( $err['error_user_msg'] ) ? $err['error_user_msg'] : '';

			$error_msg = self::format_meta_error( $base_msg, $meta_code, $subcode, $user_msg, $type );
		} elseif ( ! empty( $raw_body ) ) {
			$error_msg = sprintf( __( 'HTTP %d: %s', 'woocommerce-social-publisher' ), $status_code, wp_strip_all_tags( $raw_body ) );
		} else {
			$error_msg = sprintf( __( 'HTTP Error %d with empty response from Meta.', 'woocommerce-social-publisher' ), $status_code );
		}

		return [
			'success' => false,
			'data'    => is_array( $data ) ? $data : [],
			'error'   => $error_msg,
			'code'    => $status_code,
			'raw'     => $data,
		];
	}

	/**
	 * Provide user-friendly diagnostic guidance for common Meta error codes
	 *
	 * @param string $message
	 * @param int $code
	 * @param int $subcode
	 * @param string $user_msg
	 * @param string $type
	 * @return string
	 */
	public static function format_meta_error( $message, $code, $subcode = 0, $user_msg = '', $type = '' ) {
		$guidance = '';

		switch ( $code ) {
			case 190:
				if ( 463 === $subcode || 467 === $subcode ) {
					$guidance = __( 'Your Meta Access Token has expired. Please reconnect your account in Settings -> Facebook.', 'woocommerce-social-publisher' );
				} else {
					$guidance = __( 'Invalid Meta Access Token or session revoked. Please reconnect in Settings -> Facebook.', 'woocommerce-social-publisher' );
				}
				break;
			case 200:
			case 298:
				$guidance = __( 'Permission denied. Ensure your Meta App has approved "pages_manage_posts", "pages_read_engagement", and "instagram_content_publish" permissions.', 'woocommerce-social-publisher' );
				break;
			case 10:
			case 100:
				if ( stripos( $message, 'aspect ratio' ) !== false ) {
					$guidance = __( 'Instagram requires image aspect ratio between 4:5 (portrait) and 1.91:1 (landscape). Please adjust the product image.', 'woocommerce-social-publisher' );
				} elseif ( stripos( $message, 'reach the URL' ) !== false || stripos( $message, 'download' ) !== false ) {
					$guidance = __( 'Meta could not download the image. Ensure the image URL is public (not on localhost or a private intranet).', 'woocommerce-social-publisher' );
				}
				break;
			case 368:
				$guidance = __( 'Temporary Meta publishing block or rate limit. Please wait a few minutes before retrying.', 'woocommerce-social-publisher' );
				break;
			case 4:
			case 17:
			case 32:
			case 613:
				$guidance = __( 'Meta API Rate Limit reached. Please wait before publishing further posts.', 'woocommerce-social-publisher' );
				break;
		}

		$formatted = sprintf( '[Meta Error #%d%s] %s', $code, $subcode ? ":{$subcode}" : '', $message );
		if ( ! empty( $user_msg ) ) {
			$formatted .= ' ' . $user_msg;
		}
		if ( ! empty( $guidance ) ) {
			$formatted .= ' — ' . $guidance;
		}

		return $formatted;
	}
}
