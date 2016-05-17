<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- VISA TEST CARDS
 Argentina: 4509 9535 6623 3704
 Brazil: 4235 6477 2802 5682
 Mexico: 4357 6064 1502 1810
 Venezuela: 4966 3823 3110 9310
 Colombia: 4013 5406 8274 6260
-->
<!-- MASTERCARD TEST CARDS
 Argentina: 5031 7557 3453 0604
 Brazil: 5031 4332 1540 6351
 Mexico: 5031 7531 3431 1717
 Venezuela: 5177 0761 6430 0010
 Colombia: 5254 1336 7440 3564
-->
<!-- AMEX TEST CARDS
 Argentina: 3711 803032 57522
 Brazil: 3753 651535 56885
 Mexico: not available
 Venezuela: not available
 Colombia: 3743 781877 55283
-->

<div width="100%" style="margin:1px; padding:16px; background:white;">
	<img class="logo" src="<?php echo ( $images_path . 'mplogo.png' ); ?>" width="156" height="40" />
	<?php if ( !empty( $banner_path ) ) { ?>
		<img class="mp-creditcard-banner" src="<?php echo $banner_path; ?>" width="312" height="40" />
	<?php } ?>
</div>
<fieldset id="mercadopago-form" style="background:white;">
	
	<input id="public_key" type="hidden" value="<?php echo $public_key; ?>" />
	<input id="amount" type="hidden" value="<?php echo $amount; ?>" />

	<div class="mp-box-inputs mp-line mp-paymentMethodsSelector" style="display:none;">
        <label for="paymentMethodIdSelector">Payment Method <em>*</em></label>
        <select id="paymentMethodIdSelector" name="paymentMethodIdSelector" data-checkout="paymentMethodIdSelector"></select>
  	</div>
  	<div class="mp-box-inputs mp-line">
		<div class="mp-box-inputs mp-col-45">
			<label for="cardNumber">Credit card number <em>*</em></label>
			<input type="text" id="cardNumber" data-checkout="cardNumber" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" />
		</div>
		<div class="mp-box-inputs mp-col-10">
			<div id="mp-separete-date">&nbsp;</div>
	    </div>
	    <div class="mp-box-inputs mp-col-45">
	    	<label style="font-size: 6px;">&nbsp;</label>
	    	<label for="cardExpirationMonth" style="font-size: 14px;">Accepted cards for this store:</label>
	    	<span>
	    		<?php for ($x=0; $x<sizeof($accepted_payments); $x++): ?>
	    			<img class="logo" src="<?php echo $accepted_payments[ $x ]; ?>" width="28.33" height="11.66" />
        		<?php endfor; ?>
	    	</span>
	    </div>
	</div>
	<div class="mp-box-inputs mp-line">
		<div class="mp-box-inputs mp-col-45">
      		<label for="cardExpirationMonth">Expiration month <em>*</em></label>
			<select id="cardExpirationMonth" data-checkout="cardExpirationMonth">
        	<option value="-1"> Month </option>
				<?php for ($x=1; $x<=12; $x++): ?>
					<option value="<?php echo $x; ?>"> <?php echo $x; ?></option>
        		<?php endfor; ?>
			</select>
    	</div>
    	<div class="mp-box-inputs mp-col-10">
			<div id="mp-separete-date">&nbsp;</div>
	    </div>
    	<div class="mp-box-inputs mp-col-45">
			<label for="cardExpirationYear">Expiration year <em>*</em></label>
			<select  id="cardExpirationYear" data-checkout="cardExpirationYear">
				<option value="-1"> Year </option>
        		<?php for ($x=date("Y"); $x<= date("Y") + 10; $x++): ?>
  					<option value="<?php echo $x; ?>"> <?php echo $x; ?> </option>
    			<?php endfor; ?>
      		</select>
    	</div>
	</div>
	<div class="mp-box-inputs mp-col-100">
    	<label for="cardholderName">Card holder name <em>*</em></label>
        <input type="text" id="cardholderName" name="cardholderName" data-checkout="cardholderName" placeholder=" as it appears in your card ... " />
  	</div>
	<div class="mp-box-inputs mp-line">
    	<div class="mp-box-inputs mp-col-45">
			<label for="securityCode">Security code <em>*</em></label>
    		<input type="text" id="securityCode" data-checkout="securityCode" placeholder="" style="font-size: 1.5em; padding: 8px; background: url( <?php echo ( $images_path . 'cvv.png' ); ?> ) 98% 50% no-repeat;"/>
		</div>
	</div>
	<div class="mp-box-inputs mp-col-100 mp-doc">
    	<div class="mp-box-inputs mp-col-25 mp-docType">
			<label for="docType">Type <em>*</em></label>
			<select id="docType" name="docType" data-checkout="docType"></select>
    	</div>
	<div class="mp-box-inputs mp-col-75 mp-docNumber">
    	<label for="docNumber">Document number <em>*</em></label>
    	<input type="text" id="docNumber" name="docNumber" data-checkout="docNumber" placeholder="" />
    </div>
    <div class="mp-box-inputs mp-col-100 mp-issuer">
        <label for="issuer">Issuer <em>*</em></label>
        <select id="issuer" name="issuer" data-checkout="issuer"></select>
    </div>
	<div class="mp-box-inputs mp-col-100">
    	<label for="installments">Installments <em>*</em></label>
        <select id="installments" name="installments" data-checkout="installments"></select>
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
	<div class="mp-box-inputs mp-col-100">
        <input type="text" name="site_id" id="site_id" />
        <input type="text" name="amount" id="amount" value="249.99"/>
        <input type="text" name="paymentMethodId" id="paymentMethodId"/>
        <input type="text" name="token" id="token"/>
	</div>
