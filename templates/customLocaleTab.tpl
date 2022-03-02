{**
 * templates/customLocaleTab.tpl
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2003-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Custom locale plugin -- add a new tab to the settings interface.
 *}
<tab id="customLocale" label="{translate key="plugins.generic.customLocale.customLocale"}">
	{capture assign=customLocaleGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.customLocale.controllers.grid.CustomLocaleGridHandler" op="fetchGrid" escape=false}{/capture}
	{load_url_in_div id="customLocaleGridContainer" url=$customLocaleGridUrl}
</tab>
