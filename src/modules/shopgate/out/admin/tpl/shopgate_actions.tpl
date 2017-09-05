
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

<form name="link_form" id="link_form" action="[{ $oViewConf->getSelfLink() }]" enctype="multipart/form-data" method="post">
[{$oViewConf->getHiddenSid()}]
<input type="hidden" name="cl" value="shopgate_actions">
<input type="hidden" name="fnc" value="save">
<input type="hidden" name="oxid" value="[{ $oxid }]">
<input type="hidden" name="voxid" value="[{ $oxid }]">
<input type="hidden" name="editval[order__oxid]" value="[{ $oxid }]">

<label for="is_highlight">
[{ oxmultilang ident="SHOPGATE_ACTIONS_IS_HIGHLIGHT" }]:
</label>

<input type="checkbox" id="is_highlight" name="is_highlight" [{if $is_highlight}]checked=1[{/if}] />

<br />

<input type="submit" value='[{ oxmultilang ident="GENERAL_SAVE"}]' />

</form>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]