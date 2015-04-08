{**
 * templates/form/radioButton.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form radio button
 *}

<li{if $FBV_layoutInfo} class="{$FBV_layoutInfo}"{/if}>
	<input type="radio" id="{$FBV_id|escape}" {$FBV_radioParams} class="field radio"{if !empty($FBV_checked)} checked="checked"{/if}{if !empty($FBV_disabled)} disabled="disabled"{/if}/>
	{if $FBV_label}<label for="{$FBV_id|escape}" class="choice">{if $FBV_translate}{translate key=$FBV_label}{else}{$FBV_label|escape}{/if}</label>
	{elseif $FBV_content}<label for="{$FBV_id|escape}" class="choice">{$FBV_content}</label>{/if}
</li>
