{**
 * plugins/generic/reviewerInterests/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Reviewer Interests plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.reviewerInterests.manager.reviewerInterestsSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="reviewerInterestsSettings">
<div id="description">{translate key="plugins.generic.reviewerInterests.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
<tr valign="top">
	<td class="label">{fieldLabel name="interests" key="user.interests"}</td>
	<td class="value">
		{include file="form/interestsInput.tpl" FBV_interestsKeywords=$interestsKeywords}
	</td>
</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
