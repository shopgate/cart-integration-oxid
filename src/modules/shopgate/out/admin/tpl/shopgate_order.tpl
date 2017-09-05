[{assign var="order" value=$oView->getShopgateOrder()}]
[{assign var="orderData" value=$order->getOrderData()}]

[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]
[{if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]


<script type="text/javascript">
window.onload = function () {
    [{if $updatelist == 1}]
        top.oxid.admin.updateList('[{ $oxid }]');
    [{/if}]
    top.reloadEditFrame();
};

function editThis(sID) {
    var oTransfer = top.basefrm.edit.document.getElementById( "transfer" );
    oTransfer.oxid.value = sID;
    oTransfer.cl.value = top.basefrm.list.sDefClass;

    //forcing edit frame to reload after submit
    top.forceReloadingEditFrame();

    var oSearch = top.basefrm.list.document.getElementById( "search" );
    oSearch.oxid.value = sID;
    oSearch.actedit.value = 0;
    oSearch.submit();
}
</script>

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{ $oxid }]">
    <input type="hidden" name="cl" value="shopgate_order">
    <input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

[{if $oView->getIsError()}]
	
	<b>ERROR:</b> [{$oView->getErrorMessage()}]<br /><br />
	
[{/if}]

[{if $oView->getIsShopgateOrder() }]

<form name="resetstatus" id="resetstatus" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="shopgate_order">
<input type="hidden" name="fnc" value="reset">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">

</form>

<form name="update_form" id="update_form" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="shopgate_order">
<input type="hidden" name="fnc" value="syncorder">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="editval[order__oxid]" value="[{ $oxid }]">

<input type="submit" name="update" value="Update" />

</form>

<form name="unlink_form" id="unlink_form" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="shopgate_order">
<input type="hidden" name="fnc" value="unlink_order">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="editval[order__oxid]" value="[{ $oxid }]">

<a href="#" onclick="if(window.confirm('[{ oxmultilang ident="SHOPGATE_ORDER_UNLINK_ORDER_CONFIRM" }]')) { document.getElementById('unlink_form').submit(); }; return false;">([{ oxmultilang ident="SHOPGATE_ORDER_UNLINK_ORDER" }])</a>

</form>

<h2>[{ oxmultilang ident="SHOPGATE_ORDER_ORDER" }]</h2>
<table>
	<tr>
		<td>
			[{ oxmultilang ident="SHOPGATE_ORDER_ORDER_NUMBER" }]:
		</td>
		<td>
			[{$order->oxordershopgate__order_number->value}]
		</td>
	</tr>
	<tr>
		<td>
			[{ oxmultilang ident="SHOPGATE_ORDER_TRANSACTIONNUMBER" }]:
		</td>
		<td>
			[{$orderData->getPaymentTransactionNumber()}]
		</td>
	</tr>
	<tr>
		<td>
			[{ oxmultilang ident="SHOPGATE_ORDER_PAYMENT_TYPE" }]:
		</td>
		<td>
			[{$orderData->getPaymentMethod()}]
		</td>
	</tr>
	<tr>
		<td>
			[{ oxmultilang ident="SHOPGATE_IS_SENT_TO_SHOPGATE" }]:
		</td>
		<td>
			[{ if $order->oxordershopgate__is_sent_to_shopgate->value == 1 }]
				[{ oxmultilang ident="GENERAL_YES" }] ( <a href="#" onclick="if(window.confirm('[{ oxmultilang ident="SHOPGATE_SURE_RESET_SENT_STATE" }]')) { document.resetstatus.submit(); }return false;">reset</a> )
			[{ else }]
				[{ oxmultilang ident="GENERAL_NO" }]
			[{ /if }]
		</td>
	</tr>
	<tr>
		<td>
			[{ oxmultilang ident="SHOPGATE_ORDER_IS_PAID" }]:
		</td>
		<td>
			[{ if $orderData->getIsPaid() == 1 }]
				[{ oxmultilang ident="GENERAL_YES" }]
			[{ else }]
				[{ oxmultilang ident="GENERAL_NO" }]
			[{ /if }]
		</td>
	</tr>
	<tr>
		<td>
			[{ oxmultilang ident="SHOPGATE_ORDER_IS_SHIPPING_BLOCKED" }]:
		</td>
		<td>
			[{ if $orderData->getIsShippingBlocked() == 1 }]
				[{ oxmultilang ident="GENERAL_YES" }]<br />
				<strong>[{ oxmultilang ident="SHOPGATE_ORDER_IS_SHIPPING_BLOCKED_YES" }]</strong>
			[{ else }]
				[{ oxmultilang ident="GENERAL_NO" }]<br />
			[{ /if }]
		</td>
	</tr>
	<tr>
		<td>
			[{ oxmultilang ident="SHOPGATE_ORDER_IS_INVOICE_BLOCKED" }]:
		</td>
		<td>
			[{ if $orderData->getIsCustomerInvoiceBlocked() == 1 }]
				[{ oxmultilang ident="GENERAL_NO" }]<br />
				<strong>[{ oxmultilang ident="SHOPGATE_ORDER_IS_INVOICE_BLOCKED_YES" }]</strong>
			[{ else }]
				[{ oxmultilang ident="GENERAL_YES" }]<br />
			[{ /if }]
		</td>
	</tr>
	
