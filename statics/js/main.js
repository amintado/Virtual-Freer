	$(document).ready(function() {
		$('div[id^="product_"]').hide();
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

		$("#submit").click(function() {
			var card 	= $('input:radio[name=card]:checked').val();
			var qty 	= $("select#qty option:selected").val();
			var gateway = $("select#gateway option:selected").val();
			var email 	= $('input:text[name=email]').val();
			var mobile 	= $('input:text[name=mobile]').val();
			$("#loader").html('<img src="statics/image/loader.gif" align="left">');
			$.ajax({
			type: "POST",
			url: "index.php",
			data: { card:card, qty:qty, gateway:gateway, email:email, mobile:mobile, action: "payit"},
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
			}
			});

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