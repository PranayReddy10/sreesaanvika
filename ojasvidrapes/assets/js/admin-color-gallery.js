/**
 * Colour galleries panel in the product editor.
 *
 * Each colour row keeps its attachment ids in a hidden input as a comma
 * separated list; this only has to keep the thumbnails and that input in step.
 */
(function ($) {
	'use strict';

	var strings = window.odCG || {};

	function ids(row) {
		var raw = row.find('.od-cg__value').val() || '';

		return raw.split(',').map(function (n) {
			return parseInt(n, 10);
		}).filter(function (n) {
			return n > 0;
		});
	}

	function write(row, list) {
		row.find('.od-cg__value').val(list.join(','));
	}

	function thumb(id, url) {
		return $('<span class="od-cg__img" />')
			.attr('data-id', id)
			.append($('<img alt="" />').attr('src', url))
			.append('<button type="button" class="od-cg__remove" aria-label="&times;">&times;</button>');
	}

	$(document).on('click', '.od-cg__add', function (e) {
		e.preventDefault();

		var row = $(this).closest('.od-cg__row');

		var frame = wp.media({
			title: strings.title || 'Choose images',
			button: { text: strings.button || 'Use these images' },
			library: { type: 'image' },
			multiple: 'add'
		});

		frame.on('select', function () {
			/*
			 * Until now the row was showing the colour's variation photos.
			 * The moment the shop owner picks their own, those become the
			 * override and the inherited preview goes.
			 */
			if (!row.hasClass('is-override')) {
				row.addClass('is-override').find('.od-cg__images').empty();
				row.find('.od-cg__source').text('');
			}

			var chosen = ids(row);
			var box = row.find('.od-cg__images');

			frame.state().get('selection').each(function (attachment) {
				var item = attachment.toJSON();

				if (chosen.indexOf(item.id) !== -1) { return; }

				var sizes = item.sizes || {};
				var url = (sizes.thumbnail && sizes.thumbnail.url) || (sizes.medium && sizes.medium.url) || item.url;

				chosen.push(item.id);
				box.append(thumb(item.id, url));
			});

			write(row, chosen);
		});

		frame.open();
	});

	$(document).on('click', '.od-cg__remove', function (e) {
		e.preventDefault();

		var img = $(this).closest('.od-cg__img');
		var row = img.closest('.od-cg__row');
		var gone = parseInt(img.attr('data-id'), 10);

		img.remove();

		write(row, ids(row).filter(function (n) { return n !== gone; }));
	});

	$(document).on('click', '.od-cg__clear', function (e) {
		e.preventDefault();

		var row = $(this).closest('.od-cg__row');

		row.removeClass('is-override').find('.od-cg__images').empty();
		write(row, []);

		if (!row.find('.od-cg__note').length) {
			$(this).after($('<span class="od-cg__note" />').text(strings.reverted || ''));
		}

		$(this).remove();
	});
}(jQuery));
