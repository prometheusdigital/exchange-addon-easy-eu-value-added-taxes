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

	$countries = array(
		'BE' => __( 'Belgium', 'LION' ),
		'BG' => __( 'Bulgaria', 'LION' ),
		'CZ' => __( 'Czech Republic', 'LION' ),
		'DK' => __( 'Denmark', 'LION' ),
		'DE' => __( 'Germany', 'LION' ),
		'EE' => __( 'Estonia', 'LION' ),
		'EL' => __( 'Greece', 'LION' ),
		'ES' => __( 'Spain', 'LION' ),
		'FR' => __( 'France', 'LION' ),
		'HR' => __( 'Croatia', 'LION' ),
		'IE' => __( 'Ireland', 'LION' ),
		'IT' => __( 'Italy', 'LION' ),
		'CY' => __( 'Cyprus', 'LION' ),
		'LV' => __( 'Latvia', 'LION' ),
		'LT' => __( 'Lithuania', 'LION' ),
		'LU' => __( 'Luxembourg', 'LION' ),
		'HU' => __( 'Hungary', 'LION' ),
		'MT' => __( 'Malta', 'LION' ),
		'NL' => __( 'Netherlands', 'LION' ),
		'AT' => __( 'Austria', 'LION' ),
		'PL' => __( 'Poland', 'LION' ),
		'PT' => __( 'Portugal', 'LION' ),
		'RO' => __( 'Romania', 'LION' ),
		'SI' => __( 'Slovenia', 'LION' ),
		'SK' => __( 'Slovakia', 'LION' ),
		'FI' => __( 'Finland', 'LION' ),
		'SE' => __( 'Sweden', 'LION' ),
		'UK' => __( 'United Kingdom', 'LION' ),
	);

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
