{**
 * templates/form/checkbox.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form checkbox
 *}

<li{if $FBV_layoutInfo} class="{$FBV_layoutInfo}"{/if}>
	<input type="checkbox" id="{$FBV_id|escape}" {$FBV_checkboxParams} class="field checkbox{if $FBV_validation} {$FBV_validation|escape}{/if}{if !empty($FBV_required)} required{/if}"{if !empty($FBV_checked)} checked="checked"{/if}{if !empty($FBV_disabled)} disabled="disabled"{/if}/>
	{if $FBV_label}<label for="{$FBV_id|escape}" class="choice">{if $FBV_translate}{translate key=$FBV_label}{else}{if $FBV_keepLabelHtml}{$FBV_label}{else}{$FBV_label}{/if}{/if}</label>{/if}
</li>
