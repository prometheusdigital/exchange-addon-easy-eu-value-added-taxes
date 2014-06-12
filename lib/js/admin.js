jQuery(document).ready(function($) {    
	$( '#new-tax-rate' ).live( 'click', function( event ) {
		event.preventDefault();
		var parent = $( this ).parent();
		var data = {
			'action': 'it-exchange-easy-value-added-taxes-addon-add-new-rate',
			'key':  ++it_exchange_easy_eu_value_added_taxes_addon_iteration,
		}
		console.log( it_exchange_easy_eu_value_added_taxes_addon_iteration );
		$.post( ajaxurl, data, function( response ) {
			console.log( response );
			$( '#value-added-tax-rate-table' ).append( response );
		});
	});
	
	$( '.it-exchange-easy-value-added-taxes-addon-delete-tax-rate' ).live( 'click', function( event ) {
		event.preventDefault();
		$( this ).closest( '.item-row' ).remove();
	});

	$( '.it-exchange-easy-value-added-taxes-addon-default-checkmark' ).tooltip({
		items: 'span',
		content: function() {
			var checkmark = $( this );
			if ( $( this ).hasClass( 'it-exchange-easy-value-added-taxes-addon-default-checkmark-checked' ) ) {
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
	
	$( '.block-column-default' ).live( 'click', function( event ) {
		event.preventDefault();
		$( '.it-exchange-easy-value-added-taxes-addon-default-checkmark' ).removeClass( 'it-exchange-easy-value-added-taxes-addon-default-checkmark-checked' );
		$( '.it-exchange-easy-value-added-taxes-addon-default-checkmark' ).val( 'unchecked' );
		$( '.it-exchange-easy-value-added-taxes-addon-default-checkmark', this ).addClass( 'it-exchange-easy-value-added-taxes-addon-default-checkmark-checked' );
		$( '.it-exchange-easy-value-added-taxes-addon-default-checkmark', this ).val( 'checked' );
	});
});