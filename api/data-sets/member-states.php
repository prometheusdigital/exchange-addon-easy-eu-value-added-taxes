<?php
/**
 * EU Member States data sets
 * @package exchange-addon-easy-eu-value-added-taxes
 * @since 1.0.0
*/

/**
 * Returns an array of EU Member States
 *
 * @since 1.0.0
 *
 * @return array
*/
function it_exchange_easy_eu_value_added_taxes_addon_get_eu_member_states( $options=array() ) {

	// Defaults
	$defaults = array(
		'sort-by-values' => true,
	);

	$options = ITUtility::merge_defaults( $options, $defaults );

	$countries = it_exchange_get_data_set( 'states', array( 'country' => 'EU' ) );

	// Sort by values, not keys.
	if ( ! empty( $options['sort-by-values'] ) ) {
		$sorted = array();
		foreach( $countries as $key => $value ) {
			$sorted[$value] = $value;
		}
		array_multisort( $sorted, SORT_ASC, $countries );
	}

	return apply_filters( 'it_exchange_easy_eu_value_added_taxes_addon_get_eu_member_states', $countries );
}
