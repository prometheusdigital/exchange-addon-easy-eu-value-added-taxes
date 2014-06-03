<?php
/**
 * This is the template part for the customer's EU Vat Details
 * purchase requirement element in the content-checkout
 * template part.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @package IT_Exchange
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, copy over this file
 * to the exchange/content-checkout/elements/purchase-requirements directory
 * located in your theme.
*/

// Don't show anything if login-requirement exists and hasn't been met
if ( in_array( 'logged-in', it_exchange_get_pending_purchase_requirements() ) 
	&& ( in_array( 'billing-address', it_exchange_get_pending_purchase_requirements() ) || in_array( 'shipping-address', it_exchange_get_pending_purchase_requirements() ) ) )
	return;
	
$vat_details = it_exchange_easy_value_added_taxes_get_customer_vat_details();

if ( -1 !== $vat_details ) { //-1 = VAT not required

$edit_customer_vat = ( ( ! empty( $_REQUEST['it-exchange-update-easy-value-added-taxes-customer-vat'] ) && ! empty( $GLOBALS['it_exchange']['easy-value-added-taxes-customer-vat-error'] ) ) || ! $vat_details ) ? true : false;
?>
<?php do_action( 'it_exchange_content_checkout_easy_value_added_tax_customer_vat_purchase_requirement_before_element' ); ?>
<div class="it-exchange-checkout-customer-eu-vat-number-purchase-requirement">
	<h3><?php _e( 'VAT Number', 'LION' ); ?></h3>
	<?php if ( false !== ( $customer_vat_number = it_exchange_easy_value_added_taxes_get_customer_vat_details() ) && empty( $edit_customer_vat ) ) : ?>
		<div class="checkout-purchase-requirement-customer-eu-vat-number-options">
			<div class="existing-customer-eu-vat-number">
				<?php echo $customer_vat_number; ?>
			</div>
		</div>
	<?php endif; ?>
	<div class="<?php echo $edit_customer_vat ? 'it-exchange-hidden ' : ''; ?>checkout-purchase-requirement-customer-eu-vat-number-options">
		<a class="it-exchange-purchase-requirement-edit-customer-eu-vat-number" href=""><?php __( 'Edit VAT Number', 'LION' ); ?></a>
	</div>
	<div class="<?php echo $edit_customer_vat ? '' : 'it-exchange-hidden '; ?>checkout-purchase-requirement-customer-vat-edit">
		<?php
		$loops = array( 'fields', 'actions' );
		?>
		<div class="it-exchange-customer-eu-vat-number-form">
			<?php
			do_action( 'it_exchange_content_checkout_customer_eu_vat_number_purchase_requirement_before_form' );
			?>
			<form action="" method="post" >
				<?php
				do_action( 'it_exchange_content_checkout_customer_eu_vat_number_purchase_requirement_begin_form' );
				// Include template parts for each of the above loops
				foreach( it_exchange_get_template_part_loops( 'content-checkout/elements/purchase-requirements/customer-eu-vat-number/loops/', '', $loops ) as $loop ) :
					it_exchange_get_template_part( 'content', 'checkout/elements/purchase-requirements/customer-eu-vat-number/loops/' . $loop );
				endforeach;
				do_action( 'it_exchange_content_checkout_customer_eu_vat_number_purchase_requirement_end_form' );
				?>
			</form>
			<?php
			do_action( 'it_exchange_content_checkout_customer_eu_vat_number_purchase_requirement_after_form' );
			?>
		</div>
	</div>
</div>
<div class="it-exchange-clearfix"></div>
<?php do_action( 'it_exchange_content_checkout_easy_value_added_tax_customer_vat_purchase_requirement_after_element' ); ?>

<?php
}