</fieldset>

<script type="text/javascript">

	function getBin() {
		var cardSelector = document.querySelector("#cardId");
		if (cardSelector && cardSelector[cardSelector.options.selectedIndex].value != "-1") {
	    	return cardSelector[cardSelector.options.selectedIndex].getAttribute('first_six_digits');
	  	}
	  	var ccNumber = document.querySelector('input[data-checkout="cardNumber"]');
	  	return ccNumber.value.replace(/[ .-]/g, '').slice(0, 6);
	}

	function clearOptions() {
		var bin = getBin();
	  	if (bin.length == 0) {
		    //document.querySelector("#issuer").style.display = 'none';
		    // document.querySelector("#issuer").setAttribute('disabled', 'disabled');
		    // document.querySelector("#issuer").innerHTML = "";
		    // console.log("clearOptions - hide issuer");
		    hideIssuer();

		    var selectorInstallments = document.querySelector("#installments"),
		    fragment = document.createDocumentFragment(),
		    option = new Option("Choose...", '-1');

		    selectorInstallments.options.length = 0;
		    fragment.appendChild(option);
		    selectorInstallments.appendChild(fragment);
		    selectorInstallments.setAttribute('disabled', 'disabled');
		}
	}

	function guessingPaymentMethod(event) {
		var bin = getBin(),
	  	amount = document.querySelector('#amount').value;
	  	if (event.type == "keyup") {
	    	if (bin.length == 6) {
	      		Mercadopago.getPaymentMethod({
	        		"bin": bin
	      		}, setPaymentMethodInfo);
	    	}
  		} else {
			setTimeout(function() {
	    		if (bin.length >= 6) {
	        		Mercadopago.getPaymentMethod({
	        			"bin": bin
	        		}, setPaymentMethodInfo);
				}
    		}, 100);
		}
	};

	function setPaymentMethodInfo(status, response) {
		if (status == 200) {
		    // do somethings ex: show logo of the payment method
		    var form = document.querySelector('#pay');
		    if (config_mp.site_id != "MLM") {
				if (document.querySelector("input[name=paymentMethodId]") == null) {
			        var paymentMethod = document.createElement('input');
			        paymentMethod.setAttribute('name', "paymentMethodId");
			        paymentMethod.setAttribute('type', "hidden");
			        paymentMethod.setAttribute('value', response[0].id);
			        form.appendChild(paymentMethod);
				} else {
		        	document.querySelector("input[name=paymentMethodId]").value = response[0].id;
				}
				document.querySelector('input[data-checkout="cardNumber"]').style.background = "url(" + response[0].secure_thumbnail + ") 98% 50% no-repeat #fff";
		    }
			// check if the security code (ex: Tarshop) is required
			var cardConfiguration = response[0].settings,
			bin = getBin(),
			amount = document.querySelector('#amount').value;
			for (var index = 0; index < cardConfiguration.length; index++) {
	      		if (bin.match(cardConfiguration[index].bin.pattern) != null && cardConfiguration[index].security_code.length == 0) {
					// In this case you do not need the Security code. You can hide the input.
				} else {
					// In this case you NEED the Security code. You MUST show the input.
	      		}
	    	}
			Mercadopago.getInstallments({
				"bin": bin,
				"amount": amount
	    	}, setInstallmentInfo);
			// check if the issuer is necessary to pay
		    var issuerMandatory = false,
		    additionalInfo = response[0].additional_info_needed;
			for (var i = 0; i < additionalInfo.length; i++) {
	      		if (additionalInfo[i] == "issuer_id") {
	        		issuerMandatory = true;
	      		}
	    	};
	    	if (issuerMandatory) {
	      		Mercadopago.getIssuers(response[0].id, showCardIssuers);
	      		addEvent(document.querySelector('#issuer'), 'change', setInstallmentsByIssuerId);
    		} else {
		      	// document.querySelector("#issuer").style.display = 'none';
				// document.querySelector("#issuer").setAttribute('disabled', 'disabled');
				// document.querySelector("#issuer").options.length = 0;
				// console.log("setPaymentMethodInfo - hide issuer");
		      	hideIssuer();
	    	}
		}
	};

	function showCardIssuers(status, issuers) {
		console.log("showCardIssuers - here");
		var issuersSelector = document.querySelector("#issuer"),
		fragment = document.createDocumentFragment();
		issuersSelector.options.length = 0;
		var option = new Option("Choose...", '-1');
		fragment.appendChild(option);
		for (var i = 0; i < issuers.length; i++) {
	    	if (issuers[i].name != "default") {
	      		option = new Option(issuers[i].name, issuers[i].id);
	    	} else {
	      		option = new Option("Otro", issuers[i].id);
	    	}
	    	fragment.appendChild(option);
		}
		issuersSelector.appendChild(fragment);
		issuersSelector.removeAttribute('disabled');
	  	// document.querySelector("#issuer").removeAttribute('style');
	};

	function setInstallmentsByIssuerId(status, response) {
		var issuerId = document.querySelector('#issuer').value,
	  	amount = document.querySelector('#amount').value;
	  	if (issuerId === '-1') {
	    	return;
	  	}
		Mercadopago.getInstallments({
		    "bin": getBin(),
		    "amount": amount,
		    "issuer_id": issuerId
	  	}, setInstallmentInfo);
	};

	function setInstallmentInfo(status, response) {
		var selectorInstallments = document.querySelector("#installments"),
		fragment = document.createDocumentFragment();
		selectorInstallments.options.length = 0;
		if (response.length > 0) {
		    var option = new Option("Choose...", '-1'),
		    payerCosts = response[0].payer_costs;
			fragment.appendChild(option);
	    	for (var i = 0; i < payerCosts.length; i++) {
	      		option = new Option(payerCosts[i].recommended_message || payerCosts[i].installments, payerCosts[i].installments);
	      		fragment.appendChild(option);
	    	}
		    selectorInstallments.appendChild(fragment);
		    selectorInstallments.removeAttribute('disabled');
	  	}
	};

	function cardsHandler() {
		clearOptions();
	  	var cardSelector = document.querySelector("#cardId"),
	  	amount = document.querySelector('#amount').value;
		if (cardSelector && cardSelector[cardSelector.options.selectedIndex].value != "-1") {
	    	var _bin = cardSelector[cardSelector.options.selectedIndex].getAttribute("first_six_digits");
	    	Mercadopago.getPaymentMethod({
	      		"bin": _bin
    		}, setPaymentMethodInfo);
	  	}
	};

	function hideIssuer() {
		var opt = document.createElement('option');
		opt.value = "1";
		opt.innerHTML = "Other Bank";
		document.querySelector("#issuer").innerHTML = "";
		document.querySelector("#issuer").appendChild(opt);
	  	document.querySelector("#issuer").setAttribute('disabled', 'disabled');
	};

	function addEvent(el, eventName, handler) {
		if (el.addEventListener) {
	    	el.addEventListener(eventName, handler);
	  	} else {
	    	el.attachEvent('on' + eventName, function() {
	      		handler.call(el);
	    	});
		}
	};

	/*
	* Payment Methods
	*/
	function getPaymentMethods() {
		var paymentMethodsSelector = document.querySelector("#paymentMethodIdSelector");
		// set loading
		paymentMethodsSelector.style.background = "url("+config_mp.paths.loading+") 95% 50% no-repeat #fff";
		Mercadopago.getAllPaymentMethods(function(code, payment_methods) {
	    	fragment = document.createDocumentFragment();
		    option = new Option("Choose...", '-1');
	    	fragment.appendChild(option);
	    	for (var x=0; x < payment_methods.length; x++) {
	      		var pm = payment_methods[x];
	      		if ((pm.payment_type_id == "credit_card" || pm.payment_type_id == "debit_card" || pm.payment_type_id == "prepaid_card") && pm.status == "active") {
					option = new Option(pm.name, pm.id);
			        fragment.appendChild(option);
      			} //end if
	    	} // end for
	    	paymentMethodsSelector.appendChild(fragment);
	    	paymentMethodsSelector.style.background = "#fff";
	  	});
	};

	/*
	* Functions related to Create Tokens
	*/
	function createTokenByEvent() {
		for(var x = 0; x < document.querySelectorAll('[data-checkout]').length; x++) {
			var element = document.querySelectorAll('[data-checkout]')[x];
		    var event = "focusout";
		    if (element.nodeName == "SELECT") {
		      event = "change";
		    }
		    // add on element data-checkout
		    addEvent(element, event, validateInputsCreateToken);
	  	}
	};

	function createTokenBySubmit() {

		addEvent(document.querySelector(config_mp.selectors.form), 'submit', doPay);
	};

	var doSubmit = false;
	function doPay(event) {
		console.log("doPay");
		event.preventDefault();
	   	if (!doSubmit) {
			createToken();
	    	return false;
	  	}
	};

	function validateInputsCreateToken() {
		console.log("validateInputsCreateToken");
		var valid_to_create_token = true;
		for (var x = 0; x < document.querySelectorAll('[data-checkout]').length; x++) {
	    	var element = document.querySelectorAll('[data-checkout]')[x];
	    	// check is a input to create token
	    	if (config_mp.inputs_to_create_token.indexOf(element.getAttribute("data-checkout")) > -1) {
	        	if (element.value == -1 || element.value == "") {
		        	valid_to_create_token = false;
		      	} // end if check values
			} // end if check data-checkout
		} // end for
		if (valid_to_create_token) {
	    	createToken();
	  	}
	};

	function createToken() {
		// show loading
		document.querySelector(config_mp.selectors.box_loading).style.background = "url("+config_mp.paths.loading+") 50% 50% no-repeat #fff";
		// form
		var $form = document.querySelector(config_mp.selectors.form);
		Mercadopago.createToken($form, sdkResponseHandler);
		return false;
	};

	function sdkResponseHandler(status, response) {
		// hide loading
	  	document.querySelector(config_mp.selectors.box_loading).style.background = "";
		if (status != 200 && status != 201) {
	    	console.log(response);
	  	} else {
		    var token = document.querySelector('#token');
		    token.value = response.id;
		    doSubmit = true;
			if (!config_mp.create_token_on_event) {
				btn = document.querySelector(config_mp.selectors.form);
				btn.submit();
	    	}
	  	}
	};

	/*
	* Add events to guessing
	*/
	addEvent(document.querySelector('input[data-checkout="cardNumber"]'), 'keyup', guessingPaymentMethod);
	addEvent(document.querySelector('input[data-checkout="cardNumber"]'), 'keyup', clearOptions);
	addEvent(document.querySelector('input[data-checkout="cardNumber"]'), 'change', guessingPaymentMethod);
	cardsHandler();

	/*
	* Initialization functions
	*/
	var config_mp = {
		create_token_on_event: true,
		site_id: '<?php echo $site_id;?>',
		public_key: '<?php echo $public_key;?>',
		inputs_to_create_token: [
		    "cardNumber",
		    "cardExpirationMonth",
		    "cardExpirationYear",
		    "cardholderName",
		    "securityCode",
		    "docType",
		    "docNumber"
	  	],
		selectors:{
		    submit: "#submit",
		    box_loading: "#mp-box-loading",
		    site_id: "#site_id",
		    form: '#mercadopago-form'
		},
	  	paths:{
	    	loading: "<?php echo ( $images_path . 'loading.gif' ); ?>"
	  	}
	}

	if (config_mp.create_token_on_event) {
		createTokenByEvent();
	} else {
		createTokenBySubmit()
	}

	Mercadopago.setPublishableKey(config_mp.public_key);
	if (config_mp.site_id != "MLM") {
		Mercadopago.getIdentificationTypes();
	}

	if (config_mp.site_id == "MLM") {
		// hide documento for mex
		document.querySelector(".mp-doc").style.display = 'none';
		document.querySelector(".mp-paymentMethodsSelector").removeAttribute('style');
		// removing not used fields for this country
		config_mp.inputs_to_create_token.splice(config_mp.inputs_to_create_token.indexOf("docType"), 1);
		config_mp.inputs_to_create_token.splice(config_mp.inputs_to_create_token.indexOf("docNumber"), 1);
		//get payment methods and populate selector
		getPaymentMethods();
	}

	if (config_mp.site_id == "MLB") {
	  document.querySelector(".mp-docType").style.display = 'none';
	  document.querySelector(".mp-issuer").style.display = 'none';
	  //ajust css
	  document.querySelector(".mp-docNumber").classList.remove("mp-col-75");
	  document.querySelector(".mp-docNumber").classList.add("mp-col-100");
	} else if (config_mp.site_id == "MCO") {
		document.querySelector(".mp-issuer").style.display = 'none';
	}

	document.querySelector(config_mp.selectors.site_id).value = config_mp.site_id;

	console.log(config_mp);
	
</script>
