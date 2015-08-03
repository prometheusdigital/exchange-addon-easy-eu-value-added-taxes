<?php
/**
 * iThemes Exchange Easy EU Value Added Taxes Add-on
 * @package exchange-addon-easy-eu-value-added-taxes
 * @since 1.0.0
*/

function it_exchange_easy_eu_value_added_taxes_get_tax_row_settings( $key, $rate=array(), $memberstate=false ) {
	if ( empty( $rate ) ) { //just set some defaults
		$rate = array( //Member State
				'label'    => '',
				'rate'     => 0,
				'shipping' => false,
				'default'  => 'unchecked',
			);
	}
	
	if ( !empty( $memberstate ) ) {
		$name = 'it-exchange-add-on-easy-eu-value-added-taxes-vat-moss-tax-rates[' . $memberstate . '][' . $key . ']';
		$default_label = $memberstate . '-';
	} else {
		$name = 'it-exchange-add-on-easy-eu-value-added-taxes-tax-rates[' . $key . ']';
		$default_label = '';
	}
		
	$output  = '<div class="item-row block-row">'; //start block-row
	
	$output .= '<div class="item-column block-column block-column-1">';
	$output .= '<input type="text" name="' . $name . '[label]" value="' . $rate['label'] . '" />';
	$output .= '</div>';
	
	$output .= '<div class="item-column block-column block-column-2">';
	$output .= '<input type="text" name="' . $name . '[rate]" value="' . $rate['rate'] . '" />';
	$output .= '</div>';
	
	$output .= '<div class="item-column block-column block-column-3">';
	$shipping = empty( $rate['shipping'] ) ? false : true;
	$output .= '<input type="checkbox" name="' . $name . '[shipping]" ' . checked( $shipping, true, false ) . ' />';
	$output .= '</div>';
	
	$output .= '<div class="item-column block-column block-column-default">';
	$output .= '<span class="it-exchange-easy-eu-value-added-taxes-addon-default-checkmark it-exchange-easy-eu-value-added-taxes-addon-default-checkmark-' . $rate['default'] . '"></span>';	
	$output .= '<input type="hidden" class="it-exchange-easy-eu-value-added-taxes-addon-default-checkmark" name="' . $name . '[default]" value="' . $rate['default'] . '" />';
	$output .= '</div>';
	
	$output .= '<div class="item-column block-column block-column-delete">';
	$output .= '<a href class="it-exchange-easy-eu-value-added-taxes-addon-delete-tax-rate it-exchange-remove-item">&times;</a>';
	$output .= '</div>';
	
	$output .= '</div>'; //end block-row
	
	return $output;
}

function it_exchange_easy_eu_value_added_taxes_get_cart_taxes() {
	$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
	$taxes = array();
	$address = array();
		
	if ( ! $products = it_exchange_get_cart_products() )
		return false;
	
	$applied_coupons = it_exchange_get_applied_coupons();
	$serialized_coupons = maybe_serialize( $applied_coupons );
			
	$subtotals = array();
	$default_rate = 0;
	$tax_rates = $settings['tax-rates'];
	
	foreach ( $tax_rates as $key => $rate ) {
		$subtotals[$key] = 0;
		if ( !empty( $rate['default'] ) && 'checked' === $rate['default'] ) {
			$default_rate = $key;
		}
	}
		
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
				
				$tax_type = it_exchange_get_product_feature( $product['product_id'], 'value-added-taxes', array( 'setting' => 'type' ) );

				if ( 'default' === $tax_type || '' === $tax_type || false === $tax_type ) {
					$tax_type = $default_rate;
				}
					
				if ( empty( $subtotals[$tax_type] ) ) {
					$subtotals[$tax_type] = 0;
				}
				
				if ( $product_subtotal > 0 ) {
					$subtotals[$tax_type] += $product_subtotal;
				}
			}
		}
	
		$taxes = array();
		foreach( $subtotals as $key => $subtotal ) {
			$taxable_amount = $subtotal;
			$tax = $taxable_amount * ( $tax_rates[$key]['rate'] / 100 );
			$taxes[$key]['tax-rate'] = $tax_rates[$key];
			$taxes[$key]['total'] = $tax;
			$taxes[$key]['taxable_amount'] = $taxable_amount;
			$taxes[$key]['country'] = $settings['vat-country'];
		}
		
	}
									
	return $taxes;
}

