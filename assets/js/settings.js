/**
 * Settings Page Scripts for WooCommerce Social Publisher
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// 1. Placeholder Token Inserter
		$('.wsp-token-chip').on('click', function(e) {
			e.preventDefault();
			var token = $(this).data('token');
			var textarea = document.getElementById('wsp_post_template');
			if (!textarea) return;

			var startPos = textarea.selectionStart;
			var endPos   = textarea.selectionEnd;
			var currentVal = textarea.value;

			textarea.value = currentVal.substring(0, startPos) + token + currentVal.substring(endPos, currentVal.length);
			textarea.focus();
			textarea.selectionStart = startPos + token.length;
			textarea.selectionEnd   = startPos + token.length;
		});

		// 2. Reset Template to Default
		$('#wsp-reset-template-btn').on('click', function(e) {
			e.preventDefault();
			if (!confirm(wspSettingsData.i18n.confirmReset)) {
				return;
			}

			var $btn = $(this);
			$btn.prop('disabled', true);

			$.post(wspSettingsData.ajaxUrl, {
				action: 'wsp_reset_template',
				nonce: wspSettingsData.nonce
			}, function(response) {
				$btn.prop('disabled', false);
				if (response.success && response.data.default_template) {
					$('#wsp_post_template').val(response.data.default_template);
				}
			});
		});

		// 3. Copy Redirect URI
		$('.wsp-copy-btn').on('click', function(e) {
			e.preventDefault();
			var target = $(this).data('clipboard-target');
			var text = $(target).text();

			if (navigator.clipboard) {
				navigator.clipboard.writeText(text).then(function() {
					var $btn = $('.wsp-copy-btn');
					var orig = $btn.html();
					$btn.text(wspSettingsData.i18n.copied);
					setTimeout(function() {
						$btn.html(orig);
					}, 2000);
				});
			}
		});

		// 4. Test Meta Connection AJAX
		$('#wsp-test-conn-btn').on('click', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var $resultBox = $('#wsp-test-result');

			$btn.prop('disabled', true).addClass('updating-message');
			$resultBox.show().html('<span class="spinner is-active" style="float:none; margin:0 6px 0 0;"></span> ' + wspSettingsData.i18n.testing);

			$.post(wspSettingsData.ajaxUrl, {
				action: 'wsp_test_connection',
				nonce: wspSettingsData.nonce
			}, function(res) {
				$btn.prop('disabled', false).removeClass('updating-message');
				if (res.success) {
					var fb = res.data.facebook;
					var ig = res.data.instagram;

					var html = '<div class="notice notice-info inline"><p>';
					html += '<strong>Facebook Page:</strong> ' + (fb.connected ? '<span style="color:#00a32a;">✓ ' + fb.message + '</span>' : '<span style="color:#d63638;">✕ ' + fb.message + '</span>') + '<br>';
					html += '<strong>Instagram Account:</strong> ' + (ig.connected ? '<span style="color:#00a32a;">✓ ' + ig.message + '</span>' : '<span style="color:#dba617;">' + ig.message + '</span>');
					html += '</p></div>';

					$resultBox.html(html);
				} else {
					$resultBox.html('<div class="notice notice-error inline"><p>' + res.data.message + '</p></div>');
				}
			}).fail(function() {
				$btn.prop('disabled', false).removeClass('updating-message');
				$resultBox.html('<div class="notice notice-error inline"><p>Connection test failed. Network or server error.</p></div>');
			});
		});
	});

})(jQuery);
