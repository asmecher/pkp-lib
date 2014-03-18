{**
 * templates/dashboard/submissions.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard submissions tab.
 *}

<!-- Author and editor submissions grid -->
{if array_intersect(array($smarty.const.ROLE_ID_AUTHOR, $smarty.const.ROLE_ID_MANAGER, $smarty.const.ROLE_ID_GUEST_EDITOR, $smarty.const.ROLE_ID_SUB_EDITOR), $userRoles)}
	{url assign=mySubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.mySubmissions.MySubmissionsListGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="mySubmissionsListGridContainer" url=$mySubmissionsListGridUrl}
{/if}

<!-- Unassigned submissions grid: If the user is a manager or a series editor, then display these submissions which have not been assigned to anyone -->
{if array_intersect(array($smarty.const.ROLE_ID_MANAGER, $smarty.const.ROLE_ID_GUEST_EDITOR), $userRoles)}
	{url assign=unassignedSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.unassignedSubmissions.UnassignedSubmissionsListGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="unassignedSubmissionsListGridContainer" url=$unassignedSubmissionsListGridUrl}
{/if}

<!-- Assigned submissions grid: Show all submissions the user is assigned to (besides their own) -->
{if array_intersect(array($smarty.const.ROLE_ID_SITE_ADMIN, $smarty.const.ROLE_ID_MANAGER, $smarty.const.ROLE_ID_GUEST_EDITOR, $smarty.const.ROLE_ID_REVIEWER, $smarty.const.ROLE_ID_ASSISTANT, $smarty.const.ROLE_ID_SUB_EDITOR), $userRoles)}
	{url assign=assignedSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.assignedSubmissions.AssignedSubmissionsListGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="assignedSubmissionsListGridContainer" url=$assignedSubmissionsListGridUrl}
{/if}
