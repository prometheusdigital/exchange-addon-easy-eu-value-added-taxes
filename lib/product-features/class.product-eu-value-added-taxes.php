<?php
/**
 * This will control email messages with any product types that register email message support.
 * By default, it registers a metabox on the product's add/edit screen and provides HTML / data for the frontend.
 *
 * @since 1.0.0 
 * @package exchange-addon-easy-eu-value-added-taxes
*/


class IT_Exchange_Product_Feature_Product_Value_Added_Taxes {

	/**
	 * Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Product_Feature_Product_Value_Added_Taxes() {
		if ( is_admin() ) {
			add_action( 'load-post-new.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'load-post.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
		}
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_product_types' ) );
		add_action( 'it_exchange_update_product_feature_value-added-taxes', array( $this, 'save_feature' ), 9, 3 );
		add_filter( 'it_exchange_get_product_feature_value-added-taxes', array( $this, 'get_feature' ), 9, 3 );
		add_filter( 'it_exchange_product_has_feature_value-added-taxes', array( $this, 'product_has_feature') , 9, 3 );
		add_filter( 'it_exchange_product_supports_feature_value-added-taxes', array( $this, 'product_supports_feature') , 9, 2 );
	}

	/**
	 * Register the product feature and add it to enabled product-type addons
	 *
	 * @since 1.0.0
	*/
	function add_feature_support_to_product_types() {
		// Register the product feature
		$slug        = 'value-added-taxes';
		$description = __( "Set the Product's Value Added Tax options", 'LION' );
		it_exchange_register_product_feature( $slug, $description );

		// Add it to all enabled product-type addons
		$products = it_exchange_get_enabled_addons( array( 'category' => 'product-type' ) );
		foreach( $products as $key => $params ) {
			it_exchange_add_feature_support_to_product_type( 'value-added-taxes', $params['slug'] );
		}
	}

