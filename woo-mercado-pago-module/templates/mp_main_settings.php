<div class="wrap">

	<h1><?php echo esc_html( __( 'Mercado Pago Settings', 'woo-mercado-pago-module' ) ); ?></h1>
	
	<table class="form-table">
		<tr>
			<td>
				<?php echo $v0_credentials_message; ?>
				<br>
				<?php echo $v1_credentials_message; ?>
				<br>
				<?php echo $has_woocommerce_message; ?>
				<br>
				<?php echo $min_php_message; ?>
				<br>
				<?php echo $curl_message; ?>
				<br>
				<?php echo $is_ssl_message; ?>
			</td>
			<th scope="row">
				<?php echo $mp_logo; ?>
			</th>
		</tr>
	</table>
	
	<strong>
		<?php echo __( 'This module enables WooCommerce to use Mercado Pago as payment method for purchases made in your virtual store.', 'woo-mercado-pago-module' ); ?>
	</strong>

	<table class="form-table">
		<tr>
			<th scope="row"><?php echo __( 'Payment Gateways', 'woo-mercado-pago-module' ); ?></th>
			<td><?php echo $plugin_links; ?></td>
		</tr>
	</table>

	<form method="post" action="" novalidate="novalidate" method="post">

		<?php settings_fields( 'mercadopago' ); ?>

		<table class="form-table" border="0.5" frame="above" rules="void">
			<tr>
				<th scope="row"><label><h3>
					<?php echo esc_html( __( 'Basic Checkout, Subscriptions', 'woo-mercado-pago-module' ) ); ?>
				</h3></label></th>
				<td><label class="description" id="tagline-description">
					<?php echo $v0_credential_locales; ?>
				</label></td>
			</tr>
			<tr>
				<th scope="row"><label>Client ID</label></th>
				<td>
					<input name="client_id" type="text" id="client_id" value="<?php form_option('_mp_client_id'); ?>" class="regular-text" />
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'Insert your Mercado Pago Client_id.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>Client Secret</label></th>
				<td>
					<input name="client_secret" type="text" id="client_secret" aria-describedby="tagline-description" value="<?php form_option('_mp_client_secret'); ?>" class="regular-text" />
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'Insert your Mercado Pago Client_secret.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php echo __( 'Currency Conversion', 'woo-mercado-pago-module' ); ?></label></th>
				<td>
					<label>
						<input class="" type="checkbox" name="currency_conversion_v0" id="currency_conversion_v0" <?php echo $is_currency_conversion_v0; ?>>
						<?php echo __( 'If the used currency in WooCommerce is different or not supported by Mercado Pago, convert values of your transactions using Mercado Pago currency ratio. This service may slow down your server as each conversion is made in the checkout moment.', 'woo-mercado-pago-module' ); ?>
					</label>
					<p class="description" id="tagline-description">
						<?php echo $currency_conversion_v0_message; ?>
					</p>
				</td>
			</tr>
		</table>
		
		<table class="form-table" border="0.5" frame="above" rules="void">
			<tr>
				<th scope="row"><label><h3>
					<?php echo esc_html( __( 'Custom Checkout, Tickets', 'woo-mercado-pago-module' ) ); ?>
				</h3></label></th>
				<td><label class="description" id="tagline-description">
					<?php echo $v1_credential_locales; ?>
				</label></td>
			</tr>
			<tr>
				<th scope="row"><label>Public Key</label></th>
				<td>
					<input name="public_key" type="text" id="public_key" aria-describedby="tagline-description" value="<?php form_option('_mp_public_key'); ?>" class="regular-text" />
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'Insert your Mercado Pago Public key.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>Access Token</label></th>
				<td>
					<input name="access_token" type="text" id="access_token" aria-describedby="tagline-description" value="<?php form_option('_mp_access_token'); ?>" class="regular-text" />
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'Insert your Mercado Pago Access token.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php echo __( 'Currency Conversion', 'woo-mercado-pago-module' ); ?></label></th>
				<td>
					<label>
						<input class="" type="checkbox" name="currency_conversion_v1" id="currency_conversion_v1" <?php echo $is_currency_conversion_v1; ?>>
						<?php echo __( 'If the used currency in WooCommerce is different or not supported by Mercado Pago, convert values of your transactions using Mercado Pago currency ratio. This service may slow down your server as each conversion is made in the checkout moment.', 'woo-mercado-pago-module' ); ?>
					</label>
					<p class="description" id="tagline-description">
						<?php echo $currency_conversion_v1_message; ?>
					</p>
				</td>
			</tr>
		</table>

		<table class="form-table" border="0.5" frame="above" rules="void">
			<tr>
				<th scope="row"><label><h3>
					<?php echo esc_html( __( 'Server Communication', 'woo-mercado-pago-module' ) ); ?>
				</h3></label></th>
				<td><label class="description" id="tagline-description">
					<?php echo __( 'Here you can configure details of your server communication with Mercado Pago. Back URL are redirections after the checkout.', 'woo-mercado-pago-module' ); ?>
					<br>
					<?php echo sprintf(
						__( 'For status mappings between payment/order you can use the defaults, or check references of %s and %s', 'woo-mercado-pago-module' ),
						'<a target="_blank" href="https://www.mercadopago.com.br/developers/en/api-docs/basic-checkout/ipn/payment-status/">Mercado Pago</a>',
						'<a target="_blank" href="https://docs.woocommerce.com/document/managing-orders/">WooCommerce</a>.'
					); ?>
				</label></td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Sucess URL', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<input name="success_url" type="text" id="success_url" value="<?php form_option('_mp_success_url'); ?>" class="regular-text" placeholder="<?php echo get_site_url() . '/wc-api/{mp_gateway}'; ?>"/>
					<p class="description" id="tagline-description">
						<?php echo $success_back_url_message; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Failure URL', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<input name="fail_url" type="text" id="fail_url" value="<?php form_option('_mp_fail_url'); ?>" class="regular-text" placeholder="<?php echo get_site_url() . '/wc-api/{mp_gateway}'; ?>"/>
					<p class="description" id="tagline-description">
						<?php echo $fail_back_url_message; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Pending URL', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<input name="pending_url" type="text" id="pending_url" value="<?php form_option('_mp_pending_url'); ?>" class="regular-text" placeholder="<?php echo get_site_url() . '/wc-api/{mp_gateway}'; ?>"/>
					<p class="description" id="tagline-description">
						<?php echo $pending_back_url_message; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Status for PENDING', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<select name="mp_order_status_pending_map" id="mp_order_status_pending_map">
						<option value="pending" selected="selected"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PENDING</option>
						<option value="processing"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PROCESSING</option>
						<option value="on-hold"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>ON-HOLD</option>
						<option value="completed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>COMPLETED</option>
						<option value="cancelled"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>CANCELLED</option>
						<option value="refunded"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>REFUNDED</option>
						<option value="failed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>FAILED</option>
					</select>
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'when Mercado Pago updates a payment status to PENDING.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Status for APPROVED', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<select name="mp_order_status_approved_map" id="mp_order_status_approved_map">
						<option value="pending"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PENDING</option>
						<option value="processing" selected="selected"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PROCESSING</option>
						<option value="on-hold"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>ON-HOLD</option>
						<option value="completed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>COMPLETED</option>
						<option value="cancelled"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>CANCELLED</option>
						<option value="refunded"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>REFUNDED</option>
						<option value="failed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>FAILED</option>
					</select>
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'When Mercado Pago updates a payment status to APPROVED.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Status for IN_PROCESS', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<select name="mp_order_status_inprocess_map" id="mp_order_status_inprocess_map">
						<option value="pending"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PENDING</option>
						<option value="processing"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PROCESSING</option>
						<option value="on-hold" selected="selected"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>ON-HOLD</option>
						<option value="completed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>COMPLETED</option>
						<option value="cancelled"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>CANCELLED</option>
						<option value="refunded"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>REFUNDED</option>
						<option value="failed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>FAILED</option>
					</select>
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'When Mercado Pago updates a payment status to IN_PROCESS.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Status for IN_MEDIATION', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<select name="mp_order_status_inmediation_map" id="mp_order_status_inmediation_map">
						<option value="pending"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PENDING</option>
						<option value="processing"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PROCESSING</option>
						<option value="on-hold" selected="selected"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>ON-HOLD</option>
						<option value="completed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>COMPLETED</option>
						<option value="cancelled"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>CANCELLED</option>
						<option value="refunded"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>REFUNDED</option>
						<option value="failed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>FAILED</option>
					</select>
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'When Mercado Pago updates a payment status to IN_MEDIATION.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Status for REJECTED', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<select name="mp_order_status_rejected_map" id="mp_order_status_rejected_map">
						<option value="pending"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PENDING</option>
						<option value="processing"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PROCESSING</option>
						<option value="on-hold"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>ON-HOLD</option>
						<option value="completed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>COMPLETED</option>
						<option value="cancelled"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>CANCELLED</option>
						<option value="refunded"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>REFUNDED</option>
						<option value="failed" selected="selected"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>FAILED</option>
					</select>
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'When Mercado Pago updates a payment status to REJECTED.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Status for CANCELLED', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<select name="mp_order_status_cancelled_map" id="mp_order_status_cancelled_map">
						<option value="pending"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PENDING</option>
						<option value="processing"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PROCESSING</option>
						<option value="on-hold"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>ON-HOLD</option>
						<option value="completed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>COMPLETED</option>
						<option value="cancelled" selected="selected"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>CANCELLED</option>
						<option value="refunded"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>REFUNDED</option>
						<option value="failed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>FAILED</option>
					</select>
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'When Mercado Pago updates a payment status to CANCELLED.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Status for REFUNDED', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<select name="mp_order_status_refunded_map" id="mp_order_status_refunded_map">
						<option value="pending"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PENDING</option>
						<option value="processing"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PROCESSING</option>
						<option value="on-hold"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>ON-HOLD</option>
						<option value="completed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>COMPLETED</option>
						<option value="cancelled"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>CANCELLED</option>
						<option value="refunded" selected="selected"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>REFUNDED</option>
						<option value="failed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>FAILED</option>
					</select>
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'When Mercado Pago updates a payment status to REFUNDED.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>
					<?php echo __( 'Status for CHARGED_BACK', 'woo-mercado-pago-module' ); ?>
				</label></th>
				<td>
					<select name="mp_order_status_chargedback_map" id="mp_order_status_chargedback_map">
						<option value="pending"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PENDING</option>
						<option value="processing"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>PROCESSING</option>
						<option value="on-hold"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>ON-HOLD</option>
						<option value="completed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>COMPLETED</option>
						<option value="cancelled"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>CANCELLED</option>
						<option value="refunded" selected="selected"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>REFUNDED</option>
						<option value="failed"><?php echo __( 'Update WooCommerce order to ', 'woo-mercado-pago-module' ); ?>FAILED</option>
					</select>
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'When Mercado Pago updates a payment status to CHARGED_BACK.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>

		</table>

		<table class="form-table" border="0.5" frame="above" rules="void">
			<tr>
				<th scope="row"><label><h3>
					<?php echo esc_html( __( 'Store Settings', 'woo-mercado-pago-module' ) ); ?>
				</h3></label></th>
				<td><label class="description" id="tagline-description">
					<?php echo __( 'Here you can place details about your store.', 'woo-mercado-pago-module' ); ?>
				</label></td>
			</tr>
			<tr>
				<th scope="row"><label><?php echo __( 'Store Category', 'woo-mercado-pago-module' ); ?></label></th>
				<td>
					<select name="category_id" id="category_id">
						<?php
						foreach ( WC_Woo_Mercado_Pago_Module::$store_categories_id as $key=>$value) {
							if ( $category_id == $key ) {
								echo '<option value="' . $key . '" selected="selected">' . $value . '</option>';
							} else {
								echo '<option value="' . $key . '">' . $value . '</option>';
							}
						}
						?>
					</select>
					<p class="description" id="tagline-description">
						<?php echo $store_category_message; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php echo __( 'Store Identificator', 'woo-mercado-pago-module' ); ?></label></th>
				<td>
					<input name="store_identificator" type="text" id="store_identificator" aria-describedby="tagline-description" value="<?php echo $store_identificator; ?>" class="regular-text"/>
					<p class="description" id="tagline-description">
						<?php echo esc_html(
							__( 'Please, inform a prefix to your store.', 'woo-mercado-pago-module' ) . ' ' .
							__( 'If you use your Mercado Pago account on multiple stores you should make sure that this prefix is unique as Mercado Pago will not allow orders with same identificators.', 'woo-mercado-pago-module' )
						); ?>
					</p>
				</td>
			</tr>
		</table>

		<table class="form-table" border="0.5" frame="hsides" rules="void">
			<tr>
				<th scope="row"><label><h3>
					<?php echo esc_html( __( 'Test and Debug Options', 'woo-mercado-pago-module' ) ); ?>
				</h3></label></th>
				<td><label class="description" id="tagline-description">
					<?php echo __( 'Tools for debug and testing your integration.', 'woo-mercado-pago-module' ); ?>
				</label></td>
			</tr>
			<tr>
				<th scope="row"><label><?php echo __( 'Debug and Log', 'woo-mercado-pago-module' ); ?></label></th>
				<td>
					<input name="store_category" type="text" id="store_category" aria-describedby="tagline-description" value="<?php form_option('_mp_store_category'); ?>" class="regular-text" />
					<p class="description" id="tagline-description">
						<?php echo esc_html( __( 'Define which type of products your store sells.', 'woo-mercado-pago-module' ) ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php do_settings_sections( 'mercadopago' ); ?>

		<?php submit_button(); ?>

	</form>

</div>