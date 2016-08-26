<?php
/**
 * Represents a VAT Rate.
 *
 * @since   1.8.0
 * @license GPLv2
 */

/**
 * Class ITE_EU_VAT_Rate
 */
class ITE_EU_VAT_Rate {

	const MOSS = 'moss';
	const VAT = 'vat';

	/**
	 * @var int
	 */
	private $index;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var array
	 */
	private $data;

	/**
	 * @var string
	 */
	private $country;

	/**
	 * ITE_EU_VAT_Rate constructor.
	 *
	 * @param int    $index
	 * @param string $type
	 * @param array  $data
	 * @param string $country
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $index, $type, array $data, $country = '' ) {

		if ( ! is_int( $index ) || ! is_string( $type ) || count( $data ) === 0 ) {
			throw new InvalidArgumentException();
		}

		$this->index   = $index;
		$this->type    = $type;
		$this->data    = $data;
		$this->country = $country;
	}

	/**
	 * Create a VAT Rate from a code.
	 *
	 * @since 1.8.0
	 *
	 * @param string $code
	 *
	 * @return \ITE_EU_VAT_Rate|null
	 */
	public static function from_code( $code ) {

		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );

		$parts = explode( ':', $code );

		if ( ! isset( $parts[0], $parts[1] ) ) {
			return null;
		}

		if ( $parts[0] === self::MOSS ) {

			if ( ! isset( $parts[2] ) ) {
				return null;
			}

			$country = $parts[1];
			$index   = (int) $parts[2];

			if ( ! isset( $settings['vat-moss-tax-rates'][ $country ] ) ) {
				return new self( $index, self::MOSS, $settings['tax-rates'][ $index ], $country );
			}

			if ( ! isset( $settings['vat-moss-tax-rates'][ $country ][ $index ] ) ) {
				return null;
			}

			return new self( $index, self::MOSS, $settings['vat-moss-tax-rates'][ $country ][ $index ], $country );
		} else {

			$index = (int) $parts[1];

			if ( ! isset( $settings['tax-rates'][ $index ] ) ) {
				return null;
			}

			return new self( $index, self::VAT, $settings['tax-rates'][ $index ] );
		}
	}

	/**
	 * Get the percentage rate.
	 *
	 * @since 1.8.0
	 *
	 * @return int
	 */
	public function get_rate() {
		return $this->data['rate'];
	}

	/**
	 * Whether this rate should be applied to shipping.
	 *
	 * @since 1.8.0
	 *
	 * @return bool
	 */
	public function applies_to_shipping() {
		return ! empty( $this->data['shipping'] );
	}

	/**
	 * Get the index.
	 *
	 * @since 1.8.0
	 *
	 * @return int
	 */
	public function get_index() {
		return $this->index;
	}

	/**
	 * Get the type of the rate.
	 *
	 * Either 'moss' or 'vat'.
	 *
	 * @since 1.8.0
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get the rate's label.
	 *
	 * @since 1.8.0
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->data['label'];
	}

	/**
	 * Is this the default rate.
	 *
	 * @since 1.8.0
	 *
	 * @return bool
	 */
	public function is_default() {
		return $this->data['default'] === 'checked';
	}

	/**
	 * Convert the rate to an array.
	 *
	 * @since 1.8.0
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->data;
	}

	/**
	 * @inheritDoc
	 */
	public function __toString() {

		if ( $this->type === self::MOSS ) {
			return "{$this->type}:{$this->country}:{$this->index}";
		} else {
			return "{$this->type}:{$this->index}";
		}
	}
}