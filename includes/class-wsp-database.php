<?php
/**
 * Database Management for WooCommerce Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Database {

	const DB_VERSION = '1.0.0';
	const TABLE_POSTS = 'wsp_social_posts';

	/**
	 * Get table name with WordPress prefix
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_POSTS;
	}

	/**
	 * Create or update custom database tables using dbDelta
	 */
	public static function create_tables() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			product_id bigint(20) unsigned NOT NULL,
			platform varchar(50) NOT NULL,
			post_type varchar(50) NOT NULL DEFAULT 'single_image',
			caption longtext NOT NULL,
			media_url text NULL,
			media_id text NULL,
			external_post_id varchar(255) NULL,
			external_container_id varchar(255) NULL,
			status varchar(50) NOT NULL DEFAULT 'draft',
			scheduled_at datetime NULL,
			published_at datetime NULL,
			error_message text NULL,
			attempts int(11) NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY product_platform (product_id, platform),
			KEY status (status),
			KEY scheduled_at (scheduled_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'wsp_db_version', self::DB_VERSION );
	}

	/**
	 * Insert a new social post record
	 *
	 * @param array $data
	 * @return int|false
	 */
	public static function insert_post( $data ) {
		global $wpdb;

		$defaults = [
			'product_id'            => 0,
			'platform'              => 'facebook',
			'post_type'             => 'single_image',
			'caption'               => '',
			'media_url'             => '',
			'media_id'              => '',
			'external_post_id'      => null,
			'external_container_id' => null,
			'status'                => 'draft',
			'scheduled_at'          => null,
			'published_at'          => null,
			'error_message'         => null,
			'attempts'              => 0,
			'created_by'            => get_current_user_id(),
			'created_at'            => current_time( 'mysql' ),
			'updated_at'            => current_time( 'mysql' ),
		];

		$row = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			self::get_table_name(),
			$row,
			[
				'%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
				'%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s'
			]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing social post record
	 *
	 * @param int $id
	 * @param array $data
	 * @return bool
	 */
	public static function update_post( $id, $data ) {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );
		$formats = [];

		foreach ( $data as $key => $val ) {
			if ( in_array( $key, [ 'id', 'product_id', 'attempts', 'created_by' ], true ) ) {
				$formats[] = '%d';
			} else {
				$formats[] = '%s';
			}
		}

		$result = $wpdb->update(
			self::get_table_name(),
			$data,
			[ 'id' => absint( $id ) ],
			$formats,
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Retrieve a post by ID
	 *
	 * @param int $id
	 * @return object|null
	 */
	public static function get_post( $id ) {
		global $wpdb;
		$table = self::get_table_name();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) );
	}

	/**
	 * Retrieve posts with pagination and filters
	 *
	 * @param array $args
	 * @return array
	 */
	public static function get_posts( $args = [] ) {
		global $wpdb;
		$table = self::get_table_name();

		$defaults = [
			'status'   => '',
			'platform' => '',
			'limit'    => 20,
			'offset'   => 0,
			'orderby'  => 'id',
			'order'    => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );

		$where = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['platform'] ) ) {
			$where[] = 'platform = %s';
			$params[] = sanitize_text_field( $args['platform'] );
		}

		$where_clause = implode( ' AND ', $where );
		$allowed_order_by = [ 'id', 'created_at', 'scheduled_at', 'published_at', 'status', 'platform' ];
		$orderby = in_array( $args['orderby'], $allowed_order_by, true ) ? $args['orderby'] : 'id';
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$params[] = absint( $args['limit'] );
		$params[] = absint( $args['offset'] );

		$sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Count posts with optional filters
	 *
	 * @param array $args
	 * @return int
	 */
	public static function count_posts( $args = [] ) {
		global $wpdb;
		$table = self::get_table_name();

		$where = [ '1=1' ];
		$params = [];

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['platform'] ) ) {
			$where[] = 'platform = %s';
			$params[] = sanitize_text_field( $args['platform'] );
		}

		$where_clause = implode( ' AND ', $where );

		if ( ! empty( $params ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $params ) );
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}" );
	}

	/**
	 * Delete a post by ID
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete_post( $id ) {
		global $wpdb;
		$result = $wpdb->delete(
			self::get_table_name(),
			[ 'id' => absint( $id ) ],
			[ '%d' ]
		);
		return $result !== false;
	}
}
