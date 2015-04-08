{**
 * templates/form/subLabel.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form label
 *}

{if $FBV_uniqId}
	{if !empty($FBV_multilingual)}
		{assign var="forElement" value=$FBV_id|concat:"-":$formLocale:"-":$FBV_uniqId}
	{else}
		{assign var="forElement" value=$FBV_id|concat:"-":$FBV_uniqId}
	{/if}
{else}
	{assign var="forElement" value=$FBV_id}
{/if}
<label class="sub_label{if $FBV_error} error{/if}" {if empty($FBV_suppressId)} for="{$forElement|escape}"{/if}>
	{if $FBV_subLabelTranslate}{translate key=$FBV_label|escape}{else}{$FBV_label|escape}{/if}{if !empty($FBV_required)}<span class="req">*</span>{/if}
</label>
