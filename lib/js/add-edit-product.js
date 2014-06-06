jQuery(document).ready(function($) {    	
	// Format price
	$( 'input#evat-exempt' ).on( 'click', function() {
		$( '.vat-tax-types' ).toggleClass( 'hide-if-js ' );
		add_edit_product_vat_calculator();
	});
	
	$( 'input#base-price' ).on( 'focusout', function() {
		$( 'input#vat-price-calculator-pre-vat-price' ).val( $( this ).val() );
		add_edit_product_vat_calculator();
	});
	
	$( 'select#evat-type' ).on( 'change', function() {
		add_edit_product_vat_calculator();
	});
	
	$( 'input#vat-price-calculator-pre-vat-price' ).on( 'focusout', function() {
		add_edit_product_vat_calculator();
	});
	
	$( 'input#vat-price-calculator-price-w-vat' ).on( 'focusout', function() {
		add_edit_product_vat_calculator( true );
	});
	
	$( 'p#set-product-price-from-easy-value-added-taxes-addon input' ).on( 'click', function() {
		$( 'input#base-price' ).val( $( 'input#vat-price-calculator-pre-vat-price' ).val() );
		$( window ).scrollTop( 0 );
		$( 'input#base-price' ).effect( 'highlight', {}, 5000 );
	});
	
	function add_edit_product_vat_calculator( reverse ) {
		reverse = typeof reverse !== 'undefined' ? reverse : false;
		var data = {
			'action':    'it-exchange-easy-value-added-taxes-add-edit-product-vat-calculator',
			'exempt':    $( 'input#evat-exempt' ).is( ':checked' ),
			'type':      $( 'select#evat-type option:selected' ).val(),
			'pre-vat':   $( 'input#vat-price-calculator-pre-vat-price' ).val(),
			'post-vat':  $( 'input#vat-price-calculator-price-w-vat' ).val(),
			'reverse':   reverse
		}
		$.post( ajaxurl, data, function( response ) {
			if ( '' != response ) {
				prices = $.parseJSON( response );
				$( 'input#vat-price-calculator-pre-vat-price' ).val( prices['pre-vat'] );
				$( 'input#vat-price-calculator-price-w-vat' ).val( prices['post-vat'] );
			}
		});
	}

});