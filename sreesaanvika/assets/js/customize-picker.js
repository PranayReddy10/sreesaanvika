/**
 * The Customizer picker: search, choose, drag into order.
 *
 * The stored value is a plain comma separated list, the same shape the
 * settings had when they were typed by hand, so nothing downstream changes.
 */
(function ($) {
	'use strict';

	var config = window.ssPicker || {};
	var i18n = config.i18n || {};

	/* Remember what each value is called, so a chosen row can be redrawn
	   without asking the server again. */
	var labels = {};

	function key(entity, value) { return entity + ':' + value; }

	function values(box) {
		var raw = $('[data-value]', box).val() || '';

		return raw.split(',').map(function (v) {
			return v.trim();
		}).filter(Boolean);
	}

	function write(box, list) {
		$('[data-value]', box)
			.val(list.join(','))
			.trigger('change'); // what the Customizer setting is bound to
	}

	function drawChosen(box) {
		var entity = box.data('entity');
		var list = values(box);
		var chosen = $('[data-chosen]', box).empty();

		$('[data-empty]', box).toggle(list.length === 0);

		list.forEach(function (value) {
			var label = labels[key(entity, value)] || value;

			$('<li />')
				.attr('data-v', value)
				.append($('<span class="ss-picker__grip" aria-hidden="true">⋮⋮</span>'))
				.append($('<span class="ss-picker__label" />').text(label))
				.append($('<button type="button" class="ss-picker__btn" data-up />')
					.attr('title', i18n.up || 'Move up').text('▲'))
				.append($('<button type="button" class="ss-picker__btn" data-down />')
					.attr('title', i18n.down || 'Move down').text('▼'))
				.append($('<button type="button" class="ss-picker__btn ss-picker__btn--x" data-x />')
					.attr('title', i18n.remove || 'Remove').text('×'))
				.appendTo(chosen);
		});
	}

	function ask(box, params, done) {
		$.getJSON(config.ajaxUrl, $.extend({
			action: 'ss_picker_search',
			nonce: config.nonce,
			entity: box.data('entity')
		}, params)).done(function (out) {
			done((out && out.success && out.data) || []);
		}).fail(function () {
			done([]);
		});
	}

	/* Name whatever is already stored, then draw it. */
	function hydrate(box) {
		var list = values(box);

		if (!list.length) {
			drawChosen(box);
			return;
		}

		ask(box, { have: list.join(',') }, function (rows) {
			rows.forEach(function (row) {
				labels[key(box.data('entity'), row.value)] = row.label;
			});

			drawChosen(box);
		});
	}

	function search(box) {
		var term = $('[data-search]', box).val().trim();
		var results = $('[data-results]', box);

		ask(box, { q: term }, function (rows) {
			var have = values(box);

			results.empty().prop('hidden', false);

			if (!rows.length) {
				results.append($('<li class="is-in" />').text(i18n.none || 'Nothing found.'));
				return;
			}

			rows.forEach(function (row) {
				labels[key(box.data('entity'), row.value)] = row.label;

				var already = have.indexOf(row.value) !== -1;
				var li = $('<li />').attr('data-v', row.value).toggleClass('is-in', already);

				li.append(document.createTextNode(row.label));

				if (row.sub) { li.append($('<span />').text(row.sub)); }

				results.append(li);
			});
		});
	}

	var timer;

	$(document).on('input', '.ss-picker__search', function () {
		var box = $(this).closest('.ss-picker');

		clearTimeout(timer);
		timer = setTimeout(function () { search(box); }, 260);
	});

	$(document).on('focus', '.ss-picker__search', function () {
		search($(this).closest('.ss-picker'));
	});

	$(document).on('click', '.ss-picker__results li', function () {
		var li = $(this);

		if (li.hasClass('is-in')) { return; }

		var box = li.closest('.ss-picker');
		var list = values(box);
		var value = String(li.data('v'));

		if (!value || list.indexOf(value) !== -1) { return; }

		list.push(value);
		write(box, list);
		drawChosen(box);
		li.addClass('is-in');
	});

	$(document).on('click', '.ss-picker__chosen [data-x]', function () {
		var li = $(this).closest('li');
		var box = li.closest('.ss-picker');

		write(box, values(box).filter(function (v) { return v !== String(li.data('v')); }));
		drawChosen(box);
		$('[data-results] li[data-v="' + li.data('v') + '"]', box).removeClass('is-in');
	});

	$(document).on('click', '.ss-picker__chosen [data-up], .ss-picker__chosen [data-down]', function () {
		var li = $(this).closest('li');
		var box = li.closest('.ss-picker');
		var list = values(box);
		var at = list.indexOf(String(li.data('v')));
		var to = at + ($(this).is('[data-up]') ? -1 : 1);

		if (at < 0 || to < 0 || to >= list.length) { return; }

		list.splice(to, 0, list.splice(at, 1)[0]);
		write(box, list);
		drawChosen(box);
	});

	/* Dragging says "this order matters" more plainly than two arrows do. */
	function sortable(box) {
		var chosen = $('[data-chosen]', box);

		if (!chosen.length || !$.fn.sortable) { return; }

		chosen.sortable({
			axis: 'y',
			containment: 'parent',
			tolerance: 'pointer',
			start: function (e, ui) { ui.item.addClass('is-dragging'); },
			stop: function (e, ui) { ui.item.removeClass('is-dragging'); },
			update: function () {
				write(box, chosen.children().map(function () {
					return String($(this).data('v'));
				}).get());
			}
		});
	}

	function start() {
		$('.ss-picker').each(function () {
			var box = $(this);

			if (box.data('ssReady')) { return; }

			box.data('ssReady', true);
			hydrate(box);
			sortable(box);
		});
	}

	$(start);

	// Controls are drawn as their section is opened.
	if (window.wp && wp.customize) {
		wp.customize.bind('ready', function () {
			wp.customize.section.each(function (section) {
				section.expanded.bind(function (open) {
					if (open) { setTimeout(start, 50); }
				});
			});
		});
	}
}(jQuery));
