<?php
if (!defined('ABSPATH')) {
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

<!-- std
6990185330840813
G1YyZNeO54jmuxJ4QNwOGrf0rEzW4dJe
-->
<!-- custom
PROD
PK APP_USR-1343e85c-9212-4c37-9803-3b1dc6bd3829
AT APP_USR-6990185330840813-011415-074515a8f225af6bb734be05f2631951__LB_LD__-200429801
SAND
PK TEST-c9e985fc-34d2-4518-a8f0-d1886b37ba86
AT TEST-6990185330840813-011415-4323cd45dfe446411e241f052e7bbebd__LD_LB__-200429801
-->

<!--<script defer type="text/javascript"
	src="{$this_path_ssl|unescape:'htmlall'}modules/mercadopago/views/js/jquery.dd.js"></script>
-->

<script type="text/javascript">

	// ============================================================
	// Date selectors fiil ups
	function setExpirationMonth() {
		var html_options = "";
		var currentMonth = new Date().getMonth();
		var months = [
			"January",
			"Febuary",
			"March",
			"April",
			"May",
			"June",
			"July",
			"August",
			"September",
			"October",
			"November",
			"December" ];
		for (i = 0; i < 12; i++) {
			if (currentMonth == i)
				html_options += "<option value='" + (i + 1) + "' selected>" + months[i] + "</option>";
			else
				html_options += "<option value='" + (i + 1) + "'>" + months[i] + "</option>";
		};
		$("#id-card-expiration-month").html(html_options);
	}
	function setExpirationYear() {
		var html_options = "";
		var currentYear = new Date().getFullYear();
		for (i = 0; i <= 20; i++) {
			html_options += "<option value='"
					+ (currentYear + i).toString().substr(2, 2) + "'>"
					+ (currentYear + i) + "</option>";
		};
		$("#id-card-expiration-year").html(html_options);
	}
	setExpirationMonth();
	setExpirationYear();
	// ============================================================
	
	// ============================================================
	// Checkout identification, installments setup, etc...
	function getBin() {
		var ccNumber = document.querySelector('input[data-checkout="cardNumber"]');
		return bin = ccNumber.value.replace(/[ .-]/g, '').slice(0, 6);
	}
	function returnAmount() {
		if ($("#amount_discount").text() != "") {
			return $("#total_amount_discount").text();
		} else {
			return $("#amount").val();
		}
	};
	function guessingPaymentMethod(event) {
		var bin = getBin();
		if (event.type == "keyup") {
			if (bin.length >= 6) {
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
	function setPaymentMethodInfo(status, result) {
		if (status != 404 && status != 400 && result != undefined) {
			var payment_method = result[0];
			var amount = returnAmount();
			var bin = getBin();
			if (country === "MLM" || country === "MLA") {
				// check if the issuer is necessary to pay
				var issuerMandatory = false, additionalInfo = result[0].additional_info_needed;
				for (var i = 0; i < additionalInfo.length; i++) {
					if (additionalInfo[i] == "issuer_id") {
						issuerMandatory = true;
					}
				}
				if (issuerMandatory) {
					Mercadopago.getIssuers(result[0].id, showCardIssuers);
					$("#id-issuers-options").bind("change", function() {
						setInstallmentsByIssuerId(status, result)
					});
				} else {
					document.querySelector("#id-issuers-options").options.length = 0;
					document.querySelector("#id-issuers-options").style.display = 'none';
					document.querySelector(".issuers-options").style.display = 'none';
				}
			}
			$("#id-card-number").css("background", "url(" + payment_method.secure_thumbnail + ") 98% 50% no-repeat");
			$("#payment_method_id").val(payment_method.id);
			//$("#payment_method_id").val($("input[name=card-types]:checked").val() ? $("input[name=card-types]:checked").val() + payment_method.id : payment_method.id);
			$("#payment_type_id").val(payment_method.payment_type_id);
			loadInstallments();
		} else {
			$("#id-card-number").css('background-image', '');
			$("#id-installments").html('');
		}
	};
	function addEvent(el, eventName, handler){
		if (el != null) {
			if (el.addEventListener) {
				   el.addEventListener(eventName, handler);
			} else {
				el.attachEvent('on' + eventName, function(){
				  handler.call(el);
				});
			}
		}
	};
	addEvent(document.querySelector('input[data-checkout="cardNumber"]'), 'keyup', guessingPaymentMethod);
	addEvent(document.querySelector('input[data-checkout="cardNumber"]'), 'change', guessingPaymentMethod);
	// ============================================================
	
	// ============================================================
	// Installment values load
	function loadInstallments() {
		var opcaoPagamento = $("#opcaoPagamentoCreditCard").val();
		if (opcaoPagamento == "Customer") {
			var customerCards = $("#id-customerCards");
			var id = customerCards.val();
			var card = document.querySelector('select[data-checkout="cardId"]');
			var payment_type_id = card[card.options.selectedIndex].getAttribute('payment_type_id');
			var firstSixDigits = card[card.options.selectedIndex].getAttribute('first_six_digits');
			var json = {}
			json.amount = returnAmount();
			json.bin = firstSixDigits;
			json.payment_method_id = $("#payment_method_id").val();
			json.payment_type_id = $("#payment_type_id").val();
		} else { // load Installment
			var bin = getBin();
			var json = {}
			json.amount = returnAmount();
			json.bin = bin;
			if (country === "MLM" || country === "MLA") {
				var issuerId = document.querySelector('#id-issuers-options').value;
				if (issuerId != undefined && issuerId != "-1") {
					json.issuer_id = issuerId;
					json.payment_method_id = $("#payment_method_id").val();
					json.payment_type_id = $("#payment_type_id").val();
				}
			}
		}
		try {
			Mercadopago.getInstallments(json, setInstallmentInfo);
		} catch(e) {
			console.info(e);	
		}
	}
	function setInstallmentInfo(status, installments) {
		var html_options = "";
		if (status != 404 && status != 400 && installments.length > 0) {
			html_options += "<option value='' selected>Choice...</option>";
			var installments = installments[0].payer_costs;
			$.each(installments, function(key, value) {
				html_options += "<option value='"+ value.installments + "'>"
						+ value.recommended_message + "</option>";
			});
		} else {
			console.error("Installments Not Found.");
		}
		var opcaoPagamento = $("#opcaoPagamentoCreditCard").val();
		if (opcaoPagamento == "Customer") {
			$("#id-installments-cust").html(html_options);
		} else {
			$("#id-installments").html(html_options);
		}
	};
	// ============================================================
	
	// ============================================================
	// Checkout process
	/*jQuery(document).ready(function($) {
        // hide place order button on checkout page
        $('input[name=woocommerce_checkout_place_order]').hide();
    });*/
	doSubmit = false;
	$("#form-pagar-mp").submit(
		function(event) {
			event.preventDefault();
			/*clearErrorStatus();*/
			if (!validate()) {
				event.preventDefault();
				submit = false;
			} else {
				/*var opcaoPagamento = $("#opcaoPagamentoCreditCard").val();
				if (opcaoPagamento == "Customer") {
					var $form = document.querySelector('#customerCardsAll');
					Mercadopago.createToken($form, function (status, response) {
						if (response.error) {
							$.each(response.cause, function(p,e) {
								switch (e.code) {
									case "E203":
									case "E302":
										submit = false;
										$("#id-security-code-status-cust").html("{l s='CVV invalid' mod='mercadopago'}");
										$("#id-security-code-cust").addClass("form-error");
										break;
								}
							});
							if (!submit) {
								event.preventDefault();
							}
						} else {
							$('#card_token_id').val(response.id);
							document.getElementById("form-pagar-mp").action = "{$custom_action_url|unescape:'htmlall'}";
							document.getElementById("form-pagar-mp").submit();
							$(".lightbox").show();
						}
					});
				} else {*/
					var $form = $('#form-pagar-mp');
					var $cardDiv = $('#cardDiv');
					Mercadopago.createToken($cardDiv, function(status, response) {
						if (response.error) {
							submit = false;
							event.preventDefault();
							$.each(response.cause, function(p, e) {
								switch (e.code) {
									case "E301":
										/*$("#id-card-number-status").html("Card invalid");
										$("#id-card-number").addClass("form-error");*/
										alert('Erro E301');
										break;
									case "E302":
										/*$("#id-security-code-status").html("CVV invalid");
										$("#id-security-code").addClass("form-error");*/
										alert('Erro E302');
										break;
									case "325":
									case "326":
										/*$("#id-card-expiration-year-status").html("Date invalid");
										$("#id-card-expiration-month").addClass("boxshadow-error");
										$("#id-card-expiration-year").addClass("boxshadow-error");*/
										alert('Erro 325/326');
										break;
									case "316":
									case "221":
										/*$("#id-card-holder-name-status").html("Name invalid");
										$("#id-card-holder-name").addClass("form-error");*/
										alert('Erro 316/221');
										break;
									case "324":
									case "214":
										/*$("#id-doc-number-status").html("Document invalid");
										$("#id-doc-number").addClass("form-error");*/
										alert('Erro 324/214');
										break;
								}
							});
						} else {
							submit = true;
							var card_token_id = response.id;
							$form.append($('<input type="hidden" id="card_token_id" name="card_token_id"/>').val(card_token_id));
							var cardNumber = $("#id-card-number").val();
							var lastFourDigits = cardNumber.substring(cardNumber.length-4);
							$form.append($('<input name="lastFourDigits" type="hidden" value="' + lastFourDigits + '"/>'));
							document.getElementById("form-pagar-mp").action = "{$custom_action_url|unescape:'htmlall'}";
							document.getElementById("form-pagar-mp").submit();
							$(".lightbox").show();
						}
					});
				/*}*/
			}
		}
	)
	// ============================================================
	// Validations
	function validate() {
		/*var opcaoPagamento = $("#opcaoPagamentoCreditCard").val();
		if (opcaoPagamento == "Customer") {
			if ($("#id-customerCards").val().length == 0) {
				$("#id-card-number-status-cust").html(
						"{l s='Card invalid' mod='mercadopago'}");
				$( "#id-customerCards_msdd" ).addClass("form-error");
			}
			if ($("#id-security-code-cust").val().length == 0) {
				$("#id-security-code-status-cust").html(
						"{l s='CVV invalid' mod='mercadopago'}");
				$("#id-security-code-cust").addClass("form-error");
			}
			if ($("#id-installments-cust").val() == null
					|| $("#id-installments-cust").val().length == 0) {
				$("#id-installments-status-cust").html(
						"{l s='Installments invalid' mod='mercadopago'}");
				$("#id-installments-cust").addClass("form-error");
			}
			if ($("#id-installments-cust").val() == null
					|| $("#id-installments-cust").val().length == 0
					|| $("#id-security-code-cust").val().length == 0
					|| $("#id-customerCards").val().length == 0
					) {
				return false;
			}
			return true;
		}
		if ($("#id-card-number").val().length == 0) {
			$("#id-card-number-status").html(
					"{l s='Card invalid' mod='mercadopago'}");
			$("#id-card-number").addClass("form-error");
		}
		if ($("#id-card-holder-name").val().length == 0) {
			$("#id-card-holder-name-status").html(
					"{l s='Name invalid' mod='mercadopago'}");
			$("#id-card-holder-name").addClass("form-error");
		}
		if ($("#id-security-code").val().length == 0) {
			$("#id-security-code-status").html(
					"{l s='CVV invalid' mod='mercadopago'}");
			$("#id-security-code").addClass("form-error");
		}
		if ($("#id-docType").val() == null || $("#id-docType").val() == "") {
			$("#id-docType").addClass("form-error");
		}
		if (country != "MLM") {
			if ($("#id-doc-number").val().length == 0) {
				$("#id-doc-number-status").html(
						"{l s='Document invalid' mod='mercadopago'}");
				$("#id-doc-number").addClass("form-error");
			}
		} else {
		}
		if ($("#id-installments").val() == null
				|| $("#id-installments").val().length == 0) {
			$("#id-installments-status").html(
					"{l s='Installments invalid' mod='mercadopago'}");
			$("#id-installments").addClass("form-error");
		}
		if ($("#id-installments").val() == null
				|| $("#id-installments").val().length == 0
				|| $("#id-security-code").val().length == 0
				|| $("#id-card-holder-name").val().length == 0
				|| $("#id-card-number").val().length == 0
				
				|| (country != "MLM" && $("#id-doc-number").val().length == 0)) {
			return false;
		}
		if (country == "MLB") {
			if (!validateCpf($("#id-doc-number").val())) {
				$("#id-doc-number-status").html(
						"{l s='CPF invalid' mod='mercadopago'}");
				$("#id-doc-number").addClass("form-error");
				return false;
			}
		}*/
		return true;
	}
	function validateCpf(cpf) {
		var sum = 0;
		var remainer;
		if (cpf == "00000000000")
			return false;
		for (i=1; i<=9; i++) {
			sum = sum + parseInt(cpf.substring(i-1, i))*(11-i);
			remainer = (sum*10)%11;
		}
		if ((remainer == 10) || (remainer == 11))
			remainer = 0;
		if (remainer != parseInt(cpf.substring(9, 10)))
			return false;
		sum = 0;
		for (i=1; i<=10; i++) {
			sum = sum + parseInt(cpf.substring(i-1, i))*(12-i);
			remainer = (sum*10)%11;
		}
		if ((remainer == 10) || (remainer == 11))
			remainer = 0;
		if (remainer != parseInt(cpf.substring(10, 11))) {
			return false;
		} else {
			return true;
		}
	}
	// ============================================================
	
	// first load force to clear all fields
	$("#id-card-number").val("");
	$("#id-security-code").val("");
	$("#id-card-holder-name").val("");
	$("#id-doc-number").val("");
	
	// Checkout trigger
	var country = "{$country|escape:'javascript'}";
	if (window.Mercadopago === undefined) {
		$.getScript("https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js")
		.done(function(script, textStatus) {
			Mercadopago.setPublishableKey('TEST-c9e985fc-34d2-4518-a8f0-d1886b37ba86');
			Mercadopago.getIdentificationTypes();
		});
	}
	
</script>

<!--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">-->
<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
	
<fieldset id="mercadopago-credit-cart-form" style="background:white;">
	
	<div class="mp-module">
		<div class="payment_module mp-form-custom">
			<div class="row">
				<img class="logo" src="<?php echo $mplogo_path; ?>" width="156" height="40" />
				<?php if (!empty($banner_path)) { ?>
					<img class="mp-creditcard-banner" src="<?php echo $banner_path; ?>" width="312" height="40" />
				<?php } ?>
			</div>
			<form action="<?php echo $custom_action_url; ?>" method="post" id="form-pagar-mp">
				<input id="amount" type="hidden" value="<?php echo $amount; ?>" />
				<input id="payment_method_id" type="hidden" name="payment_method_id" />
				<input id="payment_type_id" type="hidden" name="payment_type_id" />
				<input name="mercadopago_coupon" type="hidden" class="mercadopago_coupon_ticket" />
				
				<div id="cardDiv">
					<div class="row">
						<div class="col" style="margin-top: 32px;>
							<label for="id-card-number">Card number: <em>*</em></label> 
							<input id="id-card-number" data-checkout="cardNumber" type="text" />
							<div id="id-card-number-status" class="status"></div>
						</div>
					</div>
					<div class="row">
						<div class="col">
							<label for="id-card-expiration-month">Expiration: <em>*</em></label> 
							<select id="id-card-expiration-month" class="small-select" data-checkout="cardExpirationMonth" type="text"></select>
						</div>
						<div class="col">
							<select id="id-card-expiration-year" class="small-select" data-checkout="cardExpirationYear" type="text"></select>
							<div id="id-card-expiration-year-status" class="status"></div>
						</div>
					</div>
					<div class="row">
						<div class="col">
							<label for="id-card-holder-name">Card Holder Name: <em>*</em></label> 
							<input id="id-card-holder-name" data-checkout="cardholderName" type="text" name="cardholderName" />
							<div id="id-card-holder-name-status" class="status"></div>
						</div>
					</div>
					<div class="row">
						<div class="col">
							<label for="id-security-code" style="font-weight: 700;">Security Code: <em>*</em></label> 
							<input id="id-security-code" data-checkout="securityCode" type="text" maxlength="4" /> 
							<img src="<?php echo $cvv_path; ?>" class="cvv" />
							<div id="id-security-code-status" class="status"></div>
						</div>
					</div>
					<?php if ($country == 'MLB') { ?>
						<div class="row">
							<div class="col">
								<label for="id-doc-number">CPF: <em>*</em></label> 
								<input id="id-doc-number" data-checkout="docNumber" type="text" maxlength="11" />
								<div id="id-doc-number-status" class="status"></div>
								<input name="docType" data-checkout="docType" type="hidden" id="id-docType" value="CPF" />
							</div>
						</div>
					<?php } elseif ($country == 'MLM' || $country == 'MLA') { ?>
						<div class="row">
							<div class="col">
								<label class="issuers-options" for="id-issuers-options">Bank: <em>*</em>
								</label> <select class="issuers-options" id="id-issuers-options" name="issuersOptions" type="text"></select>
							</div>
						</div>
					<?php } if ($country == 'MLM') { ?>
						<div class="row">
							<div class="col">
								<label for="card-types">Card Type: </label> 
								<input id="id-credit-card" name="card-types" type="radio" value="" checked>Credit</input> 
								<input id="id-debit-card" name="card-types" type="radio" value="deb">Debit</input>
							</div>
						</div>
					<?php } elseif ($country == 'MLA' || $country == 'MCO' || $country == 'MLV') { ?> {
						<div class="row">
							<div class="col">
								<label for="docType">Document type: <em>*</em></label> 
								<select name="docType" type="text" class="document-type" d="id-docType" style="width: 92px;" data-checkout="docType"></select>
							</div>
							<div class="col">
								<input id="id-doc-number" name="docNumber" style="width: 102px;" data-checkout="docNumber" type="text" />
								<div id="id-doc-number-status" class="status"></div>
							</div>
						</div>
						<div class="row"></div>
					<?php } if ($country == 'MLC') { ?>
						<div class="row">
							<div class="col">
								<label for="id-doc-number">RUT: <em>*</em></label> 
								<input type="hidden" name="docType" id="docType" value="RUT" id="id-docType" data-checkout="docType"> 
								<input type="text" id="id-doc-number" data-checkout="docNumber" maxlength="10" size="14" placeholder="11111111-1">
								<div id="id-doc-number-status" class="status"></div>
							</div>
						</div>
					<?php } ?>
					<div class="row">
						<div class="col">
							<label for="id-installments">Installments: <em>*</em></label> 
							<select id="id-installments" name="installments" type="text"></select>
							<div id="id-installments-status" class="status"></div>
						</div>
					</div>
					<div class="row" style="display: none;">
						<div class="col-xs-12">
							<p class="payment-errors"></p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-bottom">
						<?php if ($country != "MLB") { ?>
							<button class="ch-btn ch-btn-big submit" value="Confirm payment" type="submit" id="btnSubmit"> Confirm payment</button>
						<?php } else { ?>
							<button class="ch-btn ch-btn-big es-button submit" value="Confirm payment" type="submit" id="btnSubmit"> Confirm payment</button>
						<?php } ?>
					</div>
				</div>
				<!--<div class="row">
					<div class="col" style="margin-top: 32px;">
						<label for="id-card-number">Card number: </label> 
						<input id="id-card-number" data-checkout="cardNumber" type="text" />
						<div id="id-card-number-status" class="status"></div>
					</div>
					<div class="col col-expiration">
						<label for="id-card-expiration-month">Month Exp: </label> 
						<select id="id-card-expiration-month"
							class="small-select" data-checkout="cardExpirationMonth"
							type="text">
						</select>
						<label for="id-card-expiration-month">Year Exp: </label> 
						<select id="id-card-expiration-year"
							class="small-select" data-checkout="cardExpirationYear"
							type="text">
						</select>
						<div id="id-card-expiration-year-status" class="status"></div>
					</div>
					<div class="col">
						<label for="id-card-holder-name">Card Holder Name: </label> 
						<input id="id-card-holder-name" data-checkout="cardholderName" type="text" name="cardholderName" />
						<div id="id-card-holder-name-status" class="status"></div>
					</div>
				</div>
				
				<div class="row">
					<div class="col col-security">
						<label for="id-security-code">Security Code: </label> 
						<input id="id-security-code" data-checkout="securityCode" type="text" maxlength="4" />
						<img src="<?php /*echo $cvv_path;*/ ?>" class="cvv" />
						<div id="id-security-code-status" class="status"></div>
					</div>
					<?php /*if ($country == 'MLB') {*/ ?>
						<div class="col col-cpf">
							<label for="id-doc-number">CPF: </label>
							<input id="id-doc-number" name="docNumber" data-checkout="docNumber" type="text" maxlength="11" />
							<div id="id-doc-number-status" class="status"></div>
							<input name="docType" data-checkout="docType" type="hidden" id="id-docType" value="CPF" />
						</div>
					<?php /*} elseif ($country == 'MLM' || $country == 'MLA') {*/ ?>
						<div class="col col-bank">
							<label class="issuers-options" for="id-issuers-options">Bank: </label> 
							<select class="issuers-options" id="id-issuers-options" name="issuersOptions" type="text>"></select>
						</div>
					<?php /*}*/ ?>
					<div class="col">
						<label for="id-installments">Installments: </label> 
						<select id="id-installments" name="installments" type="text"></select>
						<div id="id-installments-status" class="status"></div>
					</div>
				</div>

				<div class="row">
					<div class="col-bottom">
						<?php /*if ($country == 'MLM') {*/ ?>
							<div id="div-card-type">
								<label for="card-types">Card Type: </label> 
								<input id="id-credit-card" name="card-types" type="radio" value="" checked>Credit: </input> 
								<input id="id-debit-card" name="card-types" type="radio" value="deb">Debit: </input>
							</div>
						<?php /*} elseif ($country == 'MLA') {*/ ?>
							<div class="row">
								<div class="col">
									<label for="docType">Document type: </label> 
									<select name="docType" id="id-docType" data-checkout="docType"></select>
								</div>
								<div class="col">
									<input id="id-doc-number" name="docNumber" data-checkout="docNumber" type="text" />
									<div id="id-doc-number-status" class="status"></div>
								</div>
							</div>
						<?php /*}*/ ?>
						<input type="submit" value="Confirm payment" class="ch-btn ch-btn-big submit" />
					</div>
				</div>-->
			</form>
		</div>
	</div>
	
</fieldset>
