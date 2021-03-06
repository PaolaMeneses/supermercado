<?php
/**
 * Customizer Setup and Custom Controls
 *
 */

/**
 * Adds the individual sections, settings, and controls to the theme customizer
 */
class wcast_partial_shipped_customizer_email {
	
	
	// Get our default values	
	public function __construct() {
		// Get our Customizer defaults
		$this->defaults = $this->wcast_generate_defaults();
						
		// Register our sample default controls
		add_action( 'customize_register', array( $this, 'wcast_register_sample_default_controls' ) );
		
		// Only proceed if this is own request.		
		if ( ! $this->is_own_customizer_request() && ! $this->is_own_preview_request() )return;				
		
		// Register our sections
		add_action( 'customize_register', array( wcast_customizer(), 'wcast_add_customizer_sections' ) );	
		
		// Remove unrelated components.
		add_filter( 'customize_loaded_components', array( wcast_customizer(), 'remove_unrelated_components' ), 99, 2 );

		// Remove unrelated sections.
		add_filter( 'customize_section_active', array( wcast_customizer(), 'remove_unrelated_sections' ), 10, 2 );	
		
		// Unhook divi front end.
		add_action( 'woomail_footer', array( wcast_customizer(), 'unhook_divi' ), 10 );

		// Unhook Flatsome js
		add_action( 'customize_preview_init', array( wcast_customizer(), 'unhook_flatsome' ), 50  );
		
		add_filter( 'customize_controls_enqueue_scripts', array( wcast_customizer(), 'enqueue_customizer_scripts' ) );				
		
		add_action( 'parse_request', array( $this, 'set_up_preview' ) );	
		
		add_action( 'customize_preview_init', array( $this, 'enqueue_preview_scripts' ) );					
	}
	
	public function enqueue_preview_scripts() {		 
		wp_enqueue_script('wcast-email-preview-scripts', wc_advanced_shipment_tracking()->plugin_dir_url() . 'assets/js/preview-scripts.js', array('jquery', 'customize-preview'), wc_advanced_shipment_tracking()->version, true);
		wp_enqueue_style('wcast-preview-styles', wc_advanced_shipment_tracking()->plugin_dir_url() . 'assets/css/preview-styles.css', array(), wc_advanced_shipment_tracking()->version  );
		// Send variables to Javascript
		$preview_id     = get_theme_mod('wcast_partial_shipped_preview_order_id');
		wp_localize_script('wcast-email-preview-scripts', 'wcast_preview', array(
			'site_title'   => $this->get_blogname(),
			'order_number' => $preview_id,			
		));
	}
	
