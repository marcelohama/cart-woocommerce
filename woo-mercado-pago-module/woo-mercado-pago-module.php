<?php
/**
 * Plugin Name: WooCommerce Mercado Pago
 * Plugin URI: https://github.com/mercadopago/cart-woocommerce
 * Description: The <strong>oficial</strong> plugin of Mercado Pago for WooCommerce. Enables WooCommerce to use Mercado Pago as payment gateway.
 * Version: 3.0.0
 * Author: Mercado Pago
 * Author URI: https://www.mercadopago.com.br/developers/
 * Requires at least: 4.4
 * Tested up to: 4.7
 *
 * Text Domain: woo-mercado-pago-module
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

// Load Mercado Pago SDK
require_once dirname( __FILE__ ) . '/includes/sdk/lib/mercadopago.php';

// Load module class if it wasn't loaded yet.
if ( ! class_exists( 'WC_WooMercadoPago_Module' ) ) :

	/**
	 * Summary: WooCommerce MercadoPago Module main class.
	 * Description: Used as a kind of manager to enable/disable each Mercado Pago gateway.
	 * @since 1.0.0
	 */
	class WC_WooMercadoPago_Module {

		// ============================================================

		// Constants.
		const VERSION = '3.0.0';

		// Plugin variables.
		public static $mp = null;
		public static $country_configs = array();

		// ============================================================

		// A singleton design pattern to access this class in global scope.
		protected static $instance = null;
		public static function init_mercado_pago_class() {
			if ( self::$instance === null ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		// Class constructor.
		private function __construct() {

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			WC_WooMercadoPago_Module::$country_configs = array(
				'MCO' => array(
					'sponsor_id'             => 208687643,
					'checkout_banner'        => plugins_url( 'assets/images/MCO/standard_mco.jpg', __FILE__ ),
					'checkout_banner_custom' => plugins_url( 'assets/images/MCO/credit_card.png', __FILE__ ),
					'currency'               => 'COP'
				),
				'MLA' => array(
					'sponsor_id'             => 208682286,
					'checkout_banner'        => plugins_url( 'assets/images/MLA/standard_mla.jpg', __FILE__ ),
					'checkout_banner_custom' => plugins_url( 'assets/images/MLA/credit_card.png', __FILE__ ),
					'currency'               => 'ARS'
				),
				'MLB' => array(
					'sponsor_id'             => 208686191,
					'checkout_banner'        => plugins_url( 'assets/images/MLB/standard_mlb.jpg', __FILE__ ),
					'checkout_banner_custom' => plugins_url( 'assets/images/MLB/credit_card.png', __FILE__ ),
					'currency'               => 'BRL'
				),
				'MLC' => array(
					'sponsor_id'             => 208690789,
					'checkout_banner'        => plugins_url( 'assets/images/MLC/standard_mlc.gif', __FILE__ ),
					'checkout_banner_custom' => plugins_url( 'assets/images/MLC/credit_card.png', __FILE__ ),
					'currency'               => 'CLP'
				),
				'MLM' => array(
					'sponsor_id'             => 208692380,
					'checkout_banner'        => plugins_url( 'assets/images/MLM/standard_mlm.jpg', __FILE__ ),
					'checkout_banner_custom' => plugins_url( 'assets/images/MLM/credit_card.png', __FILE__ ),
					'currency'               => 'MXN'
				),
				'MLU' => array(
					'sponsor_id'             => 243692679,
					'checkout_banner'        => plugins_url( 'assets/images/MLU/standard_mlu.png', __FILE__ ),
					'checkout_banner_custom' => plugins_url( 'assets/images/MLU/credit_card.png', __FILE__ ),
					'currency'               => 'UYU'
				),
				'MLV' => array(
					'sponsor_id'             => 208692735,
					'checkout_banner'        => plugins_url( 'assets/images/MLV/standard_mlv.jpg', __FILE__ ),
					'checkout_banner_custom' => plugins_url( 'assets/images/MLV/credit_card.png', __FILE__ ),
					'currency'               => 'VEF'
				),
				'MPE' => array(
					'sponsor_id'             => 216998692,
					'checkout_banner'        => plugins_url( 'assets/images/MPE/standard_mpe.png', __FILE__ ),
					'checkout_banner_custom' => plugins_url( 'assets/images/MPE/credit_card.png', __FILE__ ),
					'currency'               => 'PEN'
				)
			);

		}

		// Multi-language setup.
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'woo-mercado-pago-module', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/' );
		}

		// Return boolean indicating if currency is supported.
		protected function is_supported_currency() {
			$site_id = get_option( '_site_id', true );
			return get_woocommerce_currency() == WC_WooMercadoPago_Module::$country_configs[$site_id]['currency'];
		}

		// ============================================================

		/**
		 * Summary: Check if we have valid credentials.
		 * Description: Check if we have valid credentials.
		 * @return boolean true/false depending on the validation result.
		 */
		public static function validate_credentials() {

			$client_id = get_option( '_mp_client_id', true );
			$client_secret = get_option( '_mp_client_secret', true );

			if ( empty( $client_id ) || empty( $client_secret ) ) {
				return false;
			}

			WC_WooMercadoPago_Module::$mp = new MP( WC_WooMercadoPago_Module::VERSION, $client_id, $client_secret );
			$access_token = WC_WooMercadoPago_Module::$mp->get_access_token();
			$get_request = WC_WooMercadoPago_Module::$mp->get( '/users/me?access_token=' . $access_token );

			if ( isset( $get_request['response']['site_id'] ) ) {
				
				update_option( '_test_user', in_array( 'test_user', $get_request['response']['tags'] ), true );
				update_option( '_site_id', $get_request['response']['site_id'], true );
				update_option( '_collector_id', $get_request['response']['id'], true );

				/*$payment_split_mode = $mp->check_two_cards();
				$payments = $mp->get( '/v1/payment_methods/?access_token=' . $access_token );
				array_push( $payment_methods, 'n/d' );
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

			return false;
		}

		public static function get_country_name() {
			$site_id = get_option( '_site_id', true );
			switch ( $site_id ) {
				case 'MCO':
					return __( 'Colombia', 'woo-mercado-pago-module' );
				case 'MLA':
					return __( 'Argentine', 'woo-mercado-pago-module' );
				case 'MLB':
					return __( 'Brazil', 'woo-mercado-pago-module' );
				case 'MLC':
					return __( 'Chile', 'woo-mercado-pago-module' );
				case 'MLM':
					return __( 'Mexico', 'woo-mercado-pago-module' );
				case 'MLU':
					return __( 'Uruguay', 'woo-mercado-pago-module' );
				case 'MLV':
					return __( 'Venezuela', 'woo-mercado-pago-module' );
				case 'MPE':
					return __( 'Peru', 'woo-mercado-pago-module' );
			}
			return '';
		}

		public static function build_invalid_credentials_msg() {
			$img_path = plugins_url( 'assets/images/error.png', __FILE__ );
			return '<img width="12" height="12" src="' . $img_path . '"> ' .
				__( 'Your credentials are <strong>not valid</strong>!', 'woo-mercado-pago-module' );
		}

		public static function build_valid_credentials_msg() {
			$site_id = get_option( '_site_id', true );
			$img_path = plugins_url( 'assets/images/check.png', __FILE__ );
			return '<img width="12" height="12" src="' . $img_path . '"> ' .
				__( 'Your credentials are <strong>valid</strong> for', 'woo-mercado-pago-module' ) . ': ' .
				WC_WooMercadoPago_Module::get_country_name() .
				' <img width="18.6" height="12" src="' . plugins_url( 'assets/images/' . $site_id . '/' . $site_id . '.png', __FILE__ ) . '"> ';
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

		if ( isset( $_POST['submit'] ) ) {
			if ( isset( $_POST['client_id'] ) ) {
				update_option( '_mp_client_id', $_POST['client_id'], true );
			}
			if ( isset( $_POST['client_secret'] ) ) {
				update_option( '_mp_client_secret', $_POST['client_secret'], true );
			}
		}

		// Trigger API to get payment methods and site_id, also validates Client_id/Client_secret.
		if ( WC_WooMercadoPago_Module::validate_credentials() ) {
			// Checking the currency.
			/*$currency_message = '';
			if ( ! $is_supported_currency() ) {
				if ( $currency_conversion === 'no' ) {
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
					update_option( '_currency_ratio', -1, true );
					$this->currency_message .= WC_WooMercadoPago_Module::build_currency_conversion_err_msg(
						WC_WooMercadoPago_Module::$this->country_configs['currency']
					);
				}
			} else {
				update_option( '_currency_ratio', -1, true );
			}*/
			$credentials_message = WC_WooMercadoPago_Module::build_valid_credentials_msg();
			$payment_desc = __( 'Select the payment methods that you <strong>don\'t</strong> want to receive with Mercado Pago.', 'woo-mercado-pago-module' );
		} else {
			//array_push( $payment_methods, 'n/d' );
			$credentials_message = WC_WooMercadoPago_Module::build_invalid_credentials_msg();
			$payment_desc = '<img width="12" height="12" src="' .
				plugins_url( 'assets/images/warning.png', __FILE__ ) . '">' . ' ' .
				__( 'Configure your Client_id and Client_secret to have access to more options.', 'woo-mercado-pago-module' );
		}

		$title = __( 'Mercado Pago Settings', 'woo-mercado-pago-module' );
		$logo = '<img width="200" height="52" src="' . plugins_url( 'assets/images/mplogo.png', __FILE__ ) . '">';
		$plugin_description = '<strong>' .
			__( 'This module enables WooCommerce to use Mercado Pago as payment method for purchases made in your virtual store.', 'woo-mercado-pago-module' ) .
			'</strong>';

		?>

		<div class="wrap">

			<h1><?php echo esc_html( $title ); ?></h1>

			<form method="post" action="" novalidate="novalidate" method="post">

				<?php settings_fields( 'mercadopago' ); ?>

				<table class="wp-list-table widefat plugins">
					<tr>
						<th scope="row"><?php echo $logo; ?></th>
						<td>
							<?php echo $credentials_message; ?>
							<!-- place here other setting statuses ... -->
						</td>
					</tr>
				</table>

				<?php echo $plugin_description; ?>

				<table class="form-table">
					<tr>
						<th scope="row"><label for="client_id">Client ID</label></th>
						<td>
							<input name="client_id" type="text" id="client_id" value="<?php form_option('_mp_client_id'); ?>" class="regular-text" />
							<p class="description" id="tagline-description"><?php echo esc_html( __( 'Insert your Mercado Pago Client_id.', 'woo-mercado-pago-module' ) ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="client_secret">Client Secret</label></th>
						<td>
							<input name="client_secret" type="text" id="client_secret" aria-describedby="tagline-description" value="<?php form_option('_mp_client_secret'); ?>" class="regular-text" />
							<p class="description" id="tagline-description"><?php echo esc_html( __( 'Insert your Mercado Pago Client_secret.', 'woo-mercado-pago-module' ) ); ?></p>
						</td>
					</tr>

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
