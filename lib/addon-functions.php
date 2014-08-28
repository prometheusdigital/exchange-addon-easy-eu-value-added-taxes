<?php
/**
 * iThemes Exchange Easy EU Value Added Taxes Add-on
 * @package exchange-addon-easy-eu-value-added-taxes
 * @since 1.0.0
*/

function it_exchange_easy_eu_value_added_taxes_get_tax_row_settings( $key, $rate=array() ) {
	if ( empty( $rate ) ) { //just set some defaults
		$rate = array( //Member State
				'label'    => '',
				'rate'     => 0,
				'shipping' => false,
				'default'  => 'unchecked',
			);
	}
		
	$output  = '<div class="item-row block-row">'; //start block-row
	
	$output .= '<div class="item-column block-column block-column-1">';
	$output .= '<input type="text" name="it-exchange-add-on-easy-eu-value-added-taxes-tax-rates[' . $key . '][label]" value="' . $rate['label'] . '" />';
	$output .= '</div>';
	
	$output .= '<div class="item-column block-column block-column-2">';
	$output .= '<input type="text" name="it-exchange-add-on-easy-eu-value-added-taxes-tax-rates[' . $key . '][rate]" value="' . $rate['rate'] . '" />';
	$output .= '</div>';
	
	$output .= '<div class="item-column block-column block-column-3">';
	$shipping = empty( $rate['shipping'] ) ? false : true;
	$output .= '<input type="checkbox" name="it-exchange-add-on-easy-eu-value-added-taxes-tax-rates[' . $key . '][shipping]" ' . checked( $shipping, true, false ) . ' />';
	$output .= '</div>';
	
	$output .= '<div class="item-column block-column block-column-default">';
	$output .= '<span class="it-exchange-easy-eu-value-added-taxes-addon-default-checkmark it-exchange-easy-eu-value-added-taxes-addon-default-checkmark-' . $rate['default'] . '"></span>';	
	$output .= '<input type="hidden" class="it-exchange-easy-eu-value-added-taxes-addon-default-checkmark" name="it-exchange-add-on-easy-eu-value-added-taxes-tax-rates[' . $key . '][default]" value="' . $rate['default'] . '" />';
	$output .= '</div>';
	
	$output .= '<div class="item-column block-column block-column-delete">';
	$output .= '<a href class="it-exchange-easy-eu-value-added-taxes-addon-delete-tax-rate it-exchange-remove-item">&times;</a>';
	$output .= '</div>';
	
	$output .= '</div>'; //end block-row
	
	return $output;
}

