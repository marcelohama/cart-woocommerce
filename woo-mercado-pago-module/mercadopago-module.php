<?php
/**
 * Plugin Name: Woo Mercado Pago Module
 * Plugin URI: https://github.com/mercadopago/cart-woocommerce
 * Description: This is the <strong>oficial</strong> module of Mercado Pago for WooCommerce plugin. This module enables WooCommerce to use Mercado Pago as a payment Gateway for purchases made in your e-commerce store.
 * Author: Mercado Pago
 * Author URI: https://www.mercadopago.com.br/developers/
 * Developer: Marcelo Tomio Hama / marcelo.hama@mercadolivre.com
 * Copyright: Copyright(c) MercadoPago [http://www.mercadopago.com]
 * Version: 2.0.4
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * Text Domain: woocommerce-mercadopago-module
 * Domain Path: /languages/
 */

/**
 * Implementation references:
 * 1. https://docs.woothemes.com/document/payment-gateway-api/
 * 2. https://www.mercadopago.com.br/developers/en/api-docs/
 */
 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Check if class is already loaded
if ( !class_exists( 'WC_WooMercadoPago_Module' ) ) :

// WooCommerce MercadoPago Module main class
class WC_WooMercadoPago_Module {

	// Singleton design pattern
	protected static $instance = null;
	public static function initMercadoPagoGatewayClass() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	// Class constructor
	private function __construct() {
		// load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		// verify if WooCommerce is already installed
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			include_once 'mercadopago/mercadopago-gateway.php';
			include_once 'mercadopago/mercadopago-custom-gateway.php';
			include_once 'mercadopago/mercadopago-ticket-gateway.php';
			add_filter( 'woocommerce_payment_gateways', array( $this, 'addGateway' ) );
			add_filter( 'woomercadopago_settings_link_' . plugin_basename( __FILE__ ), array( $this, 'woomercadopago_settings_link' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'notifyWooCommerceMiss' ) );
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
	
	// Places a warning error to notify user that WooCommerce is missing
	public function notifyWooCommerceMiss() {
		echo
			'<div class="error"><p>' . sprintf(
				__( 'Woo Mercado Pago Module depends on the last version of %s to execute!', 'woocommerce-mercadopago-module' ),
				'<a href="http://wordpress.org/extend/plugins/woocommerce/">' . 'WooCommerce' . '</a>'
			) .
			'</p></div>';
	}
	
	// Multi-language plugin
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-mercadopago-module' );
		load_textdomain(
			'woocommerce-mercadopago-module',
			trailingslashit(WP_LANG_DIR ) . 'woocommerce-mercadopago-module/woocommerce-mercadopago-module-' . $locale . '.mo'
		);
		load_plugin_textdomain( 'woocommerce-mercadopago-module', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	
	public static function woocommerceInstance() {
		if ( function_exists( 'WC' ) ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}
	}
	
	public static function getTemplatesPath() {
		return plugin_dir_path( __FILE__ ) . 'templates/';
	}
	
}
	
// Payment gateways should be created as additional plugins that hook into WooCommerce.
// Inside the plugin, you need to create a class after plugins are loaded
add_action( 'plugins_loaded', array( 'WC_WooMercadoPago_Module', 'initMercadoPagoGatewayClass' ), 0 );
 
// Add settings link on plugin page
function woomercadopago_settings_link( $links ) { 
	$plugin_links = array();
	$plugin_links[] = '<a href="' . esc_url( admin_url(
		'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPago_Gateway' ) ) . '">' .
		__( 'Standard Checkout', 'woocommerce-mercadopago-module' ) .
	'</a>';
	$plugin_links[] = '<a href="' . esc_url( admin_url(
		'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoCustom_Gateway' ) ) . '">' .
		__( 'Custom Checkout', 'woocommerce-mercadopago-module' ) .
	'</a>';
	$plugin_links[] = '<a href="' . esc_url( admin_url(
		'admin.php?page=wc-settings&tab=checkout&section=WC_WooMercadoPagoTicket_Gateway' ) ) . '">' .
		__( 'Ticket', 'woocommerce-mercadopago-module' ) .
	'</a>';
	return array_merge( $plugin_links, $links );
}
$plugin = plugin_basename( __FILE__ ); 
add_filter( "plugin_action_links_$plugin", 'woomercadopago_settings_link' );



// REST API
register_activation_hook( __FILE__, 'giar_activate' );
function giar_activate() {
	flush_rewrite_rules();
}

add_action( 'rest_api_init', 'dt_register_api_hooks' );
function dt_register_api_hooks() {
	$namespace = 'give-it-a-rest/v1';

	register_rest_route( $namespace, '/list-posts/', array(
		'methods'  => 'GET',
		'callback' => 'giar_get_posts',
	) );

	register_rest_route( $namespace, '/vote/', array(
		'methods'  => 'POST',
		'callback' => 'giar_process_vote',
	) );
}


function giar_get_posts() {
	if ( 0 || false === ( $return = get_transient( 'dt_all_posts' ) ) ) {
		$query     = apply_filters( 'giar_get_posts_query', array(
			'numberposts' => 10,
			'post_type'   => 'post',
			'post_status' => 'publish',
		) );
		$all_posts = get_posts( $query );
		$return    = array();
		foreach ( $all_posts as $post ) {
			$return[] = array(
				'ID'        => $post->ID,
				'title'     => 'Marcelo T. Hama',
				'permalink' => get_permalink( $post->ID ),
				'upvotes'   => intval( get_post_meta( $post->ID, 'giar_upvotes', true ) ),
				'downvotes' => intval( get_post_meta( $post->ID, 'giar_downvotes', true ) ),
			);
		}
		
		$args = array(
			'post_type' => 'shop_order',
			'post_status' => 'publish',
			'meta_key' => '_customer_user',
			'posts_per_page' => '-1'
		);
		$my_query = new WP_Query($args);
		$return = $my_query->posts;

		// cache for 10 minutes
		set_transient( 'giar_all_posts', $return, apply_filters( 'giar_posts_ttl', 60 * 10 ) );
	}
	$response = new WP_REST_Response( $return );
	$response->header( 'Access-Control-Allow-Origin', apply_filters( 'giar_access_control_allow_origin', '*' ) );

	return $response;
}


function giar_process_vote() {
	$vote    = $_POST['vote'];
	$post_id = $_POST['id'];

	// input validation
	if ( ! is_numeric( $post_id ) || ! in_array( strtolower( $vote ), array( 'up', 'down' ) ) ) {
		return false;
	}

	$meta_name      = 'giar_' . $vote . 'votes';
	$vote_count     = intval( get_post_meta( $post_id, $meta_name, true ) );
	$update_success = update_post_meta( $post_id, $meta_name, ++$vote_count ) ? true : false;

	// clear transient posts cache
	delete_transient( 'giar_all_posts' );
	
	$response = new WP_REST_Response( $update_success );
	$response->header( 'Access-Control-Allow-Origin', apply_filters( 'giar_access_control_allow_origin', '*' ) );

	return $response;
}

endif;

?>
