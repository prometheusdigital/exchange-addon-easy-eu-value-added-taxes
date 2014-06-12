<?php
/**
 * Includes all of our AJAX functions
 * @since 1.0.0
 * @package exchange-addon-easy-eu-value-added-taxes
*/

/**
 * AJAX function called to add new content access rule rows
 *
 * @since 1.0.0
 * @return string HTML output of content access rule row div
*/
function it_exchange_easy_eu_value_added_taxes_addon_ajax_add_new_rate() {
	
	$return = '';
	
	if ( isset( $_REQUEST['key'] ) ) { //use isset() in case count is 0
		
		$key = $_REQUEST['key'];

		die( it_exchange_easy_eu_value_added_taxes_get_tax_row_settings( $key ) );		
	
	}
	
	die( $return );
}
add_action( 'wp_ajax_it-exchange-easy-value-added-taxes-addon-add-new-rate', 'it_exchange_easy_eu_value_added_taxes_addon_ajax_add_new_rate' );

/**
 * AJAX function called to add new content access rule rows
 *
 * @since 1.0.0
 * @return string HTML output of content access rule row div
*/
function it_exchange_easy_eu_value_added_taxes_add_edit_product_vat_calculator() {
	
	$results = array();
	
	if ( isset( $_POST['exempt'] ) && isset( $_POST['type'] ) ) {
	
		if ( 'true' === $_POST['exempt'] ) {
			$results = array(
				'pre-vat' => $_POST['pre-vat'],
				'post-vat' => $_POST['pre-vat']
			);
		} else {
			$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
		
			$pre_vat = it_exchange_convert_from_database_number( it_exchange_convert_to_database_number( $_POST['pre-vat'] ) );
			$post_vat = it_exchange_convert_from_database_number( it_exchange_convert_to_database_number( $_POST['post-vat'] ) );
		
			if ( 'default' === $_POST['type'] ) {
				foreach( $settings['tax-rates'] as $tax_rate ) {
					if ( 'checked' === $tax_rate['default'] ) {
						$rate = $tax_rate['rate'];
					}
				}
			} else {
				if ( isset( $settings['tax-rates'][$_POST['type']]['rate'] ) )
					$rate = $settings['tax-rates'][$_POST['type']]['rate'];
			}
						
			if ( !empty( $_POST['reverse'] ) && 'true' === $_POST['reverse'] ) {
				$results = array(
					'pre-vat' => $post_vat / ( ( 100 + $rate ) / 100 ),
					'post-vat' => $post_vat,
				);
			} else {
				$results = array(
					'pre-vat' => $pre_vat,
					'post-vat' => $pre_vat * ( ( 100 + $rate ) / 100 ),
				);
			}
		}
	}
	
	$return = array(
		'pre-vat' => it_exchange_format_price( $results['pre-vat'] ),
		'post-vat' => it_exchange_format_price( $results['post-vat'] ),
	);
		
	die( json_encode( $return ) );
}
add_action( 'wp_ajax_it-exchange-easy-value-added-taxes-add-edit-product-vat-calculator', 'it_exchange_easy_eu_value_added_taxes_add_edit_product_vat_calculator' );

/**
 * Ajax called from Backbone modal to add new EU VAT number to transaction.
 *
 * @since 1.0.0
*/
function it_exchange_easy_eu_value_added_taxes_save_vat_number() {	
	
	$errors = array();
		
	if ( ! empty( $_POST ) ) {
		
		if ( wp_verify_nonce( $_POST['it-exchange-easy-value-added-taxes-add-edit-vat-number-nonce'], 'it-exchange-easy-value-added-taxes-add-edit-vat-number' ) ) {
						
	        if ( empty( $_POST['eu-vat-country'] ) ) {
	            $errors[] = __( 'You must select a valid EU Member State.', 'LION' );
	        } else {
	            $vat_country = $_POST['eu-vat-country'];
	        }
	        
	        if ( empty( $_POST['eu-vat-number'] ) ) {
	            $errors[] = __( 'You must enter a valid VAT number.', 'LION' );
	        } else {
	            $vat_number = $_POST['eu-vat-number'];
	        }
	        		        
	        if ( empty( $errors ) ) {
	        	$return = it_exchange_easy_eu_value_added_taxes_addon_verify_vat( $vat_country, $vat_number );
	        	
				if ( true === $return ) {
					$tax_session = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );
					$tax_session['vat_country'] = $vat_country;
					$tax_session['vat_number'] = $vat_number;
					it_exchange_update_session_data( 'addon_easy_eu_value_added_taxes', $tax_session );
					wp_send_json_success();
				} else
					$errors[] = __( 'Unable to verify VAT Number, please try again', 'LION' );
								}		
		} else {
			
			$errors[] = __( 'Unable to verify security token, please try again', 'LION' );
			
		}
		
	}
	
	wp_send_json_error( $errors );
}
add_action( 'wp_ajax_it-exchange-easy-value-added-taxes-save-vat-number', 'it_exchange_easy_eu_value_added_taxes_save_vat_number' );
add_action( 'wp_ajax_no_priv_it-exchange-easy-value-added-taxes-save-vat-number', 'it_exchange_easy_eu_value_added_taxes_save_vat_number' );

/**
 * Ajax called from Backbone modal to remove EU VAT number from transaction.
 *
 * @since 1.0.0
*/
function it_exchange_easy_eu_value_added_taxes_remove_vat_number() {	
	
	$errors = array();
		
	if ( ! empty( $_POST ) ) {
		
		if ( wp_verify_nonce( $_POST['it-exchange-easy-value-added-taxes-add-edit-vat-number-nonce'], 'it-exchange-easy-value-added-taxes-add-edit-vat-number' ) ) {
						
			$tax_session = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );
			unset( $tax_session['vat_country'] );
			unset( $tax_session['vat_number'] );
			it_exchange_update_session_data( 'addon_easy_eu_value_added_taxes', $tax_session );
			wp_send_json_success();

		} else {
			
			$errors[] = __( 'Unable to verify security token, please try again', 'LION' );
			
		}
		
	}
	
	wp_send_json_error( $errors );
}
add_action( 'wp_ajax_it-exchange-easy-value-added-taxes-remove-vat-number', 'it_exchange_easy_eu_value_added_taxes_remove_vat_number' );
add_action( 'wp_ajax_no_priv_it-exchange-easy-value-added-taxes-remove-vat-number', 'it_exchange_easy_eu_value_added_taxes_remove_vat_number' );