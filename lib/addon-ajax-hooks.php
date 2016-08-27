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
		$member_state = $_REQUEST['memberstate'];

		die( it_exchange_easy_eu_value_added_taxes_get_tax_row_settings( $key, array(), $member_state ) );

	}

	die( $return );
}
add_action( 'wp_ajax_it-exchange-easy-eu-value-added-taxes-addon-add-new-rate', 'it_exchange_easy_eu_value_added_taxes_addon_ajax_add_new_rate' );

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
					if ( !empty( $tax_rate['default'] ) && 'checked' === $tax_rate['default'] ) {
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
		'pre-vat' => html_entity_decode( it_exchange_format_price( $results['pre-vat'] ) ),
		'post-vat' => html_entity_decode( it_exchange_format_price( $results['post-vat'] ) ),
	);

	die( json_encode( $return ) );
}
add_action( 'wp_ajax_it-exchange-easy-eu-value-added-taxes-add-edit-product-vat-calculator', 'it_exchange_easy_eu_value_added_taxes_add_edit_product_vat_calculator' );

/**
 * Ajax called from Backbone modal to add new EU VAT number to transaction.
 *
 * @since 1.0.0
*/
function it_exchange_easy_eu_value_added_taxes_save_vat_number() {

	if ( empty( $_POST ) ) {
		wp_send_json_error();
	}

	$errors = array();

    if ( empty( $_POST['eu-vat-country'] ) ) {
        $errors[] = __( 'You must select a valid EU Country.', 'LION' );
    } else {
        $vat_country = $_POST['eu-vat-country'];
    }

    if ( empty( $_POST['eu-vat-number'] ) ) {
        $errors[] = __( 'You must enter a valid VAT number.', 'LION' );
    } else {
        $vat_number = $_POST['eu-vat-number'];
    }

    if ( count( $errors ) ) {
    	wp_send_json_error( $errors );
    }

    $valid = it_exchange_easy_eu_value_added_taxes_addon_verify_vat( $vat_country, $vat_number );

	if ( $valid === true ) {

		$cart = it_exchange_get_current_cart();
		$cart->set_meta( 'eu-vat-country', $vat_country );
		$cart->set_meta( 'eu-vat-number', $vat_number );

		wp_send_json_success();
	} else {
		wp_send_json_error( array( __( 'Unable to verify VAT Number, please try again', 'LION' ) ) );
	}
}

add_action( 'wp_ajax_it-exchange-easy-eu-value-added-taxes-save-vat-number', 'it_exchange_easy_eu_value_added_taxes_save_vat_number' );
add_action( 'wp_ajax_nopriv_it-exchange-easy-eu-value-added-taxes-save-vat-number', 'it_exchange_easy_eu_value_added_taxes_save_vat_number' );

/**
 * Ajax called from Backbone modal to remove EU VAT number from transaction.
 *
 * @since 1.0.0
*/
function it_exchange_easy_eu_value_added_taxes_remove_vat_number() {

	if ( empty( $_POST ) ) {
		wp_send_json_error();
	}

	$nonce_error =  __( 'Unable to verify security token, please try again', 'LION' );

	if ( ! isset( $_POST['it-exchange-easy-eu-value-added-taxes-add-edit-vat-number-nonce'] ) ) {
		wp_send_json_error( array( $nonce_error ) );
	}

	$nonce = $_POST['it-exchange-easy-eu-value-added-taxes-add-edit-vat-number-nonce'];

	if ( ! wp_verify_nonce( $nonce, 'it-exchange-easy-eu-value-added-taxes-add-edit-vat-number' ) ) {
		wp_send_json_error( array( $nonce_error ) );
	}

	$cart = it_exchange_get_current_cart();
	$cart->remove_meta( 'eu-vat-country' );
	$cart->remove_meta( 'eu-vat-number' );

	wp_send_json_success();
}

add_action( 'wp_ajax_it-exchange-easy-eu-value-added-taxes-remove-vat-number', 'it_exchange_easy_eu_value_added_taxes_remove_vat_number' );
add_action( 'wp_ajax_nopriv_it-exchange-easy-eu-value-added-taxes-remove-vat-number', 'it_exchange_easy_eu_value_added_taxes_remove_vat_number' );