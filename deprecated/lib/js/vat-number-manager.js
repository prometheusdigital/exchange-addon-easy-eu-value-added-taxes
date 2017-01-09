/*global jQuery */
var VATNumberManager = VATNumberManager || {};

jQuery(document).ready(function($) {
	var vat_number_manager = new VATNumberManager.AddEditVATNumberView();
		
	$( '.it-exchange-super-widget, .it-exchange-add-edit-vat-number-div' ).on( 'click', '#it-exchange-add-edit-vat-number', function( event ) {
		event.preventDefault();
		vat_number_manager.render();
	});
});
