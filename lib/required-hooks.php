<?php
/**
 * iThemes Exchange Easy EU Value Added Taxes Add-on
 * @package exchange-addon-easy-eu-value-added-taxes
 * @since 1.0.0
*/

//For calculation shipping, we need to require billing addresses... 
//incase a product doesn't have a shipping address and the shipping add-on is not enabled
add_filter( 'it_exchange_billing_address_purchase_requirement_enabled', '__return_true' );

/**
 * Register the EU VAT taxes provider.
 *
 * @since 1.8.0
 *
 * @param \ITE_Tax_Managers $manager
 */
function it_exchange_register_eu_vat_taxes_provider( ITE_Tax_Managers $manager ) {
	$manager::register_provider( new ITE_EU_VAT_Tax_Provider() );
}

add_action( 'it_exchange_register_tax_providers', 'it_exchange_register_eu_vat_taxes_provider' );

/**
 * Checkes if include VAT in prices is enabled, if so, apply new filters
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_easy_eu_value_added_taxes_addon_include_vat_filters() {
	if ( !is_admin() ) {
		it_exchange_easy_eu_value_added_taxes_setup_session();
	}

	$cart = it_exchange_get_current_cart( false );

	if ( $cart && $country = it_exchange_easy_eu_vat_get_country( $cart ) ) {
		$show = it_exchange_easy_eu_vat_valid_country_for_tax( $country );
	} elseif ( is_user_logged_in() ) {
		$customer = it_exchange_get_current_customer();
		$address  = $customer->get_shipping_address() ?: $customer->get_billing_address();
		$show     = $address && it_exchange_easy_eu_vat_valid_country_for_tax( $address['country'] );
	} else {
		$show = true;
	}

	$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes', true );

	if ( $show && empty( $settings['price-hide-vat'] ) ) {
		add_filter( 'it_exchange_api_theme_product_base_price', 'it_exchange_easy_eu_value_added_taxes_addon_api_theme_product_base_price', 10, 2 );
	}

	add_filter( 'it_exchange_api_theme_cart_item_sub_total', 'it_exchange_easy_eu_value_added_taxes_addon_api_theme_cart_item_with_vat', 10, 3 );
	add_filter( 'it_exchange_api_theme_cart_item_price', 'it_exchange_easy_eu_value_added_taxes_addon_api_theme_cart_item_with_vat', 10, 3 );
}

add_action( 'template_redirect', 'it_exchange_easy_eu_value_added_taxes_addon_include_vat_filters' );

/**
 * Handle the VAT # being set on a cart.
 *
 * @since 1.8.0
 *
 * @param string    $key
 * @param string    $value
 * @param \ITE_Cart $cart
 */
function it_exchange_easy_eu_vat_handle_set_vat_number( $key, $value, ITE_Cart $cart ) {

	if ( $key !== 'eu-vat-number' ) {
		return;
	}

	$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

	// A VAT # only exempts tax for customers in a different state to the shop's base for VAT.
	if ( $settings['vat-country'] !== it_exchange_easy_eu_vat_get_country( $cart ) ) {
		$cart->get_items( 'tax', true )->with_only_instances_of( 'ITE_EU_VAT_Line_Item' )->delete();
	}
}

add_action( 'it_exchange_set_cart_meta', 'it_exchange_easy_eu_vat_handle_set_vat_number', 10, 3 );

/**
 * Handle the VAT # being removed from a cart.
 *
 * @since 1.8.0
 *
 * @param string    $key
 * @param \ITE_Cart $cart
 */
function it_exchange_easy_eu_vat_handle_remove_vat_number( $key, ITE_Cart $cart ) {

	if ( $key !== 'eu-vat-number' ) {
		return;
	}

	$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

	$provider = new ITE_EU_VAT_Tax_Provider();

	if ( $settings['vat-country'] !== it_exchange_easy_eu_vat_get_country( $cart ) ) {
		foreach ( $cart->get_items() as $item ) {
			$provider->add_taxes_to( $item, $cart );
		}
	}
}

add_action( 'it_exchange_remove_cart_meta', 'it_exchange_easy_eu_vat_handle_remove_vat_number', 10, 2 );

