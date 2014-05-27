<?php
/**
 * This is the default template for the Easy Value Added Taxes
 * element in the totals loop of the content-checkout
 * template part. It was added by Easy Value Added Taxes add-on.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @package exchange-addon-easy-value-added-taxes
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, copy over this file
 * to the exchange/content-checkout/elements/
 * directory located in your theme.
*/
?>

<?php do_action( 'it_exchange_content_checkout_before_easy_valued_added_taxes_element' ); ?>
<div class="it-exchange-cart-totals-title it-exchange-table-column">
	<?php do_action( 'it_exchange_content_checkout_before_easy_valued_added_taxes_label' ); ?>
	<div class="it-exchange-table-column-inner">
		<?php _e( 'Tax', 'LION' ); ?>
	</div>
	<?php do_action( 'it_exchange_content_checkout_after_easy_valued_added_taxes_label' ); ?>
</div>
<div class="it-exchange-cart-totals-amount it-exchange-table-column">
	<?php do_action( 'it_exchange_content_checkout_before_easy_valued_added_taxes_value' ); ?>
	<div class="it-exchange-table-column-inner">
		<?php it_exchange( 'value-added-taxes', 'taxes' ); ?>
	</div>
	<?php do_action( 'it_exchange_content_checkout_after_easy_valued_added_taxes_value' ); ?>
</div>
<?php do_action( 'it_exchange_content_checkout_after_easy_valued_added_taxes_element' ); ?>
