<?php
/*
 * Plugin Name: iThemes Exchange - Easy Value Added Taxes
 * Version: 1.0.0
 * Description: Adds Easy Value Added Taxes to iThemes Exchange.
 * Plugin URI: http://ithemes.com/exchange/easy-value-added-taxes/
 * Author: iThemes
 * Author URI: http://ithemes.com
 * iThemes Package: exchange-addon-easy-value-added-taxes
 
 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
*/

/**
 * This registers our plugin as a customer pricing addon
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_easy_value_added_taxes_addon() {
	$options = array(
		'name'              => __( 'Easy Value Added Taxes', 'LION' ),
		'description'       => __( 'Now store owners can now charge the proper Value Added Tax for each of their product types.', 'LION' ),
		'author'            => 'iThemes',
		'author_url'        => 'http://ithemes.com/exchange/easy-value-added-taxes/',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/taxes50px.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'category'          => 'taxes',
		'basename'          => plugin_basename( __FILE__ ),
		'labels'      => array(
			'singular_name' => __( 'Easy Value Added Taxes', 'LION' ),
		),
		'settings-callback' => 'it_exchange_easy_value_added_taxes_settings_callback',
	);
	it_exchange_register_addon( 'easy-value-added-taxes', $options );
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_easy_value_added_taxes_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
function it_exchange_easy_value_added_taxes_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
//add_action( 'plugins_loaded', 'it_exchange_easy_value_added_taxes_set_textdomain' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
function ithemes_exchange_addon_easy_value_added_taxes_updater_register( $updater ) { 
	$updater->register( 'exchange-addon-easy-value-added-taxes', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_easy_value_added_taxes_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );