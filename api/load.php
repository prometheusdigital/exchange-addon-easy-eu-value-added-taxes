<?php
/**
 * iThemes Exchange Easy Canadian Sales Taxes Add-on
 * load theme API functions
 * @package exchange-addon-easy-eu-value-added-taxes
 * @since 1.0.0
*/

if ( is_admin() ) {
	// Admin only
} else {
	// Frontend only
	include( 'theme.php' );
}

// Data Sets
include( 'data-sets.php' );