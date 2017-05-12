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
if ( ! class_exists( 'WC_Woo_Mercado_Pago_Module' ) ) :

	/**
	 * Summary: WooCommerce MercadoPago Module main class.
	 * Description: Used as a kind of manager to enable/disable each Mercado Pago gateway.
	 * @since 1.0.0
	 */
	class WC_Woo_Mercado_Pago_Module {

		// ============================================================

		// Constants.
		const VERSION = '3.0.0';
		const MIN_PHP = 5.6;

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

			WC_Woo_Mercado_Pago_Module::$country_configs = array(
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
			return get_woocommerce_currency() == WC_Woo_Mercado_Pago_Module::$country_configs[$site_id]['currency'];
		}

		// ============================================================

		/**
		 * Summary: Check if we have valid credentials for v0.
		 * Description: Check if we have valid credentials.
		 * @return boolean true/false depending on the validation result.
		 */
		public static function validate_credentials_v0() {
			$client_id = get_option( '_mp_client_id', true );
			$client_secret = get_option( '_mp_client_secret', true );
			if ( empty( $client_id ) || empty( $client_secret ) ) {
				return false;
			}
			WC_Woo_Mercado_Pago_Module::$mp = new MP( WC_Woo_Mercado_Pago_Module::VERSION, $client_id, $client_secret );
			$access_token = WC_Woo_Mercado_Pago_Module::$mp->get_access_token();
			$get_request = WC_Woo_Mercado_Pago_Module::$mp->get( '/users/me?access_token=' . $access_token );
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
					$currency_ratio = WC_Woo_Mercado_Pago_Module::get_conversion_rate(
						WC_Woo_Mercado_Pago_Module::$country_configs['currency']
					);
				}*/
				return true;
			} else {
				$mp = null;
				return false;
			}
			return false;
		}

		/**
		 * Summary: Check if we have valid credentials for v1.
		 * Description: Check if we have valid credentials.
		 * @return boolean true/false depending on the validation result.
		 */
		public static function validate_credentials_v1() {
			$public_key = get_option( '_mp_public_key', true );
			$access_token = get_option( '_mp_access_token', true );
			if ( empty( $public_key ) || empty( $access_token ) ) {
				return false;
			}
			WC_Woo_Mercado_Pago_Module::$mp = new MP( WC_Woo_Mercado_Pago_Module::VERSION, $access_token );
			$access_token = WC_Woo_Mercado_Pago_Module::$mp->get_access_token();
			$get_request = WC_Woo_Mercado_Pago_Module::$mp->get( '/users/me?access_token=' . $access_token );
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
					$currency_ratio = WC_Woo_Mercado_Pago_Module::get_conversion_rate(
						WC_Woo_Mercado_Pago_Module::$country_configs['currency']
					);
				}*/
				return true;
			} else {
				$mp = null;
				return false;
			}
			return false;
		}

		public static function get_country_name( $site_id ) {
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

	}

	//=====

	function mercadopago_plugin_menu() {
		add_options_page(
			'My Plugin Options',
			'Mercado Pago',
			'manage_options',
			'my-unique-identifier',
			function() {

				// Verify permissions.
				if ( ! current_user_can( 'manage_options' ) )  {
					wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
				}

				// Check for submits.
				if ( isset( $_POST['submit'] ) ) {
					if ( isset( $_POST['client_id'] ) ) {
						update_option( '_mp_client_id', $_POST['client_id'], true );
					}
					if ( isset( $_POST['client_secret'] ) ) {
						update_option( '_mp_client_secret', $_POST['client_secret'], true );
					}
					if ( isset( $_POST['public_key'] ) ) {
						update_option( '_mp_public_key', $_POST['public_key'], true );
					}
					if ( isset( $_POST['access_token'] ) ) {
						update_option( '_mp_access_token', $_POST['access_token'], true );
					}
				}

				$title = __( 'Mercado Pago Settings', 'woo-mercado-pago-module' );
				$logo = '<img width="185" height="48" src="' . plugins_url( 'assets/images/mplogo.png', __FILE__ ) . '">';

				// Trigger v0 API to validate credentials.
				$v0_credentials_message = WC_Woo_Mercado_Pago_Module::validate_credentials_v0() ?
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/check.png', __FILE__ ) . '"> ' .
					__( 'Your <strong>client_id</strong> and <strong>client_secret</strong> are <strong>valid</strong> for', 'woo-mercado-pago-module' ) . ': ' .
					'<img style="margin-top:2px;" width="18.6" height="12" src="' .
					plugins_url( 'assets/images/' . get_option( '_site_id', true ) . '/' .
					get_option( '_site_id', true ) . '.png', __FILE__ ) . '"> ' .
					WC_Woo_Mercado_Pago_Module::get_country_name( get_option( '_site_id', true ) ) :
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/error.png', __FILE__ ) . '"> ' .
					__( 'Your <strong>client_id</strong> and <strong>client_secret</strong> are <strong>not valid</strong>!', 'woo-mercado-pago-module' );
				// Trigger v1 API to validate credentials.
				$v1_credentials_message = WC_Woo_Mercado_Pago_Module::validate_credentials_v1() ?
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/check.png', __FILE__ ) . '"> ' .
					__( 'Your <strong>public_key</strong> and <strong>access_token</strong> are <strong>valid</strong> for', 'woo-mercado-pago-module' ) . ': ' .
					'<img style="margin-top:2px;" width="18.6" height="12" src="' .
					plugins_url( 'assets/images/' . get_option( '_site_id', true ) . '/' .
					get_option( '_site_id', true ) . '.png', __FILE__ ) . '"> ' .
					WC_Woo_Mercado_Pago_Module::get_country_name( get_option( '_site_id', true ) ) :
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/error.png', __FILE__ ) . '"> ' .
					__( 'Your <strong>public_key</strong> and <strong>access_token</strong> are <strong>not valid</strong>!', 'woo-mercado-pago-module' );
				// Check WooCommerce.
				$has_woocommerce_message = class_exists( 'WC_Payment_Gateway' ) ?
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/check.png', __FILE__ ) . '"> ' .
					__( 'WooCommerce is installed and enabled.', 'woo-mercado-pago-module' ) :
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/error.png', __FILE__ ) . '"> ' .
					__( 'You don\'t have WooCommerce installed and enabled.', 'woo-mercado-pago-module' );			
				// Creating PHP version message.
				$min_php_message = phpversion() >= WC_Woo_Mercado_Pago_Module::MIN_PHP ?
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/check.png', __FILE__ ) . '"> ' .
					__( 'Your PHP version is OK.', 'woo-mercado-pago-module' ) :
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/error.png', __FILE__ ) . '"> ' .
					__( 'Your PHP version do not support this module.', 'woo-mercado-pago-module' );
				// Check cURL.
				$curl_message = in_array( 'curl', get_loaded_extensions() ) ?
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/check.png', __FILE__ ) . '"> ' .
					__( 'cURL is installed.', 'woo-mercado-pago-module' ) :
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/error.png', __FILE__ ) . '"> ' .
					__( 'cURL is not installed.', 'woo-mercado-pago-module' );
				// Check SSL.
				$is_ssl_message = empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] == 'off' ?
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/warning.png', __FILE__ ) . '"> ' .
					__( 'SSL is missing in your site.', 'woo-mercado-pago-module' ) :
					'<img width="14" height="14" src="' . plugins_url( 'assets/images/check.png', __FILE__ ) . '"> ' .
					__( 'Your site has SSL enabled.', 'woo-mercado-pago-module' );

				$plugin_description = '<strong>' .
					__( 'This module enables WooCommerce to use Mercado Pago as payment method for purchases made in your virtual store.', 'woo-mercado-pago-module' ) .
					'</strong>';

				// Create links for internal redirections to each payment solution.
				$payment_gateways = __( 'Payment Gateways', 'woo-mercado-pago-module' );
				$plugin_links = '<strong>' .
					'<a class="button button-primary" href="' . esc_url( admin_url(
						'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPago_Gateway' ) ) .
						'">' . __( 'Basic Checkout', 'woo-mercado-pago-module' ) . '</a>' . ' ' .
					'<a class="button button-primary" href="' . esc_url( admin_url(
						'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoCustom_Gateway' ) ) .
						'">' . __( 'Custom Checkout', 'woo-mercado-pago-module' ) . '</a>' . ' ' .
					'<a class="button button-primary" href="' . esc_url( admin_url(
						'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoTicket_Gateway' ) ) .
						'">' . __( 'Ticket', 'woo-mercado-pago-module' ) . '</a>' . ' ' .
					'<a class="button button-primary" href="' . esc_url( admin_url(
						'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoSubscription_Gateway' ) ) .
						'">' . __( 'Subscription', 'woo-mercado-pago-module' ) . '</a>' .
				'</strong>';
				$v0_credential_locales = sprintf(
					'%s <a href="https://www.mercadopago.com/mla/account/credentials?type=basic" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mlb/account/credentials?type=basic" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mlc/account/credentials?type=basic" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mco/account/credentials?type=basic" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mlm/account/credentials?type=basic" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mpe/account/credentials?type=basic" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mlu/account/credentials?type=basic" target="_blank">%s</a> %s ' .
					'<a href="https://www.mercadopago.com/mlv/account/credentials?type=basic" target="_blank">%s</a>',
					__( 'Credentials used in Basic Checkout and Subscriptions. Access it for your country:<br>', 'woo-mercado-pago-module' ),
					__( 'Argentine', 'woo-mercado-pago-module' ),
					__( 'Brazil', 'woo-mercado-pago-module' ),
					__( 'Chile', 'woo-mercado-pago-module' ),
					__( 'Colombia', 'woo-mercado-pago-module' ),
					__( 'Mexico', 'woo-mercado-pago-module' ),
					__( 'Peru', 'woo-mercado-pago-module' ),
					__( 'Uruguay', 'woo-mercado-pago-module' ),
					__( 'or', 'woo-mercado-pago-module' ),
					__( 'Venezuela', 'woo-mercado-pago-module' )
				);
				$v1_credential_locales = sprintf(
					'%s <a href="https://www.mercadopago.com/mla/account/credentials?type=custom" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mlb/account/credentials?type=custom" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mlc/account/credentials?type=custom" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mco/account/credentials?type=custom" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mlm/account/credentials?type=custom" target="_blank">%s</a>, ' .
					'<a href="https://www.mercadopago.com/mpe/account/credentials?type=custom" target="_blank">%s</a> %s ' .
					'<a href="https://www.mercadopago.com/mlv/account/credentials?type=custom" target="_blank">%s</a>',
					__( 'Credentials used in Custom Checkout and Tickets. Access it for your country:<br>', 'woo-mercado-pago-module' ),
					__( 'Argentine', 'woo-mercado-pago-module' ),
					__( 'Brazil', 'woo-mercado-pago-module' ),
					__( 'Chile', 'woo-mercado-pago-module' ),
					__( 'Colombia', 'woo-mercado-pago-module' ),
					__( 'Mexico', 'woo-mercado-pago-module' ),
					__( 'Peru', 'woo-mercado-pago-module' ),
					__( 'or', 'woo-mercado-pago-module' ),
					__( 'Venezuela', 'woo-mercado-pago-module' )
				);

				?>

				<div class="wrap">

					<h1><?php echo esc_html( $title ); ?></h1>
					<table class="form-table">
						<tr>
							<td>
								<?php echo $v0_credentials_message; ?>
								<br>
								<?php echo $v1_credentials_message; ?>
								<br>
								<?php echo $has_woocommerce_message; ?>
							</td>
							<td>
								<?php echo $min_php_message; ?>
								<br>
								<?php echo $curl_message; ?>
								<br>
								<?php echo $is_ssl_message; ?>
							</td>
							<th scope="row">
								<?php echo $logo; ?>
							</th>
						</tr>
					</table>
					<?php echo $plugin_description; ?>
					<table class="form-table">
						<tr>
							<th scope="row"><?php echo $payment_gateways; ?></th>
							<td><?php echo $plugin_links; ?></td>
						</tr>
					</table>

					<form method="post" action="" novalidate="novalidate" method="post">

						<?php settings_fields( 'mercadopago' ); ?>

						<table class="form-table" border="0.5" frame="above" rules="void">
							<tr>
								<th scope="row"><label for="client_id"><h3>
									<?php echo esc_html( __( 'v0 Credentials', 'woo-mercado-pago-module' ) ); ?>
								</h3></label></th>
								<td><label class="description" id="tagline-description">
									<?php echo $v0_credential_locales; ?>
								</label></td>
							</tr>
							<tr>
								<th scope="row"><label for="client_id">Client ID</label></th>
								<td>
									<input name="client_id" type="text" id="client_id" value="<?php form_option('_mp_client_id'); ?>" class="regular-text" />
									<p class="description" id="tagline-description">
										<?php echo esc_html( __( 'Insert your Mercado Pago Client_id.', 'woo-mercado-pago-module' ) ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="client_secret">Client Secret</label></th>
								<td>
									<input name="client_secret" type="text" id="client_secret" aria-describedby="tagline-description" value="<?php form_option('_mp_client_secret'); ?>" class="regular-text" />
									<p class="description" id="tagline-description">
										<?php echo esc_html( __( 'Insert your Mercado Pago Client_secret.', 'woo-mercado-pago-module' ) ); ?>
									</p>
								</td>
							</tr>
						</table>
						
						<table class="form-table" border="0.5" frame="above" rules="void">
							<tr>
								<th scope="row"><label for="client_id"><h3>
									<?php echo esc_html( __( 'v1 Credentials', 'woo-mercado-pago-module' ) ); ?>
								</h3></label></th>
								<td><label class="description" id="tagline-description">
									<?php echo $v1_credential_locales; ?>
								</label></td>
							</tr>
							<tr>
								<th scope="row"><label for="public_key">Public Key</label></th>
								<td>
									<input name="public_key" type="text" id="public_key" aria-describedby="tagline-description" value="<?php form_option('_mp_public_key'); ?>" class="regular-text" />
									<p class="description" id="tagline-description">
										<?php echo esc_html( __( 'Insert your Mercado Pago Public key.', 'woo-mercado-pago-module' ) ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="access_token">Access Token</label></th>
								<td>
									<input name="access_token" type="text" id="access_token" aria-describedby="tagline-description" value="<?php form_option('_mp_access_token'); ?>" class="regular-text" />
									<p class="description" id="tagline-description">
										<?php echo esc_html( __( 'Insert your Mercado Pago Access token.', 'woo-mercado-pago-module' ) ); ?>
									</p>
								</td>
							</tr>
						</table>

						<table class="form-table" border="0.5" frame="hsides" rules="void">
							<tr>
								<th scope="row"><label for="mp_success_url">
									<?php echo __( 'Sucess URL', 'woo-mercado-pago-module' ); ?>
								</label></th>
								<td>
									<input name="mp_success_url" type="text" id="mp_success_url" value="<?php form_option('_mp_success_url'); ?>" class="regular-text" />
									<p class="description" id="tagline-description">
										<?php echo esc_html( __( 'Where customers should be redirected after a successful purchase. Let blank to redirect to the default store order resume page.', 'woo-mercado-pago-module' ) ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="mp_fail_url">
									<?php echo __( 'Failure URL', 'woo-mercado-pago-module' ); ?>
								</label></th>
								<td>
									<input name="mp_fail_url" type="text" id="mp_fail_url" value="<?php form_option('_mp_fail_url'); ?>" class="regular-text" />
									<p class="description" id="tagline-description">
										<?php echo esc_html( __( 'Where customers should be redirected after a failed purchase. Let blank to redirect to the default store order resume page.', 'woo-mercado-pago-module' ) ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="mp_pending_url">
									<?php echo __( 'Pending URL', 'woo-mercado-pago-module' ); ?>
								</label></th>
								<td>
									<input name="mp_pending_url" type="text" id="mp_pending_url" value="<?php form_option('_mp_pending_url'); ?>" class="regular-text" />
									<p class="description" id="tagline-description">
										<?php echo esc_html( __( 'Where customers should be redirected after a pending purchase. Let blank to redirect to the default store order resume page.', 'woo-mercado-pago-module' ) ); ?>
									</p>
								</td>
							</tr>

							<!--
							WooCommerce pending processing on-hold completed cancelled refunded failed
							MercadoPago pending approved in_process in_mediation rejected cancelled refunded charged_back
							-->

						</table>

						<?php do_settings_sections( 'mercadopago' ); ?>

						<?php submit_button(); ?>

					</form>

				</div>

				<?php

			}
		);
	}

	// Payment gateways should be created as additional plugins that hook into WooCommerce.
	// Inside the plugin, you need to create a class after plugins are loaded.
	add_action(
		'plugins_loaded',
		array( 'WC_Woo_Mercado_Pago_Module', 'init_mercado_pago_class' )
	);

	// Create Mercado Pago option menu.
	add_action( 'admin_menu', 'mercadopago_plugin_menu' );

endif;
