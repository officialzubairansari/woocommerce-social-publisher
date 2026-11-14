/**
 * Publishing History Scripts for WooCommerce Social Publisher
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		var $rescheduleModal = $('#wsp-reschedule-modal');
		var $rescheduleTrigger;
		var rescheduleIds = [];
		function currentSiteDatetime() {
			var siteNow = new Date(Date.now() + Number(wspHistoryData.timezoneOffset) * 1000);
			return siteNow.toISOString().slice(0, 16);
		}
		$(document).on('click', '.wsp-reschedule-btn', function() {
			rescheduleIds = [];
			$rescheduleTrigger = $(this);
			$('#wsp-reschedule-title').text(wspHistoryData.i18n.reschedulePost);
			$('#wsp-reschedule-selection').prop('hidden', true);
			$('#wsp-reschedule-id').val($rescheduleTrigger.data('id'));
			$('#wsp-reschedule-datetime').val(currentSiteDatetime());
			$('#wsp-reschedule-error').prop('hidden', true).text('');
			$rescheduleModal.show();
			$('#wsp-reschedule-datetime').trigger('focus');
		});
		$('#wsp-bulk-reschedule-btn').on('click', function() {
			rescheduleIds = [];
			var eligible = 0;
			$('.wsp-row-cb:checked').each(function() {
				rescheduleIds.push($(this).val());
				if ($(this).attr('data-reschedulable') === '1') { eligible++; }
			});
			if (!eligible) { alert(wspHistoryData.i18n.selectReschedule); return; }
			$rescheduleTrigger = $(this);
			$('#wsp-reschedule-title').text(wspHistoryData.i18n.bulkReschedule);
			$('#wsp-reschedule-selection').text(wspHistoryData.i18n.bulkScheduleInfo.replace('%d', eligible)).prop('hidden', false);
			$('#wsp-reschedule-datetime').val(currentSiteDatetime());
			$('#wsp-reschedule-error').prop('hidden', true).text('');
			$rescheduleModal.show();
			$('#wsp-reschedule-datetime').trigger('focus');
		});
		function closeReschedule() {
			if ($rescheduleModal.find('button[type="submit"]').prop('disabled')) { return; }
			$rescheduleModal.hide();
			if ($rescheduleTrigger) { $rescheduleTrigger.trigger('focus'); }
		}
		$rescheduleModal.on('click', function(e) {
			if (e.target === this || $(e.target).closest('.wsp-modal-close-btn').length) { closeReschedule(); }
		});
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $rescheduleModal.is(':visible')) { closeReschedule(); }
		});
		$('#wsp-reschedule-form').on('submit', function(e) {
			e.preventDefault();
			var $button = $(this).find('button[type="submit"]');
			if ($button.prop('disabled')) { return; }
			var label = $button.text();
			$button.prop('disabled', true).text(wspHistoryData.i18n.savingSchedule);
			$('#wsp-reschedule-error').prop('hidden', true);
			$.post(wspHistoryData.ajaxUrl, {
				action: rescheduleIds.length ? 'wsp_bulk_reschedule_posts' : 'wsp_reschedule_post',
				nonce: wspHistoryData.nonce,
				id: $('#wsp-reschedule-id').val(),
				ids: rescheduleIds,
				scheduled_time: $('#wsp-reschedule-datetime').val()
			}).done(function(res) {
				if (res.success) {
					if (rescheduleIds.length) { alert(res.data.message); }
					location.reload();
				} else {
					$('#wsp-reschedule-error').text((res.data && res.data.message) || wspHistoryData.i18n.scheduleError).prop('hidden', false);
				}
			}).fail(function() {
				$('#wsp-reschedule-error').text(wspHistoryData.i18n.scheduleError).prop('hidden', false);
			}).always(function() {
				$button.prop('disabled', false).text(label);
			});
		});

		// 1. Select All Checkboxes
		$('#cb-select-all').on('change', function() {
			var isChecked = $(this).is(':checked');
			$('.wsp-row-cb').prop('checked', isChecked);
		});

		// 3. Delete Single Log Record
		$(document).on('click', '.wsp-delete-log-btn', function(e) {
			e.preventDefault();
			if (!confirm(wspHistoryData.i18n.confirmDelete)) {
				return;
			}

			var $btn = $(this);
			var id = $btn.data('id');
			var $row = $('#wsp-row-' + id);

			$.post(wspHistoryData.ajaxUrl, {
				action: 'wsp_delete_post',
				nonce: wspHistoryData.nonce,
				id: id
			}, function(res) {
				if (res.success) {
					$row.fadeOut(300, function() { $(this).remove(); });
				} else {
					alert(res.data.message || 'Failed to delete.');
				}
			});
		});

		// 4. Bulk Delete
		$('#wsp-bulk-delete-btn').on('click', function(e) {
			e.preventDefault();
			var ids = [];
			$('.wsp-row-cb:checked').each(function() {
				ids.push($(this).val());
			});

			if (ids.length === 0) {
				alert('Please select at least one record to delete.');
				return;
			}

			if (!confirm('Are you sure you want to delete the ' + ids.length + ' selected records?')) {
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true);

			$.post(wspHistoryData.ajaxUrl, {
				action: 'wsp_bulk_delete_posts',
				nonce: wspHistoryData.nonce,
				ids: ids
			}, function(res) {
				if (res.success) {
					location.reload();
				} else {
					alert(res.data.message || 'Bulk delete failed.');
					$btn.prop('disabled', false);
				}
			});
		});

		// 5. View Details Modal
		$(document).on('click', '.wsp-view-details-btn', function(e) {
			e.preventDefault();
			var id = $(this).data('id');
			var $modal = $('#wsp-details-modal');
			var $body = $('#wsp-modal-body');

			$body.html('<div class="wsp-modal-spinner" style="text-align:center; padding:40px 0;"><span class="spinner is-active" style="float:none; margin:0 8px 0 0;"></span> Loading log details...</div>');
			$('#wsp-modal-title').html('<span class="dashicons dashicons-analytics"></span> Publishing Log Details #' + id);
			$modal.fadeIn(150);

			$.post(wspHistoryData.ajaxUrl, {
				action: 'wsp_get_post_details',
				nonce: wspHistoryData.nonce,
				id: id
			}, function(res) {
				if (res.success) {
					var d = res.data;
					var html = '';

					// 1. Hero Card
					var platformClass = d.platform === 'facebook' ? 'fb' : 'ig';
					var platformIcon = d.platform === 'facebook' ? 'dashicons-facebook' : 'dashicons-instagram';
					var platformName = d.platform === 'facebook' ? 'Facebook' : 'Instagram';
					var postTypeLabel = d.post_type === 'carousel' ? 'Carousel Post' : (d.post_type === 'single_image' ? 'Single Photo' : 'Text / Link');

					html += '<div class="wsp-detail-hero">';
					html += '  <div class="wsp-detail-hero-left">';
					if (d.media_url) {
						var firstImg = d.media_url.split(',')[0].trim();
						html += '    <img src="' + firstImg + '" class="wsp-detail-thumb" alt="Product">';
					} else {
						html += '    <div class="wsp-detail-thumb" style="display:flex; align-items:center; justify-content:center; color:#94a3b8;"><span class="dashicons dashicons-format-image"></span></div>';
					}
					html += '    <div class="wsp-detail-title-wrap">';
					if (d.product_url) {
						html += '      <h4><a href="' + d.product_url + '" target="_blank">' + d.product_title + '</a></h4>';
					} else {
						html += '      <h4>' + d.product_title + '</h4>';
					}
					html += '      <div class="wsp-detail-meta-pills">';
					html += '        <span class="wsp-meta-pill ' + platformClass + '"><span class="dashicons ' + platformIcon + '"></span> ' + platformName + '</span>';
					html += '        <span class="wsp-meta-pill">' + postTypeLabel + '</span>';
					html += '        <span class="wsp-meta-pill">Product ID: #' + d.product_id + '</span>';
					html += '      </div>';
					html += '    </div>';
					html += '  </div>';
					html += '  <div class="wsp-detail-hero-right">';
					html += '    <span class="wsp-status-badge status-' + d.status + '" style="font-size:12px; padding:4px 12px;">' + d.status.toUpperCase() + '</span>';
					html += '  </div>';
					html += '</div>';

					// 2. Metadata Grid
					html += '<div class="wsp-detail-grid">';

					// Meta Post ID
					html += '  <div class="wsp-detail-card">';
					html += '    <div class="wsp-detail-card-label"><span class="dashicons dashicons-share"></span> Meta Post ID</div>';
					html += '    <div class="wsp-detail-card-value">' + (d.external_post_id ? '<code>' + d.external_post_id + '</code>' : '<span style="color:#94a3b8;">Not available</span>') + '</div>';
					html += '  </div>';

					// IG Container ID
					if (d.platform === 'instagram' || d.external_container_id) {
						html += '  <div class="wsp-detail-card">';
						html += '    <div class="wsp-detail-card-label"><span class="dashicons dashicons-camera"></span> IG Container ID</div>';
						html += '    <div class="wsp-detail-card-value">' + (d.external_container_id ? '<code>' + d.external_container_id + '</code>' : '<span style="color:#94a3b8;">N/A</span>') + '</div>';
						html += '  </div>';
					}

					// Date
					html += '  <div class="wsp-detail-card">';
					html += '    <div class="wsp-detail-card-label"><span class="dashicons dashicons-calendar-alt"></span> ' + (d.published_at ? 'Published Date' : (d.scheduled_at ? 'Scheduled Date' : 'Created Date')) + '</div>';
					html += '    <div class="wsp-detail-card-value">' + (d.published_at || d.scheduled_at || d.created_at) + '</div>';
					html += '  </div>';

					// Attempts
					html += '  <div class="wsp-detail-card">';
					html += '    <div class="wsp-detail-card-label"><span class="dashicons dashicons-update"></span> Delivery Attempts</div>';
					html += '    <div class="wsp-detail-card-value">' + d.attempts + ' attempt' + (d.attempts > 1 ? 's' : '') + '</div>';
					html += '  </div>';

					html += '</div>';

					// 3. Technical Error Diagnostic Box (if any)
					if (d.error_message) {
						html += '<div class="wsp-detail-error-box">';
						html += '  <div class="wsp-detail-error-header"><span class="dashicons dashicons-warning"></span> Meta API Error Diagnostic</div>';
						html += '  <div class="wsp-detail-error-content">' + d.error_message + '</div>';
						html += '</div>';
					}

					// 4. Caption Box
					html += '<div class="wsp-detail-box">';
					html += '  <div class="wsp-detail-box-header">';
					html += '    <span><span class="dashicons dashicons-editor-quote"></span> Social Caption</span>';
					html += '    <span style="font-weight:normal; color:#64748b;">' + d.caption.length + ' characters</span>';
					html += '  </div>';
					html += '  <div class="wsp-detail-box-content">' + d.caption + '</div>';
					html += '</div>';

					// 5. Media Assets Gallery (if images present)
					if (d.media_url) {
						var imgs = d.media_url.split(',');
						html += '<div class="wsp-detail-box">';
						html += '  <div class="wsp-detail-box-header"><span><span class="dashicons dashicons-images-alt2"></span> Media Assets (' + imgs.length + ')</span></div>';
						html += '  <div class="wsp-detail-media-gallery">';
						for (var i = 0; i < imgs.length; i++) {
							var u = imgs[i].trim();
							if (u) {
								html += '    <a href="' + u + '" target="_blank" title="View Full Image"><img src="' + u + '" class="wsp-detail-media-item"></a>';
							}
						}
						html += '  </div>';
						html += '</div>';
					}

					$body.html(html);

				} else {
					$body.html('<p class="notice notice-error" style="padding:12px;">' + (res.data.message || 'Error loading details.') + '</p>');
				}
			});
		});

		$('.wsp-modal-close-btn, #wsp-details-modal').on('click', function(e) {
			if (e.target === this || $(this).hasClass('wsp-modal-close-btn')) {
				$('#wsp-details-modal').fadeOut(150);
			}
		});
	});

})(jQuery);
