<?php
/**
 * Scheduler Helpers for WooCommerce Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Scheduler {

	/**
	 * Get site timezone string
	 *
	 * @return string
	 */
	public static function get_timezone_string() {
		$tz = get_option( 'timezone_string' );
		if ( ! empty( $tz ) ) {
			return $tz;
		}

		$gmt_offset = get_option( 'gmt_offset' );
		if ( 0 == $gmt_offset ) {
			return 'UTC';
		}

		return 'UTC' . ( $gmt_offset > 0 ? '+' : '' ) . $gmt_offset;
	}

	/**
	 * Convert local datetime string to UTC timestamp
	 *
	 * @param string $datetime_str 'Y-m-d H:i'
	 * @return int|false
	 */
	public static function parse_local_datetime_to_timestamp( $datetime_str ) {
		if ( empty( $datetime_str ) ) {
			return false;
		}

		$timezone_string = self::get_timezone_string();
		try {
			$tz = new DateTimeZone( $timezone_string );
			$dt = new DateTime( $datetime_str, $tz );
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			return false;
		}
	}
}
