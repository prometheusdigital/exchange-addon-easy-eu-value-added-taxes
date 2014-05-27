jQuery(document).ready(function($) {    
	$( '#new-tax-rate' ).live( 'click', function( event ) {
		event.preventDefault();
		var parent = $( this ).parent();
		var data = {
			'action': 'it-exchange-easy-value-added-taxes-addon-add-new-rate',
			'count':  it_exchange_easy_value_added_taxes_addon_iteration,
		}
		$.post( ajaxurl, data, function( response ) {
			console.log( response );
			$( '#value-added-tax-rate-table' ).append( response );
		});
		it_exchange_easy_value_added_taxes_addon_iteration++;
	});
	
	$( '.it-exchange-easy-value-added-taxes-addon-delete-tax-rate' ).live( 'click', function( event ) {
		event.preventDefault();
		$( this ).closest( '.item-row' ).remove();
	});

});