	/**
	* Get blog name formatted for emails.
	*
	* @return string
	*/
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}
	
	/**
	 * Checks to see if we are opening our custom customizer preview
	 *
	 * @access public
	 * @return bool
	 */
	public function is_own_preview_request() {
		return isset( $_REQUEST['wcast-partial-shipped-email-customizer-preview'] ) && '1' === $_REQUEST['wcast-partial-shipped-email-customizer-preview'];
	}
	
	/**
	 * Checks to see if we are opening our custom customizer controls
	 *
	 * @access public
	 * @return bool
	 */
	public function is_own_customizer_request() {
		return isset( $_REQUEST['email'] ) && $_REQUEST['email'] === 'custom_order_status_email';
	}

	/**
	 * Get Customizer URL
	 *
	 */
	public function get_customizer_url( $email, $order_status ) {		
		return add_query_arg( array(
			'wcast-customizer' => '1',
			'email' => $email,
			'order_status' => $order_status,
			'autofocus[section]' => 'custom_order_status_email',
			'url'                  => urlencode( add_query_arg( array( 'wcast-partial-shipped-email-customizer-preview' => '1' ), home_url( '/' ) ) ),
			'return'               => urlencode( $this->get_email_settings_page_url() ),
		), admin_url( 'customize.php' ) );
	}		
	
	/**
	 * Get WooCommerce email settings page URL
	 *
	 * @access public
	 * @return string
	 */
	public function get_email_settings_page_url() {
		return admin_url( 'admin.php?page=woocommerce-advanced-shipment-tracking' );
	}
	
	/**
	 * code for initialize default value for customizer
	*/
	public function wcast_generate_defaults() {
		$customizer_defaults = array(			
			'wcast_partial_shipped_email_subject' => __( 'Your {site_title} order is now partially shipped', 'woo-advanced-shipment-tracking' ),
			'wcast_partial_shipped_email_heading' => __( 'Your Order is Partially Shipped', 'woo-advanced-shipment-tracking' ),
			'wcast_partial_shipped_email_content' => __( "Hi there. we thought you'd like to know that your recent order from {site_title} has been partially shipped.", 'woo-advanced-shipment-tracking' ),				
			'wcast_enable_partial_shipped_email'  => 'no',
		);

		return apply_filters( 'partially_shipped_customizer_defaults', $customizer_defaults );
	}

	/**
	 * Register our sample default controls
	 */
	public function wcast_register_sample_default_controls( $wp_customize ) {		
		/**
		* Load all our Customizer Custom Controls
		*/
		require_once trailingslashit( dirname(__FILE__) ) . 'custom-controls.php';						
		
		// Preview Order		
		$wp_customize->add_setting( 'wcast_email_preview_order_id',
			array(
				'default' => 'mockup',
				'transport' => 'refresh',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( new AST_Dropdown_Select_Custom_Control( $wp_customize, 'wcast_email_preview_order_id',
			array(
				'label' => __( 'Preview order', 'woo-advanced-shipment-tracking' ),
				'description' => '',
				'section' => 'custom_order_status_email',
				'input_attrs' => array(
					'placeholder' => __( 'Mockup Order', 'woo-advanced-shipment-tracking' ),
					'class' => 'preview_order_select',
				),
				'choices' => wcast_customizer()->get_order_ids(),
			)
		) );
		
		$order_status = array(
			'partially_shipped' => __( 'Partially Shipped', 'woo-advanced-shipment-tracking' ),			
		);
		$order_status = apply_filters('wcast_order_status_email_type', $order_status);
		
		// Preview Order		
		$wp_customize->add_setting( 'wcast_order_status_email_type',
			array(
				'default' => 'mockup',
				'transport' => 'postMessage',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new AST_Dropdown_Select_Custom_Control( $wp_customize, 'wcast_order_status_email_type',
			array(
				'label' => __( 'Email Type', 'woo-advanced-shipment-tracking' ),
				'description' => '',
				'section' => 'custom_order_status_email',
				'input_attrs' => array(
					'placeholder' => __( 'Select email type', 'woo-advanced-shipment-tracking' ),
					'class' => 'preview_email_type',
				),
				'choices' => $order_status,
			)
		) );
		
		// Display Shipment Provider image/thumbnail
		$wp_customize->add_setting( 'customizer_partial_shipped_order_settings_enabled',
			array(
				'default' => $this->defaults['wcast_enable_partial_shipped_email'],
				'transport' => 'postMessage',
				'type'      => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'customizer_partial_shipped_order_settings_enabled',
			array(
				'label' => __( 'Enable Partially Shipped order status email', 'woo-advanced-shipment-tracking' ),
				'description' => esc_html__( '', 'woo-advanced-shipment-tracking' ),
				'section' => 'custom_order_status_email',
				'type' => 'checkbox',
				'active_callback' => array( $this, 'active_callback' ),
			)
		);				
		
		// Header Text		
		$wp_customize->add_setting( 'woocommerce_customer_partial_shipped_order_settings[subject]',
			array(
				'default' => $this->defaults['wcast_partial_shipped_email_subject'],
				'transport' => 'postMessage',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'woocommerce_customer_partial_shipped_order_settings[subject]',
			array(
				'label' => __( 'Subject', 'woocommerce' ),
				'description' => esc_html__( 'Available variables:', 'woo-advanced-shipment-tracking' ).' {site_title}, {order_number}',
				'section' => 'custom_order_status_email',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => __( $this->defaults['wcast_partial_shipped_email_subject'], 'woo-advanced-shipment-tracking' ),
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		);
		
		// Header Text		
		$wp_customize->add_setting( 'woocommerce_customer_partial_shipped_order_settings[heading]',
			array(
				'default' => $this->defaults['wcast_partial_shipped_email_heading'],
				'transport' => 'postMessage',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'woocommerce_customer_partial_shipped_order_settings[heading]',
			array(
				'label' => __( 'Email heading', 'woocommerce' ),
				'description' => esc_html__( 'Available variables:', 'woo-advanced-shipment-tracking' ).' {site_title}, {order_number}',
				'section' => 'custom_order_status_email',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => __( $this->defaults['wcast_partial_shipped_email_heading'], 'woo-advanced-shipment-tracking' ),
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		);
		
		
		// Test of TinyMCE control
		$wp_customize->add_setting( 'woocommerce_customer_partial_shipped_order_settings[wcast_partial_shipped_email_content]',
			array(
				'default' => $this->defaults['wcast_partial_shipped_email_content'],
				'transport' => 'refresh',
				'sanitize_callback' => 'wp_kses_post',
				'type'  => 'option',
			)
		);
		$wp_customize->add_control( new AST_TinyMCE_Custom_control( $wp_customize, 'woocommerce_customer_partial_shipped_order_settings[wcast_partial_shipped_email_content]',
			array(
				'label' => __( 'Email content', 'woo-advanced-shipment-tracking' ),
				'description' => __( 'Available variables:', 'woo-advanced-shipment-tracking' ).' {site_title}, {customer_email}, {customer_first_name}, {customer_last_name}, {customer_username}, {order_number}',
				'section' => 'custom_order_status_email',
				'input_attrs' => array(
					'toolbar1' => 'bold italic bullist numlist alignleft aligncenter alignright link',
					'mediaButtons' => true,
					'placeholder' => __( $this->defaults['wcast_partial_shipped_email_content'], 'woo-advanced-shipment-tracking' ),
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		) );						
		
		$wp_customize->add_setting( 'wcast_partial_shipped_code_block',
			array(
				'default' => '',
				'transport' => 'postMessage',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( new WP_Customize_codeinfoblock_Control( $wp_customize, 'wcast_partial_shipped_code_block',
			array(
				'label' => __( 'Available variables:', 'woo-advanced-shipment-tracking' ),
				'description' => '<code>{site_title}<br>{customer_email}<br>{customer_first_name}<br>{customer_last_name}<br>{customer_company_name}<br>{customer_username}<br>{order_number}</code>',
				'section' => 'custom_order_status_email',
				'active_callback' => array( $this, 'active_callback' ),	
			)
		) );	
	}
	
	public function active_callback(){
		return ( $this->is_own_preview_request() ) ? true : false ;	
	}
		
	/**
	 * Set up preview
	 *
	 * @access public
	 * @return void
	 */
	public function set_up_preview() {
		// Make sure this is own preview request.
		if ( ! $this->is_own_preview_request() )return;
		include wc_advanced_shipment_tracking()->get_plugin_path() . '/includes/customizer/preview/partial_shipped_preview.php';exit;			
	}
	
	/**
	 * code for preview of delivered order status email
	*/
	public function preview_partial_shipped_email(){
		// Load WooCommerce emails.
		$wc_emails      = WC_Emails::instance();
		$emails         = $wc_emails->get_emails();		
		$preview_id     = get_theme_mod('wcast_email_preview_order_id');
		
		if($preview_id == '' || $preview_id == 'mockup') {
			$content = '<div style="padding: 35px 40px; background-color: white;">' . __( 'Please select order to preview.', 'woo-advanced-shipment-tracking' ) . '</div>';							
			echo $content;
			return;
		}		
		
		$order = wc_get_order( $preview_id );
		
		if(!$order){
			$content = '<div style="padding: 35px 40px; background-color: white;">' . __( 'Please select order to preview.', 'woo-advanced-shipment-tracking' ) . '</div>';							
			echo $content;
			return;
		}
		
		$email_type = 'WC_Email_Customer_Partial_Shipped_Order';
		
		if ( false === $email_type ) {
			return false;
		}
		
		// Reference email.
		if ( isset( $emails[ $email_type ] ) && is_object( $emails[ $email_type ] ) ) {
			$email = $emails[ $email_type ];
		}
		$order_status = 'partial-shipped';
		// Get an order
		$order = $this->get_wc_order_for_preview( $order_status, $preview_id );		
		
		// Make sure gateways are running in case the email needs to input content from them.
		WC()->payment_gateways();
		// Make sure shipping is running in case the email needs to input content from it.
		WC()->shipping();
			
		$email->object               = $order;
		$email->find['order-date']   = '{order_date}';
		$email->find['order-number'] = '{order_number}';
		if ( is_object( $order ) ) {
			$email->replace['order-date']   = wc_format_datetime( $email->object->get_date_created() );
			$email->replace['order-number'] = $email->object->get_order_number();
			// Other properties
			$email->recipient = $email->object->get_billing_email();
		}
		
		// Get email content and apply styles.
		$content = $email->get_content();		
		
		$content = $email->style_inline( $content );
		$content = apply_filters( 'woocommerce_mail_content', $content );
		
		echo $content;		
	}
	
	/**
	 * Get WooCommerce order for preview
	 *
	 * @access public
	 * @param string $order_status
	 * @return object
	 */
	public function get_wc_order_for_preview( $order_status = null, $order_id = null ) {
		if ( ! empty( $order_id ) && 'mockup' != $order_id ) {
			return wc_get_order( $order_id );
		} else {
			// Use mockup order

			// Instantiate order object
			$order = new WC_Order();

			// Other order properties
			$order->set_props( array(
				'id'                 => 1,
				'status'             => ( null === $order_status ? 'processing' : $order_status ),
				'billing_first_name' => 'Sherlock',
				'billing_last_name'  => 'Holmes',
				'billing_company'    => 'Detectives Ltd.',
				'billing_address_1'  => '221B Baker Street',
				'billing_city'       => 'London',
				'billing_postcode'   => 'NW1 6XE',
				'billing_country'    => 'GB',
				'billing_email'      => 'sherlock@holmes.co.uk',
				'billing_phone'      => '02079304832',
				'date_created'       => date( 'Y-m-d H:i:s' ),
				'total'              => 24.90,
			) );

			// Item #1
			$order_item = new WC_Order_Item_Product();
			$order_item->set_props( array(
				'name'     => 'A Study in Scarlet',
				'subtotal' => '9.95',
				'sku'      => 'kwd_ex_1',
			) );
			$order->add_item( $order_item );

			// Item #2
			$order_item = new WC_Order_Item_Product();
			$order_item->set_props( array(
				'name'     => 'The Hound of the Baskervilles',
				'subtotal' => '14.95',
				'sku'      => 'kwd_ex_2',
			) );
			$order->add_item( $order_item );

			// Return mockup order
			return $order;
		}

	}	
}

/**
 * Returns an instance of zorem_woocommerce_advanced_shipment_tracking.
 *
 * @since 1.6.5
 * @version 1.6.5
 *
 * @return zorem_woocommerce_advanced_shipment_tracking
*/
function ps_customizer() {
	static $instance;

	if ( ! isset( $instance ) ) {		
		$instance = new wcast_partial_shipped_customizer_email();
	}

	return $instance;
}

/**
 * Register this class globally.
 *
 * Backward compatibility.
*/
ps_customizer();

add_action( 'customize_save_customizer_partial_shipped_order_settings_enabled', 'woocommerce_customer_partial_shipped_order_settings_fun', 100, 1 ); 

/**
 * Update Delivered order email enable/disable
 *
 */
function woocommerce_customer_partial_shipped_order_settings_fun($data){
	$post_values = json_decode( wp_unslash( $_POST['customized'] ), true );
	$partial_shipped_order_settings = get_option( 'woocommerce_customer_partial_shipped_order_settings');
	
	if(isset($post_values['customizer_partial_shipped_order_settings_enabled']) && ($post_values['customizer_partial_shipped_order_settings_enabled'] == 1)){
		$partial_shipped_order_settings['enabled'] = 'yes';
	} else{
		$partial_shipped_order_settings['enabled'] = 'no';
	}		
	update_option( 'woocommerce_customer_partial_shipped_order_settings',$partial_shipped_order_settings);	
}