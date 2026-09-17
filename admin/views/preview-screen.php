<?php
/**
 * Configuration and Preview Screen View
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tz_string = WSP_Scheduler::get_timezone_string();
$default_delay_mins = WSP_Settings::get( 'schedule_default_delay_mins', 60 );
$default_schedule_time = date( 'Y-m-d\TH:i', strtotime( "+{$default_delay_mins} minutes", current_time( 'timestamp' ) ) );
?>

<div class="wrap wsp-wrap wsp-preview-wrap">
	<!-- Top Sticky Action Bar -->
	<div class="wsp-action-bar">
		<div class="wsp-bar-left">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button button-secondary wsp-cancel-btn">
				&larr; <?php esc_html_e( 'Cancel & Back to Products', 'woocommerce-social-publisher' ); ?>
			</a>
			<div class="wsp-bar-meta">
				<h2><?php esc_html_e( 'Social Publisher Preview', 'woocommerce-social-publisher' ); ?></h2>
				<span class="wsp-item-counter">
					<?php printf( esc_html__( '%d Products Selected', 'woocommerce-social-publisher' ), count( $items ) ); ?>
				</span>
			</div>
		</div>

		<div class="wsp-bar-right">
			<div class="wsp-target-filter-wrap">
				<label for="wsp-global-platform"><strong><?php esc_html_e( 'Target:', 'woocommerce-social-publisher' ); ?></strong></label>
				<select id="wsp-global-platform" class="wsp-platform-select">
					<option value="both" <?php selected( $selected_platform, 'both' ); ?>><?php esc_html_e( 'Facebook & Instagram', 'woocommerce-social-publisher' ); ?></option>
					<option value="facebook" <?php selected( $selected_platform, 'facebook' ); ?>><?php esc_html_e( 'Facebook Only', 'woocommerce-social-publisher' ); ?></option>
					<option value="instagram" <?php selected( $selected_platform, 'instagram' ); ?>><?php esc_html_e( 'Instagram Only', 'woocommerce-social-publisher' ); ?></option>
				</select>
			</div>
			<button type="button" id="wsp-toggle-schedule-btn" class="button button-secondary">
				<?php esc_html_e( 'Schedule...', 'woocommerce-social-publisher' ); ?>
			</button>
			<button type="button" id="wsp-publish-all-btn" class="button button-secondary">
				<?php esc_html_e( 'Publish Now', 'woocommerce-social-publisher' ); ?> &rarr;
			</button>
		</div>
	</div>

	<!-- Scheduling Drawer (Initially Hidden) -->
	<div id="wsp-schedule-drawer" class="wsp-schedule-drawer" style="display:none;">
		<div class="wsp-drawer-inner">
			<h3><?php esc_html_e( 'Schedule Postings for Later', 'woocommerce-social-publisher' ); ?></h3>
			<p class="description">
				<?php printf( esc_html__( 'All selected posts will be queued in Action Scheduler and published at the chosen time in store timezone (%s).', 'woocommerce-social-publisher' ), esc_html( $tz_string ) ); ?>
			</p>
			<div class="wsp-drawer-inputs">
				<label for="wsp-schedule-datetime"><strong><?php esc_html_e( 'Publish Date & Time:', 'woocommerce-social-publisher' ); ?></strong></label>
				<input type="datetime-local" id="wsp-schedule-datetime" class="regular-text" value="<?php echo esc_attr( $default_schedule_time ); ?>">
				<button type="button" id="wsp-confirm-schedule-btn" class="button button-primary">
					<?php esc_html_e( 'Confirm & Schedule All', 'woocommerce-social-publisher' ); ?>
				</button>
				<button type="button" id="wsp-cancel-schedule-drawer-btn" class="button button-secondary">
					<?php esc_html_e( 'Close', 'woocommerce-social-publisher' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Real-Time Progress Bar (Hidden Until Publishing starts) -->
	<div id="wsp-progress-container" class="wsp-progress-container" style="display:none;">
		<div class="wsp-progress-header">
			<div class="wsp-progress-title">
				<span class="spinner is-active"></span>
				<strong id="wsp-progress-label"><?php esc_html_e( 'Publishing posts to Meta...', 'woocommerce-social-publisher' ); ?></strong>
			</div>
			<div class="wsp-progress-stats">
				<span id="wsp-progress-count">0 / <?php echo count( $items ); ?></span>
				(<span id="wsp-progress-percent">0%</span>)
			</div>
		</div>
		<div class="wsp-progress-bar-track">
			<div id="wsp-progress-bar-fill" class="wsp-progress-bar-fill" style="width: 0%;"></div>
		</div>
	</div>

	<!-- Notice Alerts -->
	<?php if ( ! $is_fb_connected ) : ?>
		<div class="notice notice-error inline">
			<p>
				<strong><?php esc_html_e( 'Facebook Not Connected:', 'woocommerce-social-publisher' ); ?></strong>
				<?php printf( esc_html__( 'You have not connected a Facebook Page. Posts to Facebook will fail. Please connect in %sSettings &rarr; Facebook%s.', 'woocommerce-social-publisher' ), '<a href="' . esc_url( admin_url( 'admin.php?page=woocommerce-social-publisher&tab=facebook' ) ) . '">', '</a>' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( ! $is_ig_connected && in_array( $selected_platform, [ 'instagram', 'both' ], true ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'Instagram Not Connected:', 'woocommerce-social-publisher' ); ?></strong>
				<?php printf( esc_html__( 'No Instagram Business or Creator account is connected to your Facebook Page. Please review requirements in %sSettings &rarr; Instagram%s.', 'woocommerce-social-publisher' ), '<a href="' . esc_url( admin_url( 'admin.php?page=woocommerce-social-publisher&tab=instagram' ) ) . '">', '</a>' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Product Cards List -->
	<div class="wsp-products-list" id="wsp-items-list">
		<?php foreach ( $items as $idx => $item ) : 
			$fb_dup = $item['duplicates']['facebook']['published'];
			$ig_dup = $item['duplicates']['instagram']['published'];
			$has_dup = $fb_dup || $ig_dup;
		?>
			<div class="wsp-product-card" data-product-id="<?php echo esc_attr( $item['id'] ); ?>" id="wsp-product-card-<?php echo esc_attr( $item['id'] ); ?>">
				<div class="wsp-card-header">
					<div class="wsp-header-left">
						<span class="wsp-card-number">#<?php echo esc_html( $idx + 1 ); ?></span>
						<h3 class="wsp-product-name"><?php echo esc_html( $item['title'] ); ?></h3>
						<span class="wsp-price-pill">
							<?php echo esc_html( $item['price'] ); ?> <?php echo esc_html( $item['currency'] ); ?>
							<?php if ( ! empty( $item['sale_price'] ) ) : ?>
								&rarr; <strong class="wsp-sale-price"><?php echo esc_html( $item['sale_price'] ); ?> <?php echo esc_html( $item['currency'] ); ?></strong>
							<?php endif; ?>
						</span>
					</div>
					<div class="wsp-header-right">
						<a href="<?php echo esc_url( $item['permalink'] ); ?>" target="_blank" class="wsp-view-product-link" title="<?php esc_attr_e( 'View public product page', 'woocommerce-social-publisher' ); ?>">
							<span class="dashicons dashicons-external"></span> <?php esc_html_e( 'View Product', 'woocommerce-social-publisher' ); ?>
						</a>
					</div>
				</div>

				<div class="wsp-card-body">
					<!-- Column 1: Media Preview & Selector -->
					<div class="wsp-col-media">
						<div class="wsp-media-preview-box">
							<?php if ( ! empty( $item['featured_url'] ) ) : ?>
								<img src="<?php echo esc_url( $item['featured_url'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" class="wsp-active-image-preview" id="wsp-img-preview-<?php echo esc_attr( $item['id'] ); ?>">
								<input type="hidden" class="wsp-selected-image-url" value="<?php echo esc_url( $item['featured_url'] ); ?>">
							<?php else : ?>
								<div class="wsp-no-image-placeholder">
									<span class="dashicons dashicons-format-image"></span>
									<span><?php esc_html_e( 'No Image', 'woocommerce-social-publisher' ); ?></span>
									<input type="hidden" class="wsp-selected-image-url" value="">
								</div>
							<?php endif; ?>
						</div>

						<?php if ( count( $item['images'] ) > 1 ) : ?>
							<div class="wsp-gallery-thumbnails">
								<span class="wsp-gallery-label"><?php esc_html_e( 'Choose Image:', 'woocommerce-social-publisher' ); ?></span>
								<div class="wsp-thumb-list">
									<?php foreach ( $item['images'] as $img ) : ?>
										<button type="button" class="wsp-thumb-btn <?php echo ( $img['url'] === $item['featured_url'] ) ? 'active' : ''; ?>" 
												data-image-url="<?php echo esc_url( $img['url'] ); ?>" 
												data-target-card="<?php echo esc_attr( $item['id'] ); ?>"
												title="<?php echo $img['is_featured'] ? esc_attr__( 'Featured Image', 'woocommerce-social-publisher' ) : esc_attr__( 'Gallery Image', 'woocommerce-social-publisher' ); ?>">
											<img src="<?php echo esc_url( $img['url'] ); ?>" alt="thumb">
										</button>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>

					<!-- Column 2: Configuration & Caption Editor -->
					<div class="wsp-col-content">
						<div class="wsp-item-platform-row">
							<label><strong><?php esc_html_e( 'Publish To:', 'woocommerce-social-publisher' ); ?></strong></label>
							<div class="wsp-platform-checks">
								<label class="wsp-check-label">
									<input type="checkbox" class="wsp-check-fb" value="facebook" <?php checked( in_array( $selected_platform, [ 'facebook', 'both' ], true ) ); ?>>
									<span class="dashicons dashicons-facebook"></span> Facebook
								</label>
								<label class="wsp-check-label">
									<input type="checkbox" class="wsp-check-ig" value="instagram" <?php checked( in_array( $selected_platform, [ 'instagram', 'both' ], true ) ); ?>>
									<span class="dashicons dashicons-instagram"></span> Instagram
								</label>
							</div>
						</div>

						<?php if ( $has_dup ) : ?>
							<div class="wsp-duplicate-warning">
								<span class="dashicons dashicons-warning"></span>
								<div class="wsp-dup-text">
									<?php if ( $fb_dup && $ig_dup ) : ?>
										<?php esc_html_e( 'Warning: This product was previously published to both Facebook and Instagram.', 'woocommerce-social-publisher' ); ?>
									<?php elseif ( $fb_dup ) : ?>
										<?php esc_html_e( 'Warning: This product was previously published to Facebook.', 'woocommerce-social-publisher' ); ?>
									<?php else : ?>
										<?php esc_html_e( 'Warning: This product was previously published to Instagram.', 'woocommerce-social-publisher' ); ?>
									<?php endif; ?>
								</div>
								<label class="wsp-publish-again-toggle">
									<input type="checkbox" class="wsp-publish-again-check" value="1">
									<strong><?php esc_html_e( 'Publish Again', 'woocommerce-social-publisher' ); ?></strong>
								</label>
							</div>
						<?php endif; ?>

						<div class="wsp-caption-editor-wrap">
							<div class="wsp-caption-header">
								<label for="wsp-caption-<?php echo esc_attr( $item['id'] ); ?>"><strong><?php esc_html_e( 'Generated Caption (Editable):', 'woocommerce-social-publisher' ); ?></strong></label>
								<span class="wsp-char-count"><span class="wsp-char-num"><?php echo mb_strlen( $item['caption'] ); ?></span> chars</span>
							</div>
							<textarea id="wsp-caption-<?php echo esc_attr( $item['id'] ); ?>" class="wsp-caption-input" rows="7"><?php echo esc_textarea( $item['caption'] ); ?></textarea>
						</div>
					</div>

					<!-- Column 3: Live Social Mockup & Status Indicator -->
					<div class="wsp-col-status">
						<div class="wsp-mockup-card">
							<div class="wsp-mockup-header">
								<div class="wsp-mockup-avatar"></div>
								<div class="wsp-mockup-name">
									<strong><?php echo esc_html( ! empty( $fb_page_name ) ? $fb_page_name : get_bloginfo( 'name' ) ); ?></strong>
									<span>Just now • 🌍</span>
								</div>
							</div>
							<div class="wsp-mockup-body" id="wsp-mockup-body-<?php echo esc_attr( $item['id'] ); ?>">
								<?php echo nl2br( esc_html( $item['caption'] ) ); ?>
							</div>
							<?php if ( ! empty( $item['featured_url'] ) ) : ?>
								<div class="wsp-mockup-img-box">
									<img src="<?php echo esc_url( $item['featured_url'] ); ?>" alt="preview" id="wsp-mockup-img-<?php echo esc_attr( $item['id'] ); ?>">
								</div>
							<?php endif; ?>
						</div>

						<div class="wsp-result-badge-container" id="wsp-badge-<?php echo esc_attr( $item['id'] ); ?>">
							<span class="wsp-badge wsp-badge-ready"><?php esc_html_e( 'Ready to Publish', 'woocommerce-social-publisher' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
