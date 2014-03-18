{**
 * templates/dashboard/archives.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard submissions tab.
 *}

<!-- Archived submissions grid: Show all archived submissions -->
{if array_intersect(array($smarty.const.ROLE_ID_SITE_ADMIN, $smarty.const.ROLE_ID_MANAGER, $smarty.const.ROLE_ID_SUB_EDITOR, $smarty.const.ROLE_ID_REVIEWER, $smarty.const.ROLE_ID_ASSISTANT), $userRoles)}
	{url assign=archivedSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.archivedSubmissions.ArchivedSubmissionsListGridHandler" op="fetchGrid"}
	{load_url_in_div id="archivedSubmissionsListGridContainer" url=$archivedSubmissionsListGridUrl}
{/if}