function it_exchange_easy_eu_value_added_taxes_setup_session( $clear_cache=false ) {
	$tax_session = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );
	$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
	$taxes = array();
	$total_taxes = 0;
	
	//We always want to get the Shipping Address if it's available...
	$address = it_exchange_get_cart_shipping_address();
	
	//We only care about the province!
	if ( empty( $address['country'] ) ) 
		$address = it_exchange_get_cart_billing_address();
	
	//If not a member state, not taxable
	$memberstates = it_exchange_get_data_set( 'eu-member-states' );
	if ( !empty( $address['country'] ) && empty( $memberstates[$address['country']] ) ) {
		return false;
	} else {
		if ( empty( $tax_session['vat_country'] ) && empty( $tax_session['vat_number'] ) )
			$tax_session['vat_country'] = $address['country'];
	}
	
	if ( empty( $tax_session['vat_number'] ) || $settings['vat-country'] == $address['country'] ) {
		//Charge Tax no VAT Number is supplied or if the customer is purchasing from the same member state
		$tax_session['summary_only'] = false;
	} else {
		//Otherwise, don't charge tax, just summarize the VAT
		$tax_session['summary_only'] = true;
	}
	
	if ( ! $products = it_exchange_get_cart_products() )
		return false;
	
	$cart_subtotal = 0;
	foreach( (array) $products as $product ) {
		$cart_subtotal += it_exchange_get_cart_product_subtotal( $product, false );
	}
	$cart_subtotal = apply_filters( 'it_exchange_get_cart_subtotal', $cart_subtotal );
	$shipping_cost = it_exchange_get_cart_shipping_cost( false, false );
	
	if ( !empty( $tax_session ) ) {

		//We want to store the cart subtotal, in case it changes so we know we need to recalculate tax
		if ( empty( $tax_session['country'] ) || $address['country'] != $tax_session['country'] ) {
			$tax_session['country'] = $address['country'];
			$clear_cache = true; //re-calculate taxes
		}

		//We want to store the cart subtotal, in case it changes so we know we need to recalculate tax
		if ( empty( $tax_session['cart_subtotal'] ) || $cart_subtotal != $tax_session['cart_subtotal'] ) {
			$tax_session['cart_subtotal'] = $cart_subtotal;
			$clear_cache = true; //re-calculate taxes
		}
		
		//We want to store the cart subtotal with shipping, in case it changes so we know we need to recalculate tax
		if ( empty( $tax_session['shipping_cost'] ) || $shipping_cost != $tax_session['shipping_cost'] ) {
			$tax_session['shipping_cost'] = $shipping_cost;
			$clear_cache = true; //re-calculate taxes
		}
		
	} else {
		$clear_cache = true; //not really any cache, but it's easier this way :)
		$tax_session['country'] = $address['country'];
		$tax_session['cart_subtotal'] = $cart_subtotal;
		$tax_session['shipping_cost'] = $shipping_cost;
	}
	
	if ( $clear_cache ) {
	
		$subtotals = array();
		$product_taxes = array();
		$default_rate = 0;
		foreach ( $settings['tax-rates'] as $key => $rate ) {
			$subtotals[$key] = 0;
			if ( !empty( $rate['default'] ) && 'checked' === $rate['default'] )
				$default_rate = $key;
		}

		$applied_coupons = it_exchange_get_applied_coupons();
		if ( !empty( $applied_coupons['cart'] ) ) {
			foreach( $applied_coupons['cart'] as $key => $coupon ) {
					$product_id = get_post_meta( $coupon['id'], '_it-basic-product-id', true );
					$applied_coupons['cart'][$key]['product_id'] = $product_id;
			}
		}
		$product_count = it_exchange_get_cart_products_count( true );
				
		foreach( (array) $products as $product ) {	
	
			if ( it_exchange_product_supports_feature( $product['product_id'], 'value-added-taxes' ) ) {
				if ( !it_exchange_get_product_feature( $product['product_id'], 'value-added-taxes', array( 'setting' => 'exempt' ) ) ) {
					$tax_type = it_exchange_get_product_feature( $product['product_id'], 'value-added-taxes', array( 'setting' => 'type' ) );
					
					if ( 'default' === $tax_type || '' === $tax_type || false === $tax_type )
						$tax_type = $default_rate;
						
					if ( empty( $subtotals[$tax_type] ) )
						$subtotals[$tax_type] = 0;
						
					$product_subtotal = it_exchange_get_cart_product_subtotal( $product, false );
						
					if ( !empty( $applied_coupons['cart'] ) ) {
						foreach( $applied_coupons['cart'] as $coupon ) {
							if ( !empty( $coupon['product_id'] ) ) {
								if ( $product['product_id'] == $coupon['product_id'] ) {
									if ( '%' === $coupon['amount_type'] ) {
										$product_subtotal *= ( 100 - $coupon['amount_number'] ) / 100;
									} else {
										$product_subtotal -= ( $product['count'] * $coupon['amount_number'] );
									}
								}
							} else {
								if ( '%' === $coupon['amount_type'] ) {
									$product_subtotal *= ( 100 - $coupon['amount_number'] ) / 100;
								} else {
									$product_subtotal -= ( $coupon['amount_number'] / $product_count );
								}
							}
						}
					}
					
					if ( $product_subtotal > 0 ) {
						$subtotals[$tax_type] += $product_subtotal;
						$product_taxes[$product['product_id']] = $tax_type;
					}
				}
			}
		}
	
		$taxes = array();
		$total_taxes = 0;
		foreach( $subtotals as $key => $subtotal ) {
			$taxable_amount = 0;
			if ( !empty( $settings['tax-rates'][$key]['shipping'] ) ) {
				$taxable_amount = $subtotal + $shipping_cost;
			} else {
				$taxable_amount = $subtotal;
			}
			$tax = $taxable_amount * ( $settings['tax-rates'][$key]['rate'] / 100 );
			$taxes[$key]['tax-rate'] = $settings['tax-rates'][$key];
			$taxes[$key]['total'] = $tax;
			$taxes[$key]['taxable_amount'] = $taxable_amount;
			$total_taxes += $tax;
		}
		
	} else {
		$product_taxes = $tax_session['product_taxes'];
		$taxes = $tax_session['taxes'];
		$total_taxes = $tax_session['total_taxes'];
	}
	
	$tax_session['product_taxes'] = $product_taxes;
	$tax_session['taxes'] = $taxes;
	$tax_session['total_taxes'] = $total_taxes;
	it_exchange_update_session_data( 'addon_easy_eu_value_added_taxes', $tax_session );
									
	return true;

}

