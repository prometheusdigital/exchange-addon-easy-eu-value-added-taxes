<?php
/**
 * iThemes Exchange Easy Value Added Taxes Add-on
 * @package exchange-addon-easy-value-added-taxes
 * @since 1.0.0
*/

//For calculation shipping, we need to require billing addresses... 
//incase a product doesn't have a shipping address and the shipping add-on is not enabled
add_filter( 'it_exchange_billing_address_purchase_requirement_enabled', '__return_true' );


/**
 * Shows the nag when needed.
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_easy_value_added_taxes_addon_show_conflict_nag() {
    if ( ! empty( $_REQUEST['it_exchange_easy_value_added_taxes-dismiss-conflict-nag'] ) )
        update_option( 'it-exchange-easy-value-added-taxes-conflict-nag', true );

    if ( true == (boolean) get_option( 'it-exchange-easy-value-added-taxes-conflict-nag' ) )
        return;

	$taxes_addons = it_exchange_get_enabled_addons( array( 'category' => 'taxes' ) );
	
	if ( 1 < count( $taxes_addons ) ) {
		?>
		<div id="it-exchange-easy-value-added-taxes-conflict-nag" class="it-exchange-nag">
			<?php
			$nag_dismiss = add_query_arg( array( 'it_exchange_easy_value_added_taxes-dismiss-conflict-nag' => true ) );
			echo __( 'Warning: You have multiple tax add-ons enabled. You may need to disable one to avoid conflicts.', 'LION' );
			?>
			<a class="dismiss btn" href="<?php esc_attr_e( $nag_dismiss ); ?>">&times;</a>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				if ( jQuery( '.wrap > h2' ).length == '1' ) {
					jQuery("#it-exchange-easy-value-added-taxes-conflict-nag").insertAfter( '.wrap > h2' ).addClass( 'after-h2' );
				}
			});
		</script>
		<?php
	}
}
add_action( 'admin_notices', 'it_exchange_easy_value_added_taxes_addon_show_conflict_nag' );

/**
 * Enqueues Easy Value Added Taxes scripts to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress passed variable
 * @return void
*/
function it_exchange_easy_value_added_taxes_addon_admin_wp_enqueue_scripts( $hook_suffix ) {
	global $post;
			
	if ( isset( $_REQUEST['post_type'] ) ) {
		$post_type = $_REQUEST['post_type'];
	} else {
		if ( isset( $_REQUEST['post'] ) )
			$post_id = (int) $_REQUEST['post'];
		elseif ( isset( $_REQUEST['post_ID'] ) )
			$post_id = (int) $_REQUEST['post_ID'];
		else
			$post_id = 0;

		if ( $post_id )
			$post = get_post( $post_id );

		if ( isset( $post ) && !empty( $post ) )
			$post_type = $post->post_type;
	}
	
	$url_base = ITUtility::get_url_from_file( dirname( __FILE__ ) );
		
	if ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'easy-value-added-taxes' === $_GET['add-on-settings'] ) {
	
		$deps = array( 'jquery' );
		wp_enqueue_script( 'it-exchange-easy-value-added-taxes-addon-admin-js', $url_base . '/js/admin.js' );

	} else if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		$deps = array( 'jquery' );
		wp_enqueue_script( 'it-exchange-easy-value-added-taxes-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-edit-product.js', $deps );
	}
}
add_action( 'admin_enqueue_scripts', 'it_exchange_easy_value_added_taxes_addon_admin_wp_enqueue_scripts' );

