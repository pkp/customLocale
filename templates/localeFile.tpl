{**
 * templates/localeFile.tpl
 *
 * Copyright (c) 2016-2021 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 *}

<form class="pkp_form" id="localeFilesForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.customLocale.controllers.grid.CustomLocaleGridHandler" op="updateLocale" locale=$locale key=$filePath anchor="localeContents"}">
	<link rel="stylesheet" href="{$baseUrl}/plugins/generic/customLocale/css/customLocale.css" type="text/css" />
	<div id="customLocales">
		{* TABLE *}
		<table class="pkpTable">
			{* TABLE HEADER *}
			<caption>
				<span class="pkpHeader__title">
					<h3>{translate key="plugins.generic.customLocale.file.editHeader"}</h3>
				</span>
				<div class="pkpHeader__actions">
					{* SEARCH BOX *}
					<div class="pkpSearch">
						<label>
							<span class="-screenReader">{translate key="common.search"}</span>
							<input
								type="search"
								id="customLocale__searchInput"
								placeholder="{translate key="common.search"}"
								class="pkpSearch__input"
								v-model="searchPhrase"
								@keydown.enter.prevent="search"
							/>
							<span class="pkpSearch__icons" @click.prevent="search">
								<span aria-hidden="true" class="fa pkpSearch__icons--search fa-search pkpIcon--inline"></span>
							</span>
						</label>
						<button 
							aria-controls="customLocale__searchInput" 
							class="pkpSearch__clear"
							@click.prevent="initializeView"
							v-if="searchPhrase.length > 0"
						>
							<span aria-hidden="true" class="fa fa-times"></span>
							<span class="-screenReader">{translate key="common.clearSearch"}</span>
						</button>
					</div>
					<button class="pkpButton" @click.prevent="search">{translate key="common.search"}</button>

				</div>
				<div class="customLocale__headerDescription">
					{translate key="plugins.generic.customLocale.file.edit" filename=$filePath|escape}
				</div>
			</caption>
			<tr v-if="displaySearchResults">
				<td class="customLocale__itemCount">
					{translate key="plugins.generic.customLocale.searchResultsCount"}
				</td>
			</tr>
			{* MAIN BODY *}
			<tr v-for="(localeKey, index) in currentLocaleKeys" :key="localeKey.localeKey">
				<td>
					<table class="pkpTable customLocale__cellTable">
						<tr class="customLocale__cellHeader">
							<td colspan="2">{{ localeKey.localeKey }}</td>
						</tr>
						<tr>
							<td width="50%">
								<label class="-screenReader" :for="'default-text-' + index">{translate key="plugins.generic.customLocale.file.reference"}</label>
								<div v-if="localeKey.value.length > 50">
									<textarea 
										class="customLocale__fixedSize"
										:id="'default-text-' + index"
										v-model="localeKey.value"
										rows="5"
										cols="50"
										disabled

									></textarea>
								</div>
								<div v-else>
									<input
										type="text"
										:id="'default-text-' + index"
										v-model="localeKey.value"
										size="50"
										disabled
									>
								</div>
							</td>
							<td width="50%">
								<label class="-screenReader" :for="'custom-text-' + index">{translate key="plugins.generic.customLocale.file.custom"}</label>
								<div v-if="localeKey.value.length > 50">
									<textarea
										class="customLocale__fixedSize"
										:id="'custom-text-' + index"
										:name="'changes[' + localeKey.localeKey + ']'"
										v-model="localEdited[localeKey.localeKey]"
										rows="5"
										cols="50"
										v-bind:class="{ valueChanged : localEdited[localeKey.localeKey] != null}"

									></textarea>
								</div>
								<div v-else>
									<input
										type="text"
										:id="'custom-text-' + index"
										:name="'changes[' + localeKey.localeKey + ']'"
										v-model="localEdited[localeKey.localeKey]"
										v-bind:class="{ valueChanged : localEdited[localeKey.localeKey] != null}"
									>
								</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>

		{* PAGINATION *}
		<nav role="navigation" aria-label="{translate key='common.pagination.label'}" class="pkpPagination" v-if="maxPages > 1">
			<ul>
				<li>
					<button 
						class="pkpButton" 
						:disabled="currentPage === 1 ? true : null"
						type="button"
						@click="() => (currentPage -= 1)"
						aria-label="{translate key='common.pagination.goToPage' page={translate key='common.pagination.previous'}}"
					>
						{translate key="common.pagination.previous"}
					</button>
				</li>
				<li v-for="i in maxPages" :key="i">
					<button 
						class="pkpButton"
						v-bind:class="currentPage === i ? 'pkpButton--isActive' : 'pkpButton--isLink'"
						type="button"
						@click="() => (currentPage = i)"
						v-bind:aria-label="'{translate key="common.pagination.goToPage" page="{translate key='common.pageNumber' pageNumber=''}"}' + i"
					>
						{{ i }}
					</button>

				</li>
				<li>
					<button 
						class="pkpButton" 
						:disabled="currentPage === maxPages ? true : null" 
						type="button"
						@click="() => (currentPage += 1)"
						aria-label="{translate key='common.pagination.goToPage' page={translate key='common.pagination.next'}}"
					>
						{translate key="common.pagination.next"}
					</button>
				</li>
			</ul>
		</nav>

		{fbvFormButtons id="submitCustomLocaleFileTemplate" submitText="plugins.generic.customLocale.saveAndContinue"}
	</div>
	<script type="text/javascript">
		$(function() {ldelim}
			// Attach the form handler.
			$('#localeFilesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{rdelim});
		{if $localeContents}
			customLocalesApp.data.edited = {$localeContents|json_encode};
		{else}
			customLocalesApp.data.edited = {ldelim}{rdelim};
		{/if}
		customLocalesApp.data.localeKeysMaster = {$referenceLocaleContents|json_encode};
		new pkp.Vue(customLocalesApp);
	</script>
</form>