/**
 * The offer countdown.
 *
 * Nothing else here needs JavaScript — the discount is worked out on the
 * server, so a shopper with scripts off still gets it.
 */
(function () {
	'use strict';

	var i18n = window.ssoI18n || {};

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