/**
 * Set VAT manager to checkout mode on the invoice page.
 *
 * @since 1.7.4
 */
function it_exchange_easy_eu_vat_set_checkout_mode_on_invoice_page() {

	$product_id = it_exchange_get_the_product_id();

	if ( it_exchange_get_product_type( $product_id ) !== 'invoices-product-type' ) {
		return;
	}

	echo '<script> var ITExchangeEasyValueAddedTaxesCheckoutPage = true;</script>';
}

add_action( 'wp_head', 'it_exchange_easy_eu_vat_set_checkout_mode_on_invoice_page' );

/**
 * Whitelist the VAT manager nonce with invoices.
 *
 * @since 1.7.4
 *
 * @param array $actions
 *
 * @return array
 */
function it_exchange_easy_eu_vat_invoices_whitelist_manager_nonce( $actions ) {

	$actions[] = 'it-exchange-easy-eu-value-added-taxes-add-edit-vat-number';

	return $actions;
}

add_filter( 'it_exchange_invoices_user_id_nonce_verification_whitelist', 'it_exchange_easy_eu_vat_invoices_whitelist_manager_nonce' );

/**
 * Adds VAT to product and store pages if enabled in settings.
 *
 * @since 1.0.0
 *
 * @param string $price
 * @param int    $product_id
 *
 * @return string
*/
function it_exchange_easy_eu_value_added_taxes_addon_api_theme_product_base_price( $price, $product_id ) {

	$product = it_exchange_get_product( $product_id );

	if ( it_exchange_get_product_type( $product_id ) === 'invoices-product-type' && get_post_status( $product_id ) === 'publish' ) {
		return $price;
	}

	if ( ! $product->supports_feature( 'value-added-taxes' ) ) {
		return $price;
	}

	$provider = new ITE_EU_VAT_Tax_Provider();

	if ( $provider->is_product_tax_exempt( $product ) ) {
		return $price;
	}

	$cart    = it_exchange_get_current_cart( false );
	$country = '';

	if ( $cart ) {
		$country = it_exchange_easy_eu_vat_get_country( $cart );
	}

	if ( ! $country ) {
		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes', true );
		$country  = $settings['vat-country'];
	}

	$product = ITE_Cart_Product::create( $product );

	$provider->set_current_country( $country );
	$code = $provider->get_tax_code_for_product( $product->get_product() );

	if ( ! $code ) {
		return $price;
	}

	$rate = ITE_EU_VAT_Rate::from_code( $code );
	$tax  = ITE_EU_VAT_Line_Item::create( $rate, $product );

	$price = it_exchange_convert_from_database_number( it_exchange_convert_to_database_number( $price ) );
	$price += $tax->get_total();

	return it_exchange_format_price( $price );
}

/**
 * Adds VAT to cart/checkout products if enabled in settings.
 *
 * @since 1.0.0
 *
 * @param string            $subtotal
 * @param array             $cart_item
 * @param \ITE_Cart_Product $product
 *
 * @return string
*/
function it_exchange_easy_eu_value_added_taxes_addon_api_theme_cart_item_with_vat( $subtotal, $cart_item, ITE_Cart_Product $product = null ) {

	if ( ! $product ) {
		return $subtotal;
	}

	$provider = new ITE_EU_VAT_Tax_Provider();

	if ( $provider->is_product_tax_exempt( $product->get_product() ) ) {
		return $subtotal;
	}

	$cart    = it_exchange_get_current_cart();
	$country = it_exchange_easy_eu_vat_get_country( $cart );

	if ( ! $country || ! it_exchange_easy_eu_vat_valid_country_for_tax( $country ) ) {
		return $subtotal;
	}

	$provider->set_current_country( $country );
	$code = $provider->get_tax_code_for_product( $product->get_product() );

	if ( ! $code ) {
		return $subtotal;
	}

	$rate = ITE_EU_VAT_Rate::from_code( $code );

	if ( ! $rate ) {
		return $subtotal;
	}

	$tax  = ITE_EU_VAT_Line_Item::create( $rate, $product );

	$subtotal = it_exchange_convert_from_database_number( it_exchange_convert_to_database_number( $subtotal ) );
	$subtotal += $tax->get_total();

	return it_exchange_format_price( $subtotal );
}

