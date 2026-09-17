/**
 * Preview Screen & Batch Publishing Engine for WooCommerce Social Publisher
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// 1. Live Caption Syncing & Character Counter
		$('.wsp-caption-input').on('input', function() {
			var $textarea = $(this);
			var text = $textarea.val();
			var cardId = $textarea.closest('.wsp-product-card').data('product-id');

			// Update char counter
			$textarea.closest('.wsp-caption-editor-wrap').find('.wsp-char-num').text(text.length);

			// Update live mockup preview
			var formatted = text.replace(/\n/g, '<br>');
			$('#wsp-mockup-body-' + cardId).html(formatted);
		});

		// 2. Image Selection from Gallery
		$('.wsp-thumb-btn').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var imgUrl = $btn.data('image-url');
			var cardId = $btn.data('target-card');
			var $card = $('#wsp-product-card-' + cardId);

			$card.find('.wsp-thumb-btn').removeClass('active');
			$btn.addClass('active');

			$card.find('.wsp-active-image-preview').attr('src', imgUrl);
			$card.find('.wsp-selected-image-url').val(imgUrl);
			$card.find('#wsp-mockup-img-' + cardId).attr('src', imgUrl);
		});

		// 3. Global Platform Switcher
		$('#wsp-global-platform').on('change', function() {
			var val = $(this).val();
			if ('both' === val) {
				$('.wsp-check-fb').prop('checked', true);
				$('.wsp-check-ig').prop('checked', true);
			} else if ('facebook' === val) {
				$('.wsp-check-fb').prop('checked', true);
				$('.wsp-check-ig').prop('checked', false);
			} else if ('instagram' === val) {
				$('.wsp-check-fb').prop('checked', false);
				$('.wsp-check-ig').prop('checked', true);
			}
		});

		// 4. Toggle Schedule Drawer
		$('#wsp-toggle-schedule-btn, #wsp-cancel-schedule-drawer-btn').on('click', function(e) {
			e.preventDefault();
			$('#wsp-schedule-drawer').slideToggle(200);
		});

		// 5. Confirm & Schedule Batch
		$('#wsp-confirm-schedule-btn').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var scheduledTime = $('#wsp-schedule-datetime').val();

			if (!scheduledTime) {
				alert('Please select a date and time.');
				return;
			}

			var items = [];
			$('.wsp-product-card').each(function() {
				var $card = $(this);
				var pid = $card.data('product-id');
				var fb = $card.find('.wsp-check-fb').is(':checked');
				var ig = $card.find('.wsp-check-ig').is(':checked');

				var platform = 'both';
				if (fb && !ig) platform = 'facebook';
				if (!fb && ig) platform = 'instagram';
				if (!fb && !ig) return; // Skip unchecked

				items.push({
					product_id: pid,
					platform: platform,
					caption: $card.find('.wsp-caption-input').val(),
					image_url: $card.find('.wsp-selected-image-url').val()
				});
			});

			if (items.length === 0) {
				alert('No products selected for publishing.');
				return;
			}

			$btn.prop('disabled', true).text('Scheduling...');

			$.post(wspPreviewData.ajaxUrl, {
				action: 'wsp_schedule_batch',
				nonce: wspPreviewData.nonce,
				items: items,
				scheduled_time: scheduledTime
			}, function(res) {
				$btn.prop('disabled', false).text('Confirm & Schedule All');
				if (res.success) {
					alert(wspPreviewData.i18n.scheduledNotice);
					window.location.href = wspPreviewData.historyUrl;
				} else {
					alert(res.data.message || 'Failed to schedule.');
				}
			});
		});

		// 6. Sequential Batch Runner ("Publish Now")
		var isPublishing = false;
		$('#wsp-publish-all-btn').on('click', function(e) {
			e.preventDefault();
			if (isPublishing) return;

			var $cards = $('.wsp-product-card');
			var total = $cards.length;
			if (total === 0) return;

			isPublishing = true;
			var $mainBtn = $(this);
			$mainBtn.prop('disabled', true).addClass('updating-message');

			$('#wsp-progress-container').slideDown(200);
			$('#wsp-progress-count').text('0 / ' + total);
			$('#wsp-progress-bar-fill').css('width', '0%');
			$('#wsp-progress-percent').text('0%');

			var currentIndex = 0;

			function processNext() {
				if (currentIndex >= total) {
					isPublishing = false;
					$mainBtn.prop('disabled', false).removeClass('updating-message');
					$('#wsp-progress-label').html('<strong>Publishing completed!</strong>');
					$('.wsp-progress-title .spinner').removeClass('is-active');
					return;
				}

				var $card = $($cards[currentIndex]);
				var pid = $card.data('product-id');
				var fb = $card.find('.wsp-check-fb').is(':checked');
				var ig = $card.find('.wsp-check-ig').is(':checked');

				var platform = 'both';
				if (fb && !ig) platform = 'facebook';
				if (!fb && ig) platform = 'instagram';
				if (!fb && !ig) {
					// Skip if neither checked
					updateProgress(currentIndex + 1, total);
					currentIndex++;
					processNext();
					return;
				}

				var caption = $card.find('.wsp-caption-input').val();
				var imageUrl = $card.find('.wsp-selected-image-url').val();
				var publishAgain = $card.find('.wsp-publish-again-check').is(':checked') ? 1 : 0;

				$card.addClass('is-processing');
				$card.find('#wsp-badge-' + pid).html('<span class="spinner is-active" style="float:none;"></span> ' + wspPreviewData.i18n.publishing);

				$.post(wspPreviewData.ajaxUrl, {
					action: 'wsp_publish_single_item',
					nonce: wspPreviewData.nonce,
					product_id: pid,
					platform: platform,
					caption: caption,
					image_url: imageUrl,
					publish_again: publishAgain
				}, function(res) {
					$card.removeClass('is-processing');
					renderCardResult($card, pid, res);
					updateProgress(currentIndex + 1, total);
					currentIndex++;
					processNext();
				}).fail(function() {
					$card.removeClass('is-processing').addClass('is-failed');
					$card.find('#wsp-badge-' + pid).html('<span class="wsp-platform-result-item failed">Server / Network Error</span>');
					updateProgress(currentIndex + 1, total);
					currentIndex++;
					processNext();
				});
			}

			processNext();
		});

		function updateProgress(done, total) {
			var percent = Math.round((done / total) * 100);
			$('#wsp-progress-count').text(done + ' / ' + total);
			$('#wsp-progress-bar-fill').css('width', percent + '%');
			$('#wsp-progress-percent').text(percent + '%');
		}

		function renderCardResult($card, pid, res) {
			var $container = $card.find('#wsp-badge-' + pid);
			$container.empty();

			if (!res.success) {
				$card.addClass('is-failed');
				$container.html('<div class="wsp-platform-result-item failed">✕ ' + (res.data.message || 'Failed') + '</div>');
				return;
			}

			var results = res.data.results;
			var allOk = true;

			if (results.facebook) {
				var fb = results.facebook;
				if (fb.success) {
					$container.append('<div class="wsp-platform-result-item published"><strong>Facebook:</strong> Published ✓ (ID: ' + fb.post_id + ')</div>');
				} else {
					allOk = false;
					$container.append('<div class="wsp-platform-result-item failed"><strong>Facebook:</strong> ' + fb.error + '</div>');
				}
			}

			if (results.instagram) {
				var ig = results.instagram;
				if (ig.success) {
					$container.append('<div class="wsp-platform-result-item published"><strong>Instagram:</strong> Published ✓ (ID: ' + ig.post_id + ')</div>');
				} else {
					allOk = false;
					$container.append('<div class="wsp-platform-result-item failed"><strong>Instagram:</strong> ' + ig.error + '</div>');
				}
			}

			if (allOk) {
				$card.addClass('is-published');
			} else {
				$card.addClass('is-failed');
			}
		}
	});

})(jQuery);
