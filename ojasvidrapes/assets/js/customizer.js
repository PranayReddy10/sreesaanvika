/**
 * Customizer live preview — updates the tokens without a full refresh
 * for the settings where an instant response matters most.
 */
(function ($) {
	'use strict';

	if (!window.wp || !wp.customize) { return; }

	function setVar(name, value) {
		document.documentElement.style.setProperty(name, value);
	}

	wp.customize('blogname', function (value) {
		value.bind(function (to) {
			$('.od-brand__name').text(to);
		});
	});

	wp.customize('blogdescription', function (value) {
		value.bind(function (to) {
			$('.od-brand__tag').text(to);
		});
	});

	wp.customize('od_brand_tagline', function (value) {
		value.bind(function (to) {
			$('.od-brand__tag').text(to);
		});
	});

	var colorMap = {
		od_color_bg: '--od-bg',
		od_color_surface: '--od-surface',
		od_color_gold: '--od-gold',
		od_color_gold_light: '--od-gold-light',
		od_color_maroon: '--od-maroon',
		od_color_marigold: '--od-marigold',
		od_color_text: '--od-text'
	};

	Object.keys(colorMap).forEach(function (setting) {
		wp.customize(setting, function (value) {
			value.bind(function (to) {
				if (to) { setVar(colorMap[setting], to); }
			});
		});
	});

	wp.customize('od_radius', function (value) {
		value.bind(function (to) {
			var n = parseInt(to, 10) || 10;
			setVar('--od-radius-lg', n + 'px');
			setVar('--od-radius', Math.max(2, Math.round(n * 0.4)) + 'px');
		});
	});

	wp.customize('od_container', function (value) {
		value.bind(function (to) {
			setVar('--od-container', (parseInt(to, 10) || 1320) + 'px');
		});
	});

	wp.customize('od_font_scale', function (value) {
		value.bind(function (to) {
			document.body.style.fontSize = (parseInt(to, 10) || 16) + 'px';
		});
	});

	wp.customize('od_font_head', function (value) {
		value.bind(function (to) {
			if (to) { setVar('--od-font-head', to); }
		});
	});
})(jQuery);
