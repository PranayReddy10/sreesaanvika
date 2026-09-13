/**
 * Sree Saanvika — core front-end behaviour.
 * No dependencies; runs on its own.
 */
(function () {
	'use strict';

	var D = document;
	var data = window.ssData || {};
	var i18n = data.i18n || {};

	function $(sel, ctx) { return (ctx || D).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || D).querySelectorAll(sel)); }

	function on(el, ev, fn, opts) { if (el) { el.addEventListener(ev, fn, opts || false); } }

	/* ------------------------------------------------------------------
	 * Toasts
	 * ---------------------------------------------------------------- */
	var toastHost;

	function toast(message, type) {
		if (!message) { return; }

		if (!toastHost) {
			toastHost = D.createElement('div');
			toastHost.className = 'ss-toasts';
			toastHost.setAttribute('role', 'status');
			toastHost.setAttribute('aria-live', 'polite');
			D.body.appendChild(toastHost);
		}

		var icons = {
			success: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>',
			error: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>',
			info: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>'
		};

		var el = D.createElement('div');
		el.className = 'ss-toast ss-toast--' + (type || 'info');
		el.innerHTML = (icons[type] || icons.info) + '<span></span>';
		el.lastChild.textContent = message;
		toastHost.appendChild(el);

		setTimeout(function () {
			el.classList.add('is-out');
			setTimeout(function () {
				if (el.parentNode) { el.parentNode.removeChild(el); }
			}, 360);
		}, 3200);
	}

	window.ssToast = toast;

	/* ------------------------------------------------------------------
	 * AJAX helper
	 * ---------------------------------------------------------------- */
	function post(action, payload) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', data.nonce || '');

		Object.keys(payload || {}).forEach(function (key) {
			var value = payload[key];

			if (value && typeof value === 'object') {
				Object.keys(value).forEach(function (sub) {
					body.append(key + '[' + sub + ']', value[sub]);
				});
			} else if (value !== undefined && value !== null) {
				body.append(key, value);
			}
		});

		return fetch(data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (res) {
			return res.json().catch(function () {
				return { success: false, data: { message: i18n.error } };
			});
		});
	}

	window.ssPost = post;

	/* ------------------------------------------------------------------
	 * Scrim shared by the drawer and side panels
	 * ---------------------------------------------------------------- */
	var scrim = $('.ss-scrim');

	function lockScroll(lock) {
		D.body.classList.toggle('ss-no-scroll', !!lock);
	}

	function closeAllPanels() {
		$$('.ss-panel.is-open, .ss-drawer.is-open, .ss-shop-sidebar.is-open').forEach(function (el) {
			el.classList.remove('is-open');
			el.setAttribute('aria-hidden', 'true');
		});

		var overlay = $('.ss-search-overlay');
		if (overlay) { overlay.classList.remove('is-open'); }

		if (scrim) { scrim.classList.remove('is-open'); }
		lockScroll(false);
	}

	window.ssClosePanels = closeAllPanels;

	function openPanel(el) {
		if (!el) { return; }
		closeAllPanels();
		el.classList.add('is-open');
		el.setAttribute('aria-hidden', 'false');
		if (scrim) { scrim.classList.add('is-open'); }
		lockScroll(true);

		var focusable = el.querySelector('input, button, a[href]');
		if (focusable) { setTimeout(function () { focusable.focus(); }, 320); }
	}

	window.ssOpenPanel = openPanel;

	on(scrim, 'click', closeAllPanels);

	on(D, 'keydown', function (e) {
		if (e.key === 'Escape') { closeAllPanels(); }
	});

	// Anything with [data-panel="#id"] opens that panel; [data-close] closes.
	on(D, 'click', function (e) {
		var opener = e.target.closest('[data-panel]');

		if (opener) {
			e.preventDefault();
			openPanel($(opener.getAttribute('data-panel')));
			return;
		}

		if (e.target.closest('[data-close]')) {
			e.preventDefault();
			closeAllPanels();
		}
	});

	/* ------------------------------------------------------------------
	 * Mobile drawer accordion
	 * ---------------------------------------------------------------- */
	on(D, 'click', function (e) {
		var toggle = e.target.closest('.ss-drawer__toggle');

		if (!toggle) { return; }

		e.preventDefault();
		var li = toggle.parentNode;
		var open = li.classList.toggle('is-open');
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	});

	/* ------------------------------------------------------------------
	 * Search overlay
	 * ---------------------------------------------------------------- */
	var searchOverlay = $('.ss-search-overlay');

	if (searchOverlay) {
		$$('[data-search-open]').forEach(function (btn) {
			on(btn, 'click', function (e) {
				e.preventDefault();
				closeAllPanels();
				searchOverlay.classList.add('is-open');
				lockScroll(true);
				var field = $('input[type="search"]', searchOverlay);
				if (field) { setTimeout(function () { field.focus(); }, 300); }
			});
		});

		on(searchOverlay, 'click', function (e) {
			if (e.target === searchOverlay) { closeAllPanels(); }
		});

		// Live suggestions.
		var field = $('input[type="search"]', searchOverlay);
		var results = $('.ss-live-results', searchOverlay);
		var timer;

		if (field && results) {
			on(field, 'input', function () {
				clearTimeout(timer);
				var term = field.value.trim();

				if (term.length < 2) {
					results.innerHTML = '';
					return;
				}

				timer = setTimeout(function () {
					post('ss_live_search', { term: term }).then(function (res) {
						if (!res.success) { return; }

						var items = res.data.results || [];
						results.innerHTML = '';

						items.forEach(function (item) {
							var a = D.createElement('a');
							a.className = 'ss-live-result';
							a.href = item.url;

							var img = D.createElement('img');
							img.src = item.thumb;
							img.alt = '';
							img.loading = 'lazy';

							var box = D.createElement('div');
							var title = D.createElement('div');
							title.className = 'ss-live-result__title';
							title.textContent = item.title;
							box.appendChild(title);

							if (item.price) {
								var price = D.createElement('div');
								price.className = 'ss-live-result__price';
								price.textContent = item.price;
								box.appendChild(price);
							}

							a.appendChild(img);
							a.appendChild(box);
							results.appendChild(a);
						});
					});
				}, 280);
			});
		}
	}

	/* ------------------------------------------------------------------
	 * Sticky header shadow
	 * ---------------------------------------------------------------- */
	var header = $('.ss-header');
	var toTop = $('.ss-to-top');

	function onScroll() {
		var y = window.pageYOffset;

		if (header) { header.classList.toggle('is-stuck', y > 12); }
		if (toTop) { toTop.classList.toggle('is-visible', y > 600); }
	}

	on(window, 'scroll', onScroll, { passive: true });
	onScroll();

	on(toTop, 'click', function () {
		window.scrollTo({ top: 0, behavior: 'smooth' });
	});

	/* ------------------------------------------------------------------
	 * Hero slider
	 * ---------------------------------------------------------------- */
	$$('[data-hero]').forEach(function (hero) {
		var slides = $$('.ss-hero__slide', hero);
		if (slides.length < 1) { return; }

		var dots = $$('.ss-hero__dot', hero);
		var index = 0;
		var timer = null;
		var speed = (parseInt(hero.getAttribute('data-speed'), 10) || 6) * 1000;
		var auto = hero.getAttribute('data-autoplay') === '1' && slides.length > 1;

		function show(next) {
			index = (next + slides.length) % slides.length;

			slides.forEach(function (slide, i) {
				slide.classList.toggle('is-active', i === index);
				slide.setAttribute('aria-hidden', i === index ? 'false' : 'true');
			});

			dots.forEach(function (dot, i) {
				dot.classList.toggle('is-active', i === index);
				dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
			});
		}

		function start() {
			if (!auto) { return; }
			stop();
			timer = setInterval(function () { show(index + 1); }, speed);
		}

		function stop() {
			if (timer) { clearInterval(timer); timer = null; }
		}

		on($('.ss-hero__nav--next', hero), 'click', function () { show(index + 1); start(); });
		on($('.ss-hero__nav--prev', hero), 'click', function () { show(index - 1); start(); });

		dots.forEach(function (dot, i) {
			on(dot, 'click', function () { show(i); start(); });
		});

		on(hero, 'mouseenter', stop);
		on(hero, 'mouseleave', start);

		// Swipe.
		var startX = null;

		on(hero, 'touchstart', function (e) {
			startX = e.touches[0].clientX;
			stop();
		}, { passive: true });

		on(hero, 'touchend', function (e) {
			if (startX === null) { return; }
			var dx = e.changedTouches[0].clientX - startX;
			if (Math.abs(dx) > 45) { show(index + (dx < 0 ? 1 : -1)); }
			startX = null;
			start();
		});

		show(0);
		start();
	});

	/* ------------------------------------------------------------------
	 * Countdown timers
	 * ---------------------------------------------------------------- */
	$$('[data-countdown]').forEach(function (box) {
		var raw = box.getAttribute('data-countdown').replace(' ', 'T');
		var end = new Date(raw).getTime();

		if (isNaN(end)) { return; }

		var fields = {
			days: $('[data-unit="days"]', box),
			hours: $('[data-unit="hours"]', box),
			mins: $('[data-unit="mins"]', box),
			secs: $('[data-unit="secs"]', box)
		};

		function pad(n) { return (n < 10 ? '0' : '') + n; }

		function tick() {
			var diff = end - Date.now();

			if (diff <= 0) {
				diff = 0;
				clearInterval(timer);
			}

			var s = Math.floor(diff / 1000);

			if (fields.days) { fields.days.textContent = pad(Math.floor(s / 86400)); }
			if (fields.hours) { fields.hours.textContent = pad(Math.floor((s % 86400) / 3600)); }
			if (fields.mins) { fields.mins.textContent = pad(Math.floor((s % 3600) / 60)); }
			if (fields.secs) { fields.secs.textContent = pad(s % 60); }
		}

		var timer = setInterval(tick, 1000);
		tick();
	});

	/* ------------------------------------------------------------------
	 * Scroll reveal
	 * ---------------------------------------------------------------- */
	var reveals = $$('.ss-reveal');

	if (reveals.length) {
		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-in');
						io.unobserve(entry.target);
					}
				});
			}, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });

			reveals.forEach(function (el) { io.observe(el); });
		} else {
			reveals.forEach(function (el) { el.classList.add('is-in'); });
		}
	}

	/* ------------------------------------------------------------------
	 * Ticker — duplicate the track so the loop is seamless
	 * ---------------------------------------------------------------- */
	$$('.ss-ticker__track').forEach(function (track) {
		if (track.children.length && !track.dataset.cloned) {
			track.innerHTML += track.innerHTML;
			track.dataset.cloned = '1';
		}
	});

	/* ------------------------------------------------------------------
	 * Generic tabs — [data-tabs] with [data-tab] buttons and [data-pane]
	 * ---------------------------------------------------------------- */
	$$('[data-tabs]').forEach(function (group) {
		var buttons = $$('[data-tab]', group);
		var panes = $$('[data-pane]', group);

		buttons.forEach(function (btn) {
			on(btn, 'click', function () {
				var key = btn.getAttribute('data-tab');

				buttons.forEach(function (b) {
					var active = b === btn;
					b.classList.toggle('is-active', active);
					b.setAttribute('aria-selected', active ? 'true' : 'false');
				});

				panes.forEach(function (pane) {
					pane.classList.toggle('is-active', pane.getAttribute('data-pane') === key);
				});
			});
		});
	});

	/* ------------------------------------------------------------------
	 * Password reveal + strength meter
	 * ---------------------------------------------------------------- */
	on(D, 'click', function (e) {
		var btn = e.target.closest('.ss-pw-toggle');
		if (!btn) { return; }

		var input = btn.parentNode.querySelector('input');
		if (!input) { return; }

		var show = input.type === 'password';
		input.type = show ? 'text' : 'password';
		btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
		btn.classList.toggle('is-shown', show);
	});

	$$('[data-strength]').forEach(function (input) {
		var meter = D.querySelector(input.getAttribute('data-strength'));
		if (!meter) { return; }

		on(input, 'input', function () {
			var v = input.value;
			var score = 0;

			if (v.length >= 8) { score++; }
			if (/[A-Z]/.test(v) && /[a-z]/.test(v)) { score++; }
			if (/\d/.test(v)) { score++; }
			if (/[^A-Za-z0-9]/.test(v) && v.length >= 10) { score++; }

			meter.setAttribute('data-level', v ? String(score || 1) : '0');
		});
	});

	/* ------------------------------------------------------------------
	 * Auth forms (sign in / sign up)
	 * ---------------------------------------------------------------- */
	$$('[data-auth-form]').forEach(function (form) {
		on(form, 'submit', function (e) {
			e.preventDefault();

			var mode = form.getAttribute('data-auth-form');
			var btn = form.querySelector('[type="submit"]');
			var payload = {};

			new FormData(form).forEach(function (value, key) {
				payload[key] = value;
			});

			if (btn) { btn.classList.add('loading'); }

			post(mode === 'register' ? 'ss_register' : 'ss_login', payload).then(function (res) {
				if (btn) { btn.classList.remove('loading'); }

				var msg = (res.data && res.data.message) || i18n.error;

				if (res.success) {
					toast(msg, 'success');
					var next = form.getAttribute('data-redirect') || (res.data && res.data.redirect);
					if (next) { setTimeout(function () { window.location.href = next; }, 700); }
				} else {
					toast(msg, 'error');
				}
			}).catch(function () {
				if (btn) { btn.classList.remove('loading'); }
				toast(i18n.error, 'error');
			});
		});
	});

	/* ------------------------------------------------------------------
	 * Newsletter
	 * ---------------------------------------------------------------- */
	$$('[data-newsletter]').forEach(function (form) {
		on(form, 'submit', function (e) {
			e.preventDefault();

			var input = form.querySelector('input[type="email"]');
			var btn = form.querySelector('[type="submit"]');

			if (!input || !input.value) { return; }
			if (btn) { btn.classList.add('loading'); }

			post('ss_newsletter', { email: input.value }).then(function (res) {
				if (btn) { btn.classList.remove('loading'); }

				var msg = (res.data && res.data.message) || i18n.error;
				toast(msg, res.success ? 'success' : 'error');

				if (res.success) { input.value = ''; }
			}).catch(function () {
				if (btn) { btn.classList.remove('loading'); }
				toast(i18n.error, 'error');
			});
		});
	});

	/* ------------------------------------------------------------------
	 * Copy link
	 * ---------------------------------------------------------------- */
	on(D, 'click', function (e) {
		var btn = e.target.closest('.ss-copy-link');
		if (!btn) { return; }

		var url = btn.getAttribute('data-url') || window.location.href;

		if (navigator.clipboard) {
			navigator.clipboard.writeText(url).then(function () {
				toast(i18n.copied, 'success');
			});
		}
	});

	/* ------------------------------------------------------------------
	 * Collapsible filter panels
	 * ---------------------------------------------------------------- */
	on(D, 'click', function (e) {
		var head = e.target.closest('.ss-filter__head');
		if (!head) { return; }

		var box = head.closest('.ss-filter');
		var collapsed = box.classList.toggle('is-collapsed');
		head.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
	});
})();

