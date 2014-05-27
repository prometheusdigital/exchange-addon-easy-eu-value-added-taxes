<?php
/**
 * iThemes Exchange Easy Value Added Taxes Add-on
 * Value Added Taxes Theme Class
 * @package exchange-addon-value-added-sales-taxes
 * @since 1.0.0
*/

class IT_Theme_API_Value_Added_Taxes implements IT_Theme_API {
	
	/**
	 * API context
	 * @var string $_context
	 * @since 1.0.0
	*/
	private $_context = 'value-added-taxes';

	/**
	 * Current customer Address
	 * @var string $_address
	 * @since 1.4.0
	*/
	private $_address = '';
	
	/**
	 * Maps api tags to methods
	 * @var array $_tag_map
	 * @since 1.0.0
	*/
	public $_tag_map = array(
		'taxes'             => 'taxes',
		'confirmationtaxes' => 'confirmation_taxes',
	);

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function IT_Theme_API_Value_Added_Taxes() {
		$this->_address = it_exchange_get_cart_shipping_address();
		//We only care about the province!
		if ( empty( $this->_address['state'] ) ) 
			$this->_address = it_exchange_get_cart_billing_address();
	}

	/**
	 * Returns the context. Also helps to confirm we are an iThemes Exchange theme API class
	 *
	 * @since 1.0.0
	 * 
	 * @return string
	*/
	function get_api_context() {
		return $this->_context;
	}

	/**
	 * @since 1.0.0
	 * @return string
	*/
	function taxes( $options=array() ) {
					
		$settings  = it_exchange_get_option( 'addon_easy_value_added_taxes' );
		$result = '';
		$taxes = 0;
		
		$defaults      = array(
			'before'       => '',
			'after'        => '',
			'format_price' => true,
		);
		$options      = ITUtility::merge_defaults( $options, $defaults );
		

		$result .= $options['before'];
		if ( it_exchange_easy_value_added_taxes_setup_session() ) {
			$tax_session = it_exchange_get_session_data( 'addon_easy_value_added_taxes' );
			$result .= '<ul class="value-added-taxes">';
			$total_tax = 0;
			foreach ( $tax_session['taxes'] as $tax ) {
				if ( $tax['shipping'] ) {
					$taxes = $tax_session['cart_subtotal_w_shipping'] * ( $tax['rate'] / 100 );
				} else {
					$taxes = $tax_session['cart_subtotal'] * ( $tax['rate'] / 100 );
				}
				$total_tax += $taxes;
				if ( !empty( $taxes ) ) {
					if ( $options['format_price'] )
						$taxes = it_exchange_format_price( $taxes );
					$result .= '<li>' . $taxes . ' (' . $tax['type'] . ')</li>';
				}
			}
			
			if ( empty( $total_tax ) ) {
				if ( $options['format_price'] )
					$total_tax = it_exchange_format_price( $total_tax );
				$result .= '<li>' . $total_tax . '</li>';
			}
			$result .= '</ul>';
		} else {		
			if ( $options['format_price'] )
				$taxes = it_exchange_format_price( $taxes );
			$result .= $taxes;
		}
		$result .= $options['after'];
		
		return $result;
					
	}
	
	function confirmation_taxes( $options=array() ) {
		$result = '';
		
		$defaults      = array(
			'before'       => '',
			'after'        => '',
			'format_price' => true,
		);
		$options      = ITUtility::merge_defaults( $options, $defaults );
			
	    if ( !empty( $GLOBALS['it_exchange']['transaction'] ) ) {
	        $transaction = $GLOBALS['it_exchange']['transaction'];
	        $tax_items = get_post_meta( $transaction->ID, '_it_exchange_easy_value_added_taxes', true );
	    }
						
		$result .= $options['before'];
		$result .= '<ul class="value-added-taxes">';
		foreach ( $tax_items as $tax ) {
			if ( !empty( $tax['total'] ) ) {
				if ( $options['format_price'] )
					$tax['total'] = it_exchange_format_price( $tax['total'] );
				$result .= '<li>' . $tax['total'] . ' (' . $tax['type'] . ')</li>';
			}
		}
		$result .= '</ul>';
		$result .= $options['after'];
		
		return $result;
	}
}
