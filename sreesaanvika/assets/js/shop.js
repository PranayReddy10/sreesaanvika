/**
 * Sree Saanvika — shop behaviour: gallery, swatches, wishlist, compare,
 * quick view, quantity steppers and the mobile filter drawer.
 */
(function () {
	'use strict';

	var D = document;
	var data = window.ssData || {};
	var i18n = data.i18n || {};
	var toast = window.ssToast || function () {};
	var post = window.ssPost;

	function $(sel, ctx) { return (ctx || D).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || D).querySelectorAll(sel)); }
	function on(el, ev, fn, opts) { if (el) { el.addEventListener(ev, fn, opts || false); } }

	/* ==================================================================
	 * 1. Product gallery — thumbs, zoom, lightbox
	 * ================================================================== */
	function initGallery(root) {
		var stageImg = $('.ss-gallery__frame img', root);
		var frame = $('.ss-gallery__frame', root);
		var zoomLayer = $('.ss-gallery__zoom', root);
		var thumbsWrap = $('.ss-gallery__thumbs', root);
		var counter = $('.ss-gallery__counter', root);
		var box = $('.ss-lightbox');

		if (!stageImg) { return; }

		function readShots() {
			return $$('.ss-gallery__thumb', thumbsWrap).map(function (t) {
				var img = t.querySelector('img');

				return {
					thumb: (img && img.getAttribute('src')) || t.getAttribute('data-large'),
					full: t.getAttribute('data-full'),
					large: t.getAttribute('data-large') || t.getAttribute('data-full'),
					alt: t.getAttribute('data-alt') || ''
				};
			});
		}

		/*
		 * The set the page was rendered with. Choosing a colour swaps in that
		 * colour's photos; clearing the choice comes back here.
		 */
		var base = readShots();

		if (!base.length) {
			base = [{ thumb: stageImg.src, large: stageImg.src, full: stageImg.src, alt: stageImg.alt || '' }];
		}

		var shots = base;
		var thumbs = $$('.ss-gallery__thumb', thumbsWrap);
		var index = 0;

		function render(i, skipScroll) {
			index = (i + shots.length) % shots.length;
			var shot = shots[index];

			stageImg.src = shot.large;
			stageImg.alt = shot.alt;
			stageImg.removeAttribute('srcset');

			if (zoomLayer) { zoomLayer.style.backgroundImage = 'url(' + shot.full + ')'; }

			thumbs.forEach(function (t, n) {
				t.classList.toggle('is-active', n === index);
				t.setAttribute('aria-current', n === index ? 'true' : 'false');
			});

			if (counter) { counter.textContent = (index + 1) + ' / ' + shots.length; }

			if (!skipScroll && thumbs[index] && thumbs[index].scrollIntoView) {
				thumbs[index].scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
			}
		}

		function buildThumbs() {
			if (!thumbsWrap) { return; }

			thumbsWrap.innerHTML = '';

			shots.forEach(function (shot, n) {
				var btn = D.createElement('button');
				btn.type = 'button';
				btn.className = 'ss-gallery__thumb';
				btn.setAttribute('role', 'tab');
				btn.setAttribute('data-full', shot.full);
				btn.setAttribute('data-large', shot.large);
				btn.setAttribute('data-alt', shot.alt);

				var img = D.createElement('img');
				img.src = shot.thumb || shot.large;
				img.alt = '';
				img.loading = 'lazy';
				img.width = 90;
				img.height = 120;
				btn.appendChild(img);

				var label = D.createElement('span');
				label.className = 'screen-reader-text';
				label.textContent = (i18n.viewImage || 'View image') + ' ' + (n + 1);
				btn.appendChild(label);

				thumbsWrap.appendChild(btn);
			});

			thumbs = $$('.ss-gallery__thumb', thumbsWrap);
		}

		function markSingle() {
			var single = shots.length < 2;

			root.classList.toggle('is-single', single);

			if (box) { box.classList.toggle('is-single', single); }
		}

		// Swap the whole set — used when a colour with its own photos is picked.
		function setShots(list) {
			shots = (list && list.length) ? list : base;

			buildThumbs();
			markSingle();

			if (box) {
				var strip = $('.ss-lightbox__strip', box);

				if (strip) {
					strip.innerHTML = '';
					strip.removeAttribute('data-built');
				}
			}

			render(0, true);
		}

		root.ssSetShots = setShots;
		markSingle();

		// Delegated, because the thumbs are rebuilt on every colour change.
		on(thumbsWrap, 'click', function (e) {
			var t = e.target.closest('.ss-gallery__thumb');
			if (t) { render(thumbs.indexOf(t)); }
		});

		on(thumbsWrap, 'mouseover', function (e) {
			var t = e.target.closest('.ss-gallery__thumb');
			if (t) { render(thumbs.indexOf(t), true); }
		});

		on($('.ss-gallery__arrow--next', root), 'click', function () { render(index + 1); });
		on($('.ss-gallery__arrow--prev', root), 'click', function () { render(index - 1); });

		// Hover zoom — a background-position follow on a layer above the image.
		if (frame && zoomLayer && window.matchMedia('(hover: hover)').matches) {
			on(frame, 'mouseenter', function () { frame.classList.add('is-zooming'); });
			on(frame, 'mouseleave', function () { frame.classList.remove('is-zooming'); });

			on(frame, 'mousemove', function (e) {
				var rect = frame.getBoundingClientRect();
				var x = ((e.clientX - rect.left) / rect.width) * 100;
				var y = ((e.clientY - rect.top) / rect.height) * 100;
				zoomLayer.style.backgroundPosition = x + '% ' + y + '%';
			});
		}

		// Swipe on touch.
		var startX = null;

		on(frame, 'touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });

		on(frame, 'touchend', function (e) {
			if (startX === null) { return; }
			var dx = e.changedTouches[0].clientX - startX;
			if (Math.abs(dx) > 45) { render(index + (dx < 0 ? 1 : -1)); }
			startX = null;
		});

		/* --- Lightbox --- */
		function openLightbox(at) {
			if (!box) { return; }

			var img = $('.ss-lightbox__img', box);
			var strip = $('.ss-lightbox__strip', box);
			var current = at;

			function paint(i) {
				current = (i + shots.length) % shots.length;
				img.src = shots[current].full;
				img.alt = shots[current].alt;
				img.classList.remove('is-zoomed');

				if (strip) {
					$$('img', strip).forEach(function (t, n) {
						t.classList.toggle('is-active', n === current);
					});
				}
			}

			if (strip && !strip.dataset.built) {
				shots.forEach(function (shot, n) {
					var t = D.createElement('img');
					t.src = shot.large;
					t.alt = '';
					t.loading = 'lazy';
					t.addEventListener('click', function () { paint(n); });
					strip.appendChild(t);
				});
				strip.dataset.built = '1';
			}

			paint(at);
			box.classList.add('is-open');
			D.body.classList.add('ss-no-scroll');

			box._next = function () { paint(current + 1); };
			box._prev = function () { paint(current - 1); };
		}

		on(frame, 'click', function () { openLightbox(index); });
		on($('.ss-gallery__tools .ss-expand', root), 'click', function () { openLightbox(index); });
	}

	$$('[data-gallery]').forEach(initGallery);

	// Lightbox chrome is shared across galleries.
	(function () {
		var box = $('.ss-lightbox');
		if (!box) { return; }

		function close() {
			box.classList.remove('is-open');
			D.body.classList.remove('ss-no-scroll');
		}

		on($('.ss-lightbox__close', box), 'click', close);
		on($('.ss-lightbox__next', box), 'click', function () { if (box._next) { box._next(); } });
		on($('.ss-lightbox__prev', box), 'click', function () { if (box._prev) { box._prev(); } });

		on(box, 'click', function (e) {
			if (e.target === box) { close(); }
		});

		on($('.ss-lightbox__img', box), 'click', function (e) {
			e.stopPropagation();
			this.classList.toggle('is-zoomed');
		});

		on(D, 'keydown', function (e) {
			if (!box.classList.contains('is-open')) { return; }

			if (e.key === 'Escape') { close(); }
			if (e.key === 'ArrowRight' && box._next) { box._next(); }
			if (e.key === 'ArrowLeft' && box._prev) { box._prev(); }
		});
	})();

	/* ==================================================================
	 * 2. Quantity steppers
	 * ================================================================== */
	on(D, 'click', function (e) {
		var btn = e.target.closest('.ss-qty-btn');
		if (!btn) { return; }

		e.preventDefault();

		var wrap = btn.closest('.ss-qty, .quantity');
		var input = wrap && wrap.querySelector('input[type="number"], input.qty');

		if (!input) { return; }

		var step = parseFloat(input.getAttribute('step')) || 1;
		var min = parseFloat(input.getAttribute('min'));
		var max = parseFloat(input.getAttribute('max'));
		var value = parseFloat(input.value) || 0;

		value += btn.classList.contains('ss-qty-plus') ? step : -step;

		if (!isNaN(min) && value < min) { value = min; }
		if (!isNaN(max) && value > max) { value = max; }
		if (isNaN(min) && value < 1) { value = 1; }

		input.value = value;
		input.dispatchEvent(new Event('change', { bubbles: true }));
	});

	/* ==================================================================
	 * 3. Variation swatches mirrored from Woo's <select>s
	 * ================================================================== */
	/**
	 * Point the product gallery at one colour's photos, or back at the
	 * product's own set when the choice is cleared.
	 */
	function showColorGallery(slug) {
		var gallery = $('[data-gallery]');

		if (!gallery || !gallery.ssSetShots) { return; }

		var raw = gallery.getAttribute('data-color-galleries');

		if (!raw) { return; }

		var map;

		try {
			map = JSON.parse(raw);
		} catch (err) {
			return;
		}

		gallery.ssSetShots((slug && map[slug]) ? map[slug] : null);
	}

	function buildSwatches() {
		$$('.ss-variation-select').forEach(function (select) {
			if (select.dataset.ssSwatched) { return; }

			var row = select.closest('[data-attribute-row]') || select.parentNode;
			var host = row.querySelector('[data-swatches]');

			if (!host) { return; }

			var attribute = select.getAttribute('data-attribute_name') || select.name || '';
			var isColor = /color|colour|shade/i.test(attribute);

			host.innerHTML = '';
			host.className = isColor ? 'ss-colorswatches' : 'ss-fabricchips';

			$$('option', select).forEach(function (option) {
				if (!option.value) { return; }

				var btn = D.createElement('button');
				btn.type = 'button';
				btn.setAttribute('data-value', option.value);
				btn.title = option.textContent;

				if (isColor) {
					var hex = host.getAttribute('data-color-' + option.value);
					var shot = host.getAttribute('data-img-' + option.value);

					btn.className = 'ss-colorswatch';
					btn.style.backgroundColor = hex || swatchColor(option.textContent);

					// A colour with its own photos shows one instead of a circle.
					if (shot) {
						btn.className = 'ss-colorswatch ss-colorswatch--img';

						var chip = D.createElement('img');
						chip.src = shot;
						chip.alt = '';
						chip.loading = 'lazy';
						btn.appendChild(chip);

						var name = D.createElement('span');
						name.className = 'ss-colorswatch__name';
						name.textContent = option.textContent;
						btn.appendChild(name);
					}

					var sr = D.createElement('span');
					sr.className = 'screen-reader-text';
					sr.textContent = option.textContent;
					btn.appendChild(sr);
				} else {
					btn.className = 'ss-size-chip';
					btn.textContent = option.textContent;
				}

				btn.addEventListener('click', function () {
					select.value = option.value;
					select.dispatchEvent(new Event('change', { bubbles: true }));
				});

				host.appendChild(btn);
			});

			function sync() {
				$$('button', host).forEach(function (btn) {
					var value = btn.getAttribute('data-value');
					var option = select.querySelector('option[value="' + CSS.escape(value) + '"]');
					btn.classList.toggle('is-active', select.value === value);
					btn.classList.toggle(isColor ? 'is-oos' : 'is-oos', !option);
				});

				var label = row.querySelector('[data-selected-label]');

				if (label) {
					var chosen = select.options[select.selectedIndex];
					label.textContent = (chosen && chosen.value) ? chosen.textContent : '';
				}

				if (isColor) { showColorGallery(select.value); }
			}

			select.addEventListener('change', sync);
			select.dataset.ssSwatched = '1';

			// Only hide the native select once its swatches actually exist,
			// so a no-JS or failed build still leaves a usable control.
			if (host.children.length) {
				var block = select.closest('.ss-varblock');
				if (block) { block.classList.add('is-swatched'); }
			}

			sync();

			// Woo rebuilds the option list as choices narrow; re-sync then.
			var form = select.closest('form.variations_form');

			if (form) {
				['woocommerce_update_variation_values', 'reset_data', 'found_variation'].forEach(function (ev) {
					form.addEventListener(ev, function () { setTimeout(sync, 30); });
				});

				if (window.jQuery) {
					window.jQuery(form).on('woocommerce_update_variation_values reset_data found_variation', function () {
						setTimeout(sync, 30);
					});
				}
			}
		});
	}

	function swatchColor(name) {
		var map = {
			red: '#c0392b', maroon: '#7b1e3b', pink: '#e6799f', 'rani pink': '#c2185b',
			magenta: '#a4245e', purple: '#6b3fa0', wine: '#5c1a33', orange: '#e8952f',
			marigold: '#f0a02a', mustard: '#d9a441', yellow: '#e9c547', gold: '#d4af37',
			beige: '#c9b79c', cream: '#e8dcc6', ivory: '#efe6d6', peach: '#f0a58a',
			green: '#2e7d52', 'bottle green': '#14532d', emerald: '#1f7a63', mehendi: '#7a8c3f',
			teal: '#17565f', 'peacock blue': '#0f5a75', blue: '#2b5ea8', navy: '#1b2a55',
			turquoise: '#2fa8a0', grey: '#7b7480', silver: '#c0c3c8', black: '#1b1418',
			white: '#f2eeea', brown: '#6b4429', copper: '#a45a2a', rust: '#9c4322',
			lavender: '#b9a5d6', mint: '#9fd6bd'
		};

		return map[String(name).trim().toLowerCase()] || '#8d7a84';
	}

	buildSwatches();

	/* Swap the main gallery image when a variation is picked. */
	(function () {
		var form = $('form.variations_form');
		if (!form || !window.jQuery) { return; }

		window.jQuery(form).on('found_variation', function (event, variation) {
			var stage = $('.ss-gallery__frame img');
			var zoom = $('.ss-gallery__zoom');

			if (stage && variation.image && variation.image.src) {
				stage.src = variation.image.src;
				stage.alt = variation.image.alt || '';
			}

			if (zoom && variation.image && variation.image.full_src) {
				zoom.style.backgroundImage = 'url(' + variation.image.full_src + ')';
			}

			var price = $('[data-var-price]');

			if (price && variation.price_html) {
				price.innerHTML = variation.price_html;
			}
		});
	})();

	/* ==================================================================
	 * 3b. Colour swatches on product cards
	 *
	 * A card has no variation form to drive, so a swatch either repaints the
	 * card with that colour's photo or, when the colour has no photo of its
	 * own, opens the product with the colour already chosen.
	 * ================================================================== */
	on(D, 'click', function (e) {
		var swatch = e.target.closest('.ss-pcard__swatches .ss-swatch');

		if (!swatch || swatch.classList.contains('ss-swatch--more')) { return; }

		var card = swatch.closest('.ss-product-card, li.product');
		var front = card && card.querySelector('.ss-pcard__img--front');
		var shot = swatch.getAttribute('data-front') || swatch.getAttribute('data-img');

		if (front && shot) {
			e.preventDefault();

			// srcset would win over the src we are about to set.
			front.removeAttribute('srcset');
			front.removeAttribute('sizes');
			front.src = shot;

			// Keep the chosen colour showing instead of the hover image.
			card.classList.add('is-colored');

			$$('.ss-swatch', swatch.parentNode).forEach(function (other) {
				other.classList.toggle('is-active', other === swatch);
			});

			return;
		}

		var href = swatch.getAttribute('data-href');

		if (href) {
			e.preventDefault();
			window.location.href = href;
		}
	});

	/* ==================================================================
	 * 4. Wishlist & compare
	 * ================================================================== */
	function markState(type, ids) {
		var selector = type === 'compare' ? '.ss-compare-btn' : '.ss-wishlist-btn';

		$$(selector).forEach(function (btn) {
			var active = ids.indexOf(parseInt(btn.getAttribute('data-id'), 10)) > -1;
			btn.classList.toggle('is-active', active);
			btn.setAttribute('aria-pressed', active ? 'true' : 'false');
		});

		var badge = $('.ss-' + type + '-count');

		if (badge) {
			badge.textContent = ids.length;
			badge.hidden = ids.length === 0;
		}
	}

	function readCookieList(name) {
		var match = D.cookie.match(new RegExp('(^|; )' + name + '=([^;]*)'));

		if (!match || !match[2]) { return []; }

		return decodeURIComponent(match[2]).split(',').map(function (n) {
			return parseInt(n, 10);
		}).filter(Boolean);
	}

	markState('wishlist', readCookieList('ss_wishlist'));
	markState('compare', readCookieList('ss_compare'));

	function updateCompareBar(items) {
		var bar = $('.ss-comparebar');
		if (!bar) { return; }

		var thumbs = $('.ss-comparebar__thumbs', bar);
		var label = $('.ss-comparebar__label strong', bar);

		if (thumbs) {
			thumbs.innerHTML = '';
			(items || []).forEach(function (item) {
				var img = D.createElement('img');
				img.src = item.thumb;
				img.alt = item.name || '';
				thumbs.appendChild(img);
			});
		}

		if (label) { label.textContent = (items || []).length; }

		bar.classList.toggle('is-shown', (items || []).length > 0);
	}

	on(D, 'click', function (e) {
		var btn = e.target.closest('.ss-wishlist-btn, .ss-compare-btn');
		if (!btn) { return; }

		e.preventDefault();

		var type = btn.classList.contains('ss-compare-btn') ? 'compare' : 'wishlist';
		var id = parseInt(btn.getAttribute('data-id'), 10);

		if (!id) { return; }

		btn.disabled = true;

		post('ss_toggle_list', { id: id, type: type }).then(function (res) {
			btn.disabled = false;

			if (!res.success) {
				toast((res.data && res.data.message) || i18n.error, 'error');
				return;
			}

			var payload = res.data;

			if (payload.full) {
				toast((i18n.compareFull || 'Compare list is full (%d)').replace('%d', payload.max), 'error');
				return;
			}

			markState(type, payload.ids);

			if (type === 'compare') {
				updateCompareBar(payload.items);
				toast(payload.action === 'added' ? i18n.compareAdded : i18n.compareRemoved, 'success');
			} else {
				toast(payload.action === 'added' ? i18n.wishAdded : i18n.wishRemoved, 'success');
			}

			// On the compare/wishlist page itself, drop the removed column.
			if (payload.action === 'removed') {
				var page = $('[data-list-page="' + type + '"]');

				if (page) { window.location.reload(); }
			}
		}).catch(function () {
			btn.disabled = false;
			toast(i18n.error, 'error');
		});
	});

	/* Remove buttons on the compare table. */
	on(D, 'click', function (e) {
		var btn = e.target.closest('.ss-compare__remove');
		if (!btn) { return; }

		e.preventDefault();

		post('ss_toggle_list', { id: parseInt(btn.getAttribute('data-id'), 10), type: 'compare' })
			.then(function () { window.location.reload(); });
	});

	/* ==================================================================
	 * 5. Quick view
	 * ================================================================== */
	on(D, 'click', function (e) {
		var btn = e.target.closest('.ss-quickview-btn');
		if (!btn) { return; }

		e.preventDefault();

		var modal = $('.ss-modal--quickview');
		if (!modal) { return; }

		var body = $('.ss-modal__content', modal);
		body.innerHTML = '<div class="ss-modal__loading"><div class="ss-spinner"></div></div>';
		modal.classList.add('is-open');
		D.body.classList.add('ss-no-scroll');

		post('ss_quickview', { id: btn.getAttribute('data-id') }).then(function (res) {
			if (res.success) {
				body.innerHTML = res.data.html;
				buildSwatches();
			} else {
				body.innerHTML = '<div class="ss-modal__loading"><p>' + ((res.data && res.data.message) || i18n.error) + '</p></div>';
			}
		}).catch(function () {
			body.innerHTML = '<div class="ss-modal__loading"><p>' + i18n.error + '</p></div>';
		});
	});

	on(D, 'click', function (e) {
		var modal = $('.ss-modal.is-open');
		if (!modal) { return; }

		if (e.target === modal || e.target.closest('.ss-modal__close')) {
			modal.classList.remove('is-open');
			D.body.classList.remove('ss-no-scroll');
		}
	});

	on(D, 'keydown', function (e) {
		if (e.key !== 'Escape') { return; }

		var modal = $('.ss-modal.is-open');

		if (modal) {
			modal.classList.remove('is-open');
			D.body.classList.remove('ss-no-scroll');
		}
	});

	/* ==================================================================
	 * 6. AJAX add to cart from cards
	 * ================================================================== */
	on(D, 'click', function (e) {
		var btn = e.target.closest('.ss-ajax-add');
		if (!btn) { return; }

		e.preventDefault();

		if (btn.classList.contains('loading')) { return; }

		btn.classList.add('loading');

		post('ss_add_to_cart', {
			id: btn.getAttribute('data-id'),
			qty: btn.getAttribute('data-qty') || 1
		}).then(function (res) {
			btn.classList.remove('loading');

			if (res.success === false) {
				var payload = res.data || {};

				toast(payload.message || i18n.error, 'error');

				if (payload.redirect) {
					setTimeout(function () { window.location.href = payload.redirect; }, 900);
				}

				return;
			}

			// WC_AJAX::get_refreshed_fragments() returns { fragments, cart_hash }.
			applyFragments(res.fragments || (res.data && res.data.fragments));
			toast(i18n.added, 'success');

			var panel = $('#ss-cart-panel');
			if (panel && window.ssOpenPanel) { window.ssOpenPanel(panel); }
		}).catch(function () {
			btn.classList.remove('loading');
			toast(i18n.error, 'error');
		});
	});

	function applyFragments(fragments) {
		if (!fragments) { return; }

		Object.keys(fragments).forEach(function (selector) {
			$$(selector).forEach(function (node) {
				var temp = D.createElement('div');
				temp.innerHTML = fragments[selector];

				if (temp.firstElementChild) {
					node.replaceWith(temp.firstElementChild);
				}
			});
		});
	}

	// Keep the header count in step when Woo's own scripts refresh fragments.
	if (window.jQuery) {
		window.jQuery(D.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart', function () {
			// Woo replaces the nodes itself; nothing else to do here.
		});
	}

	/* ==================================================================
	 * 7. Shop toolbar — grid / list view
	 * ================================================================== */
	(function () {
		var wrap = $('.ss-shop-results');
		if (!wrap) { return; }

		var saved = null;

		try { saved = window.localStorage.getItem('ss_shop_view'); } catch (err) { saved = null; }

		function apply(view) {
			wrap.classList.toggle('ss-shop--list', view === 'list');

			$$('.ss-viewtoggle button').forEach(function (btn) {
				btn.classList.toggle('is-active', btn.getAttribute('data-view') === view);
			});

			try { window.localStorage.setItem('ss_shop_view', view); } catch (err) { /* private mode */ }
		}

		$$('.ss-viewtoggle button').forEach(function (btn) {
			on(btn, 'click', function () { apply(btn.getAttribute('data-view')); });
		});

		apply(saved === 'list' ? 'list' : 'grid');
	})();

	/* Mobile filter drawer. */
	$$('.ss-filter-open').forEach(function (btn) {
		on(btn, 'click', function () {
			var sidebar = $('.ss-shop-sidebar');

			if (sidebar && window.ssOpenPanel) { window.ssOpenPanel(sidebar); }
		});
	});

	/* ==================================================================
	 * 8. Price range slider
	 * ================================================================== */
	$$('[data-price-slider]').forEach(function (slider) {
		var min = $('.ss-price-min', slider);
		var max = $('.ss-price-max', slider);
		var range = $('.ss-price-slider__range', slider);
		var outMin = $('[data-out-min]', slider);
		var outMax = $('[data-out-max]', slider);
		var symbol = data.currency || '₹';

		if (!min || !max) { return; }

		function paint() {
			var lo = parseInt(min.value, 10);
			var hi = parseInt(max.value, 10);

			if (lo > hi - 1) {
				if (D.activeElement === min) { lo = hi - 1; min.value = lo; }
				else { hi = lo + 1; max.value = hi; }
			}

			var floor = parseInt(min.min, 10);
			var ceil = parseInt(min.max, 10);
			var span = Math.max(1, ceil - floor);

			if (range) {
				range.style.left = (((lo - floor) / span) * 100) + '%';
				range.style.width = (((hi - lo) / span) * 100) + '%';
			}

			if (outMin) { outMin.textContent = symbol + Number(lo).toLocaleString('en-IN'); }
			if (outMax) { outMax.textContent = symbol + Number(hi).toLocaleString('en-IN'); }

			var form = slider.closest('form');

			if (form) {
				var fMin = form.querySelector('input[name="min_price"]');
				var fMax = form.querySelector('input[name="max_price"]');
				if (fMin) { fMin.value = lo; }
				if (fMax) { fMax.value = hi; }
			}
		}

		on(min, 'input', paint);
		on(max, 'input', paint);
		paint();
	});

	/* ==================================================================
	 * 9. PIN code checker
	 * ================================================================== */
	on(D, 'click', function (e) {
		var btn = e.target.closest('.ss-pin-check');
		if (!btn) { return; }

		var box = btn.closest('.ss-delivery');
		var input = $('#ss-pin', box);
		var out = $('.ss-delivery__result', box);

		if (!input || !out) { return; }

		var pin = input.value.replace(/\D/g, '');

		if (pin.length !== 6) {
			out.textContent = i18n.badPin || 'Enter a valid 6-digit PIN code';
			out.style.color = 'var(--ss-error)';
			out.classList.add('is-shown');
			return;
		}

		out.textContent = (i18n.deliverTo || 'Delivery to %s in 3–6 business days').replace('%s', pin);
		out.style.color = 'var(--ss-success)';
		out.classList.add('is-shown');
	});

	/* ==================================================================
	 * 10. Sticky buy bar on the product page
	 * ================================================================== */
	(function () {
		var bar = $('.ss-stickybuy');
		var anchor = $('.ss-buyrow');

		if (!bar || !anchor) { return; }

		function check() {
			var box = anchor.getBoundingClientRect();
			bar.classList.toggle('is-shown', box.bottom < 0);
		}

		on(window, 'scroll', check, { passive: true });
		check();

		on($('.ss-stickybuy .ss-btn', bar), 'click', function (e) {
			e.preventDefault();
			anchor.scrollIntoView({ behavior: 'smooth', block: 'center' });

			var cart = anchor.querySelector('.single_add_to_cart_button');

			if (cart && !$('form.variations_form')) { setTimeout(function () { cart.click(); }, 500); }
		});
	})();

	/* ==================================================================
	 * 11. Product tabs
	 * ================================================================== */
	$$('.ss-tabs').forEach(function (tabs) {
		var buttons = $$('.ss-tabs__nav button', tabs);
		var panels = $$('.ss-tabs__panel', tabs);

		buttons.forEach(function (btn) {
			on(btn, 'click', function () {
				var target = btn.getAttribute('aria-controls');

				buttons.forEach(function (b) {
					var active = b === btn;
					b.classList.toggle('is-active', active);
					b.setAttribute('aria-selected', active ? 'true' : 'false');
				});

				panels.forEach(function (panel) {
					panel.classList.toggle('is-active', panel.id === target);
				});
			});
		});
	});

	/* ==================================================================
	 * 12. Cart page — live quantity updates
	 * ================================================================== */
	(function () {
		var form = $('.woocommerce-cart-form');
		if (!form) { return; }

		var timer;

		form.addEventListener('change', function (e) {
			if (!e.target.matches('input.qty')) { return; }

			var row = e.target.closest('.ss-cartrow');
			if (row) { row.classList.add('is-updating'); }

			clearTimeout(timer);

			timer = setTimeout(function () {
				var update = form.querySelector('[name="update_cart"]');

				if (update) {
					update.disabled = false;
					update.click();
				} else {
					form.submit();
				}
			}, 700);
		});
	})();
})();

