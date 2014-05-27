<?php
/**
 * Includes all of our AJAX functions
 * @since 1.0.0
 * @package exchange-addon-easy-value-added-taxes
*/

/**
 * AJAX function called to add new content access rule rows
 *
 * @since 1.0.0
 * @return string HTML output of content access rule row div
*/
function it_exchange_easy_value_added_taxes_addon_ajax_add_new_rate() {
	
	$return = '';
	
	if ( isset( $_REQUEST['count'] ) ) { //use isset() in case count is 0
		
		$count = $_REQUEST['count'];

		die( it_exchange_easy_value_added_taxes_get_tax_row_settings( $count ) );		
	
	}
	
	die( $return );
}
add_action( 'wp_ajax_it-exchange-easy-value-added-taxes-addon-add-new-rate', 'it_exchange_easy_value_added_taxes_addon_ajax_add_new_rate' );