<?php
/**
 * iThemes Exchange Easy Canadian Sales Taxes Add-on
 * load theme API functions
 * @package exchange-addon-easy-eu-value-added-taxes
 * @since   1.0.0
 */

if ( is_admin() ) {
	// Admin only
} else {
	// Frontend only
	require_once( dirname( __FILE__ ) . '/theme.php' );
}

// Data Sets
require_once( dirname( __FILE__ ) . '/data-sets.php' );