<?php
/**
 * Settings Tab: Scheduling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tz_string = WSP_Scheduler::get_timezone_string();
$current_time = current_time( 'mysql' );
?>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Publishing Scheduling & Timezone', 'woocommerce-social-publisher' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Scheduled posts are queued and processed automatically in the background using WooCommerce Action Scheduler.', 'woocommerce-social-publisher' ); ?>
	</p>

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Store Timezone', 'woocommerce-social-publisher' ); ?></th>
				<td>
					<strong><?php echo esc_html( $tz_string ); ?></strong>
					<p class="description">
						<?php printf( esc_html__( 'Current store time: %s. Timezone is configured in WordPress Settings > General.', 'woocommerce-social-publisher' ), esc_html( $current_time ) ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="schedule_default_delay_mins"><?php esc_html_e( 'Default Schedule Offset', 'woocommerce-social-publisher' ); ?></label>
				</th>
				<td>
					<input type="number" name="schedule_default_delay_mins" id="schedule_default_delay_mins" class="small-text" min="15" max="10080" value="<?php echo esc_attr( $settings['schedule_default_delay_mins'] ); ?>">
					<span><?php esc_html_e( 'minutes from now', 'woocommerce-social-publisher' ); ?></span>
					<p class="description"><?php esc_html_e( 'Default time pre-populated in the scheduling picker on the preview screen.', 'woocommerce-social-publisher' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Background Queue Engine', 'woocommerce-social-publisher' ); ?></th>
				<td>
					<?php if ( class_exists( 'ActionScheduler' ) ) : ?>
						<span class="wsp-tag success">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'WooCommerce Action Scheduler Active', 'woocommerce-social-publisher' ); ?>
						</span>
						<p class="description"><?php esc_html_e( 'Action Scheduler provides high-reliability, asynchronous queue execution without PHP timeouts.', 'woocommerce-social-publisher' ); ?></p>
					<?php else : ?>
						<span class="wsp-tag warning">
							<span class="dashicons dashicons-warning"></span>
							<?php esc_html_e( 'Using WP-Cron Fallback', 'woocommerce-social-publisher' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>
