<?php
/**
 * Line Item.
 *
 * @since   1.36.0
 * @license GPLv2
 */

/**
 * Class ITE_EU_VAT_Line_Item
 */
class ITE_EU_VAT_Line_Item implements ITE_Tax_Line_Item, ITE_Cart_Aware {

	/** @var ITE_Parameter_Bag */
	private $bag;

	/** @var ITE_Taxable_Line_Item */
	private $taxable;

	/** @var string */
	private $id;

	/** @var ITE_Parameter_bag */
	private $frozen;

	/** @var ITE_Cart */
	private $cart;

	/** @var ITE_EU_VAT_Rate */
	private $vat_rate;

	/**
	 * @inheritDoc
	 */
	public function __construct( $id, \ITE_Parameter_Bag $bag, \ITE_Parameter_Bag $frozen ) {
		$this->id       = $id;
		$this->bag      = $bag;
		$this->frozen   = $frozen;
		$this->cart     = it_exchange_get_current_cart(); // easier than a null-check later.
		$this->vat_rate = ITE_EU_VAT_Rate::from_code( $this->get_param( 'code' ) );
	}

	/**
	 * Create a new VAT Tax Item.
	 *
	 * @since 1.8.0
	 *
	 * @param \ITE_EU_VAT_Rate            $rate
	 * @param \ITE_Taxable_Line_Item|null $item
	 *
	 * @return \ITE_EU_VAT_Line_Item
	 */
	public static function create( ITE_EU_VAT_Rate $rate, ITE_Taxable_Line_Item $item = null ) {

		$id = md5( uniqid( "VAT", true ) . $rate );

		$self = new self( $id, new ITE_Array_Parameter_Bag( array(
			'code' => (string) $rate
		) ), new ITE_Array_Parameter_Bag() );

		if ( $item ) {
			$self->set_aggregate( $item );
		}

		return $self;
	}

	/**
	 * @inheritDoc
	 */
	public function get_rate() {

		if ( $this->has_param( 'rate' ) ) {
			return $this->get_param( 'rate' );
		}

		$rate = $this->get_vat_rate();

		return $rate ? $rate->get_rate() : 0;
	}

	/**
	 * @inheritDoc
	 */
	public function applies_to( ITE_Taxable_Line_Item $item ) {

		if ( ( $rate = ITE_EU_VAT_Rate::from_code( $item->get_tax_code( $this->get_provider() ) ) ) === null ) {
			return false;
		}

		if ( $item instanceof ITE_Shipping_Line_Item && ! $rate->applies_to_shipping() ) {
			return false;
		}

		if ( $item->is_tax_exempt( $this->get_provider() ) ) {
			return false;
		}

		foreach ( $item->get_taxes() as $tax ) {
			if ( $tax instanceof self ) {
				return false; // Duplicate taxes are not allowed
			}
		}

		return $item->get_tax_code( $this->get_provider() ) === (string) $this->get_vat_rate();
	}

	/**
	 * @inheritDoc
	 */
	public function create_scoped_for_taxable( ITE_Taxable_Line_Item $item ) {
		return self::create( $this->vat_rate, $item );
	}

	/**
	 * @inheritdoc
	 */
	public function get_provider() {
		$provider = new ITE_EU_VAT_Tax_Provider();

		$address = $this->cart->get_shipping_address() ?: $this->cart->get_billing_address();

		$provider->set_current_state( $address['country'] );

		return $provider;
	}

	/**
	 * Get the VAT rate.
	 *
	 * @since 1.8.0
	 *
	 * @return \ITE_EU_VAT_Rate|null
	 */
	public function get_vat_rate() { return $this->vat_rate; }

	/**
	 * @inheritDoc
	 */
	public function set_aggregate( ITE_Aggregate_Line_Item $aggregate ) { $this->taxable = $aggregate; }

	/**
	 * @inheritDoc
	 */
	public function get_aggregate() { return $this->taxable; }

	/**
	 * @inheritDoc
	 */
	public function set_cart( ITE_Cart $cart ) { $this->cart = $cart; }

	/**
	 * @inheritDoc
	 */
	public function get_id() { return $this->id; }

	/**
	 * @inheritDoc
	 */
	public function get_name() {

		if ( $this->frozen->has_param( 'name' ) ) {
			return $this->frozen->get_param( 'name' );
		}

		$rate  = $this->get_vat_rate();
		$label = $rate ? $rate->get_label() : __( 'VAT', 'LION' );
		$label .= " {$this->get_rate()}%";

		return $label;
	}

	/**
	 * @inheritDoc
	 */
	public function get_description() {
		return $this->frozen->has_param( 'description' ) ? $this->frozen->get_param( 'description' ) : '';
	}

	/**
	 * @inheritDoc
	 */
	public function get_quantity() { return 1; }

	/**
	 * @inheritDoc
	 */
	public function get_amount() {

		if ( $this->frozen->has_param( 'amount' ) ) {
			return $this->frozen->get_param( 'amount' );
		}

		if ( $this->get_aggregate() ) {
			return $this->get_aggregate()->get_taxable_amount() * ( $this->get_rate() / 100 );
		} else {
			return 0;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_total() {

		if ( $this->frozen->has_param( 'total' ) ) {
			return $this->frozen->get_param( 'total' );
		}

		return $this->get_amount() * $this->get_quantity();
	}

	/**
	 * @inheritDoc
	 */
	public function get_type( $label = false ) { return $label ? __( 'Tax', 'LION' ) : 'tax'; }

	/**
	 * @inheritDoc
	 */
	public function is_summary_only() {
		return $this->frozen->has_param( 'summary_only' ) ? $this->frozen->get_param( 'summary_only' ) : true;
	}

	/**
	 * @inheritDoc
	 */
	public function persist( ITE_Line_Item_Repository $repository ) {

		if ( $repository instanceof ITE_Line_Item_Transaction_Repository ) {
			$this->set_param( 'rate', $this->get_rate() );
		}

		return $repository->save( $this );
	}

	/**
	 * @inheritDoc
	 */
	public function get_params() { return $this->bag->get_params(); }

	/**
	 * @inheritDoc
	 */
	public function has_param( $param ) { return $this->bag->has_param( $param ); }

	/**
	 * @inheritDoc
	 */
	public function get_param( $param ) { return $this->bag->get_param( $param ); }

	/**
	 * @inheritDoc
	 */
	public function set_param( $param, $value ) { return $this->bag->set_param( $param, $value ); }

	/**
	 * @inheritDoc
	 */
	public function remove_param( $param ) { return $this->bag->remove_param( $param ); }
}