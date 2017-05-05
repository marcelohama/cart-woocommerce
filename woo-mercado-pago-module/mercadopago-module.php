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

// Load module class if it wasn't loaded yet.
if ( ! class_exists( 'WC_WooMercadoPago_Module' ) ) :

	/**
	 * Summary: WooCommerce MercadoPago Module main class.
	 * Description: Used as a kind of manager to enable/disable each Mercado Pago gateway.
	 * @since 1.0.0
	 */
	class WC_WooMercadoPago_Module {

		const VERSION = '3.0.0';

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
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-mercadopago-module' );
			$module_root = 'woocommerce-mercadopago-module/woocommerce-mercadopago-module-';
			load_textdomain(
				'woocommerce-mercadopago-module',
				trailingslashit( WP_LANG_DIR ) . $module_root . $locale . '.mo'
			);
			load_plugin_textdomain(
				'woocommerce-mercadopago-module',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
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
			__( 'This module enables WooCommerce to use Mercado Pago as payment method for purchases made in your virtual store.', 'woocommerce-mercadopago-module' ) .
			'</strong>';

		if ( isset( $_POST['submit'] ) ) {
			if ( isset( $_POST['client_id'] ) ) {
				update_option( '_mp_client_id', $_POST['client_id'], true );
			}
			if ( isset( $_POST['client_secret'] ) ) {
				update_option( '_mp_client_secret', $_POST['client_secret'], true );
			}
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
							<p class="description" id="tagline-description"><?php echo esc_html( __( 'Insert your Mercado Pago Client_id.', 'woocommerce-mercadopago-module' ) ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="client_secret">Client Secret</label></th>
						<td>
							<input name="client_secret" type="text" id="client_secret" aria-describedby="tagline-description" value="<?php form_option('_mp_client_secret'); ?>" class="regular-text" />
							<p class="description" id="tagline-description"><?php echo esc_html( __( 'Insert your Mercado Pago Client_secret.', 'woocommerce-mercadopago-module' ) ); ?></p>
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
