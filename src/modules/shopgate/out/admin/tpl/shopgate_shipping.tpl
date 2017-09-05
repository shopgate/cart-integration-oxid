
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
    <input type="hidden" name="cl" value="shopgate_shipping">
    <input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

[{if $oView->getIsError()}]
	
	<b>ERROR:</b> [{$oView->getErrorMessage()}]<br /><br />
	
[{/if}]

[{if $oxid == "mobile_shipping" }]
	<strong>[{ oxmultilang ident="SHOPGATE_SHIPPING_NOT_POSSIBLE" }]</strong>
[{else}]

<form name="link_form" id="link_form" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="shopgate_shipping">
<input type="hidden" name="fnc" value="setDeliveryService">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="editval[order__oxid]" value="[{ $oxid }]">

<label for="shopgate_shipping_service_id">
[{ oxmultilang ident="SHOPGATE_SHIPPING_SELECT_SERVICE" }]:
</label>

<select class="select" id="shopgate_shipping_service_id" name="shopgate_shipping_service_id" [{ $readonly }] style="width: 154px;">
	<option value="" [{if $edit->oxdeliveryset__shopgate_service_id->value == $sService }]selected[{/if}]></option>

[{foreach from=$delivery_services item='sService' }]
	<option value="[{$sService}]" [{if $edit->oxdeliveryset__shopgate_service_id->value == $sService }]selected[{/if}]>
		[{ $sService }]
	</option>
[{/foreach}]

</select>

<br />

<input type="submit" value='[{ oxmultilang ident="GENERAL_SAVE"}]' />

</form>

[{/if}]

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]