/* ==========================================================================
 * Policy pages — highlight the contents entry for the section in view.
 * ========================================================================== */
(function () {
	'use strict';

	var nav = document.querySelector('.ss-legal-toc');
	var body = document.querySelector('.ss-legal__body');

	if (!nav || !body || !('IntersectionObserver' in window)) { return; }

	var links = {};

	Array.prototype.forEach.call(nav.querySelectorAll('a[href^="#"]'), function (a) {
		links[a.getAttribute('href').slice(1)] = a;
	});

	var headings = Array.prototype.slice.call(body.querySelectorAll('h2[id]'));

	if (!headings.length) { return; }

	function mark(id) {
		Object.keys(links).forEach(function (key) {
			links[key].classList.toggle('is-current', key === id);
		});
	}

	var seen = [];

	var io = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			var id = entry.target.id;
			var at = seen.indexOf(id);

			if (entry.isIntersecting) {
				if (at === -1) { seen.push(id); }
			} else if (at > -1) {
				seen.splice(at, 1);
			}
		});

		if (seen.length) {
			// Whichever visible heading sits highest on the page wins.
			var top = headings.filter(function (h) { return seen.indexOf(h.id) > -1; })[0];
			if (top) { mark(top.id); }
		}
	}, { rootMargin: '-15% 0px -70% 0px' });

	headings.forEach(function (h) { io.observe(h); });
	mark(headings[0].id);
})();
