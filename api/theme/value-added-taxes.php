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
	 * Current customer VAT Number
	 * @var string $_vat_number
	 * @since 1.4.0
	*/
	private $_vat_number = '';
	
	/**
	 * Maps api tags to methods
	 * @var array $_tag_map
	 * @since 1.0.0
	*/
	public $_tag_map = array(
		'taxes'             => 'taxes',
		'confirmationtaxes' => 'confirmation_taxes',
		'vatsummary'        => 'vat_summary',
		'vatcountry'        => 'vat_country',
		'vatnumber'         => 'vat_number',
		'submit'            => 'submit',
		'cancel'            => 'cancel',
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
	//	$this->_vat_number = it_exchange_easy_value_added_taxes_get_cart_vat_number();
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
				
			$result .= '<div class="it-exchange-table-inner-row">';
			$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
			do_action( 'it_exchange_content_checkout_before_easy_valued_added_taxes_label' );
			$result .= '<div class="it-exchange-table-column-inner">';
			$result .= __( 'Tax', 'LION' );
			$result .= '</div>';
			do_action( 'it_exchange_content_checkout_after_easy_valued_added_taxes_label' );
			$result .= '</div>';
					
			$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
			do_action( 'it_exchange_content_checkout_before_easy_valued_added_taxes_value' );
			$result .= '<div class="it-exchange-table-column-inner">';
			$result .= '<div class="it-exchange-cart-vat-details">';
			if ( !empty( $tax_session['vat_country'] ) && !empty( $tax_session['vat_number'] ) )
				$result .= $tax_session['vat_country'] .'-'. $tax_session['vat_number'];
				
			$result .= '</div>';
			$result .= '<a href="#" id="it-exchange-add-edit-vat-number">' . sprintf( __( '%s EU VAT Number', 'LION' ), ( !empty( $tax_session['vat_number'] ) ? __( 'Edit', 'LION' ) : __( 'Add', 'LION' ) ) ) . '</a>';
			$result .= '</div>';
			
			$result .= '</div>';
			$result .= '</div>';
			
			
			if ( !$tax_session['summary_only'] ) {
				foreach ( $tax_session['taxes'] as $tax ) {
					if ( !empty( $tax['total'] ) ) {
						$result .= '<div class="it-exchange-table-inner-row">';
						$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
						do_action( 'it_exchange_content_checkout_before_easy_valued_added_taxes_label' );
						$result .= '<div class="it-exchange-table-column-inner">';
						$result .=  sprintf( __( '%s (%s%%)', 'LION' ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
						$result .= '</div>';
						do_action( 'it_exchange_content_checkout_after_easy_valued_added_taxes_label' );
						$result .= '</div>';
						
						$tax_total = $tax['total'];
						if ( $options['format_price'] )
							$tax_total = it_exchange_format_price( $tax_total );
							
						$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
						do_action( 'it_exchange_content_checkout_before_easy_valued_added_taxes_value' );
						$result .= '<div class="it-exchange-table-column-inner">';
						$result .= $tax_total;
						$result .= '</div>';
						do_action( 'it_exchange_content_checkout_after_easy_valued_added_taxes_value' );
						$result .= '</div>';
						$result .= '</div>';
					}
				}
			}
			
			
			
			
			
			
		}
		$result .= $options['after'];	
		
		return $result;
					
	}
	
	function confirmation_taxes( $options=array() ) {
		$result = '';
		$tax_items = false;
		
		$defaults      = array(
			'before'       => '',
			'after'        => '',
			'format_price' => true,
		);
		$options      = ITUtility::merge_defaults( $options, $defaults );
			
	    if ( !empty( $GLOBALS['it_exchange']['transaction'] ) ) {
	        $transaction = $GLOBALS['it_exchange']['transaction'];
	        $tax_items = get_post_meta( $transaction->ID, '_it_exchange_easy_value_added_taxes', true );
	        $summary_only = get_post_meta( $transaction->ID, '_it_exchange_easy_value_added_summary_only', true );
	    }
			
		if ( !$summary_only && !empty( $tax_items ) ) {
			$result .= $options['before'];	
			$result .= '<div class="it-exchange-confirmation-totals-title it-exchange-table-column">';
			do_action( 'it_exchange_content_comfirmation_before_easy_valued_added_taxes_label' );
			$result .= '    <div class="it-exchange-table-column-inner">';
			$result .=      __( 'Tax', 'LION' );
			$result .= '    </div>';
			foreach ( $tax_items as $tax ) {
				if ( !empty( $tax['total'] ) ) {
					$result .= '<div class="it-exchange-table-column-inner">';
					$result .=  sprintf( __( '%s (%s%%)', 'LION' ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
					$result .= '</div>';
				}
			}
			do_action( 'it_exchange_content_comfirmation_after_easy_valued_added_taxes_label' );
			$result .= '</div>';
					
			$result .= '<div class="it-exchange-confirmation-totals-amount it-exchange-table-column">';
			do_action( 'it_exchange_content_comfirmation_before_easy_valued_added_taxes_value' );
			$result .= '    <div class="it-exchange-table-column-inner">&nbsp;</div>';
			foreach ( $tax_items as $tax ) {
				if ( !empty( $tax['total'] ) ) {
					$tax_total = $tax['total'];
					if ( $options['format_price'] )
						$tax_total = it_exchange_format_price( $tax_total );
					
					$result .= '<div class="it-exchange-table-column-inner">';
					$result .= $tax_total;
					$result .= '</div>';
				}
			}
			do_action( 'it_exchange_content_comfirmation_after_easy_valued_added_taxes_value' );
			$result .= '</div>';
			$result .= $options['after'];
		}
		
		return $result;
	}
		
	function vat_summary( $options=array() ) {
    	$general_settings = it_exchange_get_option( 'settings_general' );
		$settings  = it_exchange_get_option( 'addon_easy_value_added_taxes' );
		$result = '';
		
		$defaults      = array(
			'before'       => '',
			'after'        => '',
			'label'        => __( 'VAT Summary', 'LION' ),
			'format_price' => true,
		);
		$options      = ITUtility::merge_defaults( $options, $defaults );
		
	    if ( !empty( $GLOBALS['it_exchange']['transaction'] ) ) {
	        $transaction = $GLOBALS['it_exchange']['transaction'];
	        $tax_items = get_post_meta( $transaction->ID, '_it_exchange_easy_value_added_taxes', true );
	        $customer_country = get_post_meta( $transaction->ID, '_it_exchange_easy_value_added_customer_vat_country', true );
	        $customer_vat = get_post_meta( $transaction->ID, '_it_exchange_easy_value_added_customer_vat_number', true );
	    }
											
		$result .= $options['before'];
		$result .= '<h3>' . $options['label'] . '</h3>';
		$result .= '<p>' . sprintf( __( 'Merchant VAT Number: %s-%s', 'LION' ), $general_settings['company-base-country'], $settings['vat-number'] ) . '</p>';
		if ( !empty( $customer_vat ) )
			$result .= '<p>' . sprintf( __( 'Customer VAT Number: %s-%s', 'LION' ), $customer_country, $customer_vat ) . '</p>';
		
		$result .= '<div class="vat-summary-table">';
		$result .= '<div class="vat-label-heading">' . __( 'VAT Type', 'LION' ) . '</div>';
		$result .= '<div class="vat-net-taxable-amount-heading">' . __( 'Net Taxable Amount', 'LION' ) . '</div>';
		if ( !empty( $settings['tax-rates'] ) && !empty( $tax_items ) ) {
			foreach( $tax_items as $tax ) {
				$net = empty( $tax['taxable_amount'] ) ? 0 : $tax['taxable_amount'];
				$result .= '<div class="vat-label">';
				$result .= sprintf( __( '%s (%s%%)', 'LION' ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
				$result .= '</div>';
		
				if ( $options['format_price'] )
					$net = it_exchange_format_price( $net );
				$result .= '<div class="vat-net-taxable-amount">';
				$result .= $net;
				$result .= '</div>';
			}
		}
		$result .= '</div>';
		$result .= $options['after'];
		
		return $result;

	}
}
