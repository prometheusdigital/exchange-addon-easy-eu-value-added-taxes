jQuery(document).ready(function($) {    	
	$( 'input#euvat-exempt' ).on( 'click', function() {
		$( '.vat-tax-types' ).toggleClass( 'hide-if-js ' );
		add_edit_product_vat_calculator();
	});    	
	
	$( 'input#vat-moss' ).on( 'click', function() {
		$( '.vat-moss-tax-types' ).toggleClass( 'hide-if-js ' );
	});
	
	$( 'input#base-price' ).on( 'focusout', function() {
		$( 'input#vat-price-calculator-pre-vat-price' ).val( $( this ).val() );
		add_edit_product_vat_calculator();
	});
	
	$( 'select#euvat-type' ).on( 'change', function() {
		add_edit_product_vat_calculator();
	});
	
	$( 'input#vat-price-calculator-pre-vat-price' ).on( 'focusout', function() {
		add_edit_product_vat_calculator();
	});
	
	$( 'input#vat-price-calculator-price-w-vat' ).on( 'focusout', function() {
		add_edit_product_vat_calculator( true );
	});
	
	$( 'p#set-product-price-from-easy-eu-value-added-taxes-addon input' ).on( 'click', function() {
		$( 'input#base-price' ).val( $( 'input#vat-price-calculator-pre-vat-price' ).val() );
		$( window ).scrollTop( 0 );
		$( 'input#base-price' ).effect( 'highlight', {}, 3000 );
	});
	
	function add_edit_product_vat_calculator( reverse ) {
		reverse = typeof reverse !== 'undefined' ? reverse : false;
		var data = {
			'action':    'it-exchange-easy-eu-value-added-taxes-add-edit-product-vat-calculator',
			'exempt':    $( 'input#euvat-exempt' ).is( ':checked' ),
			'type':      $( 'select#euvat-type option:selected' ).val(),
			'pre-vat':   $( 'input#vat-price-calculator-pre-vat-price' ).val(),
			'post-vat':  $( 'input#vat-price-calculator-price-w-vat' ).val(),
			'reverse':   reverse
		}
		$.post( ajaxurl, data, function( response ) {
			if ( '' != response ) {
				var prices = $.parseJSON( response );
				$( 'input#vat-price-calculator-pre-vat-price' ).val( prices['pre-vat'] );
				$( 'input#vat-price-calculator-price-w-vat' ).val( prices['post-vat'] );
			}
		});
	}

});