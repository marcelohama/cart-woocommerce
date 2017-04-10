<?php

/**
 * Plugin Name: Woo Mercado Pago Module
 * Plugin URI: https://github.com/mercadopago/cart-woocommerce
 * Description: The <strong>oficial</strong> module of Mercado Pago for WooCommerce plugin. This module enables WooCommerce to use Mercado Pago as a payment Gateway for purchases made in your e-commerce store, offering a set of solutions such as Basic Checkout, Custom Checkout, Mercado Pago Tickets, Mercado Pago Subscriptions, Mercado Envios, and others.
 * Author: Mercado Pago
 * Author URI: https://www.mercadopago.com.br/
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

// Load module class if it wasn't loaded yet.
if ( ! class_exists( 'WC_WooMercadoPago_Module' ) ) :

	// This include Mercado Pago library SDK
	require_once 'mercadopago/sdk/lib/mercadopago.php';

	/**
	 * Summary: WooCommerce MercadoPago Module main class.
	 * Description: Used as a kind of manager to enable/disable each Mercado Pago gateway.
	 * @class       WC_Gateway_Offline
	 * @extends     WC_Payment_Gateway
	 * @since       3.0.0
	 * @version     3.0.0
	 * @package     MercadoPago/Classes/Gateway
	 * @author      MercadoPago
	 */
	class WC_WooMercadoPago_Module {

		const VERSION = '3.0.0';

		public static $mp = null;
		protected static $site_id = null;
		protected static $access_token = null;

		// Singleton design pattern.
		protected static $instance = null;
		public static function init_mercado_pago_gateway_class() {
			if ( null == self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		// Class constructor.
		private function __construct() {

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			// Verify if WooCommerce is already installed.
			if ( class_exists( 'WC_Payment_Gateway' ) ) {

				// =================================
				WC_WooMercadoPago_Module::$mp = new MP(
					WC_WooMercadoPago_Module::get_module_version(),
					'2199920202328828',
					'EQbymezmkBRAuoxhSzCbZS81NRhhcpPU'
					//$this->settings['client_id'],
					//$this->settings['client_secret']
				);
				// =================================

				include_once 'includes/class-wc-mercadopago-basic.php';
				/*include_once 'mercadopago/mercadopago-custom-gateway.php';
				include_once 'mercadopago/mercadopago-ticket-gateway.php';
				include_once 'mercadopago/mercadopago-subscription-gateway.php';

				include_once 'mercadopago/class-wc-product-mp_recurrent.php';*/

				// Mercado Envios classes.
				/*include_once 'shipment/abstract-wc-mercadoenvios-shipping.php';
				include_once 'shipment/class-wc-mercadoenvios-shipping-normal.php';
				include_once 'shipment/class-wc-mercadoenvios-shipping-express.php';
				include_once 'shipment/class-wc-mercadoenvios-package.php';*/

				add_action(
					'woocommerce_api_wc_woomercadopago_module',
					array( $this, 'process_http_request' )
				);
				add_filter(
					'woocommerce_payment_gateways',
					array( $this, 'add_gateway' )
				);
				add_filter(
					'woomercadopago_settings_link_' . plugin_basename( __FILE__ ),
					array( $this, 'woomercadopago_settings_link' )
				);

				/*add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_payment_method_by_shipping' ) );*/

			} else {
				add_action( 'admin_notices', array( $this, 'notify_woocommerce_miss' ) );
			}

			if ( is_admin() ) {
				/*include_once dirname( __FILE__ ) . '/admin/class-wc-mercadoenvios-admin-orders.php';*/
			}

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
		 * Summary: Places a warning error to notify user that WooCommerce is missing.
		 * Description: Places a warning error to notify user that WooCommerce is missing.
		 */
		public function notify_woocommerce_miss() {
			echo
				'<div class="error"><p>' .
				sprintf(
					__( 'Woo Mercado Pago Module depends on the last version of %s to execute!', 'woocommerce-mercadopago-module' ),
					'<a href="https://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>'
				) .
				'</p></div>';
		}

		// As well as defining your class, you need to also tell WooCommerce (WC) that
		// it exists. Do this by filtering woocommerce_payment_gateways.
		public function add_gateway( $methods ) {
			$methods[] = 'WC_WooMercadoPagoBasic_Gateway';
			/*$methods[] = 'WC_WooMercadoPagoCustom_Gateway';
			$methods[] = 'WC_WooMercadoPagoTicket_Gateway';
			$methods[] = 'WC_WooMercadoPagoSubscription_Gateway';*/
			return $methods;
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

		// Get WooCommerce instance
		public static function woocommerce_instance() {
			if ( function_exists( 'WC' ) ) {
				return WC();
			} else {
				global $woocommerce;
				return $woocommerce;
			}
		}

		/**
		 * Summary: Handles a HTTP request.
		 * Description: Handles a HTTP request.
		 */
		public function process_http_request() {

			@ob_clean();
			header( 'HTTP/1.1 200 OK' );

		}

	}

	// ==========================================================================================

	// Payment gateways should be created as additional plugins that hook into WooCommerce.
	// Inside the plugin, you need to create a class after plugins are loaded.
	add_action(
		'plugins_loaded',
		array( 'WC_WooMercadoPago_Module', 'init_mercado_pago_gateway_class' ), 0
	);

	// Add extra options to plugin row meta.
	function filter_plugin_row_meta( $links, $file ) {
		if ( plugin_basename( __FILE__ ) == $file ) {
			$row_meta = array(
				'report' => '<a target="_blank" href="' .
					esc_url( apply_filters( 'woocommerce_docs_url', 'https://wordpress.org/support/plugin/woo-mercado-pago-module#postform' ) ) .
					'" aria-label="' . esc_attr__( 'Notify us about a problem', 'woocommerce' ) . '">' .
					esc_html__( 'Report Issue', 'woocommerce' ) .
				'</a>',
				'rate' => '<a target="_blank" href="' .
					esc_url( apply_filters( 'woocommerce_docs_url', 'https://wordpress.org/support/view/plugin-reviews/woo-mercado-pago-module?filter=5#postform' ) ) .
					'" aria-label="' . esc_attr__( 'Rate us in WordPress Plugin Directory', 'woocommerce' ) . '">' .
					sprintf( __( 'Rate Us', 'woocommerce-mercadopago-module' ) . ' %s', '&#9733;&#9733;&#9733;&#9733;&#9733;' ) .
				'</a>',
				'docs' => '<a target="_blank" href="' .
					esc_url( apply_filters( 'woocommerce_docs_url', 'https://github.com/mercadopago/cart-woocommerce#installation' ) ) .
					'" aria-label="' . esc_attr__( 'View tutorial for this plugin', 'woocommerce' ) . '">' .
					esc_html__( 'Docs', 'woocommerce' ) .
				'</a>',
				'apidocs' => '<a target="_blank" href="' .
					esc_url( apply_filters( 'woocommerce_docs_url', 'https://www.mercadopago.com.br/developers/en/api-docs/' ) ) .
					'" aria-label="' . esc_attr__( 'View Mercado Pago API documentation', 'woocommerce' ) . '">' .
					esc_html__( 'API docs', 'woocommerce' ) .
				'</a>'
			);
			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}
	add_filter( 'plugin_row_meta', 'filter_plugin_row_meta', 10, 2 );

	// Add settings link on plugin page.
	function woomercadopago_settings_link( $links ) {

		/*$login_link_text =
			'<img width="12" height="12" src="' .
			plugins_url( 'woo-mercado-pago-module/images/warning.png', plugin_dir_path( __FILE__ ) ) .
			'">' . ' ' . __( 'Click here to login with Mercado Pago', 'woocommerce-mercadopago-module' ) . ' ' .
			'<img width="12" height="12" src="' .
			plugins_url( 'woo-mercado-pago-module/images/warning.png', plugin_dir_path( __FILE__ ) ) .
			'">';*/

		//$access_token = WC_WooMercadoPago_Module::$mp->get_access_token();
		//$get_request = WC_WooMercadoPago_Module::init_mercado_pago_gateway_class()->mp->get( '/users/me?access_token=' . $access_token );
		//update_option( 'access_token', $access_token );
		$access_token = get_option( 'access_token', null );

		//$prompt_js = file_get_contents( plugins_url( 'woo-mercado-pago-module/templates/credential_prompt.php', plugin_dir_path( __FILE__ ) ) );
		$prompt_js = '
			<div id="dialog-form" title="Create new user" style="margin-bottom:-10px; margin-top:8px;">
				<form>
					<fieldset>
						<input type="password" placeholder="Client ID" name="client_id" id="client_id" class="text ui-widget-content ui-corner-all">
						<input type="password" placeholder="Client Secret" name="client_secret" id="client_secret" class="text ui-widget-content ui-corner-all">
						<input type="button" value="Connect Account" name="client_secret" id="connect_mp" class="button" style="margin:1px; height:25px;">
					</fieldset>
				</form>
			</div>
			<script type="text/javascript">
				( function() {
					var WooMP = {
						selectors: {
							connect_mp: "#connect_mp"
						}
					}
					WooMP.checkCredentials = function () {
						WooMP.AJAX({
							url: "' . get_site_url() . '/index.php/woocommerce-mercadopago-module' .
								'?wc-api=WC_WooMercadoPago_Module' .
								'&clientid=2199920202328828' .
								'&client_secret=EQbymezmkBRAuoxhSzCbZS81NRhhcpPU' .
							'",
							method : "GET",
							timeout : 5000,
							error: function() {
								// Request failed.
							},
							success : function ( status, response ) {
								if ( response.status == 200 ) {
									alert( "Marcelo" );
								} else if ( response.status == 400 || response.status == 404 ) {
									alert( "Hama" );
								}
							}
						});
					}
					WooMP.referer = (function () {
						var referer = window.location.protocol + "//" +
							window.location.hostname + ( window.location.port ? ":" + window.location.port: "" );
						return referer;
					})();
					WooMP.AJAX = function( options ) {
						var useXDomain = !!window.XDomainRequest;
						var req = useXDomain ? new XDomainRequest() : new XMLHttpRequest()
						var data;
						options.url += ( options.url.indexOf( "?" ) >= 0 ? "&" : "?" ) + "referer=" + escape( WooMP.referer );
						options.requestedMethod = options.method;
						if ( useXDomain && options.method == "PUT" ) {
							options.method = "POST";
							options.url += "&_method=PUT";
						}
						req.open( options.method, options.url, true );
						req.timeout = options.timeout || 1000;
						if ( window.XDomainRequest ) {
							req.onload = function() {
								data = JSON.parse( req.responseText );
								if ( typeof options.success === "function" ) {
									options.success( options.requestedMethod === "POST" ? 201 : 200, data );
								}
							};
							req.onerror = req.ontimeout = function() {
								if ( typeof options.error === "function" ) {
									options.error( 400, {
										user_agent:window.navigator.userAgent, error : "bad_request", cause:[]
									});
								}
							};
							req.onprogress = function() {};
						} else {
							req.setRequestHeader( "Accept", "application/json" );
							if ( options.contentType ) {
								req.setRequestHeader( "Content-Type", options.contentType );
							} else {
								req.setRequestHeader( "Content-Type", "application/json" );
							}
							req.onreadystatechange = function() {
								if ( this.readyState === 4 ) {
									if ( this.status >= 200 && this.status < 400 ) {
										// Success!
										data = JSON.parse( this.responseText );
										if ( typeof options.success === "function" ) {
											options.success( this.status, data );
										}
									} else if ( this.status >= 400 ) {
										data = JSON.parse( this.responseText );
										if ( typeof options.error === "function" ) {
											options.error( this.status, data );
										}
									} else if ( typeof options.error === "function" ) {
										options.error( 503, {} );
									}
								}
							};
						}
						if ( options.method === "GET" || options.data == null || options.data == undefined ) {
							req.send();
						} else {
							req.send( JSON.stringify( options.data ) );
						}
					}
					WooMP.addListenerEvent = function( el, eventName, handler ) {
						if ( el.addEventListener ) {
							el.addEventListener( eventName, handler );
						} else {
							el.attachEvent( "on" + eventName, function() {
								handler.call( el );
							});
						}
					};
					WooMP.Initialize = function() {
						WooMP.addListenerEvent(
							document.querySelector( WooMP.selectors.connect_mp ),
							"click", WooMP.checkCredentials
						);
					}
					this.WooMP = WooMP;
				} ).call();
				WooMP.Initialize();
			</script>
		';

		$plugin_links = array();
		//$plugin_links[] = '<a href="javascript:void(0);">' . $access_token . '</a>';
		$plugin_links[] = $prompt_js;
		//. '<a onclick="get_check_credentials()" href="javascript:void(0);">' . $login_link_text . '</a>';
		$plugin_links[] = '<br><a href="' . esc_url( admin_url(
			'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoBasic_Gateway' ) ) .
			'">' . __( 'Basic Checkout', 'woocommerce-mercadopago-module' ) . '</a>';
		$plugin_links[] = '<a href="' . esc_url( admin_url(
			'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoCustom_Gateway' ) ) .
			'">' . __( 'Custom Checkout', 'woocommerce-mercadopago-module' ) . '</a>';
		$plugin_links[] = '<a href="' . esc_url( admin_url(
			'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoTicket_Gateway' ) ) .
			'">' . __( 'Tickets', 'woocommerce-mercadopago-module' ) . '</a>';
		$plugin_links[] = '<a href="' . esc_url( admin_url(
			'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoSubscription_Gateway' ) ) .
			'">' . __( 'Subscriptions', 'woocommerce-mercadopago-module' ) . '</a>';
		return array_merge( $plugin_links, $links );
	}
	$plugin = plugin_basename( __FILE__ );
	add_filter("plugin_action_links_$plugin", 'woomercadopago_settings_link' );

endif;

?>