/* ==========================================================================
 * 13. Buy it now
 * Flips the hidden flag so the add-to-cart redirect lands on checkout, and
 * keeps the button in step with Woo's own disabled state on variable
 * products so it cannot submit before a variation is chosen.
 * ========================================================================== */
(function () {
	'use strict';

	var D = document;

	D.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-buy-now]');
		if (!btn) { return; }

		var form = btn.closest('form');
		var flag = form && form.querySelector('.ss-buy-now-flag');

		if (flag) { flag.value = '1'; }
	});

	// Reset the flag if the shopper then uses the plain add-to-cart button.
	D.addEventListener('click', function (e) {
		var btn = e.target.closest('.single_add_to_cart_button');
		if (!btn || btn.hasAttribute('data-buy-now')) { return; }

		var form = btn.closest('form');
		var flag = form && form.querySelector('.ss-buy-now-flag');

		if (flag) { flag.value = '0'; }
	});

	var variations = D.querySelector('form.variations_form');

	if (variations) {
		var mirror = function () {
			var cart = variations.querySelector('.single_add_to_cart_button');
			var buy = variations.querySelector('[data-buy-now]');

			if (!cart || !buy) { return; }

			var off = cart.classList.contains('disabled') || cart.disabled;
			buy.classList.toggle('disabled', off);
			buy.disabled = !!off;
		};

		['show_variation', 'hide_variation', 'reset_data', 'found_variation', 'woocommerce_variation_has_changed'].forEach(function (ev) {
			variations.addEventListener(ev, function () { setTimeout(mirror, 30); });
		});

		if (window.jQuery) {
			window.jQuery(variations).on(
				'show_variation hide_variation reset_data found_variation woocommerce_variation_has_changed',
				function () { setTimeout(mirror, 30); }
			);
		}

		mirror();
	}
})();

