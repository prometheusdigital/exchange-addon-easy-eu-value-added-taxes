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
	private $current_state;

	/**
	 * set the current state.
	 *
	 * @since 1.36.0
	 *
	 * @param string $current_state
	 *
	 * @return $this
	 */
	public function set_current_state( $current_state ) {
		$this->current_state = $current_state;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function get_tax_code_for_product( IT_Exchange_Product $product ) {

		if ( ! $product->supports_feature( 'value-added-taxes' ) ) {
			return false;
		}

		if ( ! $this->current_state ) {
			return "vat:{$this->get_vat_rate( $product )}";
		}

		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

		if ( $this->current_state === $settings['vat-country'] ) {
			return "vat:{$this->get_vat_rate( $product )}";
		}

		if ( $product->get_feature( 'value-added-taxes', array( 'setting' => 'vat-moss' ) ) === 'on' ) {
			$types = $product->get_feature( 'value-added-taxes', array( 'setting' => 'vat-moss-tax-types' ) );

			if ( isset( $types[ $this->current_state ] ) ) {
				return "moss:{$this->current_state}:{$types[$this->current_state]}";
			} else {
				return "moss:{$this->current_state}:{$this->get_vat_rate( $product )}";
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
}