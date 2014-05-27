<?php
/**
 * This will control email messages with any product types that register email message support.
 * By default, it registers a metabox on the product's add/edit screen and provides HTML / data for the frontend.
 *
 * @since 1.0.0 
 * @package exchange-addon-easy-canadian-sales-taxes
*/


class IT_Exchange_Product_Feature_Product_Canadian_Tax_Exempt_Status {

	/**
	 * Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Product_Feature_Product_Canadian_Tax_Exempt_Status() {
		if ( is_admin() ) {
			add_action( 'load-post-new.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'load-post.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
		}
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_product_types' ) );
		add_action( 'it_exchange_update_product_feature_canadian-tax-exempt-status', array( $this, 'save_feature' ), 9, 2 );
		add_filter( 'it_exchange_get_product_feature_canadian-tax-exempt-status', array( $this, 'get_feature' ), 9, 2 );
		add_filter( 'it_exchange_product_has_feature_canadian-tax-exempt-status', array( $this, 'product_has_feature') , 9, 2 );
		add_filter( 'it_exchange_product_supports_feature_canadian-tax-exempt-status', array( $this, 'product_supports_feature') , 9, 2 );
	}

	/**
	 * Register the product feature and add it to enabled product-type addons
	 *
	 * @since 1.0.0
	*/
	function add_feature_support_to_product_types() {
		// Register the product feature
		$slug        = 'canadian-tax-exempt-status';
		$description = __( "Set the Product's Taxability Information Class", 'LION' );
		it_exchange_register_product_feature( $slug, $description );

		// Add it to all enabled product-type addons
		$products = it_exchange_get_enabled_addons( array( 'category' => 'product-type' ) );
		foreach( $products as $key => $params ) {
			it_exchange_add_feature_support_to_product_type( 'canadian-tax-exempt-status', $params['slug'] );
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
			if ( !empty( $product_type ) &&  it_exchange_product_type_supports_feature( $product_type, 'canadian-tax-exempt-status' ) )
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
		add_meta_box( 'it-exchange-product-canadian-tax-exempt-status', __( 'Canadian Tax Status', 'LION' ), array( $this, 'print_metabox' ), 'it_exchange_prod', 'normal' );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function print_metabox( $product ) {
		// Set description		
		$tax_status = it_exchange_get_product_feature( $product->ID, 'canadian-tax-exempt-status' );

		?>
		
		<p>
            <label for="easy-us-sales-taxes-canadian-tax-exempt-status"><?php _e( 'Tax Exempt?', 'LION' ) ?></label>
			
			<input type="checkbox" name="it-exchange-add-on-easy-us-sales-taxes-canadian-tax-exempt-status" id="canadian-tax-exempt-status" <?php checked( $tax_status ); ?> />
        </p>
		<?php
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
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'canadian-tax-exempt-status' ) )
			return;

		// Get new value from post
		$new_value = empty( $_POST['it-exchange-add-on-easy-us-sales-taxes-canadian-tax-exempt-status'] ) ? false : true;

		// Save new value
		it_exchange_update_product_feature( $product_id, 'canadian-tax-exempt-status', $new_value );

	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.0.0
	 * @param integer $product_id the product id
	 * @param mixed $new_value the new value
	 * @return bolean
	*/
	function save_feature( $product_id, $new_value ) {
		update_post_meta( $product_id, '_it-exchange-add-on-easy-us-sales-taxes-canadian-tax-exempt-status', $new_value );
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
	function get_feature( $existing, $product_id ) {
		if ( $tax_status = get_post_meta( $product_id, '_it-exchange-add-on-easy-us-sales-taxes-canadian-tax-exempt-status', true ) ) {
			return $tax_status;
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
	function product_has_feature( $result, $product_id ) {
		// Does this product type support this feature?
		if ( false === $this->product_supports_feature( false, $product_id ) )
			return false;

		// If it does support, does it have it?
		return (boolean) $this->get_feature( false, $product_id );
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
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'canadian-tax-exempt-status' ) )
			return false;

		return true;
	}
}
$IT_Exchange_Product_Feature_Product_Canadian_Tax_Exempt_Status = new IT_Exchange_Product_Feature_Product_Canadian_Tax_Exempt_Status();
