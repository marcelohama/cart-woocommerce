<?php

/**
 * Plugin Name: Woo Mercado Pago Module
 * Plugin URI: https://github.com/mercadopago/cart-woocommerce
 * Description: This is the <strong>oficial</strong> module of Mercado Pago for WooCommerce plugin. This module enables WooCommerce to use Mercado Pago as a payment Gateway for purchases made in your e-commerce store.
 * Author: Mercado Pago
 * Author URI: https://www.mercadopago.com.br/developers/
 * Developer: Marcelo Tomio Hama / marcelo.hama@mercadolivre.com, Claudio Sanches / claudio@automattic.com
 * Copyright: Copyright(c) MercadoPago [https://www.mercadopago.com]
 * Version: 3.0.0
 * License: https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * Text Domain: woocommerce-mercadopago-module
 * Domain Path: /languages/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	
/**
 * Offline Payment Gateway
 *
 * Provides an Offline Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Gateway_Offline
 * @extends     WC_Payment_Gateway
 * @version     3.0.0
 * @package     MercadoPago/Classes/Gateway
 * @author      MercadoPago
 */
class WC_WooMercadoPagoBasic_Gateway extends WC_Payment_Gateway {

	public function __construct() {

		$this->id = 'woocommerce-mercadopago-module';
		$this->domain = get_site_url() . '/index.php';
		$this->icon = apply_filters(
			'woocommerce_mercadopago_icon',
			plugins_url( 'images/mercadopago.png', plugin_dir_path( __FILE__ ) )
		);
		$this->method_title = __( 'Mercado Pago - Basic Checkout', 'woocommerce-mercadopago-module' );
		$this->method_description = '<img width="200" height="52" src="' .
			plugins_url(
				'images/mplogo.png',
				plugin_dir_path( __FILE__ )
			) . '"><br><br>' . '<strong>' .
			__( 'This module enables WooCommerce to use Mercado Pago as payment method for purchases made in your virtual store.', 'woocommerce-mercadopago-module' ) .
			'</strong>';

		$this->init_form_fields();
		$this->init_settings();

		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
	
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce-mercadopago-module' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Basic Checkout', 'woocommerce-mercadopago-module' ),
				'default' => 'no'
			)
		);
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param	mixed $links Plugin Row Meta
	 * @param	mixed $file  Plugin Base file
	 * @return	array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( strpos( $file, 'plugin-file-name.php' ) !== false ) {
			$new_links = array(
				'donate' => '<a href="donation_url" target="_blank">Donate</a>',
				'doc' => '<a href="doc_url" target="_blank">Documentation</a>'
			);
			$links = array_merge( $links, $new_links );
		}
		return $links;
	}
	
	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the
	 * erroring field out.
	 * @return bool was anything saved?
	 */
	/*public function admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		return update_option(
			$this->get_option_key(),
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings )
		);
	}*/

	public function process_payment( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );
		$order->payment_complete();
		$order->reduce_order_stock();
		$woocommerce->cart->empty_cart();

		return array(
			'result' => 'success',
			//'redirect' => add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('thanks')))),
			'redirect' => $order->get_checkout_order_received_url()
		);
	}

}

?>