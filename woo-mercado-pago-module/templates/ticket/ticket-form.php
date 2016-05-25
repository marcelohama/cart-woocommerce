<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div width="100%" style="margin:1px; padding:36px 36px 16px 36px; background:white; ">
	<img class="logo" src="<?php echo ( $images_path . 'mplogo.png' ); ?>" width="156" height="40" />
	<img class="logo" src="<?php echo ( $images_path . 'boleto.png' ); ?>" width="90" height="40" style="float:right;"/>
</div>
<fieldset id="mercadopago-form" style="background:white; ">
	<div style="padding:0px 36px 0px 36px;">

		<p>
			<?php echo $form_labels[ 'payment_instructions' ] ?>
			<br />
			<?php echo $form_labels[ 'ticket_note' ] ?>
		</p>
		<div class="mp-box-inputs mp-col-100">
			<select id="paymentMethodId" name="mercadopago_ticket[paymentMethodId]">
				<?php foreach ( $payment_methods as $payment ) { ?>
		  			<option value="<?php echo $payment[ 'id' ]; ?>"> <?php echo $payment[ 'name' ]; ?></option>
				<?php } ?>
			</select>
		</div>

		<div class="mp-box-inputs mp-line">
	    	<!-- <div class="mp-box-inputs mp-col-50">
	    		<input type="submit" value="Pay" id="submit"/>
	    	</div> -->
			<div class="mp-box-inputs mp-col-25">
	    		<div id="mp-box-loading">
	        	</div>
			</div>
		</div>

		<!-- utilities -->
		<div class="mp-box-inputs mp-col-100" id="mercadopago-utilities">
			<input type="hidden" id="public_key" value="<?php echo $public_key; ?>" name="mercadopago_ticket[amount]"/>
			<input type="hidden" id="site_id"  value="<?php echo $site_id; ?>" name="mercadopago_ticket[site_id]"/>
			<input type="hidden" id="amount" value="<?php echo $amount; ?>" name="mercadopago_ticket[amount]"/>
		</div>

	</div>
</fieldset>
