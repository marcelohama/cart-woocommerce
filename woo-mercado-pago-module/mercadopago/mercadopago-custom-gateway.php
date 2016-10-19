<?php
/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer - Marcelo Tomio Hama / marcelo.hama@mercadolivre.com
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// This include Mercado Pago library SDK
require_once 'sdk/lib/mercadopago.php';

/**
 * Summary: Extending from WooCommerce Payment Gateway class.
 * Description: This class implements Mercado Pago custom checkout.
 * @since 2.0.0
 */
class WC_WooMercadoPagoCustom_Gateway extends WC_Payment_Gateway {

	public function __construct($is_instance = false) {

		// Mercado Pago fields
		$this->mp = null;
		$this->site_id = null;
		$this->currency_ratio = -1;
		$this->is_test_user = false;

		// Auxiliary fields
		$this->currency_message = '';
		$this->country_configs = array();
		$this->store_categories_id = array();
  		$this->store_categories_description = array();

		// WooCommerce fields
		$this->id = 'woocommerce-mercadopago-custom-module';
		$this->domain = get_site_url() . '/index.php';
		$this->method_title = __('Mercado Pago - Credit Card', 'woocommerce-mercadopago-module');
		$this->method_description = '<img width="200" height="52" src="' .
			plugins_url('images/mplogo.png', plugin_dir_path(__FILE__)) . '"><br><br>' . '<strong>' .
			wordwrap(
				__('This module enables WooCommerce to use Mercado Pago as payment method for purchases made in your virtual store.', 'woocommerce-mercadopago-module'),
				80, '\n'
			) . '</strong>';

		// Fields used in Mercado Pago Module configuration page
		$this->public_key = $this->get_option('public_key');
		$this->access_token = $this->get_option('access_token');
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->statement_descriptor = $this->get_option('statement_descriptor');
		$this->coupon_mode = $this->get_option('coupon_mode');
		$this->binary_mode = $this->get_option('binary_mode');
		$this->category_id = $this->get_option('category_id');
		$this->invoice_prefix = $this->get_option('invoice_prefix', 'WC-');
		$this->currency_conversion = $this->get_option('currency_conversion', false);
		$this->sandbox = $this->get_option('sandbox', false);
		$this->debug = $this->get_option('debug', false);

		// Logging and debug
		if ('yes' == $this->debug) {
			if (class_exists('WC_Logger')) {
				$this->log = new WC_Logger();
			} else {
				$this->log = WC_MercadoPago_Module::woocommerce_instance()->logger();
			}
		}

		// Render our configuration page and init/load fields
		$this->init_form_fields();
		$this->init_settings();

		// Used by IPN to receive IPN incomings
		add_action(
			'woocommerce_api_wc_woomercadopagocustom_gateway',
			array($this, 'process_http_request')
		);
		// Used by IPN to process valid incomings
		add_action(
			'valid_mercadopagocustom_ipn_request',
			array($this, 'successful_request')
		);
		// Used in settings page to hook "save settings" action
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array($this, 'process_admin_options')
		);
		// Scripts for custom checkout
		add_action(
			'wp_enqueue_scripts',
			array($this, 'custom_checkout_scripts')
		);
		// Apply the discounts
		add_action(
			'woocommerce_cart_calculate_fees',
			array($this, 'add_discount_custom'), 10
		);
		// Used in settings page to hook "save settings" action
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array($this, 'custom_process_admin_options')
		);

		if (!empty($this->settings['enabled']) && $this->settings['enabled'] == 'yes') {
			if ($is_instance) {
				if (empty($this->public_key) || empty($this->access_token)) {
					// Verify if public_key or access_token is empty
					add_action('admin_notices', array($this, 'credentials_missing_message'));
				} else {
					if (empty($this->sandbox) && $this->sandbox == 'no') {
						// Verify if SSL is supported
						add_action('admin_notices', array($this, 'check_ssl_absence'));
					}
				}
			}
		}

	}

	/**
	 * Summary: Initialise Gateway Settings Form Fields.
	 * Description: Initialise Gateway settings form fields with a customized page.
	 */
	public function init_form_fields() {

		// If module is disabled, we do not need to load and process the settings page
		if (empty($this->settings['enabled']) || 'no' == $this->settings['enabled']) {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable/Disable', 'woocommerce-mercadopago-module'),
					'type' => 'checkbox',
					'label' => __('Enable Custom Checkout', 'woocommerce-mercadopago-module'),
					'default' => 'no'
				)
			);
			return;
		}

		$api_secret_locale = sprintf(
			'<a href="https://www.mercadopago.com/mla/account/credentials?type=custom" target="_blank">%s</a>, ' .
			'<a href="https://www.mercadopago.com/mlb/account/credentials?type=custom" target="_blank">%s</a>, ' .
			'<a href="https://www.mercadopago.com/mlc/account/credentials?type=custom" target="_blank">%s</a>, ' .
			'<a href="https://www.mercadopago.com/mco/account/credentials?type=custom" target="_blank">%s</a>, ' .
			'<a href="https://www.mercadopago.com/mlm/account/credentials?type=custom" target="_blank">%s</a>, ' .
			'<a href="https://www.mercadopago.com/mpe/account/credentials?type=custom" target="_blank">%s</a> %s ' .
			'<a href="https://www.mercadopago.com/mlv/account/credentials?type=custom" target="_blank">%s</a>',
			__('Argentine', 'woocommerce-mercadopago-module'),
			__('Brazil', 'woocommerce-mercadopago-module'),
			__('Chile', 'woocommerce-mercadopago-module'),
			__('Colombia', 'woocommerce-mercadopago-module'),
			__('Mexico', 'woocommerce-mercadopago-module'),
			__('Peru', 'woocommerce-mercadopago-module'),
			__('or', 'woocommerce-mercadopago-module'),
			__('Venezuela', 'woocommerce-mercadopago-module')
		);

		// Trigger API to get payment methods and site_id, also validates public_key/access_token
		if ($this->validate_credentials()) {
			// checking the currency
			$this->currency_message = '';
			if (!$this->is_supported_currency() && 'yes' == $this->settings['enabled']) {
				if ($this->currency_conversion == 'no') {
					$this->currency_ratio = -1;
					$this->currency_message .= WC_WooMercadoPago_Module::build_currency_not_converted_msg(
						$this->country_configs['currency'],
						$this->country_configs['country_name']
					);
				} else if ($this->currency_conversion == 'yes' && $this->currency_ratio != -1) {
					$this->currency_message .= WC_WooMercadoPago_Module::build_currency_converted_msg(
						$this->country_configs['currency'],
						$this->currency_ratio
					);
				} else {
					$this->currency_ratio = -1;
					$this->currency_message .=
						WC_WooMercadoPago_Module::build_currency_conversion_err_msg(
							$this->country_configs['currency']
						);
				}
			} else {
				$this->currency_ratio = -1;
			}
			$this->credentials_message = WC_WooMercadoPago_Module::build_valid_credentials_msg(
				$this->country_configs['country_name'],
				$this->site_id
			);
		} else {
			$this->credentials_message = WC_WooMercadoPago_Module::build_invalid_credentials_msg();
		}

		// fill categories (can be handled without credentials)
		$categories = WC_WooMercadoPago_Module::get_categories();
		$this->store_categories_id = $categories['store_categories_id'];
		$this->store_categories_description = $categories['store_categories_description'];

		// This array draws each UI (text, selector, checkbox, label, etc)
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'woocommerce-mercadopago-module'),
				'type' => 'checkbox',
				'label' => __('Enable Custom Checkout', 'woocommerce-mercadopago-module'),
				'default' => 'no'
			),
			'credentials_title' => array(
				'title' => __('Mercado Pago Credentials', 'woocommerce-mercadopago-module'),
				'type' => 'title',
				'description' => sprintf('%s', $this->credentials_message) . '<br>' . sprintf(
					__('You can obtain your credentials for', 'woocommerce-mercadopago-module') .
					' %s.', $api_secret_locale
				)
			),
			'public_key' => array(
				'title' => 'Public key',
				'type' => 'text',
				'description' =>
					__('Insert your Mercado Pago Public key.', 'woocommerce-mercadopago-module'),
				'default' => '',
				'required' => true
			),
			'access_token' => array(
				'title' => 'Access token',
				'type' => 'text',
				'description' =>
					__('Insert your Mercado Pago Access token.', 'woocommerce-mercadopago-module'),
				'default' => '',
				'required' => true
			),
			'ipn_url' => array(
				'title' =>
					__('Instant Payment Notification (IPN) URL', 'woocommerce-mercadopago-module'),
				'type' => 'title',
				'description' => sprintf(
					__('Your IPN URL to receive instant payment notifications is', 'woocommerce-mercadopago-module') .
					'<br>%s', '<code>' . $this->domain . '/' . $this->id .
					'/?wc-api=WC_WooMercadoPagoCustom_Gateway' . '</code>.'
				)
			),
			'checkout_options_title' => array(
				'title' => __('Checkout Options', 'woocommerce-mercadopago-module'),
				'type' => 'title',
				'description' => ''
			),
			'title' => array(
				'title' => __('Title', 'woocommerce-mercadopago-module'),
				'type' => 'text',
				'description' =>
					__('Title shown to the client in the checkout.', 'woocommerce-mercadopago-module'),
				'default' => __('Mercado Pago - Credit Card', 'woocommerce-mercadopago-module')
			),
			'description' => array(
				'title' => __('Description', 'woocommerce-mercadopago-module'),
				'type' => 'textarea',
				'description' =>
					__('Description shown to the client in the checkout.', 'woocommerce-mercadopago-module'),
				'default' => __('Pay with Mercado Pago', 'woocommerce-mercadopago-module')
			),
			'statement_descriptor' => array(
				'title' => __('Statement Descriptor', 'woocommerce-mercadopago-module'),
				'type' => 'text',
				'description' => __('The description that will be shown in your customer\'s invoice.', 'woocommerce-mercadopago-module'),
				'default' => __('Mercado Pago', 'woocommerce-mercadopago-module')
			),
			'coupon_mode' => array(
				'title' => __('Coupons', 'woocommerce-mercadopago-module'),
				'type' => 'checkbox',
				'label' => __('Enable coupons of discounts', 'woocommerce-mercadopago-module'),
				'default' => 'no',
				'description' =>
					__('If there is a Mercado Pago campaign, allow your store to give discounts to customers.', 'woocommerce-mercadopago-module')
			),
			'binary_mode' => array(
				'title' => __('Binary Mode', 'woocommerce-mercadopago-module'),
				'type' => 'checkbox',
				'label' => __('Enable binary mode for checkout status', 'woocommerce-mercadopago-module'),
				'default' => 'no',
				'description' =>
					__('When charging a credit card, only [approved] or [reject] status will be taken.', 'woocommerce-mercadopago-module')
			),
			'category_id' => array(
				'title' => __('Store Category', 'woocommerce-mercadopago-module'),
				'type' => 'select',
				'description' =>
					__('Define which type of products your store sells.', 'woocommerce-mercadopago-module'),
				'options' => $this->store_categories_id
			),
			'invoice_prefix' => array(
				'title' => __('Store Identificator', 'woocommerce-mercadopago-module'),
				'type' => 'text',
				'description' =>
					__('Please, inform a prefix to your store.', 'woocommerce-mercadopago-module')
					. ' ' .
					__('If you use your Mercado Pago account on multiple stores you should make sure that this prefix is unique as Mercado Pago will not allow orders with same identificators.', 'woocommerce-mercadopago-module'),
				'default' => 'WC-'
			),
			'currency_conversion' => array(
				'title' => __('Currency Conversion', 'woocommerce-mercadopago-module'),
				'type' => 'checkbox',
				'label' =>
					__('If the used currency in WooCommerce is different or not supported by Mercado Pago, convert values of your transactions using Mercado Pago currency ratio', 'woocommerce-mercadopago-module'),
				'default' => 'no',
				'description' => sprintf('%s', $this->currency_message)
			),
			'testing' => array(
				'title' => __('Test and Debug Options', 'woocommerce-mercadopago-module'),
				'type' => 'title',
				'description' => ''
			),
			'sandbox' => array(
				'title' => __('Mercado Pago Sandbox', 'woocommerce-mercadopago-module'),
				'type' => 'checkbox',
				'label' => __('Enable Mercado Pago Sandbox', 'woocommerce-mercadopago-module'),
				'default' => 'no',
				'description' =>
					__('This option allows you to test payments inside a sandbox environment.', 'woocommerce-mercadopago-module'),
			),
			'debug' => array(
				'title' => __('Debug and Log', 'woocommerce-mercadopago-module'),
				'type' => 'checkbox',
				'label' => __('Enable log', 'woocommerce-mercadopago-module'),
				'default' => 'no',
				'description' => sprintf(
					__('Register event logs of Mercado Pago, such as API requests, in the file', 'woocommerce-mercadopago-module') .
					' %s.', $this->build_log_path_string() . '.<br>' .
					__('File location: ', 'woocommerce-mercadopago-module') .
					'<code>wordpress/wp-content/uploads/wc-logs/' . $this->id . '-' .
					sanitize_file_name(wp_hash($this->id)) . '.log</code>')
			)
		);

	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the
	 * erroring field out.
	 * @return bool was anything saved?
	 */
	public function custom_process_admin_options() {
		$this->init_settings();

      $post_data = $this->get_post_data();

		foreach ($this->get_form_fields() as $key => $field) {
      	if ('title' !== $this->get_field_type($field)) {
         	try {
      			$this->settings[$key] = $this->get_field_value($key, $field, $post_data);
      			if ($key == 'enabled') {
      				// Handle analytics info
						WC_WooMercadoPago_Module::$status_custom =
							($this->settings[$key] == 'yes' ? 1 : 0);
					}
            } catch (Exception $e) {
            	$this->add_error($e->getMessage());
				}
         }
		}

		// Saving analytics for settings
		if ($this->mp != null) {
			$response = $this->mp->analytics_save_settings(null); // TODO:
			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[custom_process_admin_options] - analytics info: ' .
					json_encode(WC_WooMercadoPago_Module::get_module_settings(
						$this->site_id, 123
					), JSON_PRETTY_PRINT)
				);
			}
		}

		return update_option(
        	$this->get_option_key(),
        	apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings)
     	);
	}

	/*
	 * ========================================================================
	 * CHECKOUT BUSINESS RULES (CLIENT SIDE)
	 * ========================================================================
	 */

	public function custom_checkout_scripts() {
		if (is_checkout() && $this->is_available()) {
			if (!get_query_var('order-received')) {
				wp_enqueue_style(
					'woocommerce-mercadopago-style', plugins_url(
						'assets/css/custom_checkout_mercadopago.css',
						plugin_dir_path(__FILE__)));
				wp_enqueue_script(
					'woocommerce-mercadopago-v1',
					'https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js');
			}
		}
	}

	public function payment_fields() {
		$amount = $this->get_order_total();

		$parameters = array(
			'public_key' => $this->public_key,
			'site_id' => $this->site_id,
			'images_path' => plugins_url('images/', plugin_dir_path(__FILE__)),
			'banner_path' => $this->country_configs['checkout_banner_custom'],
			'amount' => $amount *
				((float) $this->currency_ratio > 0 ? (float) $this->currency_ratio : 1),
			'coupon_mode' => $this->coupon_mode,
			'is_currency_conversion' => $this->currency_ratio,
			'woocommerce_currency' => get_woocommerce_currency(),
			'account_currency' => $this->country_configs['currency'],
			'discount_action_url' => $this->domain .
				'/woocommerce-mercadopago-module/?wc-api=WC_WooMercadoPagoCustom_Gateway',
			'form_labels' => array(
				'form' => array(
					'payment_converted' =>
						__('Payment converted from', 'woocommerce-mercadopago-module'),
					'to' => __('to', 'woocommerce-mercadopago-module'),
					'coupon_empty' =>
						__('Please, inform your coupon code', 'woocommerce-mercadopago-module'),
					'apply' => __('Apply', 'woocommerce-mercadopago-module'),
					'remove' => __('Remove', 'woocommerce-mercadopago-module'),
					'discount_info1' => __('You will save', 'woocommerce-mercadopago-module'),
					'discount_info2' => __('with discount from', 'woocommerce-mercadopago-module'),
					'discount_info3' => __('Total of your purchase:', 'woocommerce-mercadopago-module'),
					'discount_info4' =>
						__('Total of your purchase with discount:', 'woocommerce-mercadopago-module'),
					'discount_info5' => __('*Uppon payment approval', 'woocommerce-mercadopago-module'),
					'discount_info6' =>
						__('Terms and Conditions of Use', 'woocommerce-mercadopago-module'),
					'coupon_of_discounts' => __('Discount Coupon', 'woocommerce-mercadopago-module'),
					'label_other_bank' => __('Other Bank', 'woocommerce-mercadopago-module'),
					'label_choose' => __('Choose', 'woocommerce-mercadopago-module'),
					'your_card' => __('Your Card', 'woocommerce-mercadopago-module'),
					'other_cards' => __('Other Cards', 'woocommerce-mercadopago-module'),
	        		'other_card' => __('Other Card', 'woocommerce-mercadopago-module'),
	        		'ended_in' => __('ended in', 'woocommerce-mercadopago-module'),
					'card_holder_placeholder' =>
						__(' as it appears in your card ...', 'woocommerce-mercadopago-module'),
	        		'payment_method' => __('Payment Method', 'woocommerce-mercadopago-module'),
					'credit_card_number' => __('Credit card number', 'woocommerce-mercadopago-module'),
					'expiration_month' => __('Expiration month', 'woocommerce-mercadopago-module'),
					'expiration_year' => __('Expiration year', 'woocommerce-mercadopago-module'),
					'year' => __('Year', 'woocommerce-mercadopago-module'),
					'month' => __('Month', 'woocommerce-mercadopago-module'),
					'card_holder_name' => __('Card holder name', 'woocommerce-mercadopago-module'),
					'security_code' => __('Security code', 'woocommerce-mercadopago-module'),
					'document_type' => __('Document Type', 'woocommerce-mercadopago-module'),
					'document_number' => __('Document number', 'woocommerce-mercadopago-module'),
					'issuer' => __('Issuer', 'woocommerce-mercadopago-module'),
					'installments' => __('Installments', 'woocommerce-mercadopago-module')
      		),
      		'error' => array(
	        		// card number
		        	'205' =>
		        		__('Parameter cardNumber can not be null/empty', 'woocommerce-mercadopago-module'),
		        	'E301' => __('Invalid Card Number', 'woocommerce-mercadopago-module'),
					// expiration date
					'208' => __('Invalid Expiration Date', 'woocommerce-mercadopago-module'),
					'209' => __('Invalid Expiration Date', 'woocommerce-mercadopago-module'),
					'325' => __('Invalid Expiration Date', 'woocommerce-mercadopago-module'),
					'326' => __('Invalid Expiration Date', 'woocommerce-mercadopago-module'),
					// card holder name
					'221' =>
						__('Parameter cardholderName can not be null/empty', 'woocommerce-mercadopago-module'),
					'316' => __('Invalid Card Holder Name', 'woocommerce-mercadopago-module'),
					// security code
					'224' =>
						__('Parameter securityCode can not be null/empty', 'woocommerce-mercadopago-module'),
					'E302' => __('Invalid Security Code', 'woocommerce-mercadopago-module'),
					// doc type
					'212' =>
						__('Parameter docType can not be null/empty', 'woocommerce-mercadopago-module'),
					'322' => __('Invalid Document Type', 'woocommerce-mercadopago-module'),
					// doc number
					'214' =>
						__('Parameter docNumber can not be null/empty', 'woocommerce-mercadopago-module'),
					'324' => __('Invalid Document Number', 'woocommerce-mercadopago-module'),
					// doc sub type
					'213' => __('The parameter cardholder.document.subtype can not be null or empty', 'woocommerce-mercadopago-module'),
					'323' => __('Invalid Document Sub Type', 'woocommerce-mercadopago-module'),
					// issuer
					'220' =>
						__('Parameter cardIssuerId can not be null/empty', 'woocommerce-mercadopago-module')
				)
			)
		);

		// find logged user
		try {
			if (wp_get_current_user()->ID != 0) {
				$logged_user_email = wp_get_current_user()->user_email;
				$customer = $this->mp->get_or_create_customer($logged_user_email);
				$customer_cards = $customer['cards'];
				$parameters['customerId'] = $customer['id'];
				$parameters['customer_cards'] = $customer_cards;
			}
		} catch (Exception $e) {
			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[process_fields] - there is a problem when retrieving information for cards: ' .
					json_encode(array('status' => $e->getCode(), 'message' => $e->getMessage()))
				);
			}
		}

		wc_get_template(
			'credit-card/payment-form.php',
			$parameters,
			'woocommerce/mercadopago/',
			WC_WooMercadoPago_Module::get_templates_path()
		);
	}

	/**
	 * Summary: Handle the payment and processing the order.
	 * Description: This function is called after we click on [place_order] button, and each field is
	 * passed to this function through $_POST variable.
	 * @return an array containing the result of the processment and the URL to redirect.
	 */
	public function process_payment($order_id) {

		if (!isset($_POST['mercadopago_custom']))
			return;

		$order = new WC_Order($order_id);
		$custom_checkout = $_POST['mercadopago_custom'];

		// we have got parameters from checkout page, now its time to charge the card
		if ('yes' == $this->debug) {
			$this->log->add(
				$this->id,
				'[process_payment] - Received [$_POST] from customer front-end page: ' .
				json_encode($_POST, JSON_PRETTY_PRINT)
			);
		}

		// Mexico country case
		if ($custom_checkout['paymentMethodId'] == '' || empty($custom_checkout['paymentMethodId'])) {
			$custom_checkout['paymentMethodId'] = $custom_checkout['paymentMethodSelector'];
		}

		if (isset($custom_checkout['amount']) && !empty($custom_checkout['amount']) &&
			isset($custom_checkout['token']) && !empty($custom_checkout['token']) &&
			isset($custom_checkout['paymentMethodId']) && !empty($custom_checkout['paymentMethodId']) &&
			isset($custom_checkout['installments']) && !empty($custom_checkout['installments']) &&
			$custom_checkout['installments'] != -1) {

			$response = self::create_url($order, $custom_checkout);

   		if (array_key_exists('status', $response)) {
        		switch ($response['status']) {
        			case 'approved':
        				WC()->cart->empty_cart();
		        		wc_add_notice(
		        			'<p>' .
		        				__($this->get_order_status('accredited'), 'woocommerce-mercadopago-module') .
		        			'</p>',
		        			'notice'
		        		);
        				$order->add_order_note(
							'Mercado Pago: ' .
							__('Payment approved.', 'woocommerce-mercadopago-module')
						);
						return array(
							'result' => 'success',
							'redirect' => $order->get_checkout_order_received_url()
						);
						break;
          		case 'pending':
          			// order approved/pending, we just redirect to the thankyou page
            		return array(
							'result' => 'success',
							'redirect' => $order->get_checkout_order_received_url()
						);
						break;
          		case 'in_process':
          			// for pending, we don't know if the purchase will be made, so we must inform this status
          			WC()->cart->empty_cart();
						wc_add_notice(
							'<p>' .
								__($this->get_order_status($response['status_detail']), 'woocommerce-mercadopago-module') .
							'</p>' .
							'<p><a class="button" href="' .
								esc_url($order->get_checkout_order_received_url()) .
							'">' .
								__('Check your order resume', 'woocommerce-mercadopago-module') .
							'</a></p>',
							'notice'
						);
						return array(
							'result' => 'success',
							'redirect' => $order->get_checkout_payment_url(true)
						);
						break;
          		case 'rejected':
	        			// if rejected is received, the order will not proceed until another payment try,
          			// so we must inform this status
						wc_add_notice(
							'<p>' .
								__('Your payment was refused. You can try again.', 'woocommerce-mercadopago-module') .
							'<br>' .
								__($this->get_order_status($response['status_detail']), 'woocommerce-mercadopago-module') .
							'</p>' .
							'<p><a class="button" href="' . esc_url($order->get_checkout_payment_url()) . '">' .
								__('Click to try again', 'woocommerce-mercadopago-module') .
							'</a></p>',
							'error'
						);
						return array(
							'result' => 'success',
							'redirect' => $order->get_checkout_payment_url(true)
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
			// process when fields are imcomplete
			wc_add_notice(
				'<p>' .
					__('A problem was occurred when processing your payment. Are you sure you have correctly filled all information in the checkout form?', 'woocommerce-mercadopago-module') .
				'</p>',
				'error'
			);
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}

	/**
	 * Summary: Build Mercado Pago preference.
	 * Description: Create Mercado Pago preference and get init_point URL based in the order options
	 * from the cart.
	 * @return the preference object.
	 */
	private function build_payment_preference($order, $custom_checkout) {

		// A string to register items (workaround to deal with API problem that shows only first item)
		$list_of_items = array();

		// Here we build the array that contains ordered itens, from customer cart
		$items = array();
		if (sizeof($order->get_items()) > 0) {
			foreach ($order->get_items() as $item) {
				if ($item['qty']) {
					$product = new WC_product($item['product_id']);
					array_push($list_of_items, $product->post->post_title . ' x ' . $item['qty']);
					array_push($items, array(
						'id' => $item['product_id'],
						'title' => ($product->post->post_title . ' x ' . $item['qty']),
						'description' => sanitize_file_name(
							// This handles description width limit of Mercado Pago
							(strlen($product->post->post_content) > 230 ?
								substr($product->post->post_content, 0, 230) . '...' :
								$product->post->post_content)
						),
						'picture_url' => wp_get_attachment_url($product->get_image_id()),
						'category_id' => $this->store_categories_id[$this->category_id],
						'quantity' => 1,
						'unit_price' => floor(((float) $item['line_total'] + (float) $item['line_tax']) *
							((float) $this->currency_ratio > 0 ? (float) $this->currency_ratio : 1) * 100) / 100
					));
				}
			}
		}

    	// Creates the shipment cost structure
    	$ship_cost = ((float) $order->get_total_shipping() + (float) $order->get_shipping_tax()) *
    		((float) $this->currency_ratio > 0 ? (float) $this->currency_ratio : 1);
    	if ($ship_cost > 0) {
      	$item = array(
        		'title' => sanitize_file_name($order->get_shipping_to_display()),
        		'description' => __('Shipping service used by store', 'woocommerce-mercadopago-module'),
        		'quantity' => 1,
        		'category_id' => $this->store_categories_id[$this->category_id],
        		'unit_price' => floor($ship_cost * 100) / 100
      	);
      	$items[] = $item;
    	}

		// Discounts features
		if (isset($custom_checkout['discount']) && $custom_checkout['discount'] != '' &&
			$custom_checkout['discount'] > 0 && isset($custom_checkout['coupon_code']) &&
	  		$custom_checkout['coupon_code'] != '' &&
	  		WC()->session->chosen_payment_method == 'woocommerce-mercadopago-custom-module') {

			$item = array(
        		'title' => __('Discount', 'woocommerce-mercadopago-module'),
        		'description' => __('Discount provided by store', 'woocommerce-mercadopago-module'),
        		'quantity' => 1,
        		'category_id' => $this->store_categories_id[$this->category_id],
        		'unit_price' => -((float) $custom_checkout['discount'])
      	);
      	$items[] = $item;
	  	}

		// Build additional information from the customer data
    	$payer_additional_info = array(
      	'first_name' => $order->billing_first_name,
      	'last_name' => $order->billing_last_name,
      	//'registration_date' =>
      	'phone' => array(
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
    	$preferences = array(
    		'transaction_amount' => floor(((float) $custom_checkout['amount']) * 100) / 100,
    		'token' => $custom_checkout['token'],
    		'description' => implode(', ', $list_of_items),
    		'installments' => (int) $custom_checkout['installments'],
      	'payment_method_id' => $custom_checkout['paymentMethodId'],
      	'payer' => array(
      		'email' => $order->billing_email
      	),
      	'external_reference' => $this->invoice_prefix . $order->id,
      	'statement_descriptor' => $this->statement_descriptor,
      	'binary_mode' => ($this->binary_mode == 'yes'),
      	'additional_info' => array(
				'items' => $items,
          	'payer' => $payer_additional_info,
				'shipments' => $shipments
      	)
		);

    	// Customer's Card Feature, add only if it has issuer id
    	if (array_key_exists('token', $custom_checkout)) {
    		$preferences['metadata']['token'] = $custom_checkout['token'];
        	if (array_key_exists('issuer', $custom_checkout)) {
        		if (!empty($custom_checkout['issuer'])) {
        			$preferences['issuer_id'] = (integer) $custom_checkout['issuer'];
        		}
        	}
        	if (!empty($custom_checkout['CustomerId'])) {
    			$preferences['payer']['id'] = $custom_checkout['CustomerId'];
    		}
		}

    	// Do not set IPN url if it is a localhost
    	if (!strrpos($this->domain, 'localhost')) {
			$preferences['notification_url'] = WC_WooMercadoPago_Module::workaround_ampersand_bug(
				$this->domain . '/woocommerce-mercadopago-module/?wc-api=WC_WooMercadoPagoCustom_Gateway'
			);
		}

    	// Discounts features
    	if (isset($custom_checkout['discount']) && $custom_checkout['discount'] != '' &&
    		$custom_checkout['discount'] > 0 && isset($custom_checkout['coupon_code']) &&
    		$custom_checkout['coupon_code'] != '' &&
    		WC()->session->chosen_payment_method == 'woocommerce-mercadopago-custom-module') {

    		$preferences['campaign_id'] =  (int) $custom_checkout['campaign_id'];
      	$preferences['coupon_amount'] = ((float) $custom_checkout['discount']);
      	$preferences['coupon_code'] = strtoupper($custom_checkout['coupon_code']);
		}

		// Set sponsor ID
    	if (!$this->is_test_user) {
			$preferences['sponsor_id'] = $this->country_configs['sponsor_id'];
		}

		if ('yes' == $this->debug) {
			$this->log->add(
				$this->id,
				'[build_payment_preference] - returning just created [$preferences] structure: ' .
				json_encode($preferences, JSON_PRETTY_PRINT)
			);
		}

    	$preferences = apply_filters(
    		'woocommerce_mercadopago_module_custom_preferences',
    		$preferences, $order
    	);
		return $preferences;
	}

	// --------------------------------------------------

	protected function create_url($order, $custom_checkout) {

		// Creates the order parameters by checking the cart configuration
		$preferences = $this->build_payment_preference($order, $custom_checkout);

		// Checks for sandbox mode
		if ('yes' == $this->sandbox) {
			$this->mp->sandbox_mode(true);
			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[create_url] - sandbox mode is enabled'
				);
			}
		} else {
			$this->mp->sandbox_mode(false);
		}

		// Create order preferences with Mercado Pago API request
		try {
			$checkout_info = $this->mp->post('/v1/payments', json_encode($preferences));
			if ($checkout_info['status'] < 200 || $checkout_info['status'] >= 300) {
				// Mercado Pago trowed an error
				if ('yes' == $this->debug) {
					$this->log->add(
						$this->id,
						'[create_url] - mercado pago gave error, payment creation failed with error: ' .
						$checkout_info['response']['status']);
				}
				return false;
			} else if (is_wp_error($checkout_info)) {
				// WordPress throwed an error
				if ('yes' == $this->debug) {
					$this->log->add(
						$this->id,
						'[create_url] - wordpress gave error, payment creation failed with error: ' .
						$checkout_info['response']['status']);
				}
				return false;
			} else {
				// Obtain the URL
				if ('yes' == $this->debug) {
					$this->log->add(
						$this->id,
						'[create_url] - payment link generated with success from mercado pago, with structure as follow: ' .
						json_encode($checkout_info, JSON_PRETTY_PRINT));
				}
				return $checkout_info['response'];
			}
		} catch (MercadoPagoException $e) {
			// Something went wrong with the payment creation
			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[create_url] - payment creation failed with exception: ' .
					json_encode(array('status' => $e->getCode(), 'message' => $e->getMessage()))
				);
			}
			return false;
		}
	}

	/**
	 * Summary: Check if we have existing customer card, if not we create and save it.
	 * Description: Check if we have existing customer card, if not we create and save it.
	 * @return boolean true/false depending on the validation result.
	 */
  	public function check_and_save_customer_card($checkout_info) {

  		if ('yes' == $this->debug) {
			$this->log->add(
				$this->id,
				': @[check_and_save_customer_card] - checking info to create card: ' .
				json_encode($checkout_info, JSON_PRETTY_PRINT)
			);
		}

		$custId = null;
		$token  = null;
		$issuer_id = null;
		$payment_method_id = null;

  		if (isset($checkout_info['payer']['id']) && !empty($checkout_info['payer']['id'])) {
  			$custId = $checkout_info['payer']['id'];
  		} else {
  			return;
  		}

  		if (isset($checkout_info['metadata']['token']) && !empty($checkout_info['metadata']['token'])) {
  			$token = $checkout_info['metadata']['token'];
  		} else {
  			return;
  		}

  		if (isset($checkout_info['issuer_id']) && !empty($checkout_info['issuer_id'])) {
  			$issuer_id = (integer) ($checkout_info['issuer_id']);
  		}
  		if (isset($checkout_info['payment_method_id']) && !empty($checkout_info['payment_method_id'])) {
  			$payment_method_id = $checkout_info['payment_method_id'];
  		}

  		try {
			$this->mp->create_card_in_customer($custId, $token, $payment_method_id, $issuer_id);
		} catch (MercadoPagoException $e) {
			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[check_and_save_customer_card] - card creation failed: ' .
					json_encode(array('status' => $e->getCode(), 'message' => $e->getMessage()))
				);
			}
		}

	}

	/**
	 * Summary: Receive post data and applies a discount based in the received values.
	 * Description: Receive post data and applies a discount based in the received values.
	 */
	public function add_discount_custom() {

		if (!isset($_POST['mercadopago_custom']))
			return;

		if (is_admin() && ! defined('DOING_AJAX') || is_cart()) {
			return;
		}

		$mercadopago_custom = $_POST['mercadopago_custom'];
		if (isset($mercadopago_custom['discount']) && $mercadopago_custom['discount'] != '' &&
    		$mercadopago_custom['discount'] > 0 && isset($mercadopago_custom['coupon_code']) &&
  			$mercadopago_custom['coupon_code'] != '' &&
			WC()->session->chosen_payment_method == 'woocommerce-mercadopago-custom-module') {

			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[add_discount_custom] - custom checkout trying to apply discount...'
				);
			}

			$value = ($mercadopago_custom['discount']) /
				((float) $this->currency_ratio > 0 ? (float) $this->currency_ratio : 1);
			global $woocommerce;
			if (apply_filters(
				'wc_mercadopagocustom_module_apply_discount',
				0 < $value, $woocommerce->cart)
			) {
				$woocommerce->cart->add_fee(sprintf(
					__('Discount for %s coupon', 'woocommerce-mercadopago-module'),
					esc_attr($mercadopago_custom['campaign']
					)), ($value * -1), true
				);
			}
		}

	}

	/*
	 * ========================================================================
	 * AUXILIARY AND FEEDBACK METHODS (SERVER SIDE)
	 * ========================================================================
	 */

	/**
	 * Summary: Check if we have valid credentials.
	 * Description: Check if we have valid credentials.
	 * @return boolean true/false depending on the validation result.
	 */
	public function validate_credentials() {

		if (empty($this->public_key) || empty($this->access_token))
			return false;

		try {

			$this->mp = new MP(
				WC_WooMercadoPago_Module::get_module_version(),
				$this->get_option('access_token')
			);
			$get_request = $this->mp->get(
				'/users/me?access_token=' . $this->get_option('access_token')
			);

			if (isset($get_request['response']['site_id'])) {

				$this->is_test_user = in_array('test_user', $get_request['response']['tags']);
				$this->site_id = $get_request['response']['site_id'];
				$this->country_configs = WC_WooMercadoPago_Module::get_country_config($this->site_id);

				// check for auto converstion of currency (only if it is enabled)
				$this->currency_ratio = -1;
				if ($this->currency_conversion == 'yes') {
					$this->currency_ratio = WC_WooMercadoPago_Module::get_conversion_rate(
						$this->country_configs['currency']
					);
				}

				return true;

			} else {
				$this->mp = null;
				return false;
			}

		} catch (MercadoPagoException $e) {
			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[validate_credentials] - while validating credentials, got exception: ' .
					json_encode(array('status' => $e->getCode(), 'message' => $e->getMessage()))
				);
			}
			$this->mp = null;
			return false;
		}

		return false;

	}

	// Build the string representing the path to the log file
	protected function build_log_path_string() {
		return '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' .
			esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.log')) . '">' .
			__('WooCommerce &gt; System Status &gt; Logs', 'woocommerce-mercadopago-module') . '</a>';
	}

	// Return boolean indicating if currency is supported
	protected function is_supported_currency() {
		return get_woocommerce_currency() == $this->country_configs['currency'];
	}

	// Called automatically by WooCommerce, verify if Module is available to use
	public function is_available() {
		if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
			if (empty($this->sandbox) && $this->sandbox == 'no') {
				return false;
			}
		}
		$available = ('yes' == $this->settings['enabled']) &&
			!empty($this->public_key) &&
			!empty($this->access_token);
		return $available;
	}

	public function check_ssl_absence() {
		if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') {
			if ('yes' == $this->settings['enabled']) {
				echo '<div class="error"><p><strong>' .
					__('Custom Checkout is Inactive', 'woocommerce-mercadopago-module') .
					'</strong>: ' .
					sprintf(
						__('Your site appears to not have SSL certification. SSL is a pre-requisite because the payment process is made in your server.', 'woocommerce-mercadopago-module')
					) . '</p></div>';
			}
		}
	}

	// Get the URL to admin page
	protected function admin_url() {
		if (defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=')) {
			return admin_url(
				'admin.php?page=wc-settings&tab=checkout&section=wc_woomercadopagocustom_gateway'
			);
		}
		return admin_url(
			'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_WooMercadoPagoCustom_Gateway'
		);
	}

	// Notify that public_key and/or access_token are not valid
	public function credentials_missing_message() {
		echo '<div class="error"><p><strong>' .
			__('Custom Checkout is Inactive', 'woocommerce-mercadopago-module') .
			'</strong>: ' .
			__('Your Mercado Pago credentials Public Key/Access Token appears to be misconfigured.', 'woocommerce-mercadopago-module') .
			'</p></div>';
	}

	public function get_order_status($status_detail) {
		switch ($status_detail) {
			case 'accredited':
				return __('Done, your payment was accredited!', 'woocommerce-mercadopago-module');
			case 'pending_contingency':
				return __('We are processing the payment. In less than an hour we will e-mail you the results.', 'woocommerce-mercadopago-module');
			case 'pending_review_manual':
				return __('We are processing the payment. In less than 2 business days we will tell you by e-mail whether it has accredited or we need more information.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_bad_filled_card_number':
				return __('Check the card number.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_bad_filled_date':
				return __('Check the expiration date.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_bad_filled_other':
				return __('Check the information.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_bad_filled_security_code':
				return __('Check the security code.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_blacklist':
				return __('We could not process your payment.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_call_for_authorize':
				return __('You must authorize the payment of your orders.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_card_disabled':
				return __('Call your card issuer to activate your card. The phone is on the back of your card.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_card_error':
				return __('We could not process your payment.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_duplicated_payment':
				return __('You already made a payment for that amount. If you need to repay, use another card or other payment method.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_high_risk':
				return __('Your payment was rejected. Choose another payment method. We recommend cash.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_insufficient_amount':
				return __('Your payment do not have sufficient funds.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_invalid_installments':
				return __('Your payment does not process payments with selected installments.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_max_attempts':
				return __('You have reached the limit of allowed attempts. Choose another card or another payment method.', 'woocommerce-mercadopago-module');
			case 'cc_rejected_other_reason':
				return __('This payment method did not process the payment.', 'woocommerce-mercadopago-module');
			default:
				return __('This payment method did not process the payment.', 'woocommerce-mercadopago-module');
		}
	}

	/*
	 * ========================================================================
	 * IPN MECHANICS (SERVER SIDE)
	 * ========================================================================
	 */

	/**
	 * Summary: This call checks any incoming notifications from Mercado Pago server.
	 * Description: This call checks any incoming notifications from Mercado Pago server.
	 */
	public function process_http_request() {
		@ob_clean();
		if ('yes' == $this->debug) {
			$this->log->add(
				$this->id,
				'[process_http_request] - Received _get content: ' .
				json_encode($_GET, JSON_PRETTY_PRINT)
			);
		}
		if (isset($_GET['coupon_id']) && $_GET['coupon_id'] != '') {
			// process coupon evaluations
			if (isset($_GET['payer']) && $_GET['payer'] != '') {
				$logged_user_email = $_GET['payer'];
				$coupon_id = $_GET['coupon_id'];
			if ('yes' == $this->sandbox)
				$this->mp->sandbox_mode(true);
			else
				$this->mp->sandbox_mode(false);
				$response = $this->mp->check_discount_campaigns(
			   	$_GET['amount'],
			    	$logged_user_email,
			    	$coupon_id
				);
				header('HTTP/1.1 200 OK');
				header('Content-Type: application/json');
				echo json_encode($response);
			} else {
				$obj = new stdClass();
				$obj->status = 404;
				$obj->response = array(
					'message' =>
						__('Please, inform your email in billing address to use this feature', 'woocommerce-mercadopago-module'),
					'error' => 'payer_not_found',
					'status' => 404,
					'cause' => array()
				);
				header('HTTP/1.1 200 OK');
			   header('Content-Type: application/json');
				echo json_encode($obj);
			}
			exit(0);
		} else {
			// process IPN messages
			$data = $this->check_ipn_request_is_valid($_GET);
			if ($data) {
				header('HTTP/1.1 200 OK');
				do_action('valid_mercadopagocustom_ipn_request', $data);
			}
		}
	}

	/**
	 * Summary: Get received data from IPN and checks if its a merchant_order or a payment.
	 * Description: If we have these information, we return data to be processed by
	 * successful_request function.
	 * @return boolean indicating if it was successfuly processed.
	 */
	public function check_ipn_request_is_valid($data) {

		if (!isset($data['data_id']) || !isset($data['type'])) {
			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[check_ipn_request_is_valid] - data_id or type not set: ' .
					json_encode($data, JSON_PRETTY_PRINT)
				);
			}
			// at least, check if its a v0 ipn
			if (!isset($data['id']) || !isset($data['topic'])) {
				if ('yes' == $this->debug) {
					$this->log->add(
						$this->id,
						'[check_ipn_request_is_valid] - Mercado Pago Request failure: ' .
						json_encode($_GET, JSON_PRETTY_PRINT)
					);
				}
				wp_die(__('Mercado Pago Request Failure', 'woocommerce-mercadopago-module'));
			} else {
				header('HTTP/1.1 200 OK');
			}
			// No ID? No process!
			return false;
		}

		if ('yes' == $this->sandbox) {
			$this->mp->sandbox_mode(true);
		} else {
			$this->mp->sandbox_mode(false);
		}

		try {
			// Get the payment reported by the IPN
			if ($data['type'] == 'payment') {
				$payment_info = $this->mp->get(
					'/v1/payments/' . $data['data_id'], $this->access_token, false
				);
				if (!is_wp_error($payment_info) &&
					($payment_info['status'] == 200 || $payment_info['status'] == 201)) {
					return $payment_info['response'];
				} else {
					if ('yes' == $this->debug) {
						$this->log->add(
							$this->id,
							'[check_ipn_request_is_valid] - error when processing received data: ' .
							json_encode($payment_info, JSON_PRETTY_PRINT)
						);
					}
					return false;
				}
			}
		} catch (MercadoPagoException $e) {
			if ('yes' == $this->debug) {
				$this->log->add(
					$this->id,
					'[check_ipn_request_is_valid] - MercadoPagoException: ' .
					json_encode(array('status' => $e->getCode(), 'message' => $e->getMessage()))
				);
			}
			return false;
		}
		return true;
	}

	/**
	 * Summary: Properly handles each case of notification, based in payment status.
	 * Description: Properly handles each case of notification, based in payment status.
	 */
	public function successful_request($data) {

		if ('yes' == $this->debug) {
			$this->log->add(
				$this->id,
				'[successful_request] - starting to process ipn update...'
			);
		}

		$order_key = $data['external_reference'];
		if (!empty($order_key)) {
			$order_id = (int) str_replace($this->invoice_prefix, '', $order_key);
			$order = new WC_Order($order_id);
			// Checks whether the invoice number matches the order, if true processes the payment
			if ($order->id === $order_id) {
				if ('yes' == $this->debug) {
					$this->log->add(
						$this->id,
						'[successful_request] - got order with ID ' . $order->id . ' and data: ' .
						json_encode($data, JSON_PRETTY_PRINT)
					);
				}
				// Order details
				if (!empty($data['payer']['email'])) {
					update_post_meta(
						$order_id,
						__('Payer email', 'woocommerce-mercadopago-module'),
						$data['payer']['email']
					);
				}
				if (!empty($data['payment_type_id'])) {
					update_post_meta(
						$order_id,
						__('Payment type', 'woocommerce-mercadopago-module'),
						$data['payment_type_id']
					);
				}
				if (!empty($data)) {
					update_post_meta(
						$order_id,
						__('Mercado Pago Payment ID', 'woocommerce-mercadopago-module'),
						$data['id']
					);
				}
				// Switch the status and update in WooCommerce
				switch ($data['status']) {
					case 'approved':
						$order->add_order_note(
							'Mercado Pago: ' . __('Payment approved.', 'woocommerce-mercadopago-module')
						);
						$this->check_and_save_customer_card($data);
						$order->payment_complete();
						break;
					case 'pending':
						$order->add_order_note(
							'Mercado Pago: ' . __('Customer haven\'t paid yet.', 'woocommerce-mercadopago-module')
						);
						break;
					case 'in_process':
						$order->update_status(
							'on-hold',
							'Mercado Pago: ' . __('Payment under review.', 'woocommerce-mercadopago-module')
						);
						break;
					case 'rejected':
						$order->update_status(
							'failed',
							'Mercado Pago: ' .
								__('The payment was refused. The customer can try again.', 'woocommerce-mercadopago-module')
						);
						break;
					case 'refunded':
						$order->update_status(
							'refunded',
							'Mercado Pago: ' .
								__('The payment was refunded to the customer.', 'woocommerce-mercadopago-module')
						);
						break;
					case 'cancelled':
						$order->update_status(
							'cancelled',
							'Mercado Pago: ' .
								__('The payment was cancelled.', 'woocommerce-mercadopago-module')
						);
						break;
					case 'in_mediation':
						$order->add_order_note(
							'Mercado Pago: ' .
								__('The payment is under mediation or it was charged-back.', 'woocommerce-mercadopago-module')
						);
						break;
					case 'charged-back':
						$order->add_order_note(
							'Mercado Pago: ' .
								__('The payment is under mediation or it was charged-back.', 'woocommerce-mercadopago-module')
						);
						break;
					default:
						break;
				}
			}
		}
	}

}

new WC_WooMercadoPagoCustom_Gateway(true);
