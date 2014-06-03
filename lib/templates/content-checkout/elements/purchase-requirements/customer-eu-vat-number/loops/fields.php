<?php
/**
 * This is the default template part for the
 * fields loop in the customer-eu-vat-number purchase-requriements
 * in the content-checkout template part.
 *
 * @since 1.4.0
 * @version 1.4.0
 * @package IT_Exchange
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, copy over this file to the
 * /exchange/content-checkout/elements/purchase-requirements/customer-eu-vat-number/loops/
 * directory located in your theme.
*/
?>
<?php do_action( 'it_exchange_content_checkout_customer_eu_vat_number_purchase_requirement_before_fields_loop' ); ?>
<?php $fields = array( 'vat-country', 'vat-number', 'nonce' ); ?>
<?php foreach( it_exchange_get_template_part_elements( 'content_checkout/elements/purchase-requirements/customer-eu-vat-number/elements/', 'fields', $fields ) as $field ) : ?>
	<?php
	/**
	 * Theme and add-on devs should add code to this loop by
	 * hooking into it_exchange_get_template_part_elements filter
	 * and adding the appropriate template file to their theme or add-on
	 */
	it_exchange_get_template_part( 'content', 'checkout/elements/purchase-requirements/customer-eu-vat-number/elements/' . $field );
	?>
<?php endforeach; ?>
<?php do_action( 'it_exchange_content_checkout_customer_eu_vat_number_purchase_requirement_after_fields_loop' ); ?>
