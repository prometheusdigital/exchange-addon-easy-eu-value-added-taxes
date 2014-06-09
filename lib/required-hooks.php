<?php
/**
 * iThemes Exchange Easy Value Added Taxes Add-on
 * @package exchange-addon-easy-value-added-taxes
 * @since 1.0.0
*/

//For calculation shipping, we need to require billing addresses... 
//incase a product doesn't have a shipping address and the shipping add-on is not enabled
add_filter( 'it_exchange_billing_address_purchase_requirement_enabled', '__return_true' );

function do_stuff() {
	$settings = it_exchange_get_option( 'addon_easy_value_added_taxes', true );

	if ( $settings['price-includes-vat'] ) {
		add_filter( 'it_exchange_api_theme_product_base_price', 'it_exchange_easy_value_added_taxes_addon_api_theme_product_base_price', 10, 2 );
		add_filter( 'it_exchange_api_theme_cart_items_sub_total', 'it_exchange_easy_value_added_taxes_addon_api_theme_cart_items_sub_total', 10, 2 );
		add_filter( 'it_exchange_api_theme_cart_total', 'it_exchange_easy_value_added_taxes_addon_api_theme_cart_total' );
	}
}
add_action( 'init', 'do_stuff' );

function it_exchange_easy_value_added_taxes_addon_api_theme_product_base_price( $price, $product_id ) {
	$settings = it_exchange_get_option( 'addon_easy_value_added_taxes' );
	
	if ( it_exchange_product_supports_feature( $product_id, 'value-added-taxes' ) ) {
		if ( !it_exchange_get_product_feature( $product_id, 'value-added-taxes', array( 'setting' => 'exempt' ) ) ) {
			
			$price = it_exchange_convert_to_database_number( $price );
			$price = it_exchange_convert_from_database_number( $price );
			
			$default_rate = 0;
			foreach ( $settings['tax-rates'] as $rate ) {
				if ( $rate['default'] ) {
					$default_rate = $rate['rate'];
				}
			}
			
			$tax_type = it_exchange_get_product_feature( $product_id, 'value-added-taxes', array( 'setting' => 'type' ) );

			if ( 'default' === $tax_type || '' === $tax_type || false === $tax_type )
				$tax_rate = $default_rate;
			else
				$tax_rate = $settings['tax-rates'][$tax_type]['rate'];

			$price *= ( ( 100 + $tax_rate ) / 100 );
			$price = it_exchange_format_price( $price ) . ' <span class="ite-evat-incl-vat-class">' . __( 'incl. VAT', 'LION' ) . '</span>';

		}
	}
	
	return $price;
}

function it_exchange_easy_value_added_taxes_addon_api_theme_cart_items_sub_total( $subtotal, $cart_item ) {
	$settings = it_exchange_get_option( 'addon_easy_value_added_taxes' );
	
	if ( it_exchange_product_supports_feature( $cart_item['product_id'], 'value-added-taxes' ) ) {
		if ( !it_exchange_get_product_feature( $cart_item['product_id'], 'value-added-taxes', array( 'setting' => 'exempt' ) ) ) {
			
			$subtotal = it_exchange_convert_to_database_number( $subtotal );
			$subtotal = it_exchange_convert_from_database_number( $subtotal );
			
			$default_rate = 0;
			foreach ( $settings['tax-rates'] as $rate ) {
				if ( $rate['default'] ) {
					$default_rate = $rate['rate'];
				}
			}
			
			$tax_type = it_exchange_get_product_feature( $cart_item['product_id'], 'value-added-taxes', array( 'setting' => 'type' ) );

			if ( 'default' === $tax_type || '' === $tax_type || false === $tax_type )
				$tax_rate = $default_rate;
			else
				$tax_rate = $settings['tax-rates'][$tax_type]['rate'];

			$subtotal *= ( ( 100 + $tax_rate ) / 100 );
			$subtotal = it_exchange_format_price( $subtotal );

		}
	}
	
	return $subtotal;
}

