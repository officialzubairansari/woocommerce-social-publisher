<?php
/**
 * Publishing History Data Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_History {

	/**
	 * Get paginated list of social publishing records
	 *
	 * @param array $args
	 * @return array [ 'items' => array, 'total' => int, 'pages' => int ]
	 */
	public static function get_list( $args = [] ) {
		$defaults = [
			'status'   => '',
			'platform' => '',
			'paged'    => 1,
			'per_page' => 20,
			'orderby'  => 'id',
			'order'    => 'DESC',
		];

		$args = wp_parse_args( $args, $defaults );
		$paged = max( 1, absint( $args['paged'] ) );
		$limit = max( 1, absint( $args['per_page'] ) );
		$offset = ( $paged - 1 ) * $limit;

		$filter_args = [
			'status'   => $args['status'],
			'platform' => $args['platform'],
			'limit'    => $limit,
			'offset'   => $offset,
			'orderby'  => $args['orderby'],
			'order'    => $args['order'],
		];

		$items = WSP_Database::get_posts( $filter_args );
		$total = WSP_Database::count_posts( [
			'status'   => $args['status'],
			'platform' => $args['platform'],
		] );

		return [
			'items' => $items,
			'total' => $total,
			'pages' => ceil( $total / $limit ),
		];
	}

	/**
	 * Delete a log record
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete( $id ) {
		return WSP_Database::delete_post( $id );
	}

	/**
	 * Delete multiple log records
	 *
	 * @param array $ids
	 * @return int
	 */
	public static function bulk_delete( $ids ) {
		$count = 0;
		foreach ( (array) $ids as $id ) {
			if ( self::delete( $id ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Retry a failed log record
	 *
	 * @param int $id
	 * @return array [ 'success' => bool, 'error' => string ]
	 */
	public static function retry( $id ) {
		return WSP_Publisher::retry( $id );
	}
}
