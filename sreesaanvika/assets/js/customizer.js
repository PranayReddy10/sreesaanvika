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
			$('.ss-brand__name').text(to);
		});
	});

	wp.customize('blogdescription', function (value) {
		value.bind(function (to) {
			$('.ss-brand__tag').text(to);
		});
	});

	wp.customize('ss_brand_tagline', function (value) {
		value.bind(function (to) {
			$('.ss-brand__tag').text(to);
		});
	});

	var colorMap = {
		ss_color_bg: '--ss-bg',
		ss_color_surface: '--ss-surface',
		ss_color_gold: '--ss-gold',
		ss_color_gold_light: '--ss-gold-light',
		ss_color_maroon: '--ss-maroon',
		ss_color_marigold: '--ss-marigold',
		ss_color_text: '--ss-text'
	};

	Object.keys(colorMap).forEach(function (setting) {
		wp.customize(setting, function (value) {
			value.bind(function (to) {
				if (to) { setVar(colorMap[setting], to); }
			});
		});
	});

	wp.customize('ss_radius', function (value) {
		value.bind(function (to) {
			var n = parseInt(to, 10) || 10;
			setVar('--ss-radius-lg', n + 'px');
			setVar('--ss-radius', Math.max(2, Math.round(n * 0.4)) + 'px');
		});
	});

	wp.customize('ss_container', function (value) {
		value.bind(function (to) {
			setVar('--ss-container', (parseInt(to, 10) || 1320) + 'px');
		});
	});

	wp.customize('ss_font_scale', function (value) {
		value.bind(function (to) {
			document.body.style.fontSize = (parseInt(to, 10) || 16) + 'px';
		});
	});

	wp.customize('ss_font_head', function (value) {
		value.bind(function (to) {
			if (to) { setVar('--ss-font-head', to); }
		});
	});
})(jQuery);
