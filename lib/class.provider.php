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
	 * @since 1.36.0
	 *
	 * @param string $country
	 *
	 * @return $this
	 */
	public function set_current_country( $country ) {
		$this->current_country = $country;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function get_tax_code_for_product( IT_Exchange_Product $product ) {

		if ( ! $product->supports_feature( 'value-added-taxes' ) ) {
			return false;
		}

		if ( ! $this->current_country ) {
			return "vat:{$this->get_vat_rate( $product )}";
		}

		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

		if ( $this->current_country === $settings['vat-country'] ) {
			return "vat:{$this->get_vat_rate( $product )}";
		}

		if ( $product->get_feature( 'value-added-taxes', array( 'setting' => 'vat-moss' ) ) === 'on' ) {
			$types = $product->get_feature( 'value-added-taxes', array( 'setting' => 'vat-moss-tax-types' ) );

			if ( isset( $types[ $this->current_country ] ) ) {
				return "moss:{$this->current_country}:{$types[$this->current_country]}";
			} else {
				return "moss:{$this->current_country}:{$this->get_vat_rate( $product )}";
			}
		}

		return "vat:{$this->get_vat_rate( $product )}";
	}

	/**
	 * Get the VAT rate for a product.
	 *
	 * @since 1.36.0
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
	 * @since 1.8.0
	 *
	 * @return int
	 */
	private function get_default() {

		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

		foreach ( $settings['tax-rates'] as $i => $rate ) {
			if ( $rate['default'] === 'checked' ) {
				return $i;
			}
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

		$provider = new ITE_EU_VAT_Tax_Provider();
		$country  = it_exchange_easy_eu_vat_get_country( $cart );

		if ( ! $country || ! it_exchange_easy_eu_vat_valid_country_for_tax( $country ) ) {
			return;
		}

		if ( $cart->is_current() ) {

			$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

			if ( $cart->has_meta( 'eu-vat-number' ) && $settings['vat-country'] !== $country ) {
				return;
			}
		}

		$provider->set_current_country( $country );
		$code = $item->get_tax_code( $provider );

		if ( ! $code ) {
			return;
		}

		$rate = ITE_EU_VAT_Rate::from_code( $code );
		$tax  = ITE_EU_VAT_Line_Item::create( $rate );

		$cart->add_item( $tax );
	}

	/**
	 * @inheritDoc
	 */
	public function is_restricted_to_location() {
		return new ITE_Simple_Zone( array(
			'country' => array_keys( it_exchange_get_data_set( 'eu-member-states' ) )
		) );
	}
}