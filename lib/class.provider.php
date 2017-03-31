<?php
/**
 * Tax Provider.
 *
 * @since   1.36
 * @license GPLv2
 */

/**
 * Class ITE_EU_VAT_Tax_Provider
 */
class ITE_EU_VAT_Tax_Provider extends ITE_Tax_Provider {

	/**
	 * @var string
	 */
	private $current_country;

	/**
	 * set the current state.
	 *
	 * @since 2.0.0
	 *
	 * @param string|callable $country
	 *
	 * @return $this
	 */
	public function set_current_country( $country ) {
		$this->current_country = $country;

		return $this;
	}

	/**
	 * Get the current country.
	 * 
	 * @since 2.0.0
	 * 
	 * @return string
	 */
	public function get_current_country() {
		if ( is_callable( $this->current_country ) ) {
			return call_user_func( $this->current_country );
		}
		
		return $this->current_country;
	}

	/**
	 * @inheritDoc
	 */
	public function get_tax_code_for_product( IT_Exchange_Product $product ) {

		if ( ! $product->supports_feature( 'value-added-taxes' ) ) {
			return false;
		}
		
		$current_country = $this->get_current_country();

		if ( ! $current_country ) {
			return "vat:{$this->get_vat_rate( $product )}";
		}

		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

		if ( $current_country === $settings['vat-country'] ) {
			return "vat:{$this->get_vat_rate( $product )}";
		}

		if ( $product->get_feature( 'value-added-taxes', array( 'setting' => 'vat-moss' ) ) === 'on' ) {
			$types = $product->get_feature( 'value-added-taxes', array( 'setting' => 'vat-moss-tax-types' ) );

			if ( isset( $types[ $current_country ] ) ) {
				return "moss:{$current_country}:{$types[$current_country]}";
			} else {
				return "moss:{$current_country}:{$this->get_vat_rate( $product )}";
			}
		}

		return "vat:{$this->get_vat_rate( $product )}";
	}

	/**
	 * @inheritDoc
	 */
	public function get_tax_code_for_item( ITE_Line_Item $item ) {

		if ( $item instanceof ITE_Cart_Product ) {
			return $this->get_tax_code_for_product( $item->get_product() );
		}

		if ( $item instanceof ITE_Shipping_Line_Item ) {
			$rate = it_exchange_easy_eu_vat_get_shipping_tax_rate( $item->get_method_slug() );

			if ( ! $rate ) {
				return '';
			}

			return "vat:{$rate['index']}";
		}

		return '';
	}

	/**
	 * Get the VAT rate for a product.
	 *
	 * @since 2.0.0
	 *
	 * @param \IT_Exchange_Product $product
	 *
	 * @return int
	 */
	private function get_vat_rate( IT_Exchange_Product $product ) {
		$index = $product->get_feature( 'value-added-taxes', array( 'setting' => 'type' ) );

		if ( $index === 'default' || $index === false || $index === '' ) {
			$index = $this->get_default();
		}

		return $index;
	}

	/**
	 * Get the default rate index.
	 *
	 * @since 2.0.0
	 *
	 * @return int
	 */
	private function get_default() {

		$rate = it_exchange_easy_eu_vat_get_default_tax_rate();

		if ( $rate ) {
			return $rate['index'];
		}

		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function is_product_tax_exempt( IT_Exchange_Product $product ) {
		return (bool) $product->get_feature( 'value-added-taxes', array( 'setting' => 'exempt' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_class() {
		return 'ITE_EU_VAT_Line_Item';
	}

	/**
	 * @inheritDoc
	 */
	public function add_taxes_to( ITE_Taxable_Line_Item $item, ITE_Cart $cart ) {

		$country  = it_exchange_easy_eu_vat_get_country( $cart );
		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

		if ( $cart->has_meta( 'eu-vat-number' ) && $settings['vat-country'] !== $country ) {
			return;
		}

		$tax = $this->make_tax_for( $item, $cart );

		if ( ! $tax ) {
			return;
		}

		$item->add_tax( $tax );
		$cart->get_repository()->save( $item );
	}

	/**
	 * Make the tax item for a given taxable line item.
	 *
	 * @since 2.0.0
	 *
	 * @param \ITE_Taxable_Line_Item $item
	 * @param \ITE_Cart              $cart
	 *
	 * @return \ITE_EU_VAT_Line_Item|null
	 */
	public function make_tax_for( ITE_Taxable_Line_Item $item, ITE_Cart $cart ) {

		$provider = new ITE_EU_VAT_Tax_Provider();
		$country  = it_exchange_easy_eu_vat_get_country( $cart );

		if ( ! $country || ! it_exchange_easy_eu_vat_valid_country_for_tax( $country ) ) {
			return null;
		}

		$provider->set_current_country( $country );
		$code = $item->get_tax_code( $provider );

		if ( ! $code ) {
			return null;
		}

		$rate = ITE_EU_VAT_Rate::from_code( $code );

		return ITE_EU_VAT_Line_Item::create( $rate, $item );
	}

	/**
	 * @inheritDoc
	 */
	public function is_restricted_to_location() {
		return new ITE_Simple_Zone( array(
			'country' => array_keys( it_exchange_get_data_set( 'eu-member-states' ) )
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function inherit_tax_code_from_aggregate() { return false; }
}