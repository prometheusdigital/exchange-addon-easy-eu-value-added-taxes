<?php
/**
 * iThemes Exchange Easy EU Value Added Taxes Add-on
 * @package exchange-addon-easy-eu-value-added-taxes
 * @since 1.0.0
*/

/**
 * Call back for settings page
 *
 * This is set in options array when registering the add-on and called from it_exchange_enable_addon()
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_easy_eu_value_added_taxes_settings_callback() {
	$IT_Exchange_Easy_Value_Added_Taxes_Add_On = new IT_Exchange_Easy_Value_Added_Taxes_Add_On();
	$IT_Exchange_Easy_Value_Added_Taxes_Add_On->print_settings_page();
}

/**
 * Sets the default options for Easy EU Value Added Taxes settings
 *
 * @since 1.0.0
 * @return array settings
*/
function it_exchange_easy_eu_value_added_taxes_default_settings( $defaults ) {
	$general_settings = it_exchange_get_option( 'settings_general' );

	$defaults = array(
		'vat-country' => $general_settings['company-base-country'],
		'vat-number' => '',
		'vat-number-verified' => false,
		'tax-rates' => array(
			array(
				'label'    => __( 'Zero Rate', 'LION' ),
				'rate'     => 0,
				'shipping' => false,
				'default'  => 'unchecked',
			),
			array(
				'label'    => __( 'Reduced Rate', 'LION' ),
				'rate'     => 6,
				'shipping' => false,
				'default'  => 'unchecked',
			),
			array(
				'label'    => __( 'Standard Rate', 'LION' ),
				'rate'     => 21,
				'shipping' => false,
				'default'  => 'checked',
			),
		),
		'price-includes-vat' => true,
	);
	return $defaults;
}
add_filter( 'it_storage_get_defaults_exchange_addon_easy_eu_value_added_taxes', 'it_exchange_easy_eu_value_added_taxes_default_settings' );

class IT_Exchange_Easy_Value_Added_Taxes_Add_On {

	/**
	 * @var boolean $_is_admin true or false
	 * @since 1.0.0
	*/
	var $_is_admin;

	/**
	 * @var string $_current_page Current $_GET['page'] value
	 * @since 1.0.0
	*/
	var $_current_page;

	/**
	 * @var string $_current_add_on Current $_GET['add-on-settings'] value
	 * @since 1.0.0
	*/
	var $_current_add_on;

	/**
	 * @var string $status_message will be displayed if not empty
	 * @since 1.0.0
	*/
	var $status_message;

	/**
	 * @var string $error_message will be displayed if not empty
	 * @since 1.0.0
	*/
	var $error_message;

