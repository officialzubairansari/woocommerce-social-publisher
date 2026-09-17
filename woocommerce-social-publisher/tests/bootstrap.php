<?php
/**
 * Test Bootstrap & WordPress / WooCommerce Mock Environment
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WSP_VERSION', '1.0.0' );
define( 'WSP_PATH', dirname( __DIR__ ) . '/' );
define( 'WSP_URL', 'https://example.com/wp-content/plugins/woocommerce-social-publisher/' );
define( 'WSP_BASENAME', 'woocommerce-social-publisher/woocommerce-social-publisher.php' );
define( 'WSP_DEFAULT_META_API_VERSION', 'v21.0' );
define( 'AUTH_KEY', 'test_secret_salt_key_1234567890123456' );
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) define( 'MINUTE_IN_SECONDS', 60 );
if ( ! defined( 'HOUR_IN_SECONDS' ) )   define( 'HOUR_IN_SECONDS', 3600 );
if ( ! defined( 'DAY_IN_SECONDS' ) )    define( 'DAY_IN_SECONDS', 86400 );

// Mock Global Options Storage
global $mock_options, $mock_transients, $mock_filters, $mock_actions, $mock_http_responses, $wpdb;
$mock_options = [];
$mock_transients = [];
$mock_filters = [];
$mock_actions = [];
$mock_http_responses = [];

// Mock WordPress functions
function get_option( $key, $default = false ) {
	global $mock_options;
	return isset( $mock_options[ $key ] ) ? $mock_options[ $key ] : $default;
}

function update_option( $key, $val ) {
	global $mock_options;
	$mock_options[ $key ] = $val;
	return true;
}

function delete_option( $key ) {
	global $mock_options;
	unset( $mock_options[ $key ] );
	return true;
}

function get_transient( $key ) {
	global $mock_transients;
	return isset( $mock_transients[ $key ] ) ? $mock_transients[ $key ] : false;
}

function set_transient( $key, $val, $exp = 0 ) {
	global $mock_transients;
	$mock_transients[ $key ] = $val;
	return true;
}

function delete_transient( $key ) {
	global $mock_transients;
	unset( $mock_transients[ $key ] );
	return true;
}

function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	global $mock_filters;
	$mock_filters[ $tag ][] = $callback;
}

function apply_filters( $tag, $value ) {
	global $mock_filters;
	$args = func_get_args();
	array_shift( $args ); // remove $tag
	if ( ! empty( $mock_filters[ $tag ] ) ) {
		foreach ( $mock_filters[ $tag ] as $cb ) {
			$value = call_user_func_array( $cb, $args );
			$args[0] = $value;
		}
	}
	return $value;
}

function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
	add_filter( $tag, $callback, $priority, $accepted_args );
}

function do_action( $tag ) {
	global $mock_filters;
	$args = func_get_args();
	array_shift( $args );
	if ( ! empty( $mock_filters[ $tag ] ) ) {
		foreach ( $mock_filters[ $tag ] as $cb ) {
			call_user_func_array( $cb, $args );
		}
	}
}

function wp_parse_args( $args, $defaults = [] ) {
	return array_merge( $defaults, (array) $args );
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
	return strip_tags( $text );
}

function sanitize_text_field( $str ) {
	return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
}

function sanitize_textarea_field( $str ) {
	return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
}

function esc_html( $str ) {
	return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $str ) {
	return htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return filter_var( $url, FILTER_SANITIZE_URL );
}

function esc_url_raw( $url ) {
	return filter_var( $url, FILTER_SANITIZE_URL );
}

function esc_textarea( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function current_user_can( $capability ) {
	return true;
}

function get_current_user_id() {
	return 1;
}

function wp_create_nonce( $action = -1 ) {
	return substr( md5( $action . AUTH_KEY ), 0, 10 );
}

function wp_verify_nonce( $nonce, $action = -1 ) {
	return $nonce === wp_create_nonce( $action );
}

if ( ! function_exists( 'hash_equals' ) ) {
	function hash_equals( $known, $user ) {
		return (string) $known === (string) $user;
	}
}

function wp_generate_password( $length = 12, $special_chars = true ) {
	return substr( bin2hex( random_bytes( $length ) ), 0, $length );
}

function current_time( $type, $gmt = 0 ) {
	return 'mysql' === $type ? date( 'Y-m-d H:i:s' ) : time();
}

function date_i18n( $format, $timestamp = false ) {
	return date( $format, $timestamp ? $timestamp : time() );
}

function get_permalink( $id ) {
	return 'https://beauties.pk/product/item-' . $id . '/';
}

function wp_get_attachment_image_url( $id, $size = 'thumbnail' ) {
	return 'https://beauties.pk/wp-content/uploads/image-' . $id . '.jpg';
}

function get_the_terms( $post_id, $taxonomy ) {
	if ( 'product_cat' === $taxonomy ) {
		return [ (object) [ 'name' => 'Women Clothing' ], (object) [ 'name' => 'Nightwear' ] ];
	}
	if ( 'product_tag' === $taxonomy ) {
		return [ (object) [ 'name' => 'Comfort' ], (object) [ 'name' => 'Summer' ] ];
	}
	return [];
}

function wp_list_pluck( $list, $field ) {
	$res = [];
	foreach ( $list as $item ) {
		$res[] = is_object( $item ) ? $item->$field : $item[ $field ];
	}
	return $res;
}

function __( $text, $domain = 'default' ) { return $text; }
function esc_html__( $text, $domain = 'default' ) { return $text; }
function esc_attr__( $text, $domain = 'default' ) { return $text; }

function wp_json_encode( $data ) {
	return json_encode( $data );
}

function admin_url( $path = '' ) {
	return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
}

function get_woocommerce_currency() {
	return 'PKR';
}

function get_woocommerce_currency_symbol() {
	return 'PKR';
}

function absint( $val ) {
	return abs( (int) $val );
}

function wp_unslash( $val ) {
	return is_array( $val ) ? array_map( 'wp_unslash', $val ) : stripslashes( (string) $val );
}

function sanitize_html_class( $class, $fallback = '' ) {
	$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', $class );
	$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );
	return '' === $sanitized ? $fallback : $sanitized;
}

function wp_trim_words( $text, $num_words = 55, $more = null ) {
	if ( null === $more ) $more = '&hellip;';
	$words = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
	if ( count( $words ) > $num_words ) {
		array_pop( $words );
		$text = implode( ' ', $words ) . $more;
	} else {
		$text = implode( ' ', $words );
	}
	return $text;
}

function add_query_arg() {
	$args = func_get_args();
	if ( is_array( $args[0] ) ) {
		$params = $args[0];
		$url    = isset( $args[1] ) ? $args[1] : '';
	} else {
		$params = [ $args[0] => $args[1] ];
		$url    = isset( $args[2] ) ? $args[2] : '';
	}
	if ( empty( $params ) ) {
		return $url;
	}
	$sep = strpos( $url, '?' ) !== false ? '&' : '?';
	return $url . $sep . http_build_query( $params );
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

function wp_safe_redirect( $location, $status = 302 ) {
	return true;
}

function wc_get_price_decimals() { return 0; }
function wc_get_price_decimal_separator() { return '.'; }
function wc_get_price_thousand_separator() { return ''; }

// Mock HTTP client for Meta API
function wp_remote_get( $url, $args = [] ) {
	global $mock_http_responses;
	$sorted_patterns = array_keys( $mock_http_responses );
	usort( $sorted_patterns, function( $a, $b ) { return strlen( $b ) - strlen( $a ); } );
	foreach ( $sorted_patterns as $pattern ) {
		if ( strpos( $url, $pattern ) !== false ) {
			return $mock_http_responses[ $pattern ];
		}
	}
	return [ 'response' => [ 'code' => 200 ], 'body' => json_encode( [ 'data' => [] ] ) ];
}

function wp_remote_post( $url, $args = [] ) {
	global $mock_http_responses;
	$sorted_patterns = array_keys( $mock_http_responses );
	usort( $sorted_patterns, function( $a, $b ) { return strlen( $b ) - strlen( $a ); } );
	foreach ( $sorted_patterns as $pattern ) {
		if ( strpos( $url, $pattern ) !== false ) {
			return $mock_http_responses[ $pattern ];
		}
	}
	return [ 'response' => [ 'code' => 200 ], 'body' => json_encode( [ 'id' => 'mock_123456789' ] ) ];
}

function is_wp_error( $thing ) {
	return is_object( $thing ) && get_class( $thing ) === 'WP_Error';
}

function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 200;
}

function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}

class WP_Error {
	protected $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}

// Mock WC_Product Class
class WC_Product {
	public $id = 101;
	public $name = 'Shein – Text Pattern Women’s Nightgown';
	public $regular_price = '4500';
	public $sale_price = '3500';
	public $sku = 'SH-NG-001';
	public $short_desc = 'Soft and comfortable printed nightgown.';
	public $desc = 'Premium cotton women nightgown designed for ultimate comfort and breathability.';
	public $image_id = 55;
	public $gallery_ids = [ 55, 56, 57 ];
	public $type = 'simple';
	public $on_sale = true;

	public function get_id() { return $this->id; }
	public function get_name() { return $this->name; }
	public function get_sku() { return $this->sku; }
	public function get_regular_price() { return $this->regular_price; }
	public function get_sale_price() { return $this->sale_price; }
	public function get_price() { return ! empty( $this->sale_price ) ? $this->sale_price : $this->regular_price; }
	public function is_on_sale() { return $this->on_sale; }
	public function is_type( $t ) { return $this->type === $t; }
	public function get_short_description() { return $this->short_desc; }
	public function get_description() { return $this->desc; }
	public function get_image_id() { return $this->image_id; }
	public function get_gallery_image_ids() { return $this->gallery_ids; }
	public function get_variation_regular_price( $min_or_max, $for_display ) { return '4500'; }
	public function get_variation_sale_price( $min_or_max, $for_display ) { return '3500'; }
}

function wc_get_product( $id ) {
	$p = new WC_Product();
	$p->id = (int) $id;
	return $p;
}

// Mock WordPress Database
class Mock_WPDB {
	public $prefix = 'wp_';
	public $posts = [];
	public $last_id = 0;
	public $insert_id = 0;

	public function prepare( $query, $args ) {
		$params = is_array( $args ) ? $args : array_slice( func_get_args(), 1 );
		foreach ( $params as $val ) {
			$escaped = is_numeric( $val ) ? $val : "'" . addslashes( (string) $val ) . "'";
			$query = preg_replace( '/%[dsf]/', $escaped, $query, 1 );
		}
		return $query;
	}

	public function insert( $table, $data, $format = null ) {
		$this->last_id++;
		$this->insert_id = $this->last_id;
		$data['id'] = $this->last_id;
		$this->posts[ $this->last_id ] = (object) $data;
		return 1;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$id = $where['id'];
		if ( isset( $this->posts[ $id ] ) ) {
			foreach ( $data as $k => $v ) {
				$this->posts[ $id ]->$k = $v;
			}
			return 1;
		}
		return 0;
	}

	public function get_row( $sql ) {
		foreach ( $this->posts as $post ) {
			$match = true;
			if ( isset( $post->product_id ) && strpos( $sql, "product_id = " . $post->product_id ) === false ) {
				$match = false;
			}
			if ( isset( $post->platform ) && strpos( $sql, "platform = '" . $post->platform . "'" ) === false ) {
				$match = false;
			}
			if ( isset( $post->status ) && strpos( $sql, "status = '" . $post->status . "'" ) === false ) {
				$match = false;
			}
			if ( $match && ( isset( $post->product_id ) || isset( $post->id ) ) ) {
				return $post;
			}
			if ( strpos( $sql, "WHERE id = " . $post->id ) !== false ) {
				return $post;
			}
		}
		return null;
	}

	public function get_results( $sql ) {
		return array_values( $this->posts );
	}

	public function get_var( $sql ) {
		return count( $this->posts );
	}

	public function delete( $table, $where, $where_format = null ) {
		$id = $where['id'];
		unset( $this->posts[ $id ] );
		return 1;
	}
}

$wpdb = new Mock_WPDB();

// Require Plugin Includes
require_once WSP_PATH . 'includes/class-wsp-security.php';
require_once WSP_PATH . 'includes/class-wsp-settings.php';
require_once WSP_PATH . 'includes/class-wsp-database.php';
require_once WSP_PATH . 'includes/class-wsp-product-data.php';
require_once WSP_PATH . 'includes/class-wsp-template-engine.php';
require_once WSP_PATH . 'includes/class-wsp-caption-builder.php';
require_once WSP_PATH . 'includes/class-wsp-image-validator.php';
require_once WSP_PATH . 'includes/class-wsp-meta-api.php';
require_once WSP_PATH . 'includes/class-wsp-meta-auth.php';
require_once WSP_PATH . 'includes/class-wsp-facebook.php';
require_once WSP_PATH . 'includes/class-wsp-instagram.php';
require_once WSP_PATH . 'includes/class-wsp-publisher.php';
require_once WSP_PATH . 'includes/class-wsp-duplicate-checker.php';
require_once WSP_PATH . 'includes/class-wsp-queue.php';
require_once WSP_PATH . 'includes/class-wsp-scheduler.php';
require_once WSP_PATH . 'includes/class-wsp-history.php';
require_once WSP_PATH . 'admin/class-wsp-bulk-actions.php';
require_once WSP_PATH . 'admin/class-wsp-preview-page.php';

// Set Defaults
WSP_Settings::set_defaults();
