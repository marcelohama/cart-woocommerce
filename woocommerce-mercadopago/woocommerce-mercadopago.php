<?php
/**
 * Plugin Name: WooCommerce Mercado Pago
 * Plugin URI: https://github.com/mercadopago/cart-woocommerce
 * Description: This is the <strong>oficial</strong> module of Mercado Pago for WooCommerce plugin. This module enables WooCommerce to use Mercado Pago as a payment Gateway for purchases made in your e-commerce store.
 * Version: 3.0.0
 * Author: Mercado Pago
 * Author URI: https://www.mercadopago.com.br/developers/
 * Developer: Marcelo Tomio Hama / marcelo.hama@mercadolivre.com
 * Copyright: Copyright(c) MercadoPago [https://www.mercadopago.com]
 * Requires at least: 4.4
 * Tested up to: 4.7
 *
 * Text Domain: woocommerce-mercadopago
 * Domain Path: /i18n/languages/
 *
 * @package MercadoPago
 * @category Core
 * @author Mercado Pago
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/includes/sdk/lib/mercadopago.php';

// Load module class if it wasn't loaded yet.
if ( ! class_exists( 'WC_WooMercadoPago_Module' ) ) :

	/**
	 * Summary: WooCommerce MercadoPago Module main class.
	 * Description: Used as a kind of manager to enable/disable each Mercado Pago gateway.
	 * @since 1.0.0
	 */
	class WC_WooMercadoPago_Module {

		const VERSION = '3.0.0';

		public static $site_id = null;
		public static $country_configs = null;

		// Singleton design pattern
		protected static $instance = null;
		public static function init_mercado_pago_class() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		// Class constructor.
		private function __construct() {

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		}

		// Multi-language plugin.
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-mercadopago' );
			$module_root = 'woocommerce-mercadopago/woocommerce-mercadopago-';
			load_textdomain(
				'woocommerce-mercadopago',
				trailingslashit( WP_LANG_DIR ) . $module_root . $locale . '.mo'
			);
			load_plugin_textdomain(
				'woocommerce-mercadopago',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
		}

		/**
		 * Summary: Get module's version.
		 * Description: Get module's version.
		 * @return a string with the given version.
		 */
		public static function get_module_version() {
			return WC_WooMercadoPago_Module::VERSION;
		}

		/**
		 * Summary: Get preference data for a specific country.
		 * Description: Get preference data for a specific country.
		 * @return an array with sponsor id, country name, banner image for checkout, and currency.
		 */
		public static function get_country_config( $site_id ) {
			switch ( $site_id ) {
				case 'MLA':
					return array(
						'sponsor_id' => 208682286,
						'country_name' => __( 'Argentine', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MLA/standard_mla.jpg',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MLA/credit_card.png',
							__FILE__
						),
						'currency' => 'ARS'
					);
				case 'MLB':
					return array(
						'sponsor_id' => 208686191,
						'country_name' => __( 'Brazil', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MLB/standard_mlb.jpg',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MLB/credit_card.png',
							__FILE__
						),
						'currency' => 'BRL'
					);
				case 'MCO':
					return array(
						'sponsor_id' => 208687643,
						'country_name' => __( 'Colombia', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MCO/standard_mco.jpg',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MCO/credit_card.png',
							__FILE__
						),
						'currency' => 'COP'
					);
				case 'MLC':
					return array(
						'sponsor_id' => 208690789,
						'country_name' => __( 'Chile', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MLC/standard_mlc.gif',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MLC/credit_card.png',
							__FILE__
						),
						'currency' => 'CLP'
					);
				case 'MLM':
					return array(
						'sponsor_id' => 208692380,
						'country_name' => __( 'Mexico', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MLM/standard_mlm.jpg',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MLM/credit_card.png',
							__FILE__
						),
						'currency' => 'MXN'
					);
				case 'MLV':
					return array(
						'sponsor_id' => 208692735,
						'country_name' => __( 'Venezuela', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MLV/standard_mlv.jpg',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MLV/credit_card.png',
							__FILE__
						),
						'currency' => 'VEF'
					);
				case 'MPE':
					return array(
						'sponsor_id' => 216998692,
						'country_name' => __( 'Peru', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MPE/standard_mpe.png',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MPE/credit_card.png',
							__FILE__
						),
						'currency' => 'PEN'
					);
				case 'MLU':
					return array(
						'sponsor_id' => 243692679,
						'country_name' => __( 'Uruguay', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MLU/standard_mlu.png',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MLU/credit_card.png',
							__FILE__
						),
						'currency' => 'UYU'
					);
				default: // set Argentina as default country
					return array(
						'sponsor_id' => 208682286,
						'country_name' => __( 'Argentine', 'woocommerce-mercadopago' ),
						'checkout_banner' => plugins_url(
							'images/MLA/standard_mla.jpg',
							__FILE__
						),
						'checkout_banner_custom' => plugins_url(
							'images/MLA/credit_card.png',
							__FILE__
						),
						'currency' => 'ARS'
					);
			}
		}

		/**
		 * Summary: Check if we have valid credentials.
		 * Description: Check if we have valid credentials.
		 * @return boolean true/false depending on the validation result.
		 */
		public static function validate_credentials( $client_id, $client_secret ) {
			if ( empty( $client_id ) || empty( $client_secret ) ) {
				return false;
			}
			try {
				$mp = new MP( WC_WooMercadoPago_Module::get_module_version(), $client_id, $client_secret );
				$access_token = $mp->get_access_token();
				$get_request = $mp->get( '/users/me?access_token=' . $access_token );
				if ( isset( $get_request['response']['site_id'] ) ) {
					$is_test_user = in_array( 'test_user', $get_request['response']['tags'] );
					WC_WooMercadoPago_Module::$site_id = $get_request['response']['site_id'];
					$collector_id = $get_request['response']['id'];
					WC_WooMercadoPago_Module::$country_configs = WC_WooMercadoPago_Module::get_country_config( WC_WooMercadoPago_Module::$site_id );
					$payment_split_mode = $mp->check_two_cards();
					$payments = $mp->get( '/v1/payment_methods/?access_token=' . $access_token );
					/*array_push( $payment_methods, 'n/d' );
					foreach ( $payments['response'] as $payment ) {
						array_push( $payment_methods, str_replace( '_', ' ', $payment['id'] ) );
					}
					// Check for auto converstion of currency (only if it is enabled).
					$currency_ratio = -1;
					if ( $currency_conversion == 'yes' ) {
						$currency_ratio = WC_WooMercadoPago_Module::get_conversion_rate(
							WC_WooMercadoPago_Module::$country_configs['currency']
						);
					}*/
					return true;
				} else {
					$mp = null;
					return false;
				}
			} catch ( MercadoPagoException $e ) {
				$mp = null;
				return false;
			}
			return false;
		}

		public static function build_invalid_credentials_msg() {
			return '<img width="12" height="12" src="' .
				plugins_url( 'images/error.png', __FILE__ ) .
				'"> ' .
				__( 'Your credentials are <strong>not valid</strong>!', 'woocommerce-mercadopago' );
		}

		public static function build_valid_credentials_msg( $country_name, $site_id ) {
			return '<img width="12" height="12" src="' .
				plugins_url( 'images/check.png', __FILE__ ) .
				'"> ' .
				__( 'Your credentials are <strong>valid</strong> for', 'woocommerce-mercadopago' ) .
				': ' . $country_name . ' <img width="18.6" height="12" src="' . plugins_url(
					'images/' . $site_id . '/' . $site_id . '.png', __FILE__
				) . '"> ';
		}

	}

	add_action( 'admin_menu', 'mercadopago_plugin_menu' );

	function mercadopago_plugin_menu() {
		add_options_page(
			'My Plugin Options',
			'Mercado Pago',
			'manage_options',
			'my-unique-identifier',
			'mercadopago_plugin_options'
		);
	}

	function mercadopago_plugin_options() {

		if ( ! current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$title = __( 'Mercado Pago Settings', 'woocommerce-mercadopago' );
		$header = '<br><img width="200" height="52" src="' .
			plugins_url( 'images/mplogo.png', __FILE__ ) . '"><br><br>' . '<strong>' .
			__( 'This module enables WooCommerce to use Mercado Pago as payment method for purchases made in your virtual store.', 'woocommerce-mercadopago' ) .
			'</strong>';

		if ( isset( $_POST['submit'] ) ) {
			if ( isset( $_POST['client_id'] ) ) {
				update_option( '_mp_client_id', $_POST['client_id'], true );
			}
			if ( isset( $_POST['client_secret'] ) ) {
				update_option( '_mp_client_secret', $_POST['client_secret'], true );
			}
		}

		// Trigger API to get payment methods and site_id, also validates Client_id/Client_secret.
		if ( WC_WooMercadoPago_Module::validate_credentials( get_option( '_mp_client_id', true ), get_option( '_mp_client_secret', true ) ) ) {
			// Checking the currency.
			/*$currency_message = '';
			if ( ! $is_supported_currency() && 'yes' == $settings['enabled'] ) {
				if ( $currency_conversion == 'no' ) {
					$currency_ratio = -1;
					$currency_message .= WC_WooMercadoPago_Module::build_currency_not_converted_msg(
						WC_WooMercadoPago_Module::$country_configs['currency'],
						WC_WooMercadoPago_Module::$country_configs['country_name']
					);
				} elseif ( $currency_conversion == 'yes' && $currency_ratio != -1) {
					$currency_message .= WC_WooMercadoPago_Module::build_currency_converted_msg(
						WC_WooMercadoPago_Module::$country_configs['currency'],
						$currency_ratio
					);
				} else {
					$this->currency_ratio = -1;
					$this->currency_message .= WC_WooMercadoPago_Module::build_currency_conversion_err_msg(
						WC_WooMercadoPago_Module::$this->country_configs['currency']
					);
				}
			} else {
				$this->currency_ratio = -1;
			}*/
			$credentials_message = WC_WooMercadoPago_Module::build_valid_credentials_msg(
				WC_WooMercadoPago_Module::$country_configs['country_name'],
				WC_WooMercadoPago_Module::$site_id
			);
			$payment_desc =
				__( 'Select the payment methods that you <strong>don\'t</strong> want to receive with Mercado Pago.', 'woocommerce-mercadopago' );
		} else {
			//array_push( $payment_methods, 'n/d' );
			$credentials_message = WC_WooMercadoPago_Module::build_invalid_credentials_msg();
			$payment_desc = '<img width="12" height="12" src="' .
				plugins_url( 'images/warning.png', plugin_dir_path( __FILE__ ) ) . '">' . ' ' .
				__( 'Configure your Client_id and Client_secret to have access to more options.', 'woocommerce-mercadopago' );
		}

		?>

		<div class="wrap">

			<h1><?php echo esc_html( $title ); ?></h1>
			
			<?php echo $header; ?>

			<form method="post" action="" novalidate="novalidate" method="post">

				<?php settings_fields( 'mercadopago' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><label for="client_id">Client ID</label></th>
						<td>
							<input name="client_id" type="text" id="client_id" value="<?php form_option('_mp_client_id'); ?>" class="regular-text" />
							<p class="description" id="tagline-description"><?php echo esc_html( __( 'Insert your Mercado Pago Client_id.', 'woocommerce-mercadopago' ) ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="client_secret">Client Secret</label></th>
						<td>
							<input name="client_secret" type="text" id="client_secret" aria-describedby="tagline-description" value="<?php form_option('_mp_client_secret'); ?>" class="regular-text" />
							<p class="description" id="tagline-description"><?php echo esc_html( __( 'Insert your Mercado Pago Client_secret.', 'woocommerce-mercadopago' ) ); ?></p>
						</td>
					</tr>

					<?php echo $credentials_message; ?>

				</table>

				<?php do_settings_sections( 'mercadopago' ); ?>

				<?php submit_button(); ?>

			</form>

		</div>

		<?php

	}

	// Payment gateways should be created as additional plugins that hook into WooCommerce.
	// Inside the plugin, you need to create a class after plugins are loaded.
	add_action(
		'plugins_loaded',
		array( 'WC_WooMercadoPago_Module', 'init_mercado_pago_class' )
	);

endif;