	/**
 	 * Class constructor
	 *
	 * Sets up the class.
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Easy_Value_Added_Taxes_Add_On() {
		$this->_is_admin       = is_admin();
		$this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
		$this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

		if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'easy-eu-value-added-taxes' == $this->_current_add_on ) {
			add_action( 'it_exchange_save_add_on_settings_easy_eu_value_added_taxes', array( $this, 'save_settings' ) );
			do_action( 'it_exchange_save_add_on_settings_easy_eu_value_added_taxes' );
		}
	}

	function print_settings_page() {
		global $new_values;
		$settings = it_exchange_get_option( 'addon_easy_eu_value_added_taxes', true );
	
		$form_values  = empty( $this->error_message ) ? $settings : $new_values;
		$form_options = array(
			'id'      => apply_filters( 'it_exchange_add_on_easy_eu_value_added_taxes', 'it-exchange-add-on-easy-eu-value-added-taxes-settings' ),
			'enctype' => apply_filters( 'it_exchange_add_on_easy_eu_value_added_taxes_settings_form_enctype', false ),
			'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=easy-eu-value-added-taxes',
		);
		$form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-easy-eu-value-added-taxes' ) );

		if ( ! empty ( $this->status_message ) )
			ITUtility::show_status_message( $this->status_message );
		if ( ! empty( $this->error_message ) )
			ITUtility::show_error_message( $this->error_message );

		?>
		<div class="wrap">
			<?php screen_icon( 'it-exchange' ); ?>
			<h2><?php _e( 'Easy EU Value Added Taxes Settings', 'LION' ); ?></h2>

			<?php do_action( 'it_exchange_easy_eu_value_added_taxes_settings_page_top' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

			<?php $form->start_form( $form_options, 'it-exchange-easy-eu-value-added-taxes-settings' ); ?>
				<?php do_action( 'it_exchange_easy_eu_value_added_taxes_settings_form_top' ); ?>
				<?php $this->get_easy_eu_value_added_taxes_form_table( $form, $form_values ); ?>
				<?php do_action( 'it_exchange_easy_eu_value_added_taxes_settings_form_bottom' ); ?>
				<p class="submit">
					<?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'LION' ), 'class' => 'button button-primary button-large' ) ); ?>
				</p>
			<?php $form->end_form(); ?>
			<?php do_action( 'it_exchange_easy_eu_value_added_taxes_settings_page_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</div>
		<?php
	}

	function get_easy_eu_value_added_taxes_form_table( $form, $settings = array() ) {
		if ( !empty( $settings ) )
			foreach ( $settings as $key => $var )
				$form->set_option( $key, $var );
		?>
		
        <div class="it-exchange-addon-settings it-exchange-easy-eu-value-added-taxes-addon-settings">
            <h4>
            	<?php _e( 'Current Tax Rates and Settings', 'LION' ) ?> 
            </h4>
            
            <div id="value-added-tax-number">
				<p>
					<label for="vat-number">
						<?php _e( 'VAT Number', 'LION' ) ?> <span class="tip" title="<?php _e( 'Select your Country and enter your VAT Number', 'LION' ); ?>">i</span>
					</label>
				</p>

				<?php
				$memberstates = it_exchange_get_data_set( 'eu-member-states' );
				$form->add_drop_down( 'vat-country', $memberstates );
				echo '<br />';
				echo '<input id="vat-number-country-code" type="text" size="2" value="' . $settings['vat-country'] . '" disabled="disabled" readonly="readonly" />';
				$form->add_text_box( 'vat-number' );

				if ( !empty( $settings['vat-number-verified'] ) )
					$hidden_class = '';
				else
					$hidden_class = 'hidden';

				echo '<img src="' . ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/images/check.png" class="check ' . $hidden_class . '" id="it-exchange-vat-number-verified" title="' . __( 'VAT Number Verified', 'LION' ) . '" height="15" >';

				$form->add_hidden( 'tax-rates' ); 
				?>
			</div>
			
			<p>
				<label for="easy-eu-value-added-taxes-tax-rates"><?php _e( 'Tax Rates', 'LION' ) ?> <span class="tip" title="<?php _e( 'Add a Tax Label and a Tax Rate, select whether or not to apply tax to shipping and select a default Tax Rate.', 'LION' ); ?>">i</span> </label>
			</p>
			<div id="value-added-tax-rate-table">
				<?php
				$headings = array(
					__( 'Tax Label', 'LION' ), __( 'Tax Rate %', 'LION' ), __( 'Apply to Shipping?', 'LION' ), __( 'Default?', 'LION' )
				);
				?>
				<div class="heading-row block-row">
					<?php $column = 0; ?>
					<?php foreach ( (array) $headings as $heading ) : ?>
					<?php $column++ ?>
					<div class="heading-column block-column block-column-<?php echo $column; ?>">
					<p class="heading"><?php echo $heading; ?></p>
					</div>
					<?php endforeach; ?>
					<div class="heading-column block-column block-column-delete"></div>
				</div>
				<?php
				$last_key = 0;
				//Alpha Sort
				$tax_rates = $settings['tax-rates'];
				if ( !empty( $tax_rates ) ) {
					ksort( $tax_rates );
					foreach( $tax_rates as $key => $rate ) {
						echo it_exchange_easy_eu_value_added_taxes_get_tax_row_settings( $key, $rate );
						$last_key = $key;
					}
				}
				?>
			</div>
			<script type="text/javascript" charset="utf-8">
	            var it_exchange_easy_eu_value_added_taxes_addon_iteration = <?php echo $last_key; ?>;
	        </script>

			<p class="add-new">
				<?php $form->add_button( 'new-tax-rate', array( 'value' => __( 'Add New Tax Rate', 'LION' ), 'class' => 'button button-secondary button-large' ) ); ?>
			</p>
			
			<div>
				<p>
					<label for="price-includes-vat"><?php _e( 'Add VAT on Product Page?', 'LION' ) ?> <span class="tip" title="<?php _e( 'Displays the product price with VAT included automatically.', 'LION' ); ?>">i</span> </label>
					<?php $form->add_check_box( 'price-includes-vat' ); ?>
				</p>
			</div>
            
		</div>
		<?php
	}

	/**
	 * Save settings
	 *
	 * @since 1.0.0
	 * @return void
	*/
    function save_settings() {
    	global $new_values; //We set this as global here to modify it in the error check
        $defaults = it_exchange_get_option( 'addon_easy_eu_value_added_taxes' );
        $new_values = wp_parse_args( ITForm::get_post_data(), $defaults );
                
        // Check nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-easy-eu-value-added-taxes-settings' ) ) {
            $this->error_message = __( 'Error. Please try again', 'LION' );
            return;
        }

        $errors = apply_filters( 'it_exchange_add_on_easy_eu_value_added_taxes_validate_settings', $this->get_form_errors( $new_values ), $new_values );
                                
        if ( ! $errors && it_exchange_save_option( 'addon_easy_eu_value_added_taxes', $new_values ) ) {
            ITUtility::show_status_message( __( 'Settings saved.', 'LION' ) );
        } else if ( $errors ) {
            $errors = implode( '<br />', $errors );
            $this->error_message = $errors;
        } else {
            $this->status_message = __( 'Settings not saved.', 'LION' );
        }
    }

    /**
     * Validates for values
     *
     * Returns string of errors if anything is invalid
     *
     * @since 0.1.0
     * @return void
    */
    public function get_form_errors( $values ) {
    	global $new_values;
    	$errors = array();
    	$default_set = false;
    
    	if ( empty( $values['vat-country'] ) )
            $errors[] = __( 'Missing VAT Country.', 'LION' );
    
    	if ( empty( $values['vat-number'] ) )
            $errors[] = __( 'Missing VAT Number.', 'LION' );
        
        if ( empty( $errors ) ) {
	        if ( true === $return = it_exchange_easy_eu_value_added_taxes_addon_verify_vat( $values['vat-country'], $values['vat-number'] ) ) {
		        $new_values['vat-number-verified'] = true;
	        } else {
		        $new_values['vat-number-verified'] = false;
	            $errors[] = $return;
	        }
        }
    
        foreach( $values['tax-rates'] as $tax_rate ) {
        	if ( empty( $tax_rate['label'] ) ) {
                $errors[] = __( 'Missing or Invalid VAT Label.', 'LION' );
	        	continue;
        	}
        	
        	if ( !isset( $tax_rate['rate'] ) || !is_numeric( $tax_rate['rate'] ) ) {
                $errors[] = sprintf( __( 'Missing or Invalid Tax Rate for %s.', 'LION' ), $tax_rate['label'] );
	        	continue;
        	}
        	
        	if ( 'checked' === $tax_rate['default'] )
        		$default_set = true;
        }
        
        if ( !$default_set )
            $errors[] = __( 'You must set a default tax rate.', 'LION' );

        return $errors;
    }
}
