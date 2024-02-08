[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

<script type="text/javascript">
function _groupExp(el) {
	var _cur = el.parentNode;

	if (_cur.className == "exp") _cur.className = "";
	else _cur.className = "exp";
}
	
function sgShowInfo(id) {
	document.getElementById(id).style.visibility = 'visible';
}
function sgHideInfo(id) {
	document.getElementById(id).style.visibility = 'hidden';
}
</script>
<style type="text/css">
.sgInfoButton {
	background-color: lightskyblue;
	border: 1px solid grey;
	color: white;
	display: inline-block;
	padding: 2px 5px;
}
.sgInfoBox {
	background-color: lightyellow;
	border: 1px solid black;
	left: 250px;
	max-width: 250px;
	padding: 5px;
	position: absolute;
	visibility: hidden;
}
</style>

[{if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]
<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
	[{ $oViewConf->getHiddenSid() }]
	<input type="hidden" name="oxid" value="[{ $oxid }]">
	<input type="hidden" name="cl" value="marm_shopgate_config">
	<input type="hidden" name="fnc" value="">
	<input type="hidden" name="actshop" value="[{$oViewConf->getActiveShopId()}]">
	<input type="hidden" name="updatenav" value="">
	<input type="hidden" name="editlanguage" value="[{ $editlanguage }]">
</form>

<form name="myedit" id="myedit" action="[{ $oViewConf->getSelfLink() }]" method="post">
	[{ $oViewConf->getHiddenSid() }]
	<input type="hidden" name="cl" value="marm_shopgate_config">
	<input type="hidden" name="fnc" value="">
	<input type="hidden" name="oxid" value="[{ $oxid }]">
	<input type="hidden" name="editval[oxshops__oxid]" value="[{ $oxid }]">

	[{foreach from=$oView->getShopgateConfig() key='sConfigGroupName' item='aConfigGroup'}]
		<div class="groupExp">
			<div>
				<a href="#" onclick="_groupExp(this);return false;" class="rc"><b>[{ oxmultilang ident="SHOPGATE_CONFIG_GROUP_"|cat:$sConfigGroupName|upper }]</b></a>
	
				[{foreach from=$aConfigGroup  item='aConfigItem'}]
					[{if !isset($aConfigItem.hidden)}]
						<dl>
							<dt>
								[{if $aConfigItem.type == 'checkbox'}]
									<input type="hidden" name="confbools[[{$aConfigItem.oxid_name}]]" value="false">
									<input type="checkbox" name="confbools[[{$aConfigItem.oxid_name}]]" value="true" [{if ($aConfigItem.value)}]checked="checked"[{/if}] [{ $readonly}]>
								[{elseif $aConfigItem.type == 'select'  }]
									<select class="select" name="confstrs[[{$aConfigItem.oxid_name}]]" [{ $readonly }] style="width: 154px;">
										[{foreach from=$aConfigItem.options item='sOption' key='sKey' }]
											<option value="[{$sKey}]"  [{if $aConfigItem.value == $sKey }]selected[{/if}]>
												[{ if isset($aConfigItem.prefix) }]
													[{ oxmultilang ident=$aConfigItem.prefix|cat:$sOption|upper noerror=$aConfigItem.noerror }]
												[{ elseif !isset($aConfigItem.translate) OR $aConfigItem.translate }]
													[{ oxmultilang ident="SHOPGATE_CONFIG_"|cat:$aConfigItem.shopgate_name|cat:'_'|cat:$sOption|upper noerror=$aConfigItem.noerror }]
												[{ else }]
													[{ $sOption }]
												[{/if}]
											</option>
										[{/foreach}]
									</select>
								[{elseif $aConfigItem.type == 'input' }]
									<input type="text" class="txt" name="confstrs[[{$aConfigItem.oxid_name}]]" value="[{$aConfigItem.value}]" [{ $readonly}] style="width: 200px;">
								[{/if}]
								[{ oxinputhelp ident="SHOPGATE_CONFIG_"|cat:$aConfigItem.shopgate_name|upper|cat:"_HELP" }]
							</dt>
							<dd>
								[{ oxmultilang ident="SHOPGATE_CONFIG_"|cat:$aConfigItem.shopgate_name|upper noerror=1 alternative=$aConfigItem.shopgate_name }]
							</dd>
						</dl>
						<div class="spacer"></div>
					[{/if}]
				[{/foreach}]
			</div>
		</div>
	[{/foreach}]

	<br/>
	<input type="submit" name="save" value="[{ oxmultilang ident="GENERAL_SAVE" }]" onClick="Javascript:document.myedit.fnc.value='save'" [{ $readonly}]>
</form>

<br/>

Plugin-Version: [{ $oView->getPluginVersion() }] - Library-Version: [{ $oView->getLibraryVersion() }]

[{include file="bottomnaviitem.tpl"}]

[{include file="bottomitem.tpl"}]
