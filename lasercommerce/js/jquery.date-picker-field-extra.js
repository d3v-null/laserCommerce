/**
 * Code pinched from Woocommerce product meta box so that styles match
 */
 /*global woocommerce_admin_meta_boxes */
jQuery(function($){
		// Sale price schedule

	$('.sale_price_dates_fields_extra').each(function() {

		var $these_sale_dates = $(this);
		var sale_schedule_set = false;
		var $wrap = $these_sale_dates.closest( 'div, table' );

		$these_sale_dates.find('input').each(function(){
			if ( $(this).val() != '' )
				sale_schedule_set = true;
		});

		if ( sale_schedule_set ) {

			$wrap.find('.sale_schedule').hide();
			$wrap.find('.sale_price_dates_fields_extra').show();

		} else {

			$wrap.find('.sale_schedule').show();
			$wrap.find('.sale_price_dates_fields_extra').hide();

		}

	});

	$('#woocommerce-product-data').on( 'click', '.sale_schedule', function() {
		var $wrap = $(this).closest( 'div, table' );

		$(this).hide();
		$wrap.find('.cancel_sale_schedule').show();
		$wrap.find('.sale_price_dates_fields').show();

		return false;
	});
	$('#woocommerce-product-data').on( 'click', '.cancel_sale_schedule', function() {
		var $wrap = $(this).closest( 'div, table' );

		$(this).hide();
		$wrap.find('.sale_schedule').show();
		$wrap.find('.sale_price_dates_fields').hide();
		$wrap.find('.sale_price_dates_fields').find('input').val('');

		return false;
	});

		// DATE PICKER FIELDS
	var dates = $( ".sale_price_dates_fields_extra input" ).datepicker({
		defaultDate: "",
		dateFormat: "yy-mm-dd",
		numberOfMonths: 1,
		showButtonPanel: true,
		showOn: "button",
		buttonImage: woocommerce_admin_meta_boxes.calendar_image,
		buttonImageOnly: true,
		onSelect: function( selectedDate ) {
			var option = $(this).is('#_sale_price_dates_from, .sale_price_dates_from') ? "minDate" : "maxDate";

			var instance = $( this ).data( "datepicker" ),
				date = $.datepicker.parseDate(
					instance.settings.dateFormat ||
					$.datepicker._defaults.dateFormat,
					selectedDate, instance.settings );
			dates.not( this ).datepicker( "option", option, date );
		}
	});
})