/**
 * Enqueues Easy Value Added Taxes styles to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_easy_value_added_taxes_addon_admin_wp_enqueue_styles() {
	global $post, $hook_suffix;

	if ( isset( $_REQUEST['post_type'] ) ) {
		$post_type = $_REQUEST['post_type'];
	} else {
		if ( isset( $_REQUEST['post'] ) ) {
			$post_id = (int) $_REQUEST['post'];
		} else if ( isset( $_REQUEST['post_ID'] ) ) {
			$post_id = (int) $_REQUEST['post_ID'];
		} else {
			$post_id = 0;
		}

		if ( $post_id )
			$post = get_post( $post_id );

		if ( isset( $post ) && !empty( $post ) )
			$post_type = $post->post_type;
	}
	
	// Easy US Sales Taxes settings page
	if ( ( isset( $post_type ) && 'it_exchange_prod' === $post_type )
		|| ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'easy-value-added-taxes' === $_GET['add-on-settings'] ) ) {
		
		wp_enqueue_style( 'it-exchange-easy-value-added-taxes-addon-admin-style', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/admin.css' );
		
	}

}
add_action( 'admin_print_styles', 'it_exchange_easy_value_added_taxes_addon_admin_wp_enqueue_styles' );

/**
 * Loads the frontend CSS on all exchange pages
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_easy_value_added_taxes_load_public_scripts( $current_view ) {
	
	if ( it_exchange_is_page( 'checkout' ) || it_exchange_is_page( 'confirmation' ) || it_exchange_in_superwidget() ) {

		$url_base = ITUtility::get_url_from_file( dirname( __FILE__ ) );
		wp_enqueue_style( 'ite-easy-value-added-taxes-addon', $url_base . '/styles/taxes.css' );
		
	}

}
add_action( 'wp_enqueue_scripts', 'it_exchange_easy_value_added_taxes_load_public_scripts' );
add_action( 'it_exchange_enqueue_super_widget_scripts', 'it_exchange_easy_value_added_taxes_load_public_scripts' );

/**
 * Add Easy Value Added Taxes to the content-cart totals and content-checkout loop
 *
 * @since 1.0.0
 *
 * @param array $elements list of existing elements
 * @return array
*/
function it_exchange_easy_value_added_taxes_addon_add_taxes_to_template_totals_elements( $elements ) {
	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'totals-savings', $elements );
	if ( false === $index )
		$index = -1;
		
	// Bump index by 1 to show tax after discounts
	if ( -1 != $index )
		$index++;

	array_splice( $elements, $index, 0, 'easy-value-added-taxes' );
	return $elements;
}
add_filter( 'it_exchange_get_content_checkout_totals_elements', 'it_exchange_easy_value_added_taxes_addon_add_taxes_to_template_totals_elements' );
add_filter( 'it_exchange_get_content_confirmation_transaction_summary_elements', 'it_exchange_easy_value_added_taxes_addon_add_taxes_to_template_totals_elements' );

/**
 * Add Easy Value Added Taxes to the confirmation loop
 *
 * @since 1.0.0
 *
 * @param array $elements list of existing elements
 * @return array
*/
function it_exchange_easy_value_added_taxes_addon_get_content_confirmation_transaction_meta_elements( $elements ) {
	$elements[] = 'easy-value-added-taxes-vat-summary'; //we always want it at the end
	return $elements;
}
add_filter( 'it_exchange_get_content_confirmation_transaction_meta_elements', 'it_exchange_easy_value_added_taxes_addon_get_content_confirmation_transaction_meta_elements' );

/**
 * Add Easy Value Added Taxes to the super-widget-checkout totals loop
 *
 * @since 1.0.0
 *
 * @param array $loops list of existing elements
 * @return array
*/
function it_exchange_easy_value_added_taxes_addon_add_taxes_to_sw_template_totals_loops( $loops ) {
	// Locate the discounts key in elements array (if it exists)
	$index = array_search( 'discounts', $loops );
	if ( false === $index )
		$index = -1;
		
	// Bump index by 1 to show tax after discounts
	if ( -1 != $index )
		$index++;

	array_splice( $loops, $index, 0, 'easy-value-added-taxes' );
	return $loops;
}
add_filter( 'it_exchange_get_super-widget-checkout_after-cart-items_loops', 'it_exchange_easy_value_added_taxes_addon_add_taxes_to_sw_template_totals_loops' );

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
function it_exchange_easy_value_added_taxes_addon_taxes_register_templates( $template_paths, $template_names ) {
	// Bail if not looking for one of our templates
	$add_path = false;
	$templates = array(
		'content-checkout/elements/easy-value-added-taxes.php',
		'content-confirmation/elements/easy-value-added-taxes.php',
		'content-confirmation/elements/easy-value-added-taxes-vat-summary.php',
		'super-widget-checkout/loops/easy-value-added-taxes.php',
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
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_easy_value_added_taxes_addon_taxes_register_templates', 10, 2 );

/**
 * Adds Easy Value Added Taxes Template Path to iThemes Exchange Template paths
 *
 * @since 1.0.0
 * @param array $possible_template_paths iThemes Exchange existing Template paths array
 * @param array $template_names
 * @return array
*/
function it_exchange_easy_value_added_taxes_addon_template_path( $possible_template_paths, $template_names ) {
	$possible_template_paths[] = dirname( __FILE__ ) . '/templates/';
	return $possible_template_paths;
}
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_easy_value_added_taxes_addon_template_path', 10, 2 );

/**
 * Adjcanadiants the cart total if on a checkout page
 *
 * @since 1.0.0
 *
 * @param int $total the total passed to canadian by Exchange.
 * @return int New Total
*/
function it_exchange_easy_value_added_taxes_addon_taxes_modify_total( $total ) {
	if ( !it_exchange_is_page( 'cart' ) || it_exchange_in_superwidget() ) //we jcanadiant don't want to modify anything on the cart page
		$total += it_exchange_easy_value_added_taxes_addon_get_total_taxes_for_cart( false );
	return $total;
}
add_filter( 'it_exchange_get_cart_total', 'it_exchange_easy_value_added_taxes_addon_taxes_modify_total' );

/**
 * Save Taxes to Transaction Meta
 *
 * @since 1.0.0
 *
 * @param int $transaction_id Transaction ID
*/
function it_exchange_easy_value_added_taxes_transaction_hook( $transaction_id ) {
	$tax_session = it_exchange_get_session_data( 'addon_easy_value_added_taxes' );
	
	if ( !empty( $tax_session['taxes'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_value_added_taxes', $tax_session['taxes'] );
	}
	if ( !empty( $tax_session['vat_country'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_value_added_customer_vat_country', $tax_session['vat_country'] );
	}
	if ( !empty( $tax_session['vat_number'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_value_added_customer_vat_number', $tax_session['vat_number'] );
	}
	if ( !empty( $tax_session['total_taxes'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_value_added_taxes_total', $tax_session['total_taxes'] );
	}
	
	it_exchange_clear_session_data( 'addon_easy_value_added_taxes' );
	return;
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_easy_value_added_taxes_transaction_hook' );


/**
 * Registers our purchase requirements
 *
 * @since 1.0.0
*/
function it_exchange_easy_value_added_taxes_register_purchase_requirements() {

	// Link vars
	$login      = __( 'Log in', 'LION' );
	$register   = __( 'register', 'LION' );
	$cart       = __( 'edit your cart', 'LION' );
	$login_link = '<a href="' . it_exchange_get_page_url( 'login' ) . '" class="it-exchange-login-requirement-login">';
	$reg_link   = '<a href="' . it_exchange_get_page_url( 'registration' ) . '" class="it-exchange-login-requirement-registration">';
	$cart_link  = '<a href="' . it_exchange_get_page_url( 'cart' ) . '">';
	$close_link = '</a>';

	// Billing Address Purchase Requirement
	$properties = array(
		'requirement-met'        => 'it_exchange_easy_value_added_taxes_get_customer_vat_details', //callback
		'sw-template-part'       => 'customer-eu-vat-number',
		'checkout-template-part' => 'customer-eu-vat-number',
		'notification'           => __( 'You must enter a valid EU VAT number before you can checkout', 'LION' ),
		'priority'               => 5.13
	);
		
	it_exchange_register_purchase_requirement( 'customer-eu-vat-number', $properties );
}
add_action( 'init', 'it_exchange_easy_value_added_taxes_register_purchase_requirements' );