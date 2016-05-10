<?php
/**
 * iThemes Exchange Easy EU Value Added Taxes Add-on
 * Value Added Taxes Theme Class
 * @package exchange-addon-value-added-sales-taxes
 * @since 1.0.0
*/

class IT_Theme_API_EU_Value_Added_Taxes implements IT_Theme_API {
	
	/**
	 * API context
	 * @var string $_context
	 * @since 1.0.0
	*/
	private $_context = 'eu-value-added-taxes';

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
	function __construct() {
		$this->_address = it_exchange_get_cart_shipping_address();
		//We only care about the province!
		if ( empty( $this->_address['state'] ) ) 
			$this->_address = it_exchange_get_cart_billing_address();
	}

	/**
	 * Deprecated Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function IT_Theme_API_EU_Value_Added_Taxes() {
		self::__construct();
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
		
		$settings  = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
		$memberstates = it_exchange_get_data_set( 'eu-member-states' );
		$result = '';
		$taxes = 0;
		
		$defaults      = array(
			'before'       => '',
			'after'        => '',
			'format_price' => true,
		);
		$options      = ITUtility::merge_defaults( $options, $defaults );

		$result .= $options['before'];	
		if ( it_exchange_easy_eu_value_added_taxes_setup_session() ) {
			$tax_session = it_exchange_get_session_data( 'addon_easy_eu_value_added_taxes' );
				
			$result .= '<div class="it-exchange-table-inner-row">';
			$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
			do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_taxes_label' );
			$result .= '<div class="it-exchange-table-column-inner">';
			$result .= __( 'Tax', 'LION' );
			$result .= '</div>';
			do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_taxes_label' );
			$result .= '</div>';
					
			$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
			do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_taxes_value' );
			$result .= '<div class="it-exchange-table-column-inner">';
			$result .= '<div class="it-exchange-cart-vat-details">';
			if ( !empty( $tax_session['vat_country'] ) && !empty( $tax_session['vat_number'] ) )
				$result .= $tax_session['vat_country'] .'-'. $tax_session['vat_number'];
				
			$result .= '</div>';
			$result .= '<div class="it-exchange-add-edit-vat-number-div">';
			$result .= '<a href="#" id="it-exchange-add-edit-vat-number">' . sprintf( __( '%s EU VAT Number', 'LION' ), ( !empty( $tax_session['vat_number'] ) ? __( 'Edit', 'LION' ) : __( 'Add', 'LION' ) ) ) . '</a>';
			$result .= '</div>';
			$result .= '</div>';
			
			$result .= '</div>';
			$result .= '</div>';
						
			if ( empty( $tax_session['summary_only'] ) ) {
				foreach ( $tax_session['taxes'] as $tax ) {
					if ( !empty( $tax['total'] ) ) {
						$result .= '<div class="it-exchange-table-inner-row">';
						$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
						do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_taxes_label' );
						$result .= '<div class="it-exchange-table-column-inner">';
						$result .=  sprintf( __( '%s %s (%s%%)', 'LION' ), ( empty( $tax['country'] ) ? '' : $memberstates[$tax['country']] ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
						$result .= '</div>';
						do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_taxes_label' );
						$result .= '</div>';
						
						$tax_total = $tax['total'];
						if ( $options['format_price'] )
							$tax_total = it_exchange_format_price( $tax_total );
							
						$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
						do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_taxes_value' );
						$result .= '<div class="it-exchange-table-column-inner">';
						$result .= $tax_total;
						$result .= '</div>';
						do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_taxes_value' );
						$result .= '</div>';
						$result .= '</div>';
					}
				}
				if ( !empty( $tax_session['vat_moss_taxes'] ) ) {
					foreach ( $tax_session['vat_moss_taxes'] as $tax ) {
						if ( !empty( $tax['total'] ) ) {
							$result .= '<div class="it-exchange-table-inner-row">';
							$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
							do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_vat_moss_taxes_label' );
							$result .= '<div class="it-exchange-table-column-inner">';
							$result .=  sprintf( __( '%s %s (%s%%)', 'LION' ), ( empty( $tax['country'] ) ? '' : $memberstates[$tax['country']] ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
							$result .= '</div>';
							do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_vat_moss_taxes_label' );
							$result .= '</div>';
							
							$tax_total = $tax['total'];
							if ( $options['format_price'] )
								$tax_total = it_exchange_format_price( $tax_total );
								
							$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
							do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_vat_moss_taxes_value' );
							$result .= '<div class="it-exchange-table-column-inner">';
							$result .= $tax_total;
							$result .= '</div>';
							do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_vat_moss_taxes_value' );
							$result .= '</div>';
							$result .= '</div>';
						}
					}
				}
			}
		} else {
			if ( $taxes = it_exchange_easy_eu_value_added_taxes_get_cart_taxes() ) {
				foreach ( $taxes as $tax ) {
					if ( !empty( $tax['total'] ) ) {
						$result .= '<div class="it-exchange-table-inner-row">';
						$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
						do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_taxes_label' );
						$result .= '<div class="it-exchange-table-column-inner">';
						$result .=  sprintf( __( '%s %s (%s%%)', 'LION' ), ( empty( $tax['country'] ) || ! isset( $memberstates[ $tax['country'] ] ) ? '' : $memberstates[$tax['country']] ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
						$result .= '</div>';
						do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_taxes_label' );
						$result .= '</div>';
						
						$tax_total = $tax['total'];
						if ( $options['format_price'] )
							$tax_total = it_exchange_format_price( $tax_total );
							
						$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
						do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_taxes_value' );
						$result .= '<div class="it-exchange-table-column-inner">';
						$result .= $tax_total;
						$result .= '</div>';
						do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_taxes_value' );
						$result .= '</div>';
						$result .= '</div>';
					}
				}
				if ( !empty( $tax_session['vat_moss_taxes'] ) ) {
					foreach ( $tax_session['vat_moss_taxes'] as $tax ) {
						if ( !empty( $tax['total'] ) ) {
							$result .= '<div class="it-exchange-table-inner-row">';
							$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
							do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_vat_moss_taxes_label' );
							$result .= '<div class="it-exchange-table-column-inner">';
							$result .=  sprintf( __( '%s %s (%s%%)', 'LION' ), ( empty( $tax['country'] ) ? '' : $memberstates[$tax['country']] ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
							$result .= '</div>';
							do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_vat_moss_taxes_label' );
							$result .= '</div>';
							
							$tax_total = $tax['total'];
							if ( $options['format_price'] )
								$tax_total = it_exchange_format_price( $tax_total );
								
							$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
							do_action( 'it_exchange_content_checkout_before_easy_eu_valued_added_vat_moss_taxes_value' );
							$result .= '<div class="it-exchange-table-column-inner">';
							$result .= $tax_total;
							$result .= '</div>';
							do_action( 'it_exchange_content_checkout_after_easy_eu_valued_added_vat_moss_taxes_value' );
							$result .= '</div>';
							$result .= '</div>';
						}
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
		$memberstates = it_exchange_get_data_set( 'eu-member-states' );

		$defaults      = array(
			'before'       => '',
			'after'        => '',
			'format_price' => true,
		);
		$options      = ITUtility::merge_defaults( $options, $defaults );
			
	    if ( !empty( $GLOBALS['it_exchange']['transaction'] ) ) {
	        $transaction = $GLOBALS['it_exchange']['transaction'];
	        $tax_items = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes', true );
	        $vat_moss_tax_items = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_vat_moss_taxes', true );
	        $summary_only = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes_summary_only', true );
	    }
			
		if ( !$summary_only && !empty( $tax_items ) ) {
			$result .= $options['before'];	
			$result .= '<div class="it-exchange-table-inner-row">';
			$result .= '<div class="it-exchange-confirmation-totals-title it-exchange-table-column">';
			do_action( 'it_exchange_content_comfirmation_before_easy_eu_valued_added_taxes_label' );
			$result .= '    <div class="it-exchange-table-column-inner">';
			$result .=      __( 'Tax', 'LION' );
			$result .= '    </div>';
			do_action( 'it_exchange_content_comfirmation_after_easy_eu_valued_added_taxes_label' );
			$result .= '</div>';
					
			$result .= '<div class="it-exchange-confirmation-totals-amount it-exchange-table-column">';
			do_action( 'it_exchange_content_comfirmation_before_easy_eu_valued_added_taxes_value' );
			$result .= '    <div class="it-exchange-table-column-inner">&nbsp;</div>';
			
			do_action( 'it_exchange_content_comfirmation_after_easy_eu_valued_added_taxes_value' );
			$result .= '</div>';
			$result .= '</div>';
			
			foreach ( $tax_items as $tax ) {
				if ( !empty( $tax['total'] ) ) {
					$tax_total = $tax['total'];
					if ( $options['format_price'] )
						$tax_total = it_exchange_format_price( $tax_total );

					$result .= '<div class="it-exchange-table-inner-row">';
					$result .= '<div class="it-exchange-confirmation-totals-title it-exchange-table-column">';
					do_action( 'it_exchange_content_comfirmation_before_easy_eu_valued_added_taxes_label' );
					$result .= '<div class="it-exchange-table-column-inner">';
					$result .=  sprintf( __( '%s %s (%s%%)', 'LION' ), ( empty( $tax['country'] ) ? '' : $memberstates[$tax['country']] ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
					$result .= '</div>';
					do_action( 'it_exchange_content_comfirmation_after_easy_eu_valued_added_taxes_label' );
					$result .= '</div>';
					
					$result .= '<div class="it-exchange-confirmation-totals-amount it-exchange-table-column">';
					do_action( 'it_exchange_content_comfirmation_before_easy_eu_valued_added_taxes_value' );
					$result .= '<div class="it-exchange-table-column-inner">';
					$result .= $tax_total;
					$result .= '</div>';
					do_action( 'it_exchange_content_comfirmation_after_easy_eu_valued_added_taxes_value' );
					$result .= '</div>';
					$result .= '</div>';
				}
			}
			if ( !empty( $vat_moss_tax_items ) ) {
				foreach ( $vat_moss_tax_items as $tax ) {
					if ( !empty( $tax['total'] ) ) {
						$result .= '<div class="it-exchange-table-inner-row">';
						$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
						do_action( 'it_exchange_content_comfirmation_before_easy_eu_valued_added_vat_moss_taxes_label' );
						$result .= '<div class="it-exchange-table-column-inner">';
						$result .=  sprintf( __( '%s %s (%s%%)', 'LION' ), ( empty( $tax['country'] ) ? '' : $memberstates[$tax['country']] ), $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
						$result .= '</div>';
						do_action( 'it_exchange_content_comfirmation_after_easy_eu_valued_added_vat_moss_taxes_label' );
						$result .= '</div>';
						
						$tax_total = $tax['total'];
						if ( $options['format_price'] )
							$tax_total = it_exchange_format_price( $tax_total );
							
						$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
						do_action( 'it_exchange_content_comfirmation_before_easy_eu_valued_added_vat_moss_taxes_value' );
						$result .= '<div class="it-exchange-table-column-inner">';
						$result .= $tax_total;
						$result .= '</div>';
						do_action( 'it_exchange_content_comfirmation_after_easy_eu_valued_added_vat_moss_taxes_value' );
						$result .= '</div>';
						$result .= '</div>';
					}
				}
			}

			$result .= $options['after'];
		}
		
		return $result;
	}
		
	function vat_summary( $options=array() ) {
    	$general_settings = it_exchange_get_option( 'settings_general' );
		$settings  = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
		$memberstates = it_exchange_get_data_set( 'eu-member-states' );
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
	        $tax_items = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes', true );
	        $vat_moss_tax_items = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_vat_moss_taxes', true );
	        $customer_country = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes_customer_vat_country', true );
	        $customer_vat = get_post_meta( $transaction->ID, '_it_exchange_easy_eu_value_added_taxes_customer_vat_number', true );
	    }
		if ( !empty( $tax_items ) ) {							
			$result .= $options['before'];
			$result .= '<h3>' . $options['label'] . '</h3>';
			$result .= '<p>' . sprintf( __( 'Merchant VAT Number: %s-%s', 'LION' ), $general_settings['company-base-country'], $settings['vat-number'] ) . '</p>';
			if ( !empty( $customer_vat ) )
				$result .= '<p>' . sprintf( __( 'Customer VAT Number: %s-%s', 'LION' ), $customer_country, $customer_vat ) . '</p>';
			
			$result .= '<div class="vat-summary-table it-exchange-table">';
			$result .= '<div class="it-exchange-table-row vat-summary-table-row vat-summary-heading-row">';
			
			$result .= '<div class="vat-label-heading it-exchange-table-column">';
			$result .= '<div class="it-exchange-table-column-inner">' . __( 'VAT Type', 'LION' ) . '</div>';
			$result .= '</div>';
			$result .= '<div class="vat-net-taxable-amount-heading it-exchange-table-column">';
			$result .= '<div class="it-exchange-table-column-inner">' . __( 'Net Taxable Amount', 'LION' ) . '</div>';
			$result .= '</div>';
			
			$result .= '</div>';
			
			if ( !empty( $settings['tax-rates'] ) && !empty( $tax_items ) ) {
				foreach( $tax_items as $tax ) {
					$net = empty( $tax['taxable_amount'] ) ? 0 : $tax['taxable_amount'];

					if ( empty( $net ) ) {
						continue;
					}

					$result .= '<div class="it-exchange-table-row">';
					$result .= '<div class="vat-label it-exchange-table-column">';
					$result .= '<div class="it-exchange-table-column-inner">';
					$result .= sprintf( __( '%s %s (%s%%)', 'LION' ), $memberstates[$tax['country']], $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
					$result .= '</div>';
					$result .= '</div>';
			
					if ( $options['format_price'] )
						$net = it_exchange_format_price( $net );
						
					$result .= '<div class="vat-net-taxable-amount it-exchange-table-column">';
					$result .= '<div class="it-exchange-table-column-inner">';
					$result .= $net;
					$result .= '</div>';
					$result .= '</div>';
					
					$result .= '</div>';
				}
			}
			
			if ( !empty( $vat_moss_tax_items ) ) {
				foreach ( $vat_moss_tax_items as $tax ) {
					$net = empty( $tax['taxable_amount'] ) ? 0 : $tax['taxable_amount'];

					if ( empty( $net ) ) {
						continue;
					}

					$result .= '<div class="it-exchange-table-inner-row">';
					$result .= '<div class="it-exchange-cart-totals-title it-exchange-table-column">';
					do_action( 'it_exchange_content_comfirmation_before_easy_eu_valued_added_vat_moss_taxes_label' );
					$result .= '<div class="it-exchange-table-column-inner">';
					$result .=  sprintf( __( '%s %s (%s%%)', 'LION' ), $memberstates[$tax['country']], $tax['tax-rate']['label'], $tax['tax-rate']['rate'] );
					$result .= '</div>';
					do_action( 'it_exchange_content_comfirmation_after_easy_eu_valued_added_vat_moss_taxes_label' );
					$result .= '</div>';
					
					if ( $options['format_price'] )
						$net = it_exchange_format_price( $net );
						
					$result .= '<div class="it-exchange-cart-totals-amount it-exchange-table-column">';
					do_action( 'it_exchange_content_comfirmation_before_easy_eu_valued_added_vat_moss_taxes_value' );
					$result .= '<div class="it-exchange-table-column-inner">';
					$result .= $net;
					$result .= '</div>';
					do_action( 'it_exchange_content_comfirmation_after_easy_eu_valued_added_vat_moss_taxes_value' );
					$result .= '</div>';
					$result .= '</div>';
				}
			}
			
			$result .= '</div>';
			$result .= $options['after'];
		}
		
		return $result;

	}
}