</table>

<h2>[{ oxmultilang ident="SHOPGATE_ORDER_PAYMENT_INFOS" }]</h2>
<table>
	[{foreach key=key item=value from=$orderData->getPaymentInfos()}]
		<tr>
			<td>[{$key}]:</td>
			<td>[{$value}]</td>
		</tr>
	[{/foreach}]
</table>

[{assign var="customFields" value=$orderData->getCustomFields() }]
[{if !empty($customFields) }]
	<h2>[{ oxmultilang ident="SHOPGATE_ORDER_CUSTOM_FIELDS" }]</h2>
	<table>
		[{foreach from=$customFields item=field }]
			<tr>
				<td>[{$field->getLabel()}]:</td>
				<td>[{$field->getValue()}]</td>
			</tr>
		[{/foreach}]
	</table>
[{/if }]
	
[{assign var="invoiceAddress" value=$orderData->getInvoiceAddress() }]
[{assign var="invoiceCustomFields" value=$invoiceAddress->getCustomFields() }]
[{if !empty($invoiceCustomFields) }]
	<h2>[{ oxmultilang ident="SHOPGATE_ORDER_CUSTOM_FIELDS_INVOICE" }]</h2>
	<table>
		[{foreach from=$invoiceCustomFields item=field }]
			<tr>
				<td>[{$field->getLabel()}]:</td>
				<td>[{$field->getValue()}]</td>
			</tr>
		[{/foreach}]
	</table>
[{/if }]

[{assign var="deliveryAddress" value=$orderData->getDeliveryAddress() }]
[{assign var="deliveryCustomFields" value=$deliveryAddress->getCustomFields() }]
[{if !empty($deliveryCustomFields) }]
	<h2>[{ oxmultilang ident="SHOPGATE_ORDER_CUSTOM_FIELDS_DELIVERY" }]</h2>
	<table>
		[{foreach from=$deliveryCustomFields item=field }]
		<tr>
			<td>[{$field->getLabel()}]:</td>
			<td>[{$field->getValue()}]</td>
		</tr>
		[{/foreach}]
	</table>
[{/if }]

[{else}]

[{ oxmultilang ident="SHOPGATE_ORDER_NO_SHOPGATE_ORDER" }]
<br />

<form name="link_form" id="link_form" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="shopgate_order">
<input type="hidden" name="fnc" value="link_order">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="editval[order__oxid]" value="[{ $oxid }]">

<h2>[{ oxmultilang ident="SHOPGATE_ORDER_LINK_TO_A_SHOPGATE_ORDER" }]</h2>

[{ oxmultilang ident="SHOPGATE_ORDER_ORDER_NUMBER" }]: <input type="text" name="shopgate_order_number" />

<input type="submit" name="append_order" value='[{ oxmultilang ident="GENERAL_SAVE"}]' />

</form>

[{/if}]


[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]