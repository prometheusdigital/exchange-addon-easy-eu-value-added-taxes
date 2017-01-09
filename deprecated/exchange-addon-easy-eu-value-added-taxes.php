<?php
/**
 * This registers our plugin as a customer pricing addon
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_easy_eu_value_added_taxes_addon() {
	if ( extension_loaded( 'soap' ) ) {
		$options = array(
			'name'              => __( 'Easy EU Value Added Taxes', 'LION' ),
			'description'       => __( 'Now store owners in the EU can now charge the proper Value Added Tax for each of their product types.', 'LION' ),
			'author'            => 'iThemes',
			'author_url'        => 'http://ithemes.com/exchange/easy-eu-value-added-taxes/',
			'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/taxes50px.png' ),
			'file'              => dirname( __FILE__ ) . '/init.php',
			'category'          => 'taxes',
			'basename'          => plugin_basename( __FILE__ ),
			'labels'      => array(
				'singular_name' => __( 'Easy EU Value Added Taxes', 'LION' ),
			),
			'settings-callback' => 'it_exchange_easy_eu_value_added_taxes_settings_callback',
		);
		it_exchange_register_addon( 'easy-eu-value-added-taxes', $options );
	}
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_easy_eu_value_added_taxes_addon' );

function it_exchange_easy_eu_value_added_taxes_show_soap_nag() {
	if ( !extension_loaded( 'soap' ) ) {
		?>
		<div id="it-exchange-add-on-soap-nag" class="it-exchange-nag">
			<?php _e( 'You must have the SOAP PHP extension installed and activated on your web server to use the Easy EU Value Added Taxes Add-on for iThemes Exchange. Please contact your web host provider to ensure this extension is enabled.', 'LION' ); ?>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'it_exchange_easy_eu_value_added_taxes_show_soap_nag' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
function it_exchange_easy_eu_value_added_taxes_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
//add_action( 'plugins_loaded', 'it_exchange_easy_eu_value_added_taxes_set_textdomain' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
function ithemes_exchange_addon_easy_eu_value_added_taxes_updater_register( $updater ) { 
	$updater->register( 'exchange-addon-easy-eu-value-added-taxes', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_easy_eu_value_added_taxes_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );