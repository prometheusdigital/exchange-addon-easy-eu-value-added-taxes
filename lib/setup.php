<?php
/**
 * Do basic setup.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Shows the nag when needed.
 *
 * @since 1.0.1
 *
 * @return void
 */
function it_exchange_easy_eu_value_added_taxes_addon_show_version_nag() {
	if ( version_compare( $GLOBALS['it_exchange']['version'], '1.9.0', '<' ) ) {
		?>
		<div id="it-exchange-add-on-min-version-nag" class="it-exchange-nag">
			<?php printf( __( 'The Easy EU Value Added Taxes add-on requires iThemes Exchange version 1.9.0 or greater. %sPlease upgrade Exchange%s.', 'LION' ), '<a href="' . admin_url( 'update-core.php' ) . '">', '</a>' ); ?>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				if ( jQuery( '.wrap > h2' ).length == '1' ) {
					jQuery("#it-exchange-add-on-min-version-nag").insertAfter('.wrap > h2').addClass( 'after-h2' );
				}
			});
		</script>
		<?php
	}
}
add_action( 'admin_notices', 'it_exchange_easy_eu_value_added_taxes_addon_show_version_nag' );

/**
 * Enqueues Easy EU Value Added Taxes scripts to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress passed variable
 * @return void
 */
function it_exchange_easy_eu_value_added_taxes_addon_admin_wp_enqueue_scripts( $hook_suffix ) {
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

	if ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'easy-eu-value-added-taxes' === $_GET['add-on-settings'] ) {

		$deps = array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'jquery-ui-tabs', 'jquery-ui-tooltip', 'jquery-ui-datepicker', 'autosave' );
		wp_enqueue_script( 'it-exchange-easy-eu-value-added-taxes-addon-admin-js', $url_base . '/js/admin.js' );

	} else if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		$deps = array( 'jquery', 'jquery-effects-highlight' );
		wp_enqueue_script( 'it-exchange-easy-eu-value-added-taxes-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-edit-product.js', $deps );
	}
}
add_action( 'admin_enqueue_scripts', 'it_exchange_easy_eu_value_added_taxes_addon_admin_wp_enqueue_scripts' );

/**
 * Enqueues Easy EU Value Added Taxes styles to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_easy_eu_value_added_taxes_addon_admin_wp_enqueue_styles() {
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
	if ( ( isset( $post_type ) && ( 'it_exchange_prod' === $post_type || 'it_exchange_tran' === $post_type ) )
	     || ( !empty( $_GET['add-on-settings'] ) && 'exchange_page_it-exchange-addons' === $hook_suffix && 'easy-eu-value-added-taxes' === $_GET['add-on-settings'] ) ) {

		wp_enqueue_style( 'it-exchange-easy-eu-value-added-taxes-addon-admin-style', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/admin.css' );

	}

}
add_action( 'admin_print_styles', 'it_exchange_easy_eu_value_added_taxes_addon_admin_wp_enqueue_styles' );

/**
 * Loads the frontend CSS on all exchange pages
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_easy_eu_value_added_taxes_load_public_scripts( $current_view ) {

	if ( it_exchange_is_page( 'checkout' ) || it_exchange_is_page( 'confirmation' ) || it_exchange_in_superwidget() ) {

		$url_base = ITUtility::get_url_from_file( dirname( __FILE__ ) );
		wp_enqueue_style( 'ite-easy-eu-value-added-taxes-addon', $url_base . '/styles/taxes.css' );

		if ( it_exchange_is_page( 'checkout' ) )
			wp_enqueue_script( 'ite-vat-addon-checkout-page-var',  $url_base . '/js/checkout-page.js' );

		$deps = array( 'jquery', 'wp-backbone', 'underscore' );
		wp_enqueue_script( 'ite-vat-addon-vat-number-views',  $url_base . '/js/views/vat-number-views.js', $deps );
		$deps[] =  'ite-vat-addon-vat-number-views';
		wp_enqueue_script( 'ite-vat-addon-vat-number-manager', $url_base . '/js/vat-number-manager.js', $deps );

		wp_enqueue_style( 'ite-vat-addon-vat-number-manager', $url_base . '/styles/vat-number-manager.css' );

		add_action( 'wp_footer', 'it_exchange_easy_eu_value_added_taxes_addon_vat_number_manager_backbone_template' );

	}

}
add_action( 'wp_enqueue_scripts', 'it_exchange_easy_eu_value_added_taxes_load_public_scripts' );
add_action( 'it_exchange_enqueue_super_widget_scripts', 'it_exchange_easy_eu_value_added_taxes_load_public_scripts' );