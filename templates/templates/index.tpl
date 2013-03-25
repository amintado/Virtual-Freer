{* smarty *}
{include file='header.tpl' title=$config.config_site_title description=$config.config_site_description index=1}
<form method="post" id="meu_formulario">
<div class="right-div">
<div><select name="category" id="category" style="width:344px;">
  <option value="0">انتخاب کنید</option>
{foreach $categories as $category}
	<option value="{$category.category_id}" {if $category.category_image}title="{$category.category_image}?w=30&h=30"{/if}>{$category.category_title}</option>
{/foreach}
</select><div class="cleaner"></div></div>
<div id="waiting" class="box">{$config.config_operator_description|htmlspecialchars_decode}</div>
{foreach $categories as $category}
	<div id="product_{$category.category_id}" class="box"><ul>{if $category.category_image}<img src="{$category.category_image}?w=100&h=100" align="left">{/if}
	{foreach $category.products as $product}
		<li><input type="radio" value="{$product.product_id}" name="card" id="card_{$product.product_id}" {if $product.counter == 0}disabled{/if}> <label for="card_{$product.product_id}">{$product.product_title}</label><span id="price_{$product.product_id}" style="display: none;">{$product.product_price}</span></li>
	{/foreach}
	</ul><div class="cleaner"></div></div>
{/foreach}
<div class="box marg">تعداد: <select name="qty" size="1" class="form" id="qty" style="width:50px"><option value="1">۱</option><option value="2">۲</option><option value="3">۳</option><option value="4">۴</option><option value="5">۵</option></select> عدد</div>
<div class="box marg">
	<div class="bill">
	<table width="100%">
		<tr>
			<td width="35%">نوع کارت</td>
			<td><div id="billType">-</div></td>
		</tr>
		<tr>
			<td>قیمت واحد</td>
			<td><div id="billPrice">-</div></td>
		</tr>
		<tr>
			<td>تعداد</td>
			<td><div id="billQty">-</div></td>
		</tr>
		<tr>
			<td>جمع کل</td>
			<td><div id="billTotal">-</div></td>
		</tr>
	</table>
	</div>
</div>
</div>
<div class="left-div">
	<div class="info">
		<h2>اطلاعات تماس</h2>
		<p>ایمیل و شماره تلفن همراه خود را وارد کنید:</p>
		<label for="email" class="left">ایمیل:</label> <input type="text" name="email" id="email" class="form" dir="ltr" style="width: 265px;"><div class="cleaner"></div>
		<label for="mobile" class="left">شماره همراه:</label> <input type="text" name="mobile" id="mobile" class="form" dir="ltr" style="width: 265px;"><div class="cleaner"></div>
		<div class="cleaner"></div>
	</div>
	
	<div class="info">
	<h2>اطلاعات پرداخت <span id="loader"></span></h2>
	<label for="gateway" class="left">درگاه پرداخت:</label>
	<select name="gateway" id="gateway" class="form" style="width:150px;">
	{foreach $payment_methods as $option}
		<option value="{$option.plugin_uniq}">{$option.plugin_name}</option>
	{/foreach}
	</select> <a onclick="return false;" href="" class="button" id="submit">پرداخت</a><div class="cleaner"></div>
	قابل پرداخت با کارت بانک‌های:‌
	<ul class="banks">
	{$banks_logo}
	</ul>
	</div>
</div>
</form>
<div class="cleaner"></div>
{if $config.config_admin_yahoo_id}
<a href="ymsgr:sendIM?{$config.config_admin_yahoo_id}"><img src="http://opi.yahoo.com/online?u={$config.config_admin_yahoo_id}&amp;m=g&amp;t=14&amp;l=us" align="left"/></a>
{/if}
{$config.config_description|htmlspecialchars_decode}
<div class="cleaner"></div>
{include file='footer.tpl'}
