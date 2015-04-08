{**
 * controllers/tab/settings/reviewStage/form/reviewStageForm.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review stage management form.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#reviewStageForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="reviewStageForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.PublicationSettingsTabHandler" op="saveFormData" tab="reviewStage"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="reviewStageFormNotification"}
	{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

	<div {if $wizardMode}class="pkp_form_hidden"{/if}>
		{fbvFormArea id="reviewOptions" title="manager.setup.reviewOptions.reviewTime" class="border"}
			<!-- FIXME: also, fbvStyles.size.SMALL needs to be switched to TINY once there's a TINY option available -->
			{fbvFormSection description="manager.setup.reviewOptions.noteOnModification"}
				{fbvElement type="text" label="manager.setup.reviewOptions.numWeeksPerResponse" name="numWeeksPerResponse" id="numWeeksPerResponse" value=$numWeeksPerResponse size=$fbvStyles.size.SMALL inline=true}
				{fbvElement type="text" label="manager.setup.reviewOptions.numWeeksPerReview" name="numWeeksPerReview" id="numWeeksPerReview" value=$numWeeksPerReview size=$fbvStyles.size.SMALL inline=true}
			{/fbvFormSection}

			{capture assign="reviewReminderNote"}{translate key="manager.setup.reviewOptions.automatedReminders"} {translate key="manager.setup.reviewOptions.automatedRemindersDisabled"}{/capture}
			{fbvFormSection label="manager.setup.reviewOptions.reviewerReminders"|translate description=$reviewReminderNote translate=false}{/fbvFormSection}

			{translate assign="reminderDefault" key="manager.setup.reviewOptions.neverSendReminder"}

			{fbvFormSection description="manager.setup.reviewOptions.remindForInvite"}
				{if $scheduledTasksDisabled}{assign var="disabled" value=true}{else}{assign var="disabled" value=false}{/if}
				{fbvElement type="select" from=$numDaysBeforeInviteReminderValues selected=$numDaysBeforeInviteReminder defaultValue="" defaultLabel=$reminderDefault id="numDaysBeforeInviteReminder" disabled=$disabled translate=false size=$fbvStyles.size.SMALL inline=true}
			{/fbvFormSection}

			{fbvFormSection description="manager.setup.reviewOptions.remindForSubmit"}
				{if $scheduledTasksDisabled}{assign var="disabled" value=true}{else}{assign var="disabled" value=false}{/if}
				{fbvElement type="select" from=$numDaysBeforeSubmitReminderValues selected=$numDaysBeforeSubmitReminder defaultValue="" defaultLabel=$reminderDefault id="numDaysBeforeSubmitReminder" disabled=$disabled translate=false size=$fbvStyles.size.SMALL inline=true}
			{/fbvFormSection}
		{/fbvFormArea}

		{url assign=reviewFormsUrl router=$smarty.const.ROUTE_COMPONENT component="grid.settings.reviewForms.ReviewFormGridHandler" op="fetchGrid"}
		{load_url_in_div id="reviewFormGridContainer" url=$reviewFormsUrl}

		{fbvFormArea id="reviewProcessDetails"}
			{fbvFormSection title="manager.setup.reviewOptions.reviewerRatings" list=true}
				{fbvElement type="checkbox" id="rateReviewerOnQuality" value="1" checked=$rateReviewerOnQuality label="manager.setup.reviewOptions.onQuality"}
			{/fbvFormSection}
			{capture assign="ensureLink"}{include file="linkAction/linkAction.tpl" action=$ensuringLink contextId="uploadForm"}{/capture}
			{fbvFormSection title="manager.setup.reviewOptions.blindReview" list=true}
				{fbvElement type="checkbox" id="showEnsuringLink" value="1" checked=$showEnsuringLink label=$ensureLink translate=false keepLabelHtml=true}
			{/fbvFormSection}
		{/fbvFormArea}

		{fbvFormArea id="review"}
			{fbvFormSection label="manager.setup.competingInterests" description="manager.setup.competingInterestsDescription"}
				{fbvElement type="textarea" multilingual="true" id="competingInterests" value=$competingInterests rich=true}
			{/fbvFormSection}
			{fbvFormSection for="reviewerCompetingInterestsRequired" list=true label="manager.setup.reviewerCompetingInterestsRequired.description"}
				{fbvElement type="checkbox" id="reviewerCompetingInterestsRequired" checked=$reviewerCompetingInterestsRequired label="manager.setup.competingInterests.required" inline=true}
			{/fbvFormSection}
			{fbvFormSection label="manager.setup.reviewGuidelines" description="manager.setup.reviewGuidelinesDescription"}
				{fbvElement type="textarea" multilingual="true" name="reviewGuidelines" id="reviewGuidelines" value=$reviewGuidelines rich=true}
			{/fbvFormSection}
		{/fbvFormArea}
	</div>

	{fbvFormArea id="reviewOptions" title="manager.setup.reviewOptions" class="border"}
		{fbvFormSection description="manager.setup.reviewOptions.defaultReviewMethod"}
			{fbvElement type="select" from=$reviewMethodOptions selected=$defaultReviewMode defaultValue="" defaultLabel="" id="defaultReviewMode" size=$fbvStyles.size.SMALL inline=true}
		{/fbvFormSection}

		{$additionalReviewFormOptions}
	{/fbvFormArea}

	{if !$wizardMode}
		{fbvFormButtons id="reviewStageFormSubmit" submitText="common.save" hideCancel=true}
	{/if}
</form>
