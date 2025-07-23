/* global jQuery, wp */
jQuery(function ($) {
	var frame;

	$('#clinic_thumbnail_button').on('click', function (e) {
		e.preventDefault();

		if (frame) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: 'Select or Upload Thumbnail',
			button: { text: 'Use this thumbnail' },
			library: { type: 'image' },
			multiple: false,
		});

		frame.on('select', function () {
			var data = frame.state().get('selection').first().toJSON();
			var url = data.sizes && data.sizes.medium ? data.sizes.medium.url : data.url;

			$('#clinic_thumbnail_field').val(data.id);

			$('#clinic-thumbnail-container img').remove();
			$('#clinic-thumbnail-container').prepend(
				'<p><img src="' + url + '" style="max-width:200px; display:block; margin-bottom:10px;"></p>'
			);

			$('#clinic_thumbnail_remove').show();
			$('#clinic_thumbnail_button').text('Change Thumbnail');
		});

		frame.open();
	});

	$('#clinic_thumbnail_remove').on('click', function (e) {
		e.preventDefault();
		$('#clinic_thumbnail_field').val('');
		$('#clinic-thumbnail-container img').remove();
		$(this).hide();
		$('#clinic_thumbnail_button').text('Select Thumbnail');
	});
});
