<?php

/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer - Marcelo Tomio Hama / marcelo.hama@mercadolivre.com
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// This include Mercado Pago library SDK
require_once dirname( __FILE__ ) . '/sdk/lib/mercadopago.php';

/**
 * Summary: Extending from WooCommerce Payment Gateway class.
 * Description: This class implements Mercado Pago custom checkout.
 * @since 2.0.0
 */
class WC_WooMercadoPago_CustomGateway extends WC_Payment_Gateway {

	public function __construct() {
		
		// WooCommerce fields.
		$this->mp = null;
		$this->id = 'woo-mercado-pago-custom';
		$this->supports = array( 'products', 'refunds' );
		$this->icon = apply_filters(
			'woocommerce_mercadopago_icon',
			plugins_url( 'assets/images/mplogo.png', plugin_dir_path( __FILE__ ) )
		);
		$this->method_title = __( 'Mercado Pago - Custom Checkout', 'woo-mercado-pago-module' );
		$this->method_description = '<img width="200" height="52" src="' .
			plugins_url( 'assets/images/mplogo.png', plugin_dir_path( __FILE__ ) ) .
		'"><br><br><strong>' .
			__( 'We give you the possibility to adapt the payment experience you want to offer 100% in your website, mobile app or anywhere you want. You can build the design that best fits your business model, aiming to maximize conversion.', 'woo-mercado-pago-module' ) .
		'</strong>';

		// Mercao Pago instance.
		$this->mp = new MP(
			WC_Woo_Mercado_Pago_Module::get_module_version(),
			get_option( '_mp_access_token' )
		);

		// How checkout is shown.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		/*
		// How checkout redirections will behave.
		$this->auto_return        = $this->get_option( 'auto_return', 'yes' );
		$this->success_url        = $this->get_option( 'success_url', '' );
		$this->failure_url        = $this->get_option( 'failure_url', '' );
		$this->pending_url        = $this->get_option( 'pending_url', '' );
		// How checkout payment behaves.
		$this->coupon_mode        = $this->get_option( 'coupon_mode' );
		$this->binary_mode        = $this->get_option( 'binary_mode' );
		$this->gateway_discount   = $this->get_option( 'gateway_discount', 0 );

		// Logging and debug.
		if ( ! empty ( get_option( '_mp_debug_mode', '' ) ) ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = WC_Woo_Mercado_Pago_Module::woocommerce_instance()->logger();
			}
		}

		// Render our configuration page and init/load fields.
		$this->init_form_fields();
		$this->init_settings();

