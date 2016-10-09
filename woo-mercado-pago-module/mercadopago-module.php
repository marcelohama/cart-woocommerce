<?php
/**
 * Plugin Name: Woo Mercado Pago Module
 * Plugin URI: https://github.com/mercadopago/cart-woocommerce
 * Description: This is the <strong>oficial</strong> module of Mercado Pago for WooCommerce plugin. This module enables WooCommerce to use Mercado Pago as a payment Gateway for purchases made in your e-commerce store.
 * Author: Mercado Pago
 * Author URI: https://www.mercadopago.com.br/developers/
 * Developer: Marcelo Tomio Hama / marcelo.hama@mercadolivre.com
 * Copyright: Copyright(c) MercadoPago [https://www.mercadopago.com]
 * Version: 2.1.3
 * License: https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * Text Domain: woocommerce-mercadopago-module
 * Domain Path: /languages/
 */

// exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

require_once 'mercadopago/sdk/lib/mercadopago.php';

// load module class if it wasn't loaded yet
if (!class_exists('WC_WooMercadoPago_Module')) {

	/**
	 * Summary: WooCommerce MercadoPago Module main class.
	 * Description: Used as a kind of manager to enable/disable each Mercado Pago gateway.
	 * @since 1.0.0
	 */
	class WC_WooMercadoPago_Module {

		private $store_categories_id = array();
  		private $store_categories_description = array();

		// Singleton design pattern
		protected static $instance = null;
		public static function initMercadoPagoGatewayClass() {
			if (null == self::$instance) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		// Class constructor
		private function __construct() {

			add_action('init', array($this, 'load_plugin_textdomain'));

			// verify if WooCommerce is already installed
			if (class_exists('WC_Payment_Gateway')) {

				include_once 'mercadopago/mercadopago-gateway.php';
				include_once 'mercadopago/mercadopago-custom-gateway.php';
				include_once 'mercadopago/mercadopago-ticket-gateway.php';
				add_filter('woocommerce_payment_gateways', array($this, 'addGateway'));
				add_filter(
					'woomercadopago_settings_link_' . plugin_basename(__FILE__),
					array($this, 'woomercadopago_settings_link'));

				// get Mercado Pago store categories
				$categories = MPRestClient::get(array('uri' => '/item_categories'));
				foreach ($categories['response'] as $category) {
					array_push($this->store_categories_id, str_replace('_', ' ', $category['id']));
					array_push($this->store_categories_description, str_replace('_', ' ', $category['description']));
				}

			} else {
				add_action('admin_notices', array($this, 'notifyWooCommerceMiss'));
			}

		}

		// As well as defining your class, you need to also tell WooCommerce (WC) that
		// it exists. Do this by filtering woocommerce_payment_gateways.
		public function addGateway( $methods ) {
			$methods[] = 'WC_WooMercadoPago_Gateway';
			$methods[] = 'WC_WooMercadoPagoCustom_Gateway';
			$methods[] = 'WC_WooMercadoPagoTicket_Gateway';
			return $methods;
		}

		/**
		 * Summary: Places a warning error to notify user that WooCommerce is missing.
		 * Description: Places a warning error to notify user that WooCommerce is missing.
		 */
		public function notifyWooCommerceMiss() {
			echo
				'<div class="error"><p>' . sprintf(
					__('Woo Mercado Pago Module depends on the last version of %s to execute!', 'woocommerce-mercadopago-module'),
					'<a href="https://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>'
				) .
				'</p></div>';
		}

		/**
		 * Summary: Get store categories from Mercado Pago.
		 * Description: Trigger API to get available categories and proper description.
		 * @return an array with found categories and a description for its selector title.
		 */
		public function getCategories() {
			return array(
				'store_categories_id' => $this->store_categories_id,
				'store_categories_description' => $this->store_categories_description
			);
		}

		// Multi-language plugin
		public function load_plugin_textdomain() {
			$locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-mercadopago-module');
			$module_root = 'woocommerce-mercadopago-module/woocommerce-mercadopago-module-';
			load_textdomain(
				'woocommerce-mercadopago-module',
				trailingslashit(WP_LANG_DIR) . $module_root . $locale . '.mo'
			);
			load_plugin_textdomain(
				'woocommerce-mercadopago-module',
				false,
				dirname(plugin_basename(__FILE__)) . '/languages/'
			);
		}

		/**
		 * Summary: Get the rate of conversion between two currencies.
		 * Description: The currencies are the one used in WooCommerce and the one used in $site_id.
		 * @return a float that is the rate of conversion.
		 */
		public function getConversionRate($used_currency) {
			$currency_obj = MPRestClient::get_ml(
				array('uri' => '/currency_conversions/search?' .
					'from=' . get_woocommerce_currency() .
					'&to=' . $used_currency
				)
			);
			if (isset($currency_obj['response'])) {
				$currency_obj = $currency_obj['response'];
				if (isset($currency_obj['ratio'])) {
					return ((float) $currency_obj['ratio']);
				}
			}
			return -1;
		}

		// Get WooCommerce instance
		public static function woocommerceInstance() {
			if (function_exists('WC')) {
				return WC();
			} else {
				global $woocommerce;
				return $woocommerce;
			}
		}

		/**
		 * Summary: Find template's folder.
		 * Description: Find template's folder.
		 * @return a string that identifies the path.
		 */
		public static function getTemplatesPath() {
			return plugin_dir_path(__FILE__) . 'templates/';
		}

		/**
		 * Summary: Get module's version.
		 * Description: Get module's version.
		 * @return a string with the given version.
		 */
		public static function getModuleVersion() {
			$plugin_data = get_plugin_data(__FILE__);
			return $plugin_data['Version'];
		}

		/**
		 * Summary: Get preference data for a specific country.
		 * Description: Get preference data for a specific country.
		 * @return an array with sponsor id, country name, banner image for checkout, and currency.
		 */
		public static function getCountryConfig($site_id) {
			switch ($site_id) {
				case 'MLA':
					return array(
						'sponsor_id' => 208682286,
						'country_name' => __('Argentine', 'woocommerce-mercadopago-module'),
						'checkout_banner' => plugins_url(
							'images/MLA/standard_mla.jpg', plugin_dir_path(__FILE__)
						),
						'currency' => 'ARS'
					);
				case 'MLB':
					return array(
						'sponsor_id' => 208686191,
						'country_name' => __('Brazil', 'woocommerce-mercadopago-module'),
						'checkout_banner' => plugins_url(
							'images/MLB/standard_mlb.jpg', plugin_dir_path(__FILE__)
						),
						'currency' => 'BRL'
					);
				case 'MCO':
					return array(
						'sponsor_id' => 208687643,
						'country_name' => __('Colombia', 'woocommerce-mercadopago-module'),
						'checkout_banner' => plugins_url(
							'images/MCO/standard_mco.jpg', plugin_dir_path(__FILE__)
						),
						'currency' => 'COP'
					);
				case 'MLC':
					return array(
						'sponsor_id' => 208690789,
						'country_name' => __('Chile', 'woocommerce-mercadopago-module'),
						'checkout_banner' => plugins_url(
							'images/MLC/standard_mlc.gif', plugin_dir_path(__FILE__)
						),
						'currency' => 'CLP'
					);
				case 'MLM':
					return array(
						'sponsor_id' => 208692380,
						'country_name' => __('Mexico', 'woocommerce-mercadopago-module'),
						'checkout_banner' => plugins_url(
							'images/MLM/standard_mlm.jpg', plugin_dir_path(__FILE__)
						),
						'currency' => 'MXN'
					);
				case 'MLV':
					return array(
						'sponsor_id' => 208692735,
						'country_name' => __('Venezuela', 'woocommerce-mercadopago-module'),
						'checkout_banner' => plugins_url(
							'images/MLV/standard_mlv.jpg', plugin_dir_path(__FILE__)
						),
						'currency' => 'VEF'
					);
				case 'MPE':
					return array(
						'sponsor_id' => 216998692,
						'country_name' => __('Peru', 'woocommerce-mercadopago-module'),
						'checkout_banner' => plugins_url(
							'images/MPE/standard_mpe.png', plugin_dir_path(__FILE__)
						),
						'currency' => 'PEN'
					);
				default: // set Argentina as default country
					return array(
						'sponsor_id' => 208682286,
						'country_name' => __('Argentine', 'woocommerce-mercadopago-module'),
						'checkout_banner' => plugins_url(
							'images/MLA/standard_mla.jpg', plugin_dir_path(__FILE__)
						),
						'currency' => 'ARS'
					);
			}
		}

		public static function buildCurrencyConversionErrMsg($currency) {
			return '<img width="12" height="12" src="' .
				plugins_url('images/error.png', plugin_dir_path(__FILE__)) . '">' .
				' ' . __('ERROR: It was not possible to convert the unsupported currency', 'woocommerce-mercadopago-module') .
				' ' . get_woocommerce_currency() .
				' '	. __('to', 'woocommerce-mercadopago-module') . ' ' . $currency . '.' .
				' ' . __('Currency conversions should be made outside this module.', 'woocommerce-mercadopago-module');
		}

		public static function buildCurrencyNotConvertedMsg($currency, $country_name) {
			return '<img width="12" height="12" src="' .
				plugins_url('images/warning.png', plugin_dir_path(__FILE__)) . '">' .
				' ' . __('ATTENTION: The currency', 'woocommerce-mercadopago-module') .
				' ' . get_woocommerce_currency() .
				' ' . __('defined in WooCommerce is different from the one used in your credentials country.<br>The currency for transactions in this payment method will be', 'woocommerce-mercadopago-module') .
				' ' . $currency . ' (' . $country_name . ').' .
				' ' . __('Currency conversions should be made outside this module.', 'woocommerce-mercadopago-module');
		}

		public static function buildCurrencyConvertedMsg($currency, $currency_ratio) {
			return '<img width="12" height="12" src="' .
				plugins_url( 'images/check.png', plugin_dir_path( __FILE__ ) ) . '">' .
				' ' . __( 'CURRENCY CONVERTED: The currency conversion ratio from', 'woocommerce-mercadopago-module' )  .
				' ' . get_woocommerce_currency() .
				' ' . __( 'to', 'woocommerce-mercadopago-module' ) . ' ' . $currency .
				__( ' is: ', 'woocommerce-mercadopago-module' ) . $currency_ratio . ".";
		}

	}

	// ==========================================================================================

	// Payment gateways should be created as additional plugins that hook into WooCommerce.
	// Inside the plugin, you need to create a class after plugins are loaded
	add_action(
		'plugins_loaded',
		array('WC_WooMercadoPago_Module', 'initMercadoPagoGatewayClass'), 0
	);

	// Add settings link on plugin page
	function woomercadopago_settings_link($links) {
		$plugin_links = array();
		$plugin_links[] = '<a href="' . esc_url(admin_url(
			'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPago_Gateway')) .
		'">' . __('Basic Checkout', 'woocommerce-mercadopago-module') . '</a>';
		$plugin_links[] = '<a href="' . esc_url(admin_url(
			'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoCustom_Gateway')) .
		'">' . __('Custom Checkout', 'woocommerce-mercadopago-module') . '</a>';
		$plugin_links[] = '<a href="' . esc_url(admin_url(
			'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoTicket_Gateway')) .
		'">' . __('Ticket', 'woocommerce-mercadopago-module') . '</a>';
		return array_merge($plugin_links, $links);
	}
	$plugin = plugin_basename(__FILE__);
	add_filter("plugin_action_links_$plugin", 'woomercadopago_settings_link');

}

?>