/**
 * Includes VAT in product prices on the confirmation page.
 *
 * @since 1.0.0
 *
 * @deprecated 1.8.0 This is done automatically now.
 *
 * @return void
*/
function it_exchange_easy_eu_value_added_taxes_api_theme_transaction_product_attribute( $attribute, $options, $transaction, $product ) {
	if ( 'product_base_price' == $options['attribute'] ) {
        $summary_only = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes_summary_only', true );

        if ( !$summary_only ) {
	        $tax_rates = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes', true );
	        $taxes = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes_product_taxes', true );
	        $attribute = $product['product_base_price'];
	        if ( !empty( $taxes[$product['product_id']] )
	        	&& !empty( $tax_rates[$taxes[$product['product_id']]] )
	        	&& !empty( $tax_rates[$taxes[$product['product_id']]]['tax-rate'] )
	        	&& !empty( $tax_rates[$taxes[$product['product_id']]]['tax-rate']['rate'] ) ) {
		        $tax_rate = $tax_rates[$taxes[$product['product_id']]]['tax-rate']['rate'];
			} else {
		    	$tax_rate = 0;
			}

			$attribute *= ( ( 100 + $tax_rate ) / 100 );

			if ( (boolean) $options['format_price'] )
				$attribute = it_exchange_format_price( $attribute );
		}
	}

	return $attribute;
}

/**
 * Add Easy EU Value Added Taxes to the content-cart totals and content-checkout loop
 *
 * @since 1.0.0
 *
 * @deprecated 1.8.0
 *
 * @param array $elements list of existing elements
 * @return array
*/
function it_exchange_easy_eu_value_added_taxes_addon_add_taxes_to_template_totals_elements( $elements ) {

	if ( ! it_exchange_easy_eu_vat_show_vat_manager() ) {
		return $elements;
	}

	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'totals-savings', $elements );
	if ( false === $index )
		$index = -1;

	// Bump index by 1 to show tax after discounts
	if ( -1 != $index )
		$index++;

	array_splice( $elements, $index, 0, 'easy-eu-value-added-taxes' );

	return $elements;
}

add_filter( 'it_exchange_get_content_cart_totals_elements', 'it_exchange_easy_eu_value_added_taxes_addon_add_taxes_to_template_totals_elements' );
add_filter( 'it_exchange_get_content_checkout_totals_elements', 'it_exchange_easy_eu_value_added_taxes_addon_add_taxes_to_template_totals_elements' );

/**
 * Add Easy EU Value Added Taxes to the confirmation loop
 *
 * @since 1.0.0
 *
 * @param array $elements list of existing elements
 * @return array
*/
function it_exchange_easy_eu_value_added_taxes_addon_get_content_confirmation_transaction_meta_elements( $elements ) {
	$elements[] = 'easy-eu-value-added-taxes-vat-summary'; //we always want it at the end

	return $elements;
}
add_filter( 'it_exchange_get_content_confirmation_transaction_meta_elements', 'it_exchange_easy_eu_value_added_taxes_addon_get_content_confirmation_transaction_meta_elements' );

/**
 * Add Easy EU Value Added Taxes to the super-widget-checkout totals loop
 *
 * @since 1.0.0
 *
 * @param array $loops list of existing elements
 * @return array
*/
function it_exchange_easy_eu_value_added_taxes_addon_add_taxes_to_sw_template_totals_loops( $loops ) {

	if ( ! it_exchange_easy_eu_vat_show_vat_manager() ) {
		return $loops;
	}

	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'discounts', $loops );
	if ( false === $index )
		$index = -1;

	// Bump index by 1 to show tax after discounts
	if ( -1 != $index )
		$index++;

	array_splice( $loops, $index, 0, 'easy-eu-value-added-taxes' );
	return $loops;
}

add_filter( 'it_exchange_get_super-widget-checkout_after-cart-items_loops', 'it_exchange_easy_eu_value_added_taxes_addon_add_taxes_to_sw_template_totals_loops' );

