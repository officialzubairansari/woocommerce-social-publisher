<?php
/**
 * Publishing History Screen View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = $history['items'];
$total = $history['total'];
$pages = $history['pages'];
?>

<div class="wrap wsp-wrap wsp-history-wrap">
	<div class="wsp-header-bar">
		<div class="wsp-header-title">
			<h1><?php esc_html_e( 'Publishing History & Logs', 'woocommerce-social-publisher' ); ?></h1>
			<span class="wsp-version-badge"><?php printf( esc_html__( '%d Total Records', 'woocommerce-social-publisher' ), $total ); ?></span>
		</div>
		<div class="wsp-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=woocommerce-social-publisher' ) ); ?>" class="button button-secondary">
				<?php esc_html_e( 'Settings', 'woocommerce-social-publisher' ); ?> &rarr;
			</a>
		</div>
	</div>

	<!-- Filters & Actions Bar -->
	<div class="tablenav top wsp-tablenav">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="wsp-filter-form">
			<input type="hidden" name="page" value="wsp-history">

			<div class="alignleft actions">
				<select name="platform" id="filter-platform">
					<option value=""><?php esc_html_e( 'All Platforms', 'woocommerce-social-publisher' ); ?></option>
					<option value="facebook" <?php selected( $current_platform, 'facebook' ); ?>><?php esc_html_e( 'Facebook', 'woocommerce-social-publisher' ); ?></option>
					<option value="instagram" <?php selected( $current_platform, 'instagram' ); ?>><?php esc_html_e( 'Instagram', 'woocommerce-social-publisher' ); ?></option>
				</select>

				<select name="status" id="filter-status">
					<option value=""><?php esc_html_e( 'All Statuses', 'woocommerce-social-publisher' ); ?></option>
					<option value="published" <?php selected( $current_status, 'published' ); ?>><?php esc_html_e( 'Published', 'woocommerce-social-publisher' ); ?></option>
					<option value="failed" <?php selected( $current_status, 'failed' ); ?>><?php esc_html_e( 'Failed', 'woocommerce-social-publisher' ); ?></option>
					<option value="scheduled" <?php selected( $current_status, 'scheduled' ); ?>><?php esc_html_e( 'Scheduled', 'woocommerce-social-publisher' ); ?></option>
					<option value="processing" <?php selected( $current_status, 'processing' ); ?>><?php esc_html_e( 'Processing', 'woocommerce-social-publisher' ); ?></option>
					<option value="queued" <?php selected( $current_status, 'queued' ); ?>><?php esc_html_e( 'Queued', 'woocommerce-social-publisher' ); ?></option>
				</select>

				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'woocommerce-social-publisher' ); ?></button>
				<?php if ( ! empty( $current_platform ) || ! empty( $current_status ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wsp-history' ) ); ?>" class="button button-link"><?php esc_html_e( 'Reset Filters', 'woocommerce-social-publisher' ); ?></a>
				<?php endif; ?>
			</div>
		</form>

		<div class="alignright actions">
			<button type="button" id="wsp-bulk-delete-btn" class="button button-secondary">
				<?php esc_html_e( 'Delete Selected', 'woocommerce-social-publisher' ); ?>
			</button>
		</div>
	</div>

	<!-- History Table -->
	<table class="wp-list-table widefat fixed striped table-view-list wsp-history-table">
		<thead>
			<tr>
				<td id="cb" class="manage-column column-cb check-column">
					<input id="cb-select-all" type="checkbox">
				</td>
				<th scope="col" class="manage-column column-thumb" style="width: 60px;"><?php esc_html_e( 'Media', 'woocommerce-social-publisher' ); ?></th>
				<th scope="col" class="manage-column column-product"><?php esc_html_e( 'Product', 'woocommerce-social-publisher' ); ?></th>
				<th scope="col" class="manage-column column-platform" style="width: 110px;"><?php esc_html_e( 'Platform', 'woocommerce-social-publisher' ); ?></th>
				<th scope="col" class="manage-column column-caption"><?php esc_html_e( 'Caption Snippet', 'woocommerce-social-publisher' ); ?></th>
				<th scope="col" class="manage-column column-status" style="width: 120px;"><?php esc_html_e( 'Status', 'woocommerce-social-publisher' ); ?></th>
				<th scope="col" class="manage-column column-dates" style="width: 160px;"><?php esc_html_e( 'Dates', 'woocommerce-social-publisher' ); ?></th>
				<th scope="col" class="manage-column column-actions" style="width: 220px;"><?php esc_html_e( 'Actions', 'woocommerce-social-publisher' ); ?></th>
			</tr>
		</thead>

		<tbody id="the-list">
			<?php if ( empty( $items ) ) : ?>
				<tr>
					<td colspan="8" class="colspanchange">
						<div class="wsp-empty-history">
							<span class="dashicons dashicons-share"></span>
							<p><?php esc_html_e( 'No publishing history records found.', 'woocommerce-social-publisher' ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button button-secondary">
								<?php esc_html_e( 'View Products', 'woocommerce-social-publisher' ); ?>
							</a>
						</div>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $items as $post ) : 
					$pdata = new WSP_Product_Data( $post->product_id );
					$status_class = 'status-' . sanitize_html_class( $post->status );
				?>
					<tr id="wsp-row-<?php echo esc_attr( $post->id ); ?>" class="<?php echo esc_attr( $status_class ); ?>">
						<th scope="row" class="check-column">
							<input type="checkbox" name="post_ids[]" value="<?php echo esc_attr( $post->id ); ?>" class="wsp-row-cb">
						</th>
						<td class="column-thumb">
							<?php if ( ! empty( $post->media_url ) ) : ?>
								<img src="<?php echo esc_url( $post->media_url ); ?>" alt="media" width="48" height="48" class="wsp-table-thumb">
							<?php else : ?>
								<span class="dashicons dashicons-format-image wsp-no-thumb"></span>
							<?php endif; ?>
						</td>
						<td class="column-product">
							<strong>
								<?php if ( $pdata->is_valid() ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $post->product_id ) ); ?>" target="_blank">
										<?php echo esc_html( $pdata->get_title() ); ?>
									</a>
								<?php else : ?>
									<?php printf( esc_html__( 'Product #%d (Deleted)', 'woocommerce-social-publisher' ), $post->product_id ); ?>
								<?php endif; ?>
							</strong>
							<div class="row-actions">
								<?php if ( $pdata->is_valid() ) : ?>
									<a href="<?php echo esc_url( $pdata->get_permalink() ); ?>" target="_blank"><?php esc_html_e( 'View Product', 'woocommerce-social-publisher' ); ?></a> |
								<?php endif; ?>
								<span class="attempts-count"><?php printf( esc_html__( 'Attempts: %d', 'woocommerce-social-publisher' ), $post->attempts ); ?></span>
							</div>
						</td>
						<td class="column-platform">
							<?php if ( 'facebook' === $post->platform ) : ?>
								<span class="wsp-platform-badge fb">
									<span class="dashicons dashicons-facebook"></span> Facebook
								</span>
							<?php else : ?>
								<span class="wsp-platform-badge ig">
									<span class="dashicons dashicons-instagram"></span> Instagram
								</span>
							<?php endif; ?>
						</td>
						<td class="column-caption">
							<div class="wsp-caption-cell" title="<?php echo esc_attr( $post->caption ); ?>">
								<?php echo esc_html( wp_trim_words( $post->caption, 18, '...' ) ); ?>
							</div>
							<?php if ( ! empty( $post->error_message ) ) : ?>
								<div class="wsp-error-snippet" title="<?php echo esc_attr( $post->error_message ); ?>">
									<span class="dashicons dashicons-warning"></span>
									<?php echo esc_html( wp_trim_words( $post->error_message, 14, '...' ) ); ?>
								</div>
							<?php endif; ?>
						</td>
						<td class="column-status">
							<span class="wsp-status-badge <?php echo esc_attr( $status_class ); ?>">
								<?php echo esc_html( ucfirst( $post->status ) ); ?>
							</span>
							<?php if ( ! empty( $post->external_post_id ) ) : ?>
								<div class="wsp-ext-id">
									<small>ID: <?php echo esc_html( substr( $post->external_post_id, 0, 14 ) . '...' ); ?></small>
								</div>
							<?php endif; ?>
						</td>
						<td class="column-dates">
							<?php if ( ! empty( $post->published_at ) ) : ?>
								<small><strong><?php esc_html_e( 'Published:', 'woocommerce-social-publisher' ); ?></strong><br>
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $post->published_at ) ) ); ?></small>
							<?php elseif ( ! empty( $post->scheduled_at ) ) : ?>
								<small><strong><?php esc_html_e( 'Scheduled:', 'woocommerce-social-publisher' ); ?></strong><br>
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $post->scheduled_at ) ) ); ?></small>
							<?php else : ?>
								<small><strong><?php esc_html_e( 'Created:', 'woocommerce-social-publisher' ); ?></strong><br>
								<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $post->created_at ) ) ); ?></small>
							<?php endif; ?>
						</td>
						<td class="column-actions">
							<div class="wsp-row-actions-group">
								<?php if ( 'failed' === $post->status ) : ?>
									<button type="button" class="button button-small wsp-retry-btn" data-id="<?php echo esc_attr( $post->id ); ?>" title="<?php esc_attr_e( 'Retry Post', 'woocommerce-social-publisher' ); ?>">
										<?php esc_html_e( 'Retry', 'woocommerce-social-publisher' ); ?>
									</button>
								<?php endif; ?>
								<button type="button" class="button button-small wsp-view-details-btn" data-id="<?php echo esc_attr( $post->id ); ?>" title="<?php esc_attr_e( 'View Details', 'woocommerce-social-publisher' ); ?>">
									<?php esc_html_e( 'View', 'woocommerce-social-publisher' ); ?>
								</button>
								<button type="button" class="button button-small wsp-delete-log-btn" data-id="<?php echo esc_attr( $post->id ); ?>" title="<?php esc_attr_e( 'Delete Log', 'woocommerce-social-publisher' ); ?>">
									<?php esc_html_e( 'Delete', 'woocommerce-social-publisher' ); ?>
								</button>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<!-- Pagination Bar -->
	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav bottom">
			<div class="tablenav-pages">
				<span class="displaying-num"><?php printf( esc_html__( '%d items', 'woocommerce-social-publisher' ), $total ); ?></span>
				<?php
				echo paginate_links( [
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
					'total'     => $pages,
					'current'   => $paged,
				] );
				?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Details Modal -->
	<div id="wsp-details-modal" class="wsp-modal-overlay" style="display:none;">
		<div class="wsp-modal-content">
			<div class="wsp-modal-header">
				<h3 id="wsp-modal-title"><?php esc_html_e( 'Publishing Log Details', 'woocommerce-social-publisher' ); ?></h3>
				<button type="button" class="wsp-modal-close-btn">&times;</button>
			</div>
			<div class="wsp-modal-body" id="wsp-modal-body">
				<div class="wsp-modal-spinner"><span class="spinner is-active"></span></div>
			</div>
			<div class="wsp-modal-footer">
				<button type="button" class="button button-secondary wsp-modal-close-btn"><?php esc_html_e( 'Close', 'woocommerce-social-publisher' ); ?></button>
			</div>
		</div>
	</div>
</div>
