/* global jQuery, wp */
jQuery(function ($) {
	var frame;

	// Open the media modal
	$('#clinic_logo_button').on('click', function (e) {
		e.preventDefault();

		if (frame) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: 'Select or Upload Logo',
			button: { text: 'Use this logo' },
			library: { type: 'image' },
			multiple: false,
		});

		frame.on('select', function () {
			var data = frame.state().get('selection').first().toJSON();
			var url = data.sizes && data.sizes.medium ? data.sizes.medium.url : data.url;

			// Update hidden field
			$('#clinic_logo_field').val(data.id);

			// Update preview
			$('#clinic-logo-container img').remove();
			$('#clinic-logo-container').prepend(
				'<p><img src="' + url + '" style="max-width:200px; display:block; margin-bottom:10px;"></p>'
			);

			// Show remove button
			$('#clinic_logo_remove').show();
			$('#clinic_logo_button').text('Change Logo');
		});

		frame.open();
	});

	// Remove the image
	$('#clinic_logo_remove').on('click', function (e) {
		e.preventDefault();
		$('#clinic_logo_field').val('');
		$('#clinic-logo-container img').remove();
		$(this).hide();
		$('#clinic_logo_button').text('Select Logo');
	});
});