function it_exchange_easy_eu_value_added_taxes_setup_session( $clear_cache=false ) {
	$tax_session = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );
	$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
	$taxes = array();
	$total_taxes = 0;
	$address = array();
	
	if ( it_exchange_get_available_shipping_methods_for_cart_products() ) {
		//We always want to get the Shipping Address if it's available...
		$address = it_exchange_get_cart_shipping_address();
	}
	
	//We only care about the province!
	if ( empty( $address['country'] ) ) 
		$address = it_exchange_get_cart_billing_address();
	
	//If not a member state, not taxable
	$memberstates = it_exchange_get_data_set( 'eu-member-states' );
	if ( empty( $address['country'] ) || empty( $memberstates[$address['country']] ) ) {
		return false;
	} else {
		if ( empty( $tax_session['vat_country'] ) && empty( $tax_session['vat_number'] ) )
			$tax_session['vat_country'] = $address['country'];
	}
	
	$tax_session['intrastate'] = $settings['vat-country'] === $address['country']; //Is this an intrastate transaction, used for determining VAT MOSS
	if ( empty( $tax_session['vat_number'] ) || $tax_session['intrastate'] ) {
		//Charge Tax if no VAT Number is supplied or if the customer is purchasing from the same member state
		$tax_session['summary_only'] = false;
	} else {
		//Otherwise, don't charge tax, just summarize the VAT
		$tax_session['summary_only'] = true;
	}
	
	if ( ! $products = it_exchange_get_cart_products() )
		return false;
	
	$cart_subtotal = 0;
	$vat_moss_cart_subtotal = 0;
	foreach( (array) $products as $product ) {
		$cart_subtotal += it_exchange_get_cart_product_subtotal( $product, false );
		if ( 'on' === it_exchange_get_product_feature( $product['product_id'], 'value-added-taxes', array( 'setting' => 'vat-moss' ) ) ) {
			$vat_moss_cart_subtotal += it_exchange_get_cart_product_subtotal( $product, false );
		}
	}
	$cart_subtotal = apply_filters( 'it_exchange_get_cart_subtotal', $cart_subtotal );
	$vat_moss_cart_subtotal = apply_filters( 'it_exchange_get_cart_subtotal', $vat_moss_cart_subtotal );
	$shipping_cost = it_exchange_get_cart_shipping_cost( false, false );
	$applied_coupons = it_exchange_get_applied_coupons();
	$serialized_coupons = maybe_serialize( $applied_coupons );
	
	if ( !empty( $tax_session ) ) {

		//We want to store the cart subtotal, in case it changes so we know we need to recalculate tax
		if ( !isset( $tax_session['country'] ) || $address['country'] != $tax_session['country'] ) {
			$tax_session['country'] = $address['country'];
			$clear_cache = true; //re-calculate taxes
		}

		//We want to store the cart subtotal, in case it changes so we know we need to recalculate tax
		if ( !isset( $tax_session['cart_subtotal'] ) || $cart_subtotal != $tax_session['cart_subtotal'] ) {
			$tax_session['cart_subtotal'] = $cart_subtotal;
			$clear_cache = true; //re-calculate taxes
		}

		//We want to store the cart subtotal, in case it changes so we know we need to recalculate tax
		if ( !isset( $tax_session['vat_moss_cart_subtotal'] ) || $cart_subtotal != $tax_session['vat_moss_cart_subtotal'] ) {
			$tax_session['vat_moss_cart_subtotal'] = $vat_moss_cart_subtotal;
			$clear_cache = true; //re-calculate taxes
		}
		
		//We want to store the cart subtotal with shipping, in case it changes so we know we need to recalculate tax
		if ( !isset( $tax_session['shipping_cost'] ) || $shipping_cost != $tax_session['shipping_cost'] ) {
			$tax_session['shipping_cost'] = $shipping_cost;
			$clear_cache = true; //re-calculate taxes
		}
		
		if ( !isset( $tax_session['applied_coupons'] ) || $serialized_coupons != $tax_session['applied_coupons'] ) {
			$tax_session['applied_coupons'] = $serialized_coupons;
			$clear_cache = true;
		}
		
	} else {
		$clear_cache = true; //not really any cache, but it's easier this way :)
		$tax_session['country'] = $address['country'];
		$tax_session['cart_subtotal'] = $cart_subtotal;
		$tax_session['vat_moss_cart_subtotal'] = $vat_moss_cart_subtotal;
		$tax_session['shipping_cost'] = $shipping_cost;
		$tax_session['applied_coupons'] = $serialized_coupons;
	}
	
	$clear_cache = true;
		
	if ( $clear_cache || empty( $tax_session['product_taxes'] ) ||  empty( $tax_session['taxes'] ) ||  empty( $tax_session['vat_moss_taxes'] ) ||  empty( $tax_session['total_taxes'] ) ) {
	
		$subtotals = array();
		$vat_moss_subtotals = array();
		$product_taxes = array();
		$default_rate = 0;
		$tax_rates = $settings['tax-rates'];
		
		foreach ( $tax_rates as $key => $rate ) {
			$subtotals[$key] = 0;
			if ( !empty( $rate['default'] ) && 'checked' === $rate['default'] ) {
				$default_rate = $key;
			}
		}
		
		if ( !empty( $settings['vat-moss-tax-rates'][$tax_session['country']] ) ) {
			$vat_moss_tax_rates = $settings['vat-moss-tax-rates'][$tax_session['country']];
		} else {
			$vat_moss_tax_rates = $settings['tax-rates']; //Just to have something to fall back on.
		}
		
		foreach ( $vat_moss_tax_rates as $key => $rate ) {
			$vat_moss_subtotals[$key] = 0;
			if ( !empty( $rate['default'] ) && 'checked' === $rate['default'] ) {
				$default_rate = $key;
			}
		}

		
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
					
					if ( empty( $tax_session['intrastate'] ) && 'on' === it_exchange_get_product_feature( $product['product_id'], 'value-added-taxes', array( 'setting' => 'vat-moss' ) ) ) {

						$tax_type = it_exchange_get_product_feature( $product['product_id'], 'value-added-taxes', array( 'setting' => 'vat-moss-tax-types', 'vat-moss-country' => $tax_session['country'] ) );

						if ( 'default' === $tax_type || '' === $tax_type || false === $tax_type ) {
							$tax_type = $default_rate;
						}

						if ( empty( $vat_moss_subtotals[$tax_type] ) ) {
							$vat_moss_subtotals[$tax_type] = 0;
						}
						
						if ( $product_subtotal > 0 ) {
							$vat_moss_subtotals[$tax_type] += $product_subtotal;
							$product_taxes[$product['product_id']] = $tax_type;
						}
						
					} else {

						$tax_type = it_exchange_get_product_feature( $product['product_id'], 'value-added-taxes', array( 'setting' => 'type' ) );

						if ( 'default' === $tax_type || '' === $tax_type || false === $tax_type ) {
							$tax_type = $default_rate;
						}
							
						if ( empty( $subtotals[$tax_type] ) ) {
							$subtotals[$tax_type] = 0;
						}
						
						if ( $product_subtotal > 0 ) {
							$subtotals[$tax_type] += $product_subtotal;
							$product_taxes[$product['product_id']] = $tax_type;
						}
						
					}
				}
			}
		}
	
		$taxes = array();
		$total_taxes = 0;
		foreach( $subtotals as $key => $subtotal ) {
			$taxable_amount = 0;
			if ( !empty( $tax_rates[$key]['shipping'] ) ) {
				$taxable_amount = $subtotal + $shipping_cost;
			} else {
				$taxable_amount = $subtotal;
			}
			$tax = $taxable_amount * ( $tax_rates[$key]['rate'] / 100 );
			$taxes[$key]['tax-rate'] = $tax_rates[$key];
			$taxes[$key]['total'] = $tax;
			$taxes[$key]['taxable_amount'] = $taxable_amount;
			$taxes[$key]['country'] = $settings['vat-country'];
			$total_taxes += $tax;
		}
		
		$vat_moss_taxes = array();
		foreach( $vat_moss_subtotals as $key => $subtotal ) {
			$taxable_amount = 0;
			if ( !empty( $vat_moss_tax_rates[$key]['shipping'] ) ) {
				$taxable_amount = $subtotal + $shipping_cost;
			} else {
				$taxable_amount = $subtotal;
			}
			$tax = $taxable_amount * ( $vat_moss_tax_rates[$key]['rate'] / 100 );
			$vat_moss_taxes[$key]['tax-rate'] = $vat_moss_tax_rates[$key];
			$vat_moss_taxes[$key]['total'] = $tax;
			$vat_moss_taxes[$key]['taxable_amount'] = $taxable_amount;
			$vat_moss_taxes[$key]['country'] = $tax_session['country'];
			$total_taxes += $tax;
		}
		
	} else {
		$product_taxes = $tax_session['product_taxes'];
		$taxes = $tax_session['taxes'];
		$vat_moss_taxes = $tax_session['vat_moss_taxes'];
		$total_taxes = $tax_session['total_taxes'];
	}
	
	$tax_session['product_taxes'] = $product_taxes;
	$tax_session['taxes'] = $taxes;
	$tax_session['vat_moss_taxes'] = $vat_moss_taxes;
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
