jQuery(document).ready(function($) {
	$( '.it-exchange-easy-eu-value-added-taxes-addon-settings' ).on( 'change', '#vat-country', function( event ) {
		$( '#vat-number-country-code' ).val( $( this ).val() );
		$( 'div' ).removeClass( 'hidden-vat-country' );
		$( 'div#value-added-tax-vat-moss-rate-for-' + $( this ).val() ).addClass( 'hidden-vat-country' );
	});
	
	$( '.it-exchange-easy-eu-value-added-taxes-addon-settings' ).on( 'click', '#new-tax-rate', function( event ) {
		event.preventDefault();
		var parent = $( this ).parent();
		var data = {
			'action': 'it-exchange-easy-eu-value-added-taxes-addon-add-new-rate',
			'key':  ++it_exchange_easy_eu_value_added_taxes_addon_iteration,
		}
		$.post( ajaxurl, data, function( response ) {
			console.log( response );
			$( '#value-added-tax-rate-table' ).append( response );
		});
	});
	
	$( '.it-exchange-easy-eu-value-added-taxes-addon-settings' ).on( 'click', '.it-exchange-easy-eu-value-added-taxes-addon-delete-tax-rate', function( event ) {
		event.preventDefault();
		$( this ).closest( '.item-row' ).remove();
	});

	$( '.it-exchange-easy-eu-value-added-taxes-addon-default-checkmark' ).tooltip({
		items: 'span',
		content: function() {
			var checkmark = $( this );
			if ( $( this ).hasClass( 'it-exchange-easy-eu-value-added-taxes-addon-default-checkmark-checked' ) ) {
				return 'Current Default';
			} else {
				return 'Set as Default';
			}
		},
		position: {
			my: 'left+25 center',
			at: 'left center'
		}
	});
	
	$( '.it-exchange-easy-eu-value-added-taxes-addon-settings' ).on( 'click', '.block-column-default', function( event ) {
		event.preventDefault();
		var parent = $( this ).parent().parent();
		$( '.it-exchange-easy-eu-value-added-taxes-addon-default-checkmark', parent ).removeClass( 'it-exchange-easy-eu-value-added-taxes-addon-default-checkmark-checked' );
		$( '.it-exchange-easy-eu-value-added-taxes-addon-default-checkmark', parent ).val( 'unchecked' );
		$( '.it-exchange-easy-eu-value-added-taxes-addon-default-checkmark', this ).addClass( 'it-exchange-easy-eu-value-added-taxes-addon-default-checkmark-checked' );
		$( '.it-exchange-easy-eu-value-added-taxes-addon-default-checkmark', this ).val( 'checked' );
	});
	
	//VAT MOSS
	$( '#value-added-tax-vat-moss-rate-tables' ).on( 'click', '.new-vat-moss-tax-rate', function( event ) {
		console.log( 'here' );
		event.preventDefault();
		var parent = $( this ).parent().parent();
		var memberstate = $( this ).data( 'memberstate' );
		console.log( memberstate );
		var data = {
			'action': 'it-exchange-easy-eu-value-added-taxes-addon-add-new-rate',
			'key':  ++window['it_exchange_easy_eu_value_added_taxes_addon_vat_moss_' + memberstate + '_rate_iteration']
,
			'memberstate': memberstate,
		}
		$.post( ajaxurl, data, function( response ) {
			console.log( response );
			$( '.value-added-tax-vat-moss-rate-table', parent ).append( response );
		});
	});
});