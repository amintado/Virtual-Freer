	$(document).ready(function() {
		$('div[id^="product_"]').hide();
		$('div[id="number_topup"]').hide();
		$('div[id="amount_topup"]').hide();
		$("select#category").change(function () {
			if ($("select option:selected").val() == 0) {
				$('div[id^="product_"]').hide();
				$('#waiting').show();
			}
			else
			{
				$('#waiting').hide();
				$('div[id^="product_"]').hide();
				if ($('div[id="product_'+$("select option:selected").val()+'"]:visible')) {
			      $('div[id="product_'+$("select option:selected").val()+'"]').show();
			    }
			}
		});

		$('input:radio[name=card]').click(function () {
			var price = $('#price_'+$(this).val()).text();
			var name = $("label[for='card_"+$(this).val()+"']").text();
			var qty = $("select#qty option:selected").val();
			var product_id = $('#price_'+$(this).val()).attr('product_id');
			if(product_id >= 20 && product_id <= 28)
			{
				$('div[id="number_topup"]').slideDown();
				$('div[id="qty_div"]').slideUp();
				$('#qty_tr').slideUp();
				$('#price_tr').slideUp();
				$('#mobile_div').slideUp();
			}
			else
			{
				$('div[id="number_topup"]').slideUp();
			}
			
			if(product_id >= 20 && product_id <= 23)
			{
				price = $('#topup_amount').val();
				$('div[id="amount_topup"]').slideDown();
				$('div[id="qty_div"]').slideUp();
			}
			else
			{
				$('div[id="amount_topup"]').slideUp();
			}
			
			if(product_id < 20 || product_id > 28)
			{
				$('div[id="qty_div"]').slideDown();
				$('#qty_tr').slideDown();
				$('#price_tr').slideDown();
				$('#mobile_div').slideDown();
			}
			
			$("#billType").html(name);
			$("#billPrice").html(ReplaceNumbers(price)+' ریال ');
			$("#billQty").html(ReplaceNumbers(qty)+' عدد');
			$("#billTotal").html(ReplaceNumbers(qty*price)+' ریال ');
		});

		$('select#qty').change(function () {
			var price = $('#price_'+$('input:radio[name=card]:checked').val()).text();
			var name = $("label[for='card_"+$('input:radio[name=card]:checked').val()+"']").text();
			var qty = $("select#qty option:selected").val();
			$("#billType").html(name);
			$("#billPrice").html(ReplaceNumbers(price)+' ریال ');
			$("#billQty").html(ReplaceNumbers(qty)+' عدد');
			$("#billTotal").html(ReplaceNumbers(qty*price)+' ریال ');
		});

		try {
				oHandler = $("#category").msDropDown({mainCSS:'dd2'}).data("dd");
				$("#ver").html($.msDropDown.version);
				} catch(e) {
					alert("Error: "+e.message);
		}
        
		$("#topup_amount").keydown(function (e) {
			if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
				(e.keyCode == 65 && e.ctrlKey === true) || 
				(e.keyCode >= 35 && e.keyCode <= 40)) {
					 return;
			}
			if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
				e.preventDefault();
			}
		});

		$('#topup_amount').keyup(function (e)
        {
            var amount = $('#topup_amount').val();
            if(!amount) amount = 0;
            $("#billTotal").html(ReplaceNumbers(amount)+' ریال');
        });
        
		var requestRunning = false;
		$("#submit").click(function() {
			if (requestRunning) {
				return;
			}
			var card 	= $('input:radio[name=card]:checked').val();
			var qty 	= $("select#qty option:selected").val();
			var gateway = $("select#gateway option:selected").val();
			var email 	= $('input:text[name=email]').val();
			var mobile 	= $('input:text[name=mobile]').val();
			var topup_mobile = $('input:text[name=topup_mobile]').val();
			var topup_amount = $('input:text[name=topup_amount]').val();
			var csrf_magic = $('input:hidden[name=csrf_magic]').val();
			
			$("#loader").html('<img src="statics/image/loader.gif" align="left">');
			$.ajax({
				type: "POST",
				url: "index.php",
				data: { card:card, qty:qty, gateway:gateway, email:email, mobile:mobile, topup_mobile:topup_mobile, topup_amount:topup_amount, csrf_magic:csrf_magic, action: "payit"},
				success: function(theResponse) {
					var theResponseSplitter 	= theResponse.split("__");
					var theResponseMessage 		= theResponseSplitter[0];
					var theResponseStatus 		= theResponseSplitter[1];
					if(theResponseStatus == 1)
					{
						window.location.href = theResponseMessage;
					}
					else
					{
						jQuery('body').showMessage({
							'thisMessage':[theResponseMessage],'className':'error','displayNavigation':false,autoClose:false,opacity:75
						});
					}
					$("#loader").empty();
					requestRunning = false;
				}
			});
			requestRunning = true;
		});
	})

	numbers = new Array();
		numbers[1] = '۱';
		numbers[2] = '۲';
		numbers[3] = '۳';
		numbers[4] = '۴';
		numbers[5] = '۵';
		numbers[6] = '۶';
		numbers[7] = '۷';
		numbers[8] = '۸';
		numbers[9] = '۹';
		numbers[0] = '۰';

	function ReplaceNumbers(value){
		array = numbers;
		var newValue='';
		value = value.toString();
		for ( var i = 0; i< value.length; i++){
			newValue += array[ parseInt(value.charAt(i)) ];
		}
		return newValue;
	}
