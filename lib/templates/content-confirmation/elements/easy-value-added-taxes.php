<?php
/**
 * This is the default template for the Easy Value Added Taxes
 * element in the totals loop of the content-confirmation
 * template part. It was added by Easy UValue Added Taxes add-on.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @package exchange-addon-easy-value-added-taxes
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, copy over this file
 * to the exchange/content-confirmation/elements/
 * directory located in your theme.
*/
?>

<?php do_action( 'it_exchange_content_confirmation_before_easy_valued_added_taxes_element' ); ?>
<?php it_exchange( 'value-added-taxes', 'confirmation-taxes' ); ?>
<?php do_action( 'it_exchange_content_confirmation_after_easy_valued_added_taxes_element' ); ?>
