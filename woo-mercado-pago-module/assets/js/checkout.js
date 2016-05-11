var country = $country;

function loadSubDocType(value) {
	var options = [];
	var subDocType = $("#subDocType");
	if (value == "Pasaporte") {
		subDocType.hide();
	} else if (value == "CI") {
		options.push('<option value="V">V</option>');
		options.push('<option value="E">E</option>');
		subDocType.show();
	} else if (value == "RIF") {
		options.push('<option value="J">J</option>');
		options.push('<option value="P">P</option>');
		options.push('<option value="V">V</option>');
		options.push('<option value="E">E</option>');
		options.push('<option value="G">G</option>');
		subDocType.show();
	}
	subDocType.html(options.join(''));
}



function setInstallmentsByIssuerId(status, response) {
	var amount = returnAmount();
	
	var opcaoPagamento = $("#opcaoPagamentoCreditCard").val();
	var issuerId = null;
	var bin = null;
	
	if (opcaoPagamento == "Customer") {
		var card = document.querySelector('select[data-checkout="cardId"]');
		bin = card[card.options.selectedIndex].getAttribute('first_six_digits');
	} else{
		issuerId = document.querySelector('#id-issuers-options').value, amount;
		bin = getBin();
	}
	
	if (issuerId === '-1') {
		return;
	}

	var paymentMethodId = $("#payment_method").val();
	
	Mercadopago.getInstallments({
		"payment_method_id" : $("#payment_method_id").val(),
		"payment_type_id" : $("#payment_type_id").val(),
		"bin" : bin,
		"amount" : amount,
		"issuer_id" : issuerId
	}, setInstallmentInfo);
};

// Mostre as parcelas disponíveis no div 'installmentsOption'
function setInstallmentInfo(status, installments) {
	var html_options = "";
	if (status != 404 && status != 400 && installments.length > 0) {
		html_options += "<option value='' selected>{l s='Choice' mod='mercadopago'}...</option>";
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

function validateCard() {
	var opcaoPagamento = $("#opcaoPagamentoCreditCard").val();
	if(opcaoPagamento == "Customer") {
		return true;
	}
	if ($("#id-card-number").val().length == 0) {
		return false;
	}
	return true;
}