/**
 * Gets tax taxes based on products in cart
 *
 * @since 1.0.0
 *
 * @param bool $format_price Whether or not to format the price or leave as a float
 * @param bool $clear_cache Whether or not to force clear any cached tax values
 * @return string The calculated tax from TaxCloud
*/
function it_exchange_easy_eu_value_added_taxes_addon_get_total_taxes_for_cart( $format_price=true, $clear_cache=false ) {
	$taxes = 0;

	if ( it_exchange_easy_eu_value_added_taxes_setup_session() ) {
		$tax_session = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );
		if ( !empty( $tax_session['total_taxes'] ) ) {
			$taxes = $tax_session['total_taxes'];
		}
	}
								
	if ( $format_price )
		$taxes = it_exchange_format_price( $taxes );
	return $taxes;
}

function it_exchange_easy_eu_value_added_taxes_addon_verify_vat( $country_code, $vat_number ) {
	$soap_url = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';
	$soap_client = new SOAPClient( $soap_url );
	$query = array(
		'countryCode' => strtoupper( trim( $country_code ) ),
		'vatNumber'   => strtoupper( trim( $vat_number ) ),
	);
	
	if ( 'UK' === $query['countryCode'] )
		$query['countryCode'] = 'GB'; //VIES treats UK like Great Britain...
	
	try {
		$result = $soap_client->checkVat( $query );
		if ( is_soap_fault( $result ) ) {
			throw new Exception( $result->faultstring() );
		} else if ( !empty( $result->valid ) && $result->valid ) {
			return true;
		} else {
			throw new Exception( sprintf( __( 'Error trying to validate VAT number: %s-%s.', 'LION' ), $country_code, $vat_number ) );
		}
	}
    catch( Exception $e ) {
		return $e->getMessage();
    }
    
    return false;
}

/**
 * Get Customer EU VAT Details
 *
 * Among other things this function is used as a callback for the customer EU VAT 
 * purchase requriement.
 *
 * @since 1.0.0
 *
 * @param integer $customer_id the customer id. leave blank to use the current customer.
 * @return array
*/
function it_exchange_easy_eu_value_added_taxes_get_customer_vat_details( $customer_id=false ) {
	if ( it_exchange_easy_eu_value_added_taxes_setup_session() ) {
		$tax_session = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );
		//We only want to require the VAT details if the user is in an EU Memberstate
		if ( !empty( $tax_session['vat_country'] ) ) {
			$vat_details = it_exchange_get_customer_data( 'eu_vat_details', $customer_id );
			return apply_filters( 'it_exchange_easy_eu_value_added_taxes_get_customer_vat_details', $vat_details, $customer_id );
		}
	}
	return -1;
}