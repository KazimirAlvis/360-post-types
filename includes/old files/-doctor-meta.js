jQuery(function ($) {
	let frame;

	$('#doctor_photo_button').on('click', function (e) {
		e.preventDefault();
		if (frame) {
			frame.open();
			return;
		}
		frame = wp.media({
			title: 'Select Doctor Photo',
			button: { text: 'Use this photo' },
			multiple: false,
		});
		frame.on('select', function () {
			let att = frame.state().get('selection').first().toJSON();
			$('#doctor_photo_field').val(att.id);
			$('#doctor-details-metabox img').remove(); // <-- fixed selector
			$('#doctor_photo_button').before(
				`<img src="${att.sizes.medium.url}" style="max-width:150px; display:block; margin-bottom:10px;" />`
			);
			$('#doctor_photo_remove').show();
		});
		frame.open();
	});

	$('#doctor_photo_remove').on('click', function () {
		$('#doctor_photo_field').val('');
		$('#doctor-details-metabox img').remove();
		$(this).hide();
	});
});
