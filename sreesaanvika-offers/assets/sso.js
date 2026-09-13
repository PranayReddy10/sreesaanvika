/**
 * The offer countdown.
 *
 * Nothing else here needs JavaScript — the discount is worked out on the
 * server, so a shopper with scripts off still gets it.
 */
(function () {
	'use strict';

	var data = window.ssoData || {};
	var i18n = data.i18n || {};

	function pad(n) {
		return (n < 10 ? '0' : '') + n;
	}

	function digits(value) {
		return String(value).split('').map(function (d) {
			return '<span class="sso-digit">' + d + '</span>';
		}).join('');
	}

	function unit(value, label) {
		return '<span class="sso-unit">'
			+ '<span class="sso-unit__digits">' + digits(pad(value)) + '</span>'
			+ '<span class="sso-unit__label">' + label + '</span>'
			+ '</span>';
	}

	function start(box) {
		var ends = parseInt(box.getAttribute('data-ends'), 10);
		var clock = box.querySelector('.sso-countdown__clock');

		if (!ends || !clock) { return; }

		function tick() {
			var left = ends - Math.floor(Date.now() / 1000);

			if (left <= 0) {
				clock.textContent = i18n.ended || 'This offer has ended.';
				clearInterval(timer);
				return;
			}

			var days = Math.floor(left / 86400);
			var hours = Math.floor((left % 86400) / 3600);
			var mins = Math.floor((left % 3600) / 60);
			var secs = left % 60;

			// Past a day the hours alone would read as a much shorter offer.
			if (days > 0) { hours += days * 24; }

			clock.innerHTML = unit(hours, i18n.hours || 'Hours')
				+ '<span class="sso-colon">:</span>'
				+ unit(mins, i18n.minutes || 'Minutes')
				+ '<span class="sso-colon">:</span>'
				+ unit(secs, i18n.seconds || 'Seconds');
		}

		var timer = setInterval(tick, 1000);
		tick();
	}

	Array.prototype.forEach.call(document.querySelectorAll('.sso-countdown'), start);
}());

/**
 * Complete the look — the running total, and adding the set in one go.
 */
(function () {
	'use strict';

	var data = window.ssoData || {};
	var i18n = data.i18n || {};

	function money(amount) {
		// Reuse whatever the theme already prints, so the format matches.
		var sample = document.querySelector('.woocommerce-Price-amount');
		var symbol = sample ? (sample.textContent.match(/^[^\d]+/) || ['₹'])[0].trim() : '₹';

		return symbol + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
	}

	function ticked(look) {
		return Array.prototype.filter.call(
			look.querySelectorAll('.sso-look__tick'),
			function (t) { return t.checked; }
		);
	}

	function retotal(look) {
		var chosen = ticked(look);
		var sum = 0;
		var full = 0;

		chosen.forEach(function (t) {
			sum += parseFloat(t.getAttribute('data-price')) || 0;
			full += parseFloat(t.getAttribute('data-full')) || 0;
		});

		var count = look.querySelector('[data-look-count]');
		var total = look.querySelector('[data-look-sum]');
		var save = look.querySelector('[data-look-save]');
		var add = look.querySelector('[data-look-add]');

		if (count) {
			count.textContent = chosen.length > 1
				? (i18n.total || 'Total for %d pieces').replace('%d', chosen.length)
				: (i18n.one || 'This piece only');
		}

		if (total) { total.textContent = money(sum); }

		if (save) {
			var saved = full - sum;
			save.hidden = saved < 0.01;
			save.textContent = (i18n.save || 'You save %s').replace('%s', money(saved));
		}

		if (add) {
			// The lead product is disabled-and-ticked; only the rest can be added.
			var addable = chosen.filter(function (t) { return !t.disabled; });
			add.disabled = addable.length === 0;
			add.setAttribute('data-ids', addable.map(function (t) { return t.value; }).join(','));
		}
	}

	Array.prototype.forEach.call(document.querySelectorAll('[data-look]'), function (look) {
		look.addEventListener('change', function (e) {
			if (e.target.classList.contains('sso-look__tick')) { retotal(look); }
		});

		retotal(look);
	});

	/* --- Add to bag --- */
	document.addEventListener('click', function (e) {
		var button = e.target.closest('[data-look-add]');

		if (!button || button.disabled) { return; }

		var ids = button.getAttribute('data-ids');

		if (!ids) { return; }

		e.preventDefault();

		var label = button.textContent;
		button.disabled = true;
		button.textContent = i18n.adding || 'Adding…';

		var body = new URLSearchParams();
		body.set('action', 'sso_add_bundle');
		body.set('ids', ids);
		body.set('nonce', button.getAttribute('data-nonce') || '');

		fetch(data.ajaxUrl || '/wp-admin/admin-ajax.php', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		}).then(function (r) {
			return r.json();
		}).then(function (out) {
			var look = button.closest('[data-look]');
			var note = look && look.querySelector('[data-look-note]');
			var said = (out && out.data && out.data.message) || i18n.error || '';

			if (note) {
				note.hidden = false;
				note.textContent = said;
			}

			if (!out || !out.success) {
				button.disabled = false;
				button.textContent = label;

				if (!note && said && window.ssToast) { window.ssToast(said); }
				return;
			}

			button.textContent = i18n.added || 'Added';

			if (window.ssToast) { window.ssToast(said); }

			// Let the theme's mini-cart and any fragment listener catch up.
			if (window.jQuery) {
				window.jQuery(document.body).trigger('wc_fragment_refresh');
				window.jQuery(document.body).trigger('added_to_cart');
			}

			// On the cart page the totals have to be re-read from the server.
			if (document.body.classList.contains('woocommerce-cart')) {
				window.location.reload();
			}
		}).catch(function () {
			button.disabled = false;
			button.textContent = label;

			if (window.ssToast) { window.ssToast(i18n.error || ''); }
		});
	});
}());