		// Used in settings page to hook "save settings" action.
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'custom_process_admin_options' )
		);*/

	}

	/**
	 * Summary: Initialise Gateway Settings Form Fields.
	 * Description: Initialise Gateway settings form fields with a customized page.
	 */
	/*public function init_form_fields() {

		// Show message if credentials are not properly configured.
		if ( empty( get_option( '_site_id_v0', '' ) ) ) {
			$this->form_fields = array(
				'no_credentials_title' => array(
					'title' => sprintf(
						__( 'It appears that your credentials are not properly configured.<br/>Please, go to %s and configure it.', 'woo-mercado-pago-module' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=mercado-pago-settings' ) ) . '">' .
						__( 'Mercado Pago Settings', 'woocommerce-mercadopago-module' ) .
						'</a>'
					),
					'type' => 'title'
				),
			);
			return;
		}

		$this->two_cards_mode = $this->mp->check_two_cards();

		// Validate back URL.
		if ( ! empty( $this->success_url ) && filter_var( $this->success_url, FILTER_VALIDATE_URL ) === FALSE ) {
			$success_back_url_message = '<img width="14" height="14" src="' . plugins_url( 'assets/images/warning.png', plugin_dir_path( __FILE__ ) ) . '"> ' .
			__( 'This appears to be an invalid URL.', 'woo-mercado-pago-module' ) . ' ';
		} else {
			$success_back_url_message = __( 'Where customers should be redirected after a successful purchase. Let blank to redirect to the default store order resume page.', 'woo-mercado-pago-module' );
		}
		if ( ! empty( $this->failure_url ) && filter_var( $this->failure_url, FILTER_VALIDATE_URL ) === FALSE ) {
			$fail_back_url_message = '<img width="14" height="14" src="' . plugins_url( 'assets/images/warning.png', plugin_dir_path( __FILE__ ) ) . '"> ' .
			__( 'This appears to be an invalid URL.', 'woo-mercado-pago-module' ) . ' ';
		} else {
			$fail_back_url_message = __( 'Where customers should be redirected after a failed purchase. Let blank to redirect to the default store order resume page.', 'woo-mercado-pago-module' );
		}
		if ( ! empty( $this->pending_url ) && filter_var( $this->pending_url, FILTER_VALIDATE_URL ) === FALSE ) {
			$pending_back_url_message = '<img width="14" height="14" src="' . plugins_url( 'assets/images/warning.png', plugin_dir_path( __FILE__ ) ) . '"> ' .
			__( 'This appears to be an invalid URL.', 'woo-mercado-pago-module' ) . ' ';
		} else {
			$pending_back_url_message = __( 'Where customers should be redirected after a pending purchase. Let blank to redirect to the default store order resume page.', 'woo-mercado-pago-module' );
		}

		// This array draws each UI (text, selector, checkbox, label, etc).
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woo-mercado-pago-module' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Basic Checkout', 'woo-mercado-pago-module' ),
				'default' => 'no'
			),
			'checkout_options_title' => array(
				'title' => __( '--- Checkout Interface: How checkout is shown ---', 'woo-mercado-pago-module' ),
				'type' => 'title'
			),
			'title' => array(
				'title' => __( 'Title', 'woo-mercado-pago-module' ),
				'type' => 'text',
				'description' =>
					__( 'Title shown to the client in the checkout.', 'woo-mercado-pago-module' ),
				'default' => __( 'Mercado Pago', 'woo-mercado-pago-module' )
			),
			'description' => array(
				'title' => __( 'Description', 'woo-mercado-pago-module' ),
				'type' => 'textarea',
				'description' =>
					__( 'Description shown to the client in the checkout.', 'woo-mercado-pago-module' ),
				'default' => __( 'Pay with Mercado Pago', 'woo-mercado-pago-module' )
			),
			'method' => array(
				'title' => __( 'Integration Method', 'woo-mercado-pago-module' ),
				'type' => 'select',
				'description' => __( 'Select how your clients should interact with Mercado Pago. Modal Window (inside your store), Redirect (Client is redirected to Mercado Pago), or iFrame (an internal window is embedded to the page layout).', 'woo-mercado-pago-module' ),
				'default' => 'iframe',
				'options' => array(
					'iframe' => __( 'iFrame', 'woo-mercado-pago-module' ),
					'modal' => __( 'Modal Window', 'woo-mercado-pago-module' ),
					'redirect' => __( 'Redirect', 'woo-mercado-pago-module' )
				)
			),
			'iframe_width' => array(
				'title' => __( 'iFrame Width', 'woo-mercado-pago-module' ),
				'type' => 'text',
				'description' => __( 'If your integration method is iFrame, please inform the payment iFrame width.', 'woo-mercado-pago-module' ),
				'default' => '640'
			),
			'iframe_height' => array(
				'title' => __( 'iFrame Height', 'woo-mercado-pago-module' ),
				'type' => 'text',
				'description' => __( 'If your integration method is iFrame, please inform the payment iFrame height.', 'woo-mercado-pago-module' ),
				'default' => '800'
			),
			'checkout_navigation_title' => array(
				'title' => __( '---  Checkout Navigation: How checkout redirections will behave ---', 'woo-mercado-pago-module' ),
				'type' => 'title'
			),
			'auto_return' => array(
				'title' => __( 'Auto Return', 'woo-mercado-pago-module' ),
				'type' => 'checkbox',
				'label' => __( 'Automatic Return After Payment', 'woo-mercado-pago-module' ),
				'default' => 'yes',
				'description' =>
					__( 'After the payment, client is automatically redirected.', 'woo-mercado-pago-module' ),
			),
			'success_url' => array(
				'title' => __( 'Sucess URL', 'woo-mercado-pago-module' ),
				'type' => 'text',
				'description' => $success_back_url_message,
				'default' => ''
			),
			'failure_url' => array(
				'title' => __( 'Failure URL', 'woo-mercado-pago-module' ),
				'type' => 'text',
				'description' => $fail_back_url_message,
				'default' => ''
			),
			'pending_url' => array(
				'title' => __( 'Pending URL', 'woo-mercado-pago-module' ),
				'type' => 'text',
				'description' => $pending_back_url_message,
				'default' => ''
			),
			'payment_title' => array(
				'title' => __( '--- Payment Options: How payment options behaves ---', 'woo-mercado-pago-module' ),
				'type' => 'title'
			),
			'installments' => array(
				'title' => __( 'Max installments', 'woo-mercado-pago-module' ),
				'type' => 'select',
				'description' => __( 'Select the max number of installments for your customers.', 'woo-mercado-pago-module' ),
				'default' => '24',
				'options' => array(
					'1' => '1x installment',
					'2' => '2x installmens',
					'3' => '3x installmens',
					'4' => '4x installmens',
					'5' => '5x installmens',
					'6' => '6x installmens',
					'10' => '10x installmens',
					'12' => '12x installmens',
					'15' => '15x installmens',
					'18' => '18x installmens',
					'24' => '24x installmens'
				)
			),
			'ex_payments' => array(
				'title' => __( 'Exclude Payment Methods', 'woo-mercado-pago-module' ),
				'description' => __( 'Select the payment methods that you <strong>don\'t</strong> want to receive with Mercado Pago.', 'woo-mercado-pago-module' ),
				'type' => 'multiselect',
				'options' => explode( ',', get_option( '_all_payment_methods_v0', '' ) ),
				'default' => ''
			),
			'gateway_discount' => array(
				'title' => __( 'Discount by Gateway', 'woo-mercado-pago-module' ),
				'type' => 'number',
				'description' => __( 'Give a percentual (0 to 100) discount for your customers if they use this payment gateway.', 'woo-mercado-pago-module' ),
				'default' => '0'
			),
			'two_cards_mode' => array(
				'title' => __( 'Two Cards Mode', 'woo-mercado-pago-module' ),
				'type' => 'checkbox',
				'label' => __( 'Payments with Two Cards', 'woo-mercado-pago-module' ),
				'default' => ( $this->two_cards_mode == 'active' ? 'yes' : 'no' ),
				'description' =>
					__( 'Your customer will be able to use two different cards to pay the order.', 'woo-mercado-pago-module' )
			)
		);

	}*/

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the
	 * erroring field out.
	 * @return bool was anything saved?
	 */
	/*public function custom_process_admin_options() {
		$this->init_settings();
		$post_data = $this->get_post_data();
		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				if ( $key == 'two_cards_mode' ) {
					// We dont save two card mode as it should come from api.
					$value = $this->get_field_value( $key, $field, $post_data );
					$this->two_cards_mode = ( $value == 'yes' ? 'active' : 'inactive' );
				} elseif ( $key == 'iframe_width' ) {
					$value = $this->get_field_value( $key, $field, $post_data );
					if ( ! is_numeric( $value ) || empty ( $value ) ) {
						$this->settings[$key] = '480';
					} else {
						$this->settings[$key] = $value;
					}
				} elseif ( $key == 'iframe_height' ) {
					if ( ! is_numeric( $value ) || empty ( $value ) ) {
						$this->settings[$key] = '800';
					} else {
						$this->settings[$key] = $value;
					}
				} elseif ( $key == 'gateway_discount') {
					$value = $this->get_field_value( $key, $field, $post_data );
					if ( $value < 0 || $value > 100 || empty ( $value ) ) {
						$this->settings[$key] = 0;
					} else {
						$this->settings[$key] = $value;
					}
				} else {
					$this->settings[$key] = $this->get_field_value( $key, $field, $post_data );
				}
			}
		}
		if ( ! empty( get_option( '_site_id_v0', '' ) ) ) {
			// Create MP instance.
			$mp = new MP(
				WC_Woo_Mercado_Pago_Module::get_module_version(),
				get_option( '_mp_client_id' ),
				get_option( '_mp_client_secret' )
			);
			// Analytics.
			$infra_data = WC_Woo_Mercado_Pago_Module::get_common_settings();
			$infra_data['checkout_basic'] = ( $this->settings['enabled'] == 'yes' ? 'true' : 'false' );
			$infra_data['two_cards'] = ( $this->two_cards_mode == 'active' ? 'true' : 'false' );
			$response = $mp->analytics_save_settings( $infra_data );
			// Two cards mode.
			$response = $mp->set_two_cards_mode( $this->two_cards_mode );
		}
		// Apply updates.
		return update_option(
			$this->get_option_key(),
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings )
		);
	}*/

}