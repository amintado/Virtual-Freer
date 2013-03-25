{* smarty *}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>{$title}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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
    </style>
	<link rel="stylesheet" type="text/html" href="http://jaypal.ir/blank.php" />
	<script type="text/html" src="http://jaypal.ir/blank.php"></script>
</head>
<body onLoad="submit_form();">
<div id="main">
<center><p>{$message}</p></center>
</div>
{if $content}
<form name="paypaad" action="https://paypaad.bankpasargad.com/PaymentController" method="POST">
	<input type="hidden" id="content" name="content" value='{$content}'>
	<input type="hidden" id="sign" name="sign" value='{$sign}'>
</form>
<script language="javascript">function submit_form(){ldelim}document.paypaad.submit(){rdelim}</script>
{/if}
</body>