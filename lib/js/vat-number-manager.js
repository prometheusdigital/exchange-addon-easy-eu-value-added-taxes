/*global jQuery */
var VATNumberManager = VATNumberManager || {};

jQuery(document).ready(function($) {
	var vat_number_manager = new VATNumberManager.AddEditVATNumberView();
		
	$( '#it-exchange-add-edit-vat-number' ).live( 'click', function( event ) {
		event.preventDefault();
		console.log( 'click' );
		vat_number_manager.render();
	});
});