	/**
	 * Register's the metabox for any product type that supports the feature
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function init_feature_metaboxes() {

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

		if ( !empty( $_REQUEST['it-exchange-product-type'] ) )
			$product_type = $_REQUEST['it-exchange-product-type'];
		else
			$product_type = it_exchange_get_product_type( $post );

		if ( !empty( $post_type ) && 'it_exchange_prod' === $post_type ) {
			if ( !empty( $product_type ) &&  it_exchange_product_type_supports_feature( $product_type, 'value-added-taxes' ) )
				add_action( 'it_exchange_product_metabox_callback_' . $product_type, array( $this, 'register_metabox' ) );
		}

	}

	/**
	 * Registers the feature metabox for a specific product type
	 *
	 * Hooked to it_exchange_product_metabox_callback_[product-type] where product type supports the feature
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function register_metabox() {
		add_meta_box( 'it-exchange-product-value-added-taxes', __( 'Value Added Tax', 'LION' ), array( $this, 'print_metabox' ), 'it_exchange_prod', 'normal' );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function print_metabox( $product ) {
		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
		$tax_exempt = it_exchange_get_product_feature( $product->ID, 'value-added-taxes', array( 'setting' => 'exempt' ) );
		$tax_type = it_exchange_get_product_feature( $product->ID, 'value-added-taxes', array( 'setting' => 'type' ) );
		$vat_moss = it_exchange_get_product_feature( $product->ID, 'value-added-taxes', array( 'setting' => 'vat-moss' ) );
		$vat_moss_tax_types = it_exchange_get_product_feature( $product->ID, 'value-added-taxes', array( 'setting' => 'vat-moss-tax-types' ) );
		$default_tax_rate = array( 'label' => '', 'rate' => 0 );
		$calculation_rate = 0;
		$memberstates = it_exchange_get_data_set( 'eu-member-states' );
		?>
		
		<p>
            <label for="easy-eu-value-added-taxes-value-added-taxes"><?php _e( 'Tax Exempt?', 'LION' ) ?></label>
			<input type="checkbox" name="it-exchange-add-on-easy-eu-value-added-taxes-value-added-tax-exempt" id="euvat-exempt" <?php checked( $tax_exempt ); ?> />
        </p>
		
		<?php
		//Determine the default...
		foreach( $settings['tax-rates'] as $key => $tax_rate ) {
			if ( 'checked' === $tax_rate['default'] ) {
				$default_tax_rate = $tax_rate;
				$calculation_rate = $tax_rate['rate'];
				break;
			}
		}
		
		if ( $tax_exempt ) {
			$display = 'hide-if-js ';
			$calculation_rate = 0;
		} else {
			$display = '';
		}
		?>
		<p class="vat-tax-types <?php echo $display; ?>">
            <label for="easy-eu-value-added-taxes-value-added-taxes"><?php _e( 'Tax Type?', 'LION' ) ?></label>
			
			<select id="euvat-type" name="it-exchange-add-on-easy-eu-value-added-taxes-value-added-tax-type">
				<option value="default" <?php selected( 'default', $tax_type ); ?>><?php printf( __( 'Default (%s - %s%%)', 'LION' ), $default_tax_rate['label'], $default_tax_rate['rate'] ); ?></option>
			<?php 
			foreach( $settings['tax-rates'] as $key => $tax_rate ) {
				echo '<option value="' . $key . '" ' . selected( $key, $tax_type, false ) . '>' . sprintf( __( '%s (%s%%)', 'LION' ), $tax_rate['label'], $tax_rate['rate'] ) . '</option>';
			}
			?>
			</select>
        </p>
        <?php
        $base_price = it_exchange_get_product_feature( $product->ID, 'base-price' );
        ?>
        <div class="vat-price-calculator">
            <label for="vat-price-calculator-pre-vat-price"><?php _e( 'Price excluding VAT', 'LION' ) ?></label>
            <input id="vat-price-calculator-pre-vat-price" type="text" name="vat-price-calculator-pre-vat-price" value="<?php echo it_exchange_format_price( $base_price ); ?>" />
            <label for="vat-price-calculator-price-w-vat"><?php _e( 'Price including VAT', 'LION' ) ?></label>
            <input id="vat-price-calculator-price-w-vat" type="text" name="vat-price-calculator-price-w-vat" value="<?php echo it_exchange_format_price( $base_price * ( ( 100 + $calculation_rate ) / 100 ) ); ?>" />
            <p id="set-product-price-from-easy-eu-value-added-taxes-addon">
            <input type="button" class="button" value="Set Product Price" />
            </p>
        </div>
		
		<p>
            <label for="easy-eu-value-added-taxes-value-added-taxes-vat-moss"><?php _e( 'Enable VAT MOSS?', 'LION' ) ?></label>
			<input type="checkbox" name="it-exchange-add-on-easy-eu-value-added-taxes-vat-moss" id="vat-moss" <?php checked( $vat_moss ); ?> />
        </p>
        
		<?php
			
		if ( !empty( $vat_moss ) ) {
			$display = '';
		} else {
			$display = 'hide-if-js ';
		}
		foreach( $settings['vat-moss-tax-rates'] as $memberstate_abbrev => $tax_rates ) {
			foreach( $tax_rates as $tax_rate ) {
				if ( 'checked' === $tax_rate['default'] ) {
					$default_tax_rate = $tax_rate;
					break;
				}
			}

			?>
			<p class="vat-moss-tax-types <?php echo $display; ?>">
	            <label for="easy-eu-value-added-taxes-vat-moss-for-<?php echo $memberstate_abbrev; ?>"><?php printf( __( 'Tax Type for %s?', 'LION' ), $memberstates[$memberstate_abbrev] ) ?></label>
				
				<select id="euvat-moss-type" name="it-exchange-add-on-easy-eu-value-added-taxes-vat-moss-tax-type[<?php echo $memberstate_abbrev; ?>]">
					<option value="default" <?php selected( 'default', $vat_moss_tax_types[$memberstate_abbrev] ); ?>><?php printf( __( 'Default (%s - %s%%)', 'LION' ), $default_tax_rate['label'], $default_tax_rate['rate'] ); ?></option>
				<?php 
				foreach( $tax_rates as $key => $tax_rate ) {
					echo '<option value="' . $key . '" ' . selected( $key, $vat_moss_tax_types[$memberstate_abbrev], false ) . '>' . sprintf( __( '%s (%s%%)', 'LION' ), $tax_rate['label'], $tax_rate['rate'] ) . '</option>';
				}
				?>
				</select>
	        </p>
			<?php
		}
	}

	/**
	 * This saves the value
	 *
	 * @since 1.0.0 
	 * @param object $post wp post object
	 * @return void
	*/
	function save_feature_on_product_save() {
		// Abort if we can't determine a product type
		if ( ! $product_type = it_exchange_get_product_type() )
			return;

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( ! $product_id )
			return;

		// Abort if this product type doesn't support this feature
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'value-added-taxes' ) )
			return;

		// Get new value from post
		$tax_exempt = empty( $_POST['it-exchange-add-on-easy-eu-value-added-taxes-value-added-tax-exempt'] ) ? false : true;

		// Save new value
		it_exchange_update_product_feature( $product_id, 'value-added-taxes', $tax_exempt, array( 'setting' => 'exempt' ) );

		// Get new value from post
		$tax_type = !isset( $_POST['it-exchange-add-on-easy-eu-value-added-taxes-value-added-tax-type'] ) ? 'default' : $_POST['it-exchange-add-on-easy-eu-value-added-taxes-value-added-tax-type'];

		// Save new value
		it_exchange_update_product_feature( $product_id, 'value-added-taxes', $tax_type, array( 'setting' => 'type' ) );

		// Get new value from post
		$vat_moss = empty( $_POST['it-exchange-add-on-easy-eu-value-added-taxes-value-added-taxes-vat-moss'] ) ? false : true;

		// Save new value
		it_exchange_update_product_feature( $product_id, 'value-added-taxes', $vat_moss, array( 'setting' => 'vat-moss' ) );
		
		$vat_moss_tax_types = empty( $_POST['it-exchange-add-on-easy-eu-value-added-taxes-vat-moss-tax-type'] ) ? array() : $_POST['it-exchange-add-on-easy-eu-value-added-taxes-vat-moss-tax-type'];
		it_exchange_update_product_feature( $product_id, 'value-added-taxes', $vat_moss_tax_types, array( 'setting' => 'vat-moss-tax-types' ) );

	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.0.0
	 * @param integer $product_id the product id
	 * @param mixed $new_value the new value
	 * @return bolean
	*/
	function save_feature( $product_id, $new_value, $options=array() ) {
		$defaults['setting'] = 'exempt';
		$options = ITUtility::merge_defaults( $options, $defaults );
		
		switch ( $options['setting'] ) {
			
			case 'vat-moss':
				update_post_meta( $product_id, '_it-exchange-easy-eu-value-added-taxes-vat-moss', $new_value );
				break;
			case 'vat-moss-tax-types':
				update_post_meta( $product_id, '_it-exchange-easy-eu-value-added-taxes-vat-moss-tax-types', $new_value );
				break;
			case 'exempt':
				update_post_meta( $product_id, '_it-exchange-easy-eu-value-added-taxes-exempt', $new_value );
				break;
			case 'type':
				update_post_meta( $product_id, '_it-exchange-easy-eu-value-added-taxes-type', $new_value );
				break;
			
		}
		return true;
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.0.0
	 * @param mixed $existing the values passed in by the WP Filter API. Ignored here.
	 * @param integer product_id the WordPress post ID
	 * @return string product feature
	*/
	function get_feature( $existing, $product_id, $options=array() ) {
		$defaults['setting'] = 'exempt';
		$options = ITUtility::merge_defaults( $options, $defaults );
		
		switch ( $options['setting'] ) {
			
			case 'vat-moss':
				if ( $vat_moss = get_post_meta( $product_id, '_it-exchange-easy-eu-value-added-taxes-vat-moss', true ) ) {
					return $vat_moss;
				} else {
					$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
					$product_type = it_exchange_get_product_type( $product_id );
					return in_array( $product_type, $settings['default-vat-moss-products'] );
				}
			case 'vat-moss-tax-types':
				$vat_most_tax_types = get_post_meta( $product_id, '_it-exchange-easy-eu-value-added-taxes-vat-moss-tax-types', true );
				if ( !empty( $options['vat-moss-country'] ) ) {
					if ( !empty( $vat_most_tax_types[$options['vat-moss-country']] ) ) {
						return $vat_most_tax_types[$options['vat-moss-country']];
					} else {
						return false;
					}
				} else {
					return $vat_most_tax_types;
				}
			case 'exempt':
				return get_post_meta( $product_id, '_it-exchange-easy-eu-value-added-taxes-exempt', true );
			case 'type':
				return get_post_meta( $product_id, '_it-exchange-easy-eu-value-added-taxes-type', true );

		}
		
		return false;
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_has_feature( $result, $product_id, $options=array() ) {
		$defaults['setting'] = 'exempt';
		$options = ITUtility::merge_defaults( $options, $defaults );

		// Does this product type support this feature?
		if ( false === $this->product_supports_feature( false, $product_id, $options ) )
			return false;

		// If it does support, does it have it?
		return (boolean) $this->get_feature( false, $product_id, $options );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_supports_feature( $result, $product_id ) {
		// Does this product type support this feature?
		$product_type = it_exchange_get_product_type( $product_id );
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'value-added-taxes' ) )
			return false;

		return true;
	}
}
$IT_Exchange_Product_Feature_Product_Value_Added_Taxes = new IT_Exchange_Product_Feature_Product_Value_Added_Taxes();