function it_exchange_easy_value_added_taxes_addon_api_theme_cart_total( $total ) {
	return $total . ' <span class="ite-evat-incl-vat-class">' . __( 'incl. VAT', 'LION' ) . '</span>';
}

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
	
		$deps = array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'jquery-ui-tabs', 'jquery-ui-tooltip', 'jquery-ui-datepicker', 'autosave' );
		wp_enqueue_script( 'it-exchange-easy-value-added-taxes-addon-admin-js', $url_base . '/js/admin.js' );

	} else if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		$deps = array( 'jquery', 'jquery-effects-highlight' );
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
		
		if ( it_exchange_is_page( 'checkout' ) )
			wp_enqueue_script( 'ite-vat-addon-checkout-page-var',  $url_base . '/js/checkout-page.js' );

		$deps = array( 'jquery', 'wp-backbone', 'underscore' );
		wp_enqueue_script( 'ite-vat-addon-vat-number-views',  $url_base . '/js/views/vat-number-views.js', $deps );
		$deps[] =  'ite-vat-addon-vat-number-views';
		wp_enqueue_script( 'ite-vat-addon-vat-number-manager', $url_base . '/js/vat-number-manager.js', $deps );
		
		wp_enqueue_style( 'ite-vat-addon-vat-number-manager', $url_base . '/styles/vat-number-manager.css' );
		
		add_action( 'wp_footer', 'it_exchange_easy_value_added_taxes_addon_vat_number_manager_backbone_template' );
		
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
	$tax_session = it_exchange_get_session_data( 'addon_easy_value_added_taxes' );
	if( empty( $tax_session['summary_only'] ) ) {
		if ( !it_exchange_is_page( 'cart' ) || it_exchange_in_superwidget() ) //we jcanadiant don't want to modify anything on the cart page
			$total += it_exchange_easy_value_added_taxes_addon_get_total_taxes_for_cart( false );
	}
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
	if ( !empty( $tax_session['summary_only'] ) ) {
		update_post_meta( $transaction_id, '_it_exchange_easy_value_added_summary_only', $tax_session['summary_only'] );
	}
	
	it_exchange_clear_session_data( 'addon_easy_value_added_taxes' );
	return;
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_easy_value_added_taxes_transaction_hook' );

/**
 * Backbone template for primary EU VAT Number Manager screen.
 * Invoked by wp.template() and WordPress 
 *
 * add_action( 'wp_footer', 'it_exchange_easy_value_added_taxes_addon_vat_number_manager_backbone_template' );
 *
 * @since 1.0.0
 */
function it_exchange_easy_value_added_taxes_addon_vat_number_manager_backbone_template() {
	$tax_session = it_exchange_get_session_data( 'addon_easy_value_added_taxes' );
	?>
	<div id="it-exchange-easy-value-added-taxes-vat-manager-wrapper" class="it-exchange-hidden"></div>
	<script type="text/template" id="tmpl-it-exchange-easy-value-added-taxes-vat-manager-container">
		<span class="it-exchange-evat-close-vat-manager"><a href="">&times;</a></span>
		<div id="it-exchange-easy-value-added-taxes-vat-manager">
			<div id="it-exchange-easy-value-added-taxes-vat-manager-title-area">
				<h3 class="it-exchange-evat-tax-emeption-title">
					<?php _e( 'VAT Manager', 'LION' ); ?>
				</h3>
			</div>
		
			<div id="it-exchange-easy-value-added-taxes-vat-manager-content-area">
				<div id="it-exchange-easy-value-added-taxes-vat-manager-error-area"></div>
				<form id="it-exchange-add-on-easy-value-added-taxes-add-edit-vat" name="it-exchange-add-on-easy-value-added-taxes-add-edit-vat" action="POST">
				<?php
				if ( !empty( $tax_session['vat_country'] ) ) {
					$vat_country = $tax_session['vat_country'];
				} else {
					$vat_country = '';
				}
				
				if ( !empty( $tax_session['vat_number'] ) ) {
					$vat_number = $tax_session['vat_number'];
				} else {
					$vat_number = '';
				}
				
				$output = '<select id="it-exchange-evat-eu-vat-country" name="eu-vat-country">';
				$memberstates = it_exchange_get_data_set( 'eu-member-states' );
				foreach( $memberstates as $abbr => $name ) {					
					$output .= '<option value="' . $abbr . '" ' . selected( $abbr, $vat_country, false ) . '>' . $name . '</option>';
				}
				$output .= '</select><br />';
				
				$output .= '<input type="text" id="it-exchange-evat-eu-vat-number" name="eu-vat-number" value="' . $vat_number . '" />';
				
				echo $output;
				?>
			
				<div class="field it-exchange-add-vat-submit">
					<input type="submit" value="<?php _e( 'Verify and Save VAT Number', 'LION' ); ?>" class="button button-large it-exchange-evat-save-vat-button" id="save" name="save">
					<input type="submit" value="Cancel" class="button button-large it-exchange-evat-cancel-vat-button" id="cancel" name="cancel">
					<input type="submit" value="Remove" class="button button-large it-exchange-evat-remove-vat-button" id="remove" name="remove">
					<?php wp_nonce_field( 'it-exchange-easy-value-added-taxes-add-edit-vat-number', 'it-exchange-easy-value-added-taxes-add-edit-vat-number-nonce' ); ?>
				</div>
				</form>
			</div>
		</div>
	</script>
	<?php
}