/**
 * Adds our templates directory to the list of directories
 * searched by Exchange
 *
 * @since 1.0.0
 *
 * @param array $template_path existing array of paths Exchange will look in for templates
 * @param array $template_names existing array of file names Exchange is looking for in $template_paths directories
 * @return array Modified template paths
*/
function it_exchange_easy_eu_value_added_taxes_addon_taxes_register_templates( $template_paths, $template_names ) {
	// Bail if not looking for one of our templates
	$add_path = false;
	$templates = array(
		'content-cart/elements/easy-eu-value-added-taxes.php',
		'content-checkout/elements/easy-eu-value-added-taxes.php',
		'content-confirmation/elements/easy-eu-value-added-taxes.php',
		'content-confirmation/elements/easy-eu-value-added-taxes-vat-summary.php',
		'super-widget-checkout/loops/easy-eu-value-added-taxes.php',
	);
	foreach( $templates as $template ) {
		if ( in_array( $template, (array) $template_names ) )
			$add_path = true;
	}
	if ( ! $add_path )
		return $template_paths;

	$template_paths[] = dirname( __FILE__ ) . '/templates';
	return $template_paths;
}
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_easy_eu_value_added_taxes_addon_taxes_register_templates', 10, 2 );

/**
 * Adds Easy EU Value Added Taxes Template Path to iThemes Exchange Template paths
 *
 * @since 1.0.0
 * @param array $possible_template_paths iThemes Exchange existing Template paths array
 * @param array $template_names
 *
 * @return array
*/
function it_exchange_easy_eu_value_added_taxes_addon_template_path( $possible_template_paths, $template_names ) {
	$possible_template_paths[] = dirname( __FILE__ ) . '/templates/';

	return $possible_template_paths;
}
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_easy_eu_value_added_taxes_addon_template_path', 10, 2 );

/**
 * Adjusts the cart total if on a checkout page
 *
 * @since 1.0.0
 *
 * @param int $total the total passed to canadian by Exchange.
 *
 * @return int New Total
*/
function it_exchange_easy_eu_value_added_taxes_addon_taxes_modify_total( $total ) {
	$tax_session = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );

	if ( empty( $tax_session['summary_only'] ) ) {
		$total += it_exchange_easy_eu_value_added_taxes_addon_get_total_taxes_for_cart( false );
	}

	return $total;
}

