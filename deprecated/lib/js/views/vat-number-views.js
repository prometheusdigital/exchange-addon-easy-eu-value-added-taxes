var VATNumberManager = VATNumberManager || {};

(function ($) {
	'use strict';

	VATNumberManager.AddEditVATNumberView = Backbone.View.extend({

		// Metabox container
		el : function() {
			return $( '#it-exchange-easy-eu-value-added-taxes-vat-manager-wrapper' );
		},

		template: wp.template( 'it-exchange-easy-eu-value-added-taxes-vat-manager-container' ),

		initialize : function() {
		},

		/**
		 * Event Handlers
		*/
		events : {
			'click .it-exchange-euvat-close-vat-manager a' : 'fadeOutAddCertPopup',
			'click .it-exchange-euvat-remove-vat-button'   : 'removeButton',
			'click .it-exchange-euvat-cancel-vat-button'   : 'fadeOutAddCertPopup',
			'click .it-exchange-euvat-save-vat-button'     : 'saveButton',
		},

		render : function() {
			// Empty container
			this.$el.empty();

			// Render
			this.$el.html( this.template );
			this.$el.fadeIn();

			return this;
		},

		fadeOutAddCertPopup : function ( event ) {
			event.preventDefault();
			this.$el.fadeOut();
		},

		removeButton : function ( event ) {
			event.preventDefault();
			var self = this;
			this.clearErrors( this.$el );
			var post_data = this.getFormData( this.$el.find('form#it-exchange-add-on-easy-eu-value-added-taxes-add-edit-vat') );
			this.removeVATNumber(post_data).done( function( data ) {
				it_exchange_euvat_refresh_cart();
				self.$el.fadeOut();
			}).fail( function( errors ) {
				$( '#it-exchange-easy-eu-value-added-taxes-vat-manager', self.$el ).scrollTop(0);
				self.displayErrors( self.$el, errors );
			});
		},

		saveButton : function ( event ) {
			event.preventDefault();
			var self = this;
			this.clearErrors( this.$el );
			var newVATNumber = this.getFormData( this.$el.find('form#it-exchange-add-on-easy-eu-value-added-taxes-add-edit-vat') );

			this.saveVATNumber(newVATNumber).done( function( data ) {
				it_exchange_euvat_refresh_cart();
				self.$el.fadeOut();
			}).fail( function( errors ) {

				if ( ! $.isArray( errors ) ) {
					errors = [ "An unknown error occurred." ];
				}

				$( '#it-exchange-easy-eu-value-added-taxes-vat-manager', self.$el ).scrollTop(0);
				self.displayErrors( self.$el, errors );
			});
		},

		//Auxiliar functions
		saveVATNumber : function ( vat_details ) {
			return wp.ajax.post( 'it-exchange-easy-eu-value-added-taxes-save-vat-number', vat_details );
		},

		//Auxiliar functions
		removeVATNumber : function ( post_data ) {
			return wp.ajax.post( 'it-exchange-easy-eu-value-added-taxes-remove-vat-number', post_data );
		},

		clearErrors : function ( self ) {
			$( '#it-exchange-easy-eu-value-added-taxes-vat-manager-error-area', self ).empty();
		},

		displayErrors : function ( self, errors ) {
			var elements = '';
			elements = '<ul class="it-exchange-messages it-exchange-errors">';
			$.each( errors, function( index, value ) {
			    elements += '<li>'+value+'</li>';
			});
			elements += '</ul>' ;
			$( '#it-exchange-easy-eu-value-added-taxes-vat-manager-error-area', self ).append( elements );
		},

		getFormData : function(form) {
			var unindexed_array = form.serializeArray();
			var indexed_array = {};

			$.map(unindexed_array, function(n, i){
				indexed_array[n['name']] = n['value'];
			});

			return indexed_array;
		},
	});

	function it_exchange_euvat_refresh_cart() {
		if ( 'undefined' !== typeof ITExchangeEasyValueAddedTaxesCheckoutPage && ITExchangeEasyValueAddedTaxesCheckoutPage ) {
			//refresh the checkout page
			location.reload();
		} else if ( 'undefined' !== typeof itExchangeSWState ){
			//refresh the superwidget
			itExchangeGetSuperWidgetState( itExchangeSWState );
		}
	}

})(jQuery);
