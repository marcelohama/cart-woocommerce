<?php

// This include Mercado Pago library SDK
require_once "sdk/lib/mercadopago.php";

// Extending from WooCommerce Payment Gateway class.
// This extension implements the custom checkout.
class WC_WooMercadoPagoCustom_Gateway extends WC_Payment_Gateway {
	
	// This array stores each banner image, depending on the country it belongs to or on
	// the type of checkout we use.
	private $banners_mercadopago_credit = array(
		"MLA" => 'MLA/credit_card.png',
        "MLB" => 'MLB/credit_card.png',
        "MCO" => 'MCO/credit_card.png',
        "MLC" => 'MLC/credit_card.png',
        "MLV" => 'MLV/credit_card.png',
        "MLM" => 'MLM/credit_card.png'
        // TODO: Peru rollout
    );
    
    // Sponsor ID array by country
    private $sponsor_id = array(
    	"MLA" => '208682286',
    	"MLB" => '208686191',
    	"MCO" => '208687643',
    	"MLC" => '208690789',
    	"MLV" => '208692735',
    	"MLM" => '208692380'
    	// TODO: Peru rollout
	);
    	
	// Required inherited method from WC_Payment_Gateway class: __construct.
	// Please check:
	//    [https://docs.woothemes.com/wc-apidocs/class-WC_Payment_Gateway.html]
	// for documentation and further information.
	public function __construct() {
	
		// These fields are declared because we use them dinamically in our gateway class.
		$this->domain = get_site_url() . '/index.php';
		$this->site_id = null;
		$this->isTestUser = false;
		$this->store_categories_id = array();
    	$this->store_categories_description = array();
    	
		// Within your constructor, you should define the following variables.
		$this->id = 'woocommerce-mercadopago-custom-module';
		$this->method_title = __( 'Mercado Pago - Custom Checkout', 'woocommerce-mercadopago-module' );
		$this->method_description = '<img width="200" height="52" src="' .
			plugins_url( 'images/mplogo.png', plugin_dir_path( __FILE__ ) ) . '"><br><br>' . '<strong>' .
			wordwrap( __( 'This module enables WooCommerce to use Mercado Pago as payment method for purchases made in your virtual store.', 'woocommerce-mercadopago-module' ), 80, "\n" ) .
			'</strong>';
		
		// These fields are used in our Mercado Pago Module configuration page.
		$this->public_key = $this->get_option( 'public_key' );
		$this->access_token = $this->get_option( 'access_token' );
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->custom_ticket = $this->get_option( 'custom_ticket' );
		$this->category_id = $this->get_option( 'category_id' );
		$this->invoice_prefix = $this->get_option( 'invoice_prefix', 'WC-' );
		$this->sandbox = $this->get_option( 'sandbox', false );
		$this->debug = $this->get_option( 'debug' );
		
		// Render our configuration page and init/load fields.
		$this->init_form_fields();
		$this->init_settings();
		
		// Hook actions for WordPress.
		add_action( // Used by IPN to receive IPN incomings.
			'woocommerce_api_wc_woomercadopagocustom_gateway',
			array($this, 'check_ipn_response')
		);
		add_action( // Used by IPN to process valid incomings.
			'valid_mercadopagocustom_ipn_request',
			array($this, 'successful_request')
		);
		add_action( // Used by WordPress to render the custom checkout page.
			'woocommerce_receipt_' . $this->id,
			array( $this, 'receipt_page' )
		);
		add_action( // Used to fix CSS in some older WordPress/WooCommerce versions.
			'wp_head',
			array( $this, 'css' )
		);
		add_action( // Used in settings page to hook "save settings" action.
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);
		add_action( // Scripts for custom checkout
			'wp_enqueue_scripts',
			array( $this, 'customCheckoutScripts' )
		);
		
		// Verify if public_key or client_secret is empty.
		if ( empty( $this->public_key ) || empty( $this->access_token ) ) {
			add_action( 'admin_notices', array( $this, 'credentialsMissingMessage' ) );
		}
		
		// Verify if currency is supported.
		if ( !$this->isSupportedCurrency() ) {
			add_action( 'admin_notices', array( $this, 'currencyNotSupportedMessage' ) );
		}

		// Logging and debug.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = WC_MercadoPago_Module::woocommerce_instance()->logger();
			}
		}
		
	}
	
	// Required inherited method from WC_Payment_Gateway class: init_form_fields.
	// Initialise Gateway settings form fields with a customized page.
	public function init_form_fields() {
		
		$api_secret_locale = sprintf(
			'<a href="https://www.mercadopago.com/mla/account/credentials?type=custom" target="_blank">%s</a>, ' .
			'<a href="https://www.mercadopago.com/mlb/account/credentials?type=custom" target="_blank">%s</a>, ' .
			'<a href="https://www.mercadopago.com/mlc/account/credentials?type=custom" target="_blank">%s</a>, ' .
			'<a href="https://www.mercadopago.com/mco/account/credentials?type=custom" target="_blank">%s</a>, ' .
			// TODO: Peru rollout
			'<a href="https://www.mercadopago.com/mlm/account/credentials?type=custom" target="_blank">%s</a> %s ' .
			'<a href="https://www.mercadopago.com/mlv/account/credentials?type=custom" target="_blank">%s</a>',
			__( 'Argentine', 'woocommerce-mercadopago-module' ),
			__( 'Brazil', 'woocommerce-mercadopago-module' ),
			__( 'Chile', 'woocommerce-mercadopago-module' ),
			__( 'Colombia', 'woocommerce-mercadopago-module' ),
			__( 'Mexico', 'woocommerce-mercadopago-module' ),
			// TODO: __( 'Peru', 'woocommerce-mercadopago-module' ),
			__( 'or', 'woocommerce-mercadopago-module' ),
			__( 'Venezuela', 'woocommerce-mercadopago-module' )
		);
		
		// Trigger API to get payment methods and site_id, also validates public_key/access_token.
		if ( $this->validateCredentials() ) {
			try {
				$mp = new MP( $this->access_token );
				$get_request = $mp->get( "/users/me?access_token=" . $this->access_token );
				$this->isTestUser = in_array( 'test_user', $get_request[ 'response' ][ 'tags' ] );
				$this->site_id = $get_request[ 'response' ][ 'site_id' ];
				$this->credentials_message = '<img width="12" height="12" src="' .
					plugins_url( 'images/check.png', plugin_dir_path( __FILE__ ) ) . '">' .
					' ' . __( 'Your credentials are <strong>valid</strong> for', 'woocommerce-mercadopago-module' ) .
					': ' . $this->getCountryName( $this->site_id ) . ' <img width="18.6" height="12" src="' .
					plugins_url( 'images/' . $this->site_id . '/' . $this->site_id . '.png', plugin_dir_path( __FILE__ ) ) . '"> ';
			} catch ( MercadoPagoException $e ) {
				$this->credentials_message = '<img width="12" height="12" src="' .
					plugins_url( 'images/error.png', plugin_dir_path( __FILE__ ) ) . '">' .
					' ' . __( 'Your credentials are <strong>not valid</strong>!', 'woocommerce-mercadopago-module' );
			}
		} else {
			$this->credentials_message = '<img width="12" height="12" src="' .
				plugins_url( 'images/error.png', plugin_dir_path( __FILE__ ) ) . '">' .
				' ' . __( 'Your credentials are <strong>not valid</strong>!', 'woocommerce-mercadopago-module' );
		}
		
		// Fills categoy selector. We do not need credentials to make this call.
		$categories = MPRestClient::get( array( "uri" => "/item_categories" ) );
		foreach ( $categories[ "response" ] as $category ) {
			array_push( $this->store_categories_id, str_replace( "_", " ", $category[ 'id' ] ) );
			array_push( $this->store_categories_description, str_replace( "_", " ", $category[ 'description' ] ) );
		}
		
		// This array draws each UI (text, selector, checkbox, label, etc).
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce-mercadopago-module' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Custom Checkout', 'woocommerce-mercadopago-module' ),
				'default' => 'yes'
			),
			'credentials_title' => array(
				'title' => __( 'Mercado Pago Credentials', 'woocommerce-mercadopago-module' ),
				'type' => 'title',
				'description' => sprintf( '%s', $this->credentials_message ) . '<br>' . sprintf( __( 'You can obtain your credentials for', 'woocommerce-mercadopago-module' ) . ' %s.', $api_secret_locale )
			),
			'public_key' => array(
				'title' => 'Public key',
				'type' => 'text',
				'description' => __( 'Insert your Mercado Pago Public key.', 'woocommerce-mercadopago-module' ),
				'default' => '',
				'required' => true
			),
			'access_token' => array(
				'title' => 'Access token',
				'type' => 'text',
				'description' => __( 'Insert your Mercado Pago Access token.', 'woocommerce-mercadopago-module' ),
				'default' => '',
				'required' => true
			),
			'ipn_url' => array(
				'title' => __( 'Instant Payment Notification (IPN) URL', 'woocommerce-mercadopago-module' ),
				'type' => 'title',
				'description' => sprintf( __( 'Your IPN URL to receive instant payment notifications is', 'woocommerce-mercadopago-module' ) . '<br>%s', '<code>' . $this->domain . '/woocommerce-mercadopago-module/?wc-api=WC_WooMercadoPagoCustom_Gateway' . '</code>.' )
			),
			'checkout_options_title' => array(
				'title' => __( 'Checkout Options', 'woocommerce-mercadopago-module' ),
				'type' => 'title',
				'description' => ''
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce-mercadopago-module' ),
				'type' => 'text',
				'description' => __( 'Title shown to the client in the checkout.', 'woocommerce-mercadopago-module' ),
				'default' => __( 'Mercado Pago', 'woocommerce-mercadopago-module' )
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce-mercadopago-module' ),
				'type' => 'textarea',
				'description' => __( 'Description shown to the client in the checkout.', 'woocommerce-mercadopago-module' ),
				'default' => __( 'Pay with Mercado Pago', 'woocommerce-mercadopago-module' )
			),
			'custom_ticket' => array(
				'title'   => __( 'Ticket', 'woocommerce-mercadopago-module' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Ticket for Custom Checkout', 'woocommerce-mercadopago-module' ),
				'default' => 'yes',
				'description' => __( 'Let your customer pay you with tickets', 'woocommerce-mercadopago-module' )
			),
			'category_id' => array(
				'title' => __( 'Store Category', 'woocommerce-mercadopago-module' ),
				'type' => 'select',
				'description' => __( 'Define which type of products your store sells.', 'woocommerce-mercadopago-module' ),
				'options' => $this->store_categories_id
			),
			'invoice_prefix' => array(
				'title' => __( 'Store Identificator', 'woocommerce-mercadopago-module' ),
				'type' => 'text',
				'description' => __( 'Please, inform a prefix to your store.', 'woocommerce-mercadopago-module' ) . ' ' . __( 'If you use your Mercado Pago account on multiple stores you should make sure that this prefix is unique as Mercado Pago will not allow orders with same identificators.', 'woocommerce-mercadopago-module' ),
				'default' => 'WC-'
			),
			'testing' => array(
				'title' => __( 'Test and Debug Options', 'woocommerce-mercadopago-module' ),
				'type' => 'title',
				'description' => ''
			),
			'sandbox' => array(
				'title' => __( 'Mercado Pago Sandbox', 'woocommerce-mercadopago-module' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Mercado Pago Sandbox', 'woocommerce-mercadopago-module' ),
				'default' => 'no',
				'description' => __( 'This option allows you to test payments inside a sandbox environment.', 'woocommerce-mercadopago-module' ),
			),
			'debug' => array(
				'title' => __( 'Debug and Log', 'woocommerce-mercadopago-module' ),
				'type' => 'checkbox',
				'label' => __( 'Enable log', 'woocommerce-mercadopago-module' ),
				'default' => 'no',
				'description' => sprintf( __( 'Register event logs of Mercado Pago, such as API requests, in the file', 'woocommerce-mercadopago-module' ) .
					' %s.', $this->buildLogPathString() . '.<br>' . __( 'File location: ', 'woocommerce-mercadopago-module' ) .
					'<code>wordpress/wp-content/uploads/wc-logs/woocommerce-mercadopago-module-' . sanitize_file_name( wp_hash( 'woocommerce-mercadopago-module' ) ) . '.log</code>')
			)
		);
		
	}
	
	public function admin_options() {
		$this->validate_settings_fields();
		if ( count( $this->errors ) > 0 ) {
			$this->display_errors();
			return false;
		} else {
			echo wpautop( $this->method_description );
			?>
				<p><a href="https://wordpress.org/support/view/plugin-reviews/woo-mercado-pago-module?filter=5#postform" target="_blank" class="button button-primary">
					<?php esc_html_e( sprintf( __( 'Please, rate us %s on WordPress.org and give your feedback to help improve this module!', 'woocommerce-mercadopago-module' ), '&#9733;&#9733;&#9733;&#9733;&#9733;' ) ); ?>
				</a></p>
				<table class="form-table">
					<?php $this->generate_settings_html(); ?>
				</table>
			<?php
			return true;
		}
	}
	
	/*
	 * ========================================================================
	 * CHECKOUT BUSINESS RULES
	 * ========================================================================
	 */
	 
	public function customCheckoutScripts() {
		if ( is_checkout() && $this->is_available() ) {
			if ( !get_query_var( 'order-received' ) ) {
				wp_enqueue_style(
					'woocommerce-mercadopago-style', plugins_url(
						'assets/css/custom_checkout_mercadopago.css',
						plugin_dir_path( __FILE__ ) ) );
				wp_enqueue_script(
					'woocommerce-mercadopago-v1',
					'https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js' );
				/*wp_localize_script(
					'woocommerce-checkout-font-awesome',
					'wc_mercadopago_params',
					array(
						'session_id'         => $session_id,
						'interest_free'      => __( 'interest free', 'woocommerce-pagseguro' ),
						'invalid_card'       => __( 'Invalid credit card number.', 'woocommerce-pagseguro' ),
						'invalid_expiry'     => __( 'Invalid expiry date, please use the MM / YYYY date format.', 'woocommerce-pagseguro' ),
						'expired_date'       => __( 'Please check the expiry date and use a valid format as MM / YYYY.', 'woocommerce-pagseguro' ),
						'general_error'      => __( 'Unable to process the data from your credit card on the PagSeguro, please try again or contact us for assistance.', 'woocommerce-pagseguro' ),
						'empty_installments' => __( 'Select a number of installments.', 'woocommerce-pagseguro' ),
					)
				);*/
			}
		}
	}
	
	public function payment_fields() {
		$amount = $this->get_order_total();
		wc_get_template(
			'credit-card/payment-form.php',
			array(
				'public_key' => $this->public_key,
				'site_id' => $this->site_id,
				'images_path' => plugins_url( 'images/', plugin_dir_path( __FILE__ ) ),
				'banner_path' => plugins_url( 'images/' .
					$this->banners_mercadopago_credit[ $this->site_id ], plugin_dir_path( __FILE__ ) ),
				'amount' => $amount
			),
			'woocommerce/mercadopago/',
			WC_WooMercadoPago_Module::getTemplatesPath()
		);
	}
	
	// This function is called after we clock on [place_order] button, and each field is passed to this
	// function through $_POST variable.
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		// we have got parameters from checkout page, now its time to charge the card
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, $this->id .
				': @[process_payment] - Received [$_POST] from customer front-end page: ' .
				json_encode( $_POST, JSON_PRETTY_PRINT ) );
		}
		if ( isset( $_POST[ 'mercadopago_custom' ][ 'amount' ] ) &&
			 isset( $_POST[ 'mercadopago_custom' ][ 'token' ] ) &&
			 isset( $_POST[ 'mercadopago_custom' ][ 'installments' ] ) &&
			 isset( $_POST[ 'mercadopago_custom' ][ 'paymentMethodId' ] ) ) {
			$post = $_POST;
			$response = $this->createUrl( $order, $post );
	        if ( array_key_exists( 'status', $response ) ) {
	            switch ( $response[ 'status' ] ) {
	            	case 'approved':
	            		$order->add_order_note(
							'Mercado Pago: ' .
							__( 'Payment approved.', 'woocommerce-mercadopago-module' )
						);
	                case 'pending':
	                	// order approved/pending, we just redirect to the thankyou page
	                    return array(
							'result' => 'success',
							'redirect' => $order->get_checkout_order_received_url()
						);
						break;
	                case 'in_process':
	                	// for pending, we don't know if the purchase will be made, so we must inform this status
	                	$order->add_order_note(
							'Mercado Pago: ' .
							__( 'Your payment is under review. In less than 1h, you should be notified by email.',
								'woocommerce-mercadopago-module' )
						);
						return array(
							'result' => 'success',
							'redirect' => $order->get_checkout_payment_url( true )
						);
						break;
	                case 'rejected':
	                	// if rejected is received, the order will not proceed until another payment try,
	                	// so we must inform this status
	                	$order->add_order_note(
							'Mercado Pago: ' .
							__( 'Your payment was refused. You can try again.',
								'woocommerce-mercadopago-module' ) . '<br>' . $response[ 'status_detail' ]
						);
						return array(
							'result' => 'success',
							'redirect' => $order->get_checkout_payment_url( true )
						);
						break;
					case 'cancelled':
					case 'in_mediation':
					case 'charged-back':
						break;
					default:
						break;
				}
	        }
		} else {
			// TODO: what happens when process payment is reached, but we do not have payment data?
		}
	}

	// This function is used to display non-approved orders
	public function receipt_page( $order_id ) {
		$incomming_order = $order = wc_get_order( $order_id );

		$html = json_encode( $incomming_order, JSON_PRETTY_PRINT );
		/*if ( $status == "in_process" ) { // in-process
			$html =
				'<p>' . __( 'Your payment is under review. In less than 1h we should notify you via email.', 'woocommerce-mercadopago-module' ) . '</p>';
			$html .=
				'<a class="button" href="' . esc_url( $incomming_order->get_checkout_order_received_url() ) . '">' .
				__( 'Click to try again', 'woocommerce-mercadopago-module' ) .
				'</a>';
			echo $html;
			$incomming_order->update_status( 'pending' );
		} else if ( strpos( $status, 'rejected' ) !== false ) { // rejected
			$rejected_detail = explode( "|", $incomming_order->status );
			$html =
				'<p>' . __( 'Your payment was refused. You can try again.', 'woocommerce-mercadopago-module' ) . '</p>' .
				'<p>' . __( $rejected_detail[1], 'woocommerce-mercadopago-module' ) . '</p>';
			$html .=
				'<a class="button cancel" href="' . esc_url( $incomming_order->get_checkout_payment_url() ) . '">' .
				__( 'Click to try again', 'woocommerce-mercadopago-module' ) .
				'</a>';
			echo $html;
			$incomming_order->update_status( 'pending' );
		} else {
			echo '<p>$status: ' . $status . '</p>';
		}*/
		echo $html;
	}

	protected function createUrl( $order, $post_from_form ) {
		
		$mp = new MP( $this->access_token );
		// Checks for sandbox mode
		if ( 'yes' == $this->sandbox ) {
			$mp->sandbox_mode( true );
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, $this->id . ': @[createUrl] - sandbox mode is enabled' );
			}
		} else {
			$mp->sandbox_mode( false );
		}

		// Creates the order parameters by checking the cart configuration
		$preferences = $this->createPreferences( $order, $post_from_form );
		try {
			// Create order preferences with Mercado Pago API request
			$checkout_info = $mp->post( "/v1/payments", json_encode( $preferences ) );
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, $this->id .
					': @[createUrl] - Received [$checkout_info] from Mercado Pago API: ' .
					json_encode( $checkout_info, JSON_PRETTY_PRINT ) );
			}
			if ( is_wp_error( $checkout_info ) ||
				$checkout_info[ 'status' ] < 200 || $checkout_info[ 'status' ] >= 300 ) {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, $this->id .
						': @[createUrl] - payment creation failed with error: ' .
						$checkout_info[ 'response' ][ 'status' ] );
				}
				return false;
			} else {
				return $checkout_info[ 'response' ];
			}
		} catch ( MercadoPagoException $e ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, $this->id .
					': @[createUrl] - payment creation failed with exception: ' .
					json_encode( $e, JSON_PRETTY_PRINT ) );
			}
			return false;
		}

	}
	
	private function createPreferences( $order, $post_from_form ) {

		// Here we build the array that contains ordered itens, from customer cart
		$items = array();
		$purchase_description = "";
		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				if ( $item['qty'] ) {
					$product = new WC_product( $item[ 'product_id' ] );
					$purchase_description =
						$purchase_description . ' ' .
						( $product->post->post_title . ' x ' . $item[ 'qty' ] );
					array_push( $items, array(
						'id' => $item[ 'product_id' ],
						'title' => ( $product->post->post_title . ' x ' . $item[ 'qty' ] ),
						'description' => (
							// This handles description width limit of Mercado Pago
							strlen( $product->post->post_content ) > 230 ?
							substr( $product->post->post_content, 0, 230 ) . "..." :
							$product->post->post_content
						),
						'picture_url' => $product->get_image(),
						'category_id' => $this->store_categories_id[ $this->category_id ],
						'quantity' => 1,
						'unit_price' => (float) $item[ 'line_total' ]
					));
				}
			}
		}
        
        // Creates the shipment cost structure
        $shipping_cost = (float) $order->get_total_shipping();
        if ( $shipping_cost > 0 ) {
            $item = array(
                'title' => __( 'Shipping', 'woocommerce-mercadopago-module' ),
                'description' => __( 'Shipping service used by store', 'woocommerce-mercadopago-module' ),
                'quantity' => 1,
                'category_id' => $this->store_categories_id[ $this->category_id ],
                'unit_price' => $shipping_cost
            );
            $items[] = $item;
        }
        
        // Discounts features
        /*
        $discounts = (double) $cart->getOrderTotal( true, Cart::ONLY_DISCOUNTS );
        if ( $discounts > 0 ) {
            $item = array(
                'title' => 'Discount',
                'description' => 'Discount provided by store',
                'quantity' => 1,
                'category_id' => Configuration::get( 'MERCADOPAGO_CATEGORY' ),
                'unit_price' => - $discounts
            );
            $items[] = $item;
        }
        */

		// Build additional information from the customer data
        $payer_additional_info = array(
            'first_name' => $order->billing_first_name,
            'last_name' => $order->billing_last_name,
            //'registration_date' => 
            'phone'	=> array(
            	//'area_code' => 
				'number' => $order->billing_phone
			),
            'address' => array(
				'zip_code' => $order->billing_postcode,
				//'street_number' => 
				'street_name' => $order->billing_address_1 . ' / ' .
					$order->billing_city . ' ' .
					$order->billing_state . ' ' .
					$order->billing_country
			)
        );

        // Create the shipment address information set
        $shipments = array(
        	'receiver_address' => array(
        		'zip_code' => $order->shipping_postcode,
        		//'street_number' =>
        		'street_name' => $order->shipping_address_1 . ' ' .
        			$order->shipping_address_2 . ' ' .
        			$order->shipping_city . ' ' .
        			$order->shipping_state . ' ' .
        			$order->shipping_country,
        		//'floor' =>
        		'apartment' => $order->shipping_address_2
        	)
        );
        
        // The payment preference
        $payment_preference = array (
        	'transaction_amount' => (float) $post_from_form[ 'mercadopago_custom' ][ 'amount' ],
        	'token' => $post_from_form[ 'mercadopago_custom' ][ 'token' ],
        	'description' => $purchase_description,
        	'installments' => (int) $post_from_form[ 'mercadopago_custom' ][ 'installments' ],
            'payment_method_id' => $post_from_form[ 'mercadopago_custom' ][ 'paymentMethodId' ],
            'payer' => array(
            	'email' => $order->billing_email
            ),
            'external_reference' => $this->invoice_prefix . $order->id,
            'statement_descriptor' => $this->title,
            'additional_info' => array(
                'items' => $items,
                'payer' => $payer_additional_info,
                'shipments' => $shipments
            )
        );

        // Do not set IPN url if it is a localhost!
        $notification_url = $this->domain . '/woocommerce-mercadopago-module/?wc-api=WC_WooMercadoPagoCustom_Gateway';
        if ( !strrpos( $notification_url, "localhost" ) ) {
            $payment_preference['notification_url'] = $notification_url;
        }

        // Customer's Card Feature
        /*
        // save the card
        if ( isset( $post[ 'opcaoPagamentoCreditCard' ] ) && $post[ 'opcaoPagamentoCreditCard' ] == "Cards" ) {
            $payment_preference[ 'metadata' ] = array( 'opcao_pagamento' => $post[ 'opcaoPagamentoCreditCard' ],
                'customer_id' => $post[ 'customerID' ],
                'card_token_id' => $post[ 'card_token_id' ] );
        }
        */
        // add only for creditcard
        if ( array_key_exists( 'card_token_id', $post_from_form[ 'mercadopago_custom' ] ) ) {
            // add only it has issuer id
            if ( array_key_exists( 'issuersOptions', $post_from_form[ 'mercadopago_custom' ] ) ) {
                $payment_preference[ 'issuer_id' ] =
                	(integer) $post_from_form[ 'mercadopago_custom' ][ 'issuersOptions' ];
            }
            /*
            if ( "Customer" == $post_from_form[ 'mercadopago_custom' ][ 'opcaoPagamentoCreditCard' ] ) {
                $customerCards = $post_from_form[ 'mercadopago_custom' ][ "customerCards" ];
                $customerID = $post_from_form[ 'mercadopago_custom' ][ "customerID" ];
                $payment_preference[ 'payer' ][ 'id' ] = $customerID;
                $payment_preference[ 'installments' ] = (integer) $post_from_form[ 'mercadopago_custom' ][ 'installmentsCust' ];
            } else {
                $payment_preference[ 'installments' ] = (integer) $post_from_form[ 'mercadopago_custom' ][ 'installments' ];
            }
            $payment_preference[ 'token' ] = $post_from_form[ 'mercadopago_custom' ][ 'card_token_id' ];
            */
        }

        // Coupon Feature
        /*
        $mercadopago_coupon = isset( $post[ 'mercadopago_coupon' ] ) ? $post[ 'mercadopago_coupon' ] : "";
        if ( $mercadopago_coupon != "" ) {
        	$coupon = $this->validCoupon( $mercadopago_coupon );
            if ( $coupon[ 'status' ] == 200 ) {
            	$payment_preference[ 'campaign_id' ] =  $coupon[ 'response' ][ 'id' ];
                $payment_preference[ 'coupon_amount' ] = (float) $coupon[ 'response' ][ 'coupon_amount' ];
                $payment_preference[ 'coupon_code' ] = strtoupper( $mercadopago_coupon );
            } else {
                PrestaShopLogger::addLog ( $coupon['response']['error'] . Tools::jsonEncode($coupon), MP_SDK::ERROR, 0 );
                $this->context->smarty->assign( array(
	                'message_error' => $coupon[ 'response' ][ 'error' ],
	                'version' => $this->getPrestashopVersion()
                ) );
                return $this->display ( __file__, '/views/templates/front/error_admin.tpl' );
            }
        }
        */

        if ( !$this->isTestUser ) {
			$preferences[ 'sponsor_id' ] = (int) ( $this->sponsor_id[ $this->site_id ] );
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, $this->id .
				': @[createPreferences] - Returning just created [$payment_preference] structure: ' .
				json_encode( $payment_preference, JSON_PRETTY_PRINT ) );
		}

        $payment_preference = apply_filters(
        	'woocommerce_mercadopago_module_custom_preferences',
        	$payment_preference, $order
        );
		return $payment_preference;

    }

	/*
	 * ========================================================================
	 * AUXILIARY AND FEEDBACK METHODS
	 * ========================================================================
	 */

	// Check if we have valid credentials.
	public function validateCredentials() {
		if ( empty( $this->public_key ) ) return false;
		if ( empty( $this->access_token ) ) return false;
		if ( strlen( $this->public_key ) > 0 && strlen( $this->access_token ) > 0 ) {
			try {
				$mp = new MP( $this->access_token );
				return true;
			} catch ( MercadoPagoException $e ) {
				return false;
			}
		}
		return false;
	}
	
	// Build the string representing the path to the log file
	protected function buildLogPathString() {
		return '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' .
			esc_attr( 'woocommerce-mercadopago-module' ) . '-' .
			sanitize_file_name( wp_hash( 'woocommerce-mercadopago-module' ) ) . '.log' ) ) . '">' .
			__( 'WooCommerce &gt; System Status &gt; Logs', 'woocommerce-mercadopago-module' ) . '</a>';
	}
	
	// Return boolean indicating if currency is supported.
	// TODO: Peru rollout
	protected function isSupportedCurrency() {
		return in_array( get_woocommerce_currency(), array( 'ARS', 'BRL', 'CLP', 'COP', 'MXN', 'USD', 'VEF' ) );
	}

	// Called automatically by WooCommerce, verify if Module is available to use.
	public function is_available() {
		$available = ( 'yes' == $this->settings[ 'enabled' ] ) &&
			! empty( $this->public_key ) &&
			! empty( $this->access_token ) &&
			$this->isSupportedCurrency();
		return $available;
	}
	
	// Fix css for Mercado Pago in specific cases.
	public function css() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			$page_id = wc_get_page_id( 'checkout' );
		} else {
			$page_id = woocommerce_get_page_id( 'checkout' );
		}
		if ( is_page($page_id ) ) {
			echo '<style type="text/css">#MP-Checkout-dialog { z-index: 9999 !important; }</style>' . PHP_EOL;
		}
	}
	
	// Get the URL to admin page.
	protected function admin_url() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			return admin_url(
				'admin.php?page=wc-settings&tab=checkout&section=wc_woomercadopagocustom_gateway'
			);
		}
		return admin_url(
			'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_WooMercadoPagoCustom_Gateway'
		);
	}

	// Notify that public_key and/or access_token are not valid.
	public function credentialsMissingMessage() {
		echo '<div class="error"><p><strong>' . 
			__( 'Mercado Pago is Inactive', 'woocommerce-mercadopago-module' ) .
			'</strong>: ' .
			sprintf(
				__( 'Your Mercado Pago credentials Public Key/Access Token appears to be misconfigured.', 'woocommerce-mercadopago-module' ) . ' %s',
				'<a href="' . $this->admin_url() . '">' .
				__( 'Click here and configure!', 'woocommerce-mercadopago-module' ) . '</a>' ) .
			'</p></div>';
	}

	// Notify that currency is not supported.
	// TODO: Peru rollout
	public function currencyNotSupportedMessage() {
		echo '<div class="error"><p><strong>' .
			__( 'Mercado Pago is Inactive', 'woocommerce-mercadopago-module' ) .
			'</strong>: ' .
			sprintf(
				__( 'The currency' ) . ' <code>%s</code> ' .
				__( 'is not supported. Supported currencies are: ARS, BRL, CLP, COP, MXN, USD, VEF.', 'woocommerce-mercadopago-module' ),
				get_woocommerce_currency() ) .
			'</p></div>';
	}
	
	public function getCountryName( $site_id ) {
		$country = $site_id;
		switch ( $site_id ) {
			case 'MLA': return __( 'Argentine', 'woocommerce-mercadopago-module' );
			case 'MLB': return __( 'Brazil', 'woocommerce-mercadopago-module' );
			case 'MCO': return __( 'Colombia', 'woocommerce-mercadopago-module' );
			case 'MLC': return __( 'Chile', 'woocommerce-mercadopago-module' );
			case 'MLM': return __( 'Mexico', 'woocommerce-mercadopago-module' );
			case 'MLV': return __( 'Venezuela', 'woocommerce-mercadopago-module' );
			case 'MPE': return __( 'Peru', 'woocommerce-mercadopago-module' );
		}
	}

	/*public function showOrderStatus( $status ) {

		$mp = new MP( $this->access_token );
		if ( 'yes' == $this->sandbox )
			$mp->sandbox_mode( true );
		else
			$mp->sandbox_mode( false );

		if ( $status == "rejected" ) {
			$html =
				'<p>' . __( 'An error occurred when proccessing your payment. Please try again or contact us for assistence.', 'woocommerce-mercadopago-module' ) . '</p>';
			$html .=
				'<p>' . __( 'Reason is: ', 'woocommerce-mercadopago-module' ) . $status . '.</p>';
			$html .=
				'<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' .
				__( 'Click to try again', 'woocommerce-mercadopago-module' ) .
				'</a>';
			return $html;
		} else if ( $status == "in_process" ) {

			return $html;
		}
	}*/
	
	/*
	 * ========================================================================
	 * IPN MECHANICS
	 * ========================================================================
	 */
	
	// This call checks any incoming notifications from Mercado Pago server.
	public function check_ipn_response() {
		@ob_clean();
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, $this->id .
				': @[check_ipn_response] - Received _get content: ' .
				json_encode( $_GET, JSON_PRETTY_PRINT ) );
		}
		$data = $this->check_ipn_request_is_valid( $_GET );
		if ( $data ) {
			header( 'HTTP/1.1 200 OK' );
			do_action( 'valid_mercadopagocustom_ipn_request', $data );
		} else {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, $this->id .
					': @[check_ipn_response] - Mercado Pago Request Failure: ' .
					json_encode( $_GET, JSON_PRETTY_PRINT ) );
			}
			wp_die( __( 'Mercado Pago Request Failure', 'woocommerce-mercadopago-module' ) );
		}
	}
	
	// Get received data from IPN and checks if we have an associated
	// payment. If we have these information, we return data to be
	// processed by successful_request function.
	public function check_ipn_request_is_valid( $data ) {
		if ( !isset( $data[ 'data_id' ] ) || !isset( $data[ 'type' ] ) ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, $this->id .
					': @[check_ipn_request_is_valid] - data_id or type not set: ' .
					json_encode( $data, JSON_PRETTY_PRINT ) );
			}
			return false; // No ID? No process!
		}
		$mp = new MP( $this->access_token );
		if ( 'yes' == $this->sandbox )
			$mp->sandbox_mode( true );
		else
			$mp->sandbox_mode( false );
		try {
			$access_token = array( "access_token" => $mp->get_access_token() );
			if ( $data[ "type" ] == 'payment' ) {
				$payment_info = $mp->get( "/v1/payments/" . $data[ "data_id" ], $access_token, false );
				if ( !is_wp_error( $payment_info ) &&
					( $payment_info[ "status" ] == 200 || $payment_info[ "status" ] == 201 ) ) {
					return $payment_info[ 'response' ];
				} else {
					if ( 'yes' == $this->debug ) {
						$this->log->add( $this->id, $this->id .
							': @[check_ipn_request_is_valid] - error when processing received data: ' .
							json_encode( $payment_info, JSON_PRETTY_PRINT ) );
					}
					return false;
				}
			}
		} catch ( MercadoPagoException $e ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, $this->id .
					': @[check_ipn_request_is_valid] - MercadoPagoException: ' .
					json_encode( $e, JSON_PRETTY_PRINT ) );
			}
			return false;
		}
		return true;
	}
	
	// Properly handles each case of notification, based in payment status.
	public function successful_request( $data ) {
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, $this->id .
				': @[successful_request] - starting to process ipn update...' );
		}
		$order_key = $data[ 'external_reference' ];
		if ( !empty( $order_key ) ) {
			$order_id = (int) str_replace( $this->invoice_prefix, '', $order_key );
			$order = new WC_Order( $order_id );
			// Checks whether the invoice number matches the order, if true processes the payment
			if ( $order->id === $order_id ) {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, $this->id .
						': @[successful_request] - got order with ID ' . $order->id .
						' and status ' . $data[ 'status' ] );
				}
				// Order details.
				if ( !empty( $data[ 'payer' ][ 'email' ] ) ) {
					update_post_meta(
						$order_id,
						__( 'Payer email',
							'woocommerce-mercadopago-module' ),
						$data[ 'payer' ][ 'email' ]
					);
				}
				if ( !empty( $data[ 'payment_type_id' ] ) ) {
					update_post_meta(
						$order_id,
						__( 'Payment type',
							'woocommerce-mercadopago-module' ),
						$data[ 'payment_type_id' ]
					);
				}
				if ( !empty( $data ) ) {
					update_post_meta(
						$order_id,
						__( 'Mercado Pago Payment ID',
							'woocommerce-mercadopago-module' ),
						$data[ 'id' ]
					);
				}
				// Switch the status and update in WooCommerce
				switch ( $data[ 'status' ] ) {
					case 'approved':
						$order->add_order_note(
							'Mercado Pago: ' . __( 'Payment approved.',
								'woocommerce-mercadopago-module' )
						);
						$order->payment_complete();
						break;
					case 'pending':
						$order->add_order_note(
							'Mercado Pago: ' . __( 'Customer haven\'t paid yet.',
								'woocommerce-mercadopago-module' )
						);
						break;
					case 'in_process':
						$order->update_status(
							'on-hold',
							'Mercado Pago: ' . __( 'Payment under review.',
								'woocommerce-mercadopago-module' )
						);
						break;
					case 'rejected':
						$order->update_status(
							'failed',
							'Mercado Pago: ' . __( 'The payment was refused. The customer can try again.',
								'woocommerce-mercadopago-module' )
						);
						break;
					case 'refunded':
						$order->update_status(
							'refunded',
							'Mercado Pago: ' . __( 'The payment was refunded to the customer.',
								'woocommerce-mercadopago-module' )
						);
						break;
					case 'cancelled':
						$order->update_status(
							'cancelled',
							'Mercado Pago: ' . __( 'The payment was cancelled.',
								'woocommerce-mercadopago-module' )
						);
						break;
					case 'in_mediation':
						$order->add_order_note(
							'Mercado Pago: ' . __( 'The payment is under mediation or it was charged-back.',
								'woocommerce-mercadopago-module' )
						);
						break;
					case 'charged-back':
						$order->add_order_note(
							'Mercado Pago: ' . __( 'The payment is under mediation or it was charged-back.',
								'woocommerce-mercadopago-module' )
						);
						break;
					default:
						break;
				}
			}
		}
	}
	
}