/**
 * Save Taxes to Transaction Meta
 *
 * @since 1.0.0
 *
 * @param int       $transaction_id Transaction ID
 * @param \ITE_Cart $cart
*/
function it_exchange_easy_eu_value_added_taxes_transaction_hook( $transaction_id, \ITE_Cart $cart = null ) {

	if ( $cart ) {
		$info = it_exchange_easy_eu_vat_get_tax_info_for_cart( $cart );
	} else {
		$info = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );
	}

	if ( ! $info ) {
		return;
	}

	if ( $cart && ! empty( $info['summary_only'] ) ) {
		$info = array_merge( $info,
			it_exchange_easy_eu_vat_get_tax_summary_for_taxable_items(
				it_exchange_easy_eu_vat_do_summary_only_taxes( $cart )->to_array(),
				it_exchange_easy_eu_vat_get_country( $cart )
			)
		);
	}

	if ( ! empty( $info['summary_only'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_eu_value_added_taxes_summary_only', $info['summary_only'] );
	}

	if ( ! empty( $info['taxes'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_eu_value_added_taxes', $info['taxes'] );
	}

	if ( ! empty( $info['vat_moss_taxes'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_eu_value_added_vat_moss_taxes', $info['vat_moss_taxes'] );
	}

	if ( ! empty( $info['product_taxes'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_eu_value_added_taxes_product_taxes', $info['product_taxes'] );
	}

	if ( ! empty( $info['vat_country'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_eu_value_added_taxes_customer_vat_country', $info['vat_country'] );
	}

	if ( ! empty( $info['vat_number'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_eu_value_added_taxes_customer_vat_number', $info['vat_number'] );
	}

	if ( ! empty( $info['total_taxes'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_eu_value_added_taxes_taxes_total', $info['total_taxes'] );
	}

	if ( $cart && $cart->is_current() ) {
		it_exchange_clear_session_data( 'addon_easy_eu_value_added_taxes' );
	}
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_easy_eu_value_added_taxes_transaction_hook', 10, 2 );

/**
 * Backbone template for primary EU VAT Number Manager screen.
 * Invoked by wp.template() and WordPress
 *
 * add_action( 'wp_footer', 'it_exchange_easy_eu_value_added_taxes_addon_vat_number_manager_backbone_template' );
 *
 * @since 1.0.0
 */
function it_exchange_easy_eu_value_added_taxes_addon_vat_number_manager_backbone_template() {
	$cart = it_exchange_get_current_cart( false );
	?>
	<div id="it-exchange-easy-eu-value-added-taxes-vat-manager-wrapper" class="it-exchange-hidden"></div>
	<script type="text/template" id="tmpl-it-exchange-easy-eu-value-added-taxes-vat-manager-container">
		<span class="it-exchange-euvat-close-vat-manager"><a href="">&times;</a></span>
		<div id="it-exchange-easy-eu-value-added-taxes-vat-manager">
			<div id="it-exchange-easy-eu-value-added-taxes-vat-manager-title-area">
				<h3 class="it-exchange-euvat-tax-emeption-title">
					<?php _e( 'VAT Manager', 'LION' ); ?>
				</h3>
			</div>

			<div id="it-exchange-easy-eu-value-added-taxes-vat-manager-content-area">
				<div id="it-exchange-easy-eu-value-added-taxes-vat-manager-error-area"></div>
				<form id="it-exchange-add-on-easy-eu-value-added-taxes-add-edit-vat" name="it-exchange-add-on-easy-eu-value-added-taxes-add-edit-vat" action="POST">
				<?php

				$vat_country = $cart && $cart->has_meta( 'eu-vat-country' ) ? $cart->get_meta( 'eu-vat-country' ) : '';
				$vat_number  = $cart && $cart->has_meta( 'eu-vat-number' ) ? $cart->get_meta( 'eu-vat-number' ) : '';

				$output = '<select id="it-exchange-euvat-eu-vat-country" name="eu-vat-country">';
				$memberstates = it_exchange_get_data_set( 'eu-member-states' );
				foreach( $memberstates as $abbr => $name ) {
					$output .= '<option value="' . $abbr . '" ' . selected( $abbr, $vat_country, false ) . '>' . $name . '</option>';
				}
				$output .= '</select><br />';

				$output .= '<input type="text" id="it-exchange-euvat-eu-vat-number" name="eu-vat-number" value="' . $vat_number . '" />';

				echo $output;
				?>

				<div class="field it-exchange-add-vat-submit">
					<input type="submit" value="<?php _e( 'Verify and Save VAT Number', 'LION' ); ?>" class="button button-large it-exchange-euvat-save-vat-button" id="save" name="save">
					<input type="submit" value="Cancel" class="button button-large it-exchange-euvat-cancel-vat-button" id="cancel" name="cancel">
					<input type="submit" value="Remove" class="button button-large it-exchange-euvat-remove-vat-button" id="remove" name="remove">
					<?php wp_nonce_field( 'it-exchange-easy-eu-value-added-taxes-add-edit-vat-number', 'it-exchange-easy-eu-value-added-taxes-add-edit-vat-number-nonce' ); ?>
				</div>
				</form>
			</div>
		</div>
	</script>
	<?php
}

/**
 * Adds the cart taxes to the transaction object
 *
 * @since CHANGEME
 *
 * @param string $taxes incoming from WP Filter. False by default.
 * @return string
 *
*/
function it_exchange_easy_eu_value_added_taxes_add_cart_taxes_to_txn_object() {
    $formatted = ( 'it_exchange_set_transaction_objet_cart_taxes_formatted' == current_filter() );
    return it_exchange_easy_eu_value_added_taxes_addon_get_total_taxes_for_cart( $formatted );
}

/**
 * Returns the transaction customer's VAT number
 *
 * @since CHANGEME
 *
 * @param WP_Post|int|IT_Exchange_Transaction $transaction ID or object
 *
 * @return void
*/
function it_exchange_easy_eu_value_added_taxes_after_payment_details_vat_details( $transaction ) {
    $tax_items          = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes', true );
    $vat_moss_tax_items = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_vat_moss_taxes', true );
    $customer_country   = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes_customer_vat_country', true );
    $customer_vat       = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes_customer_vat_number', true );
	$memberstates = it_exchange_get_data_set( 'eu-member-states' );
	$result = '';

	if ( ! $tax_items && ! $vat_moss_tax_items ) {
		return;
	}

	$result .= '<h3>VAT Summary</h3>';

	if ( ! empty( $customer_vat ) ) {
		$result .= '<p>' . sprintf( __( 'Customer VAT Number: %s-%s', 'LION' ), $customer_country, $customer_vat ) . '</p>';
	}

	$result .= '<div class="it-exchange-vat-summary-table">';
	$result .= '<div class="it-exchange-vat-summary-table-row vat-summary-heading-row">';

	$result .= '<div class="vat-label-heading it-exchange-vat-summary-table-column">';
	$result .= '<div class="it-exchange-vat-summary-table-column-inner">' . __( 'VAT Type', 'LION' ) . '</div>';
	$result .= '</div>';

	$result .= '<div class="vat-label-heading it-exchange-vat-summary-table-column">';
	$result .= '<div class="it-exchange-vat-summary-table-column-inner">' . __( 'Net Taxable Amount', 'LION' ) . '</div>';
	$result .= '</div>';

	$result .= '<div class="vat-label-heading it-exchange-vat-summary-table-column">';
	$result .= '<div class="it-exchange-vat-summary-table-column-inner">' . __( 'VAT', 'LION' ) . '</div>';
	$result .= '</div>';

	$result .= '</div>';

	if ( !empty( $tax_items ) ) {
		foreach( $tax_items as $tax ) {
			$net = empty( $tax['taxable_amount'] ) ? 0 : $tax['taxable_amount'];
			$taxed = empty( $tax['total'] ) ? 0 : $tax['total'];

			if ( empty( $taxed ) ) {
				continue;
			}

			$result .= '<div class="it-exchange-vat-summary-table-row">';

			$result .= '<div class="vat-label it-exchange-vat-summary-table-column">';
			$result .= '<div class="it-exchange-vat-summary-table-column-inner">';
			$result .= sprintf( __( '%s %s (%s%%)', 'LION' ), $memberstates[$tax['country']], $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
			$result .= '</div>';
			$result .= '</div>';

			$result .= '<div class="vat-net-taxable-amount it-exchange-vat-summary-table-column">';
			$result .= '<div class="it-exchange-vat-summary-table-column-inner">';
			$result .= it_exchange_format_price( $net );;
			$result .= '</div>';
			$result .= '</div>';

			$result .= '<div class="vat-amount-taxed it-exchange-vat-summary-table-column">';
			$result .= '<div class="it-exchange-vat-summary-table-column-inner">';
			$result .= it_exchange_format_price( $taxed );
			$result .= '</div>';
			$result .= '</div>';

			$result .= '</div>';
		}
	}

	if ( !empty( $vat_moss_tax_items ) ) {
		foreach ( $vat_moss_tax_items as $tax ) {
			$net = empty( $tax['taxable_amount'] ) ? 0 : $tax['taxable_amount'];
			$taxed = empty( $tax['total'] ) ? 0 : $tax['total'];

			if ( empty( $taxed ) ) {
				continue;
			}

			$result .= '<div class="it-exchange-vat-summary-table-row">';

			$result .= '<div class="vat-moss-label it-exchange-vat-summary-table-column">';
			$result .= '<div class="it-exchange-vat-summary-table-column-inner">';
			$result .=  sprintf( __( '%s %s (%s%%)', 'LION' ), $memberstates[ $tax['country'] ], $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
			$result .= '</div>';
			$result .= '</div>';

			$result .= '<div class="vat-moss-net-taxable-amount it-exchange-vat-summary-table-column">';
			$result .= '<div class="it-exchange-vat-summary-table-column-inner">';
			$result .= it_exchange_format_price( $net );
			$result .= '</div>';
			$result .= '</div>';

			$result .= '<div class="vat-moss-amount-taxed it-exchange-vat-summary-table-column">';
			$result .= '<div class="it-exchange-vat-summary-table-column-inner">';
			$result .= it_exchange_format_price( $taxed );
			$result .= '</div>';
			$result .= '</div>';

			$result .= '</div>';
		}
	}

	$result .= '</div>';

	?>
	<div class="clearfix spacing-wrapper">
		<div class="transaction-vat-taxes left">
			<?php echo $result; ?>
		</div>
	</div>
	<?php
}
add_action( 'it_exchange_after_payment_details', 'it_exchange_easy_eu_value_added_taxes_after_payment_details_vat_details' );

/**
 * Display a VAT summary on the invoice page.
 *
 * @since 1.7.4
 */
function it_exchange_easy_eu_value_added_taxes_display_vat_summary_on_invoice() {

	if ( ! function_exists( 'it_exchange_invoice_addon_get_invoice_transaction_id' ) ) {
		return;
	}

	$txn_id = it_exchange_invoice_addon_get_invoice_transaction_id( $GLOBALS['it_exchange']['product']->ID );

	if ( ! $txn_id ) {

		echo '<div class="it-exchange-vat-invoice-summary">';
		echo '<style type="text/css">#it-exchange-add-edit-vat-number {vertical-align: top;margin-top: 0;margin-left: 0;display: block;}'
		     . '.it-exchange-vat-invoice-summary .it-exchange-cart-totals-amount .it-exchange-table-column-inner { padding: 0;}</style>';

		it_exchange( 'eu-value-added-taxes', 'taxes', array(
			'before_label' => '<span class="label">',
			'after_label'  => '</span>'
		) );
		echo '</div>';
	} else {

		$GLOBALS['it_exchange']['transaction'] = it_exchange_get_transaction( $txn_id );

		echo '<div class="it-exchange-vat-invoice-summary">';
		it_exchange( 'eu-value-added-taxes', 'vat-summary', array(
			'label_tag_open'  => '<span class="label">',
			'label_tag_close' => '</span>'
		) );
		echo '</div>';
	}
}

add_action('it_exchange_content_invoice_product_after_payment_amount', 'it_exchange_easy_eu_value_added_taxes_display_vat_summary_on_invoice' );

function it_exchange_easy_eu_value_added_taxes_replace_order_table_tag_before_total_row( $email_obj, $options ) {
    $tax_items = get_post_meta( $email_obj->transaction_id, '_it_exchange_easy_eu_value_added_taxes', true );
    $vat_moss_tax_items = get_post_meta( $email_obj->transaction_id, '_it_exchange_easy_eu_value_added_vat_moss_taxes', true );
	$memberstates = it_exchange_get_data_set( 'eu-member-states' );

	if ( !empty( $tax_items ) ) {
		?>
		<tr>
			<td colspan="2" style="padding: 10px;border:1px solid #DDD;"><?php _e( 'Taxes', 'LION' ); ?></td>
			<td style="padding: 10px;border:1px solid #DDD;">&nbsp;</td>
		</tr>
		<?php
		foreach ( $tax_items as $tax ) {
			echo '<tr>';
			if ( !empty( $tax['total'] ) ) {
				$tax_total = it_exchange_format_price( $tax['total'] );

				$tax_type =  sprintf( __( '%s %s (%s%%)', 'LION' ), ( empty( $tax['country'] ) ? '' : $memberstates[$tax['country']] ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
				echo '<td colspan="2" style="padding: 10px;border:1px solid #DDD;">' . $tax_type . '</td>';
				echo '<td style="padding: 10px;border:1px solid #DDD;">' . $tax_total . '</td>';
			}
			echo '</tr>';
		}
		if ( !empty( $vat_moss_tax_items ) ) {
			foreach ( $vat_moss_tax_items as $tax ) {
				echo '<tr>';
				if ( !empty( $tax['total'] ) ) {
					$tax_total = it_exchange_format_price( $tax['total'] );
					$tax_type =  sprintf( __( '%s %s (%s%%)', 'LION' ), ( empty( $tax['country'] ) ? '' : $memberstates[$tax['country']] ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
					echo '<td colspan="2" style="padding: 10px;border:1px solid #DDD;">' . $tax_type . '</td>';
					echo '<td style="padding: 10px;border:1px solid #DDD;">' . $tax_total . '</td>';
				}
				echo '</tr>';
			}
		}
	}
}
add_action( 'it_exchange_replace_order_table_tag_before_total_row', 'it_exchange_easy_eu_value_added_taxes_replace_order_table_tag_before_total_row', 10, 2 );