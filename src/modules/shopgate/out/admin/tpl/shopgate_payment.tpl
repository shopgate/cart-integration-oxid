
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
    <input type="hidden" name="cl" value="shopgate_payment">
    <input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

[{if $oView->getIsError()}]
	
	<b>ERROR:</b> [{$oView->getErrorMessage()}]<br /><br />
	
[{/if}]

[{if $oxid == "oxmobile_payment" || $oxid == "oxshopgate" || $oxid == "oxempty" }]
	<strong>[{ oxmultilang ident="SHOPGATE_PAYMENT_NOT_POSSIBLE" }]</strong>
[{else}]

<form name="link_form" id="link_form" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="shopgate_payment">
<input type="hidden" name="fnc" value="setPaymentMethod">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="editval[order__oxid]" value="[{ $oxid }]">

<label for="shopgate_payment_method_id">
[{ oxmultilang ident="SHOPGATE_PAYMENT_SELECT_METHOD" }]:
</label>

<select class="select" id="shopgate_payment_method_id" name="shopgate_payment_method_id" [{ $readonly }] >
	<option value="" [{if $edit->oxdeliveryset__shopgate_service_id->value == $sService }]selected[{/if}]></option>

[{foreach from=$payment_methods key='sGroup' item='_PaymentMethods' }]
	<optgroup label='[{ oxmultilang ident="SHOPGATE_PAYMENT_GROUP_$sGroup" }]'>
		[{foreach from=$_PaymentMethods item='sService' }]
			<option value="[{$sService}]"  [{if $edit->oxpayments__shopgate_payment_method->value == $sService }]selected[{/if}] >
				[{ oxmultilang ident="SHOPGATE_PAYMENT_GROUP_$sGroup }] - [{ oxmultilang ident="SHOPGATE_PAYMENT_METHOD_$sService }]
			</option>
		[{/foreach}]
	</optgroup>
[{/foreach}]

</select>

<br />

<input type="submit" value='[{ oxmultilang ident="GENERAL_SAVE"}]' />

</form>

[{/if}]

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]