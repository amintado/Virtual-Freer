{* smarty *}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    {if $data}
    <title>در حال اتصال به درگاه پرداخت امن پاسارگاد</title>
    <style type="text/css">
	#main {
	    background-color: #F1F1F1;
	    border: 1px solid #CACACA;
	    height: 90px;
	    left: 50%;
	    margin-left: -265px;
	    position: absolute;
	    top: 200px;
	    width: 530px;
	}
	#main p {
	    color: #757575;
	    direction: rtl;
	    font-family: Arial;
	    font-size: 16px;
	    font-weight: bold;
	    line-height: 27px;
	    margin-top: 30px;
	    padding-right: 60px;
	    text-align: right;
	}
	input {
		vertical-align: middle;
		border: 1px solid;
		font-family: tahoma;
		font-size: 12px;
		margin:5px;
	}	
    </style>
    <script type="text/javascript">
        function doPostback() {
                var theForm = document.forms['payform'];
                if (!theForm)
                    theForm = document.payform;
                var GateChanged = document.getElementById("x_GateChanged").value;
                if (GateChanged == "1")
                    {
                        document.getElementById("Warning").style.visibility = "visible";
                        document.getElementById("Message").style.marginTop = "20px";
                        setTimeout(' var theForm = document.forms[\'payform\'];if (!theForm) theForm = document.payform;theForm.submit();', 4000);
                    }
                else
                    theForm.submit();
           } 
       
    </script>
</head>
<body>
<div id="main">
<p>
					<form action="https://paypaad.bankpasargad.com/PaymentController" method="POST" name="payform">
						<input type="hidden" name="content" value='{$data.xml}' />
						<input type="hidden" name="sign" value="{$data.sign}" />
						<center><input type="submit" class="button" value="لطفا چند لحظه صبر كنيد تا به صفحه پرداخت امن پاسارگاد منتقل شويد" /></center>
					</form>
				</p>
</div>
		<script language="javascript">
				setTimeout ( "autoForward()" , 100 );
				function autoForward() {
					document.forms.payform.submit()
				}
				</script>
				
{else}
<title>درگاه پرداخت امن پاسارگاد . ورود اطلاعات</title>
<style type="text/css">
	.main {
	    background-color: #F1F1F1;
	    border: 1px solid #CACACA;
	    left: 50%;
	    margin-left: -265px;
	    position: absolute;
	    top: 200px;
	    width: 530px;
		padding: 10px;
		direction: rtl;
		text-align: right;
		font-family: tahoma;
		font-size: 12px;
	}
	label {
		float:right;
		display: block;
		width: 75px;
		text-align: left;
		margin:5px;
	}
	input {
		border: 1px solid;
		font-family: tahoma;
		font-size: 12px;
		margin:5px;
	}
</style>
</head>
<body>
    <div class="main">
	<p><strong><center>درگاه پرداخت امن پاسارگاد (ورود اطلاعات)</center></strong>
	<p>در صورت تمایل می توانید با ورود اطلاعات نامعتبر به صورت ناشناس از درگاه پرداخت امن پاسارگاد خرید نمایید، 
	<p>ضمنا امکان پیگیری پرداخت خود را به صورت ناشناس نخواهید داشت
	{if $error}<p><font color="red">{$error}</font></p>{/if}
	<form method="post">
		<label for="label">نام:</label><input type="text" id="name" name="name" value="{$smarty.post.name}" size="40"><br />
		<label>نام خانوادگی:</label><input type="text" name="family" value="{$smarty.post.family}" size="40"><br />
		<label>&nbsp;</label><input type="submit" class="button" name="submit" value="پرداخت">
    </form>
    </div>
{/if}
</body>