/* ==========================================================================
 * 14. Load more
 * Appends the next page of a product grid in place. The button carries only a
 * whitelisted section key (or a widget's source and category); the query is
 * rebuilt server side.
 * ========================================================================== */
(function () {
	'use strict';

	var D = document;
	var data = window.ssData || {};
	var post = window.ssPost;
	var toast = window.ssToast || function () {};

	if (!post) { return; }

	D.addEventListener('click', function (e) {
		var btn = e.target.closest('.ss-loadmore__btn');
		if (!btn || btn.classList.contains('loading')) { return; }

		var wrap = btn.closest('.ss-loadmore');
		var grid = wrap && wrap.previousElementSibling;

		while (grid && !grid.classList.contains('products')) {
			grid = grid.previousElementSibling;
		}

		if (!grid) { return; }

		var page = parseInt(btn.getAttribute('data-page'), 10) || 1;
		var next = page + 1;

		btn.classList.add('loading');

		post('ss_load_more', {
			section: btn.getAttribute('data-section') || '',
			source: btn.getAttribute('data-source') || '',
			category: btn.getAttribute('data-category') || '',
			page: next,
			per: btn.getAttribute('data-per') || 8
		}).then(function (res) {
			btn.classList.remove('loading');

			if (!res.success || !res.data || !res.data.html) {
				wrap.remove();
				return;
			}

			var temp = D.createElement('ul');
			temp.innerHTML = res.data.html;

			var added = [];

			while (temp.firstElementChild) {
				var item = temp.firstElementChild;
				item.classList.add('ss-just-loaded');
				grid.appendChild(item);
				added.push(item);
			}

			btn.setAttribute('data-page', next);

			// Let the reveal animation run, then drop the marker class.
			setTimeout(function () {
				added.forEach(function (el) { el.classList.remove('ss-just-loaded'); });
			}, 600);

			if (!res.data.more) { wrap.remove(); }

			// Move focus to the first new card so keyboard users keep their place.
			if (added.length) {
				var link = added[0].querySelector('a');
				if (link) { link.setAttribute('tabindex', '-1'); link.focus({ preventScroll: true }); }
			}
		}).catch(function () {
			btn.classList.remove('loading');
			toast((data.i18n && data.i18n.error) || 'Something went wrong.', 'error');
		});
	});
})();
