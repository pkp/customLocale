<?php

/**
 * @file plugins/generic/customLocale/controllers/grid/CustomLocaleGridHandler.inc.php
 *
 * Copyright (c) 2016 Language Science Press
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleGridHandler
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.customLocale.controllers.grid.CustomLocaleGridCellProvider');
import('classes.handler.Handler');
import('plugins.generic.customLocale.classes.CustomLocale');

require_once('CustomLocaleAction.inc.php');

class CustomLocaleGridHandler extends GridHandler {

	/** @var $form Form */
	var $form;

	/** The custom locale plugin */
	static $plugin;

	/**
	 * Set the custom locale plugin.
	 */
	static function setPlugin($plugin) {
		self::$plugin = $plugin;
	}

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		/* UZH CHANGE OJS-71 fix for role bug */
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER,ROLE_ID_SITE_ADMIN),
			array('fetchGrid', 'index', 'editLocaleFile', 'updateLocale')
		);
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Update the custom locale data.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateLocale($args, $request) {
		$context = $request->getContext();
		$contextId = $context->getId();
		$locale = $args['locale'];
		$filename = $args['key'];
		$currentPage = $args['currentPage'];
		$searchKey = ''; if (isset($args['searchKey'])) {$searchKey=$args['searchKey'];};
		$searchString = $args['searchString'];

		// don't save changes if the locale is searched
		if (!$searchKey) {

			// save changes
			$changes = $args['changes'];

			/* UZH CHANGE OJS-71 2019/03/29/mb set path according to application name (OJS or OMP) */
			/* $customFilesDir = Config::getVar('files', 'public_files_dir') .
				"/presses/$contextId/" . CUSTOM_LOCALE_DIR . "/$locale"; */
			$publicFilesDir = Config::getVar('files', 'public_files_dir');
			$fileDirectories = Application::getFileDirectories();
			$contextDir = $fileDirectories['context'];
			$customFilePath = "$publicFilesDir" . $contextDir . "$contextId/" . 
				CUSTOM_LOCALE_DIR . "/$locale/$filename";
			/* END UZH CHANGE OJS-71 */

			// Create empty custom locale file if it doesn't exist
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();

			import('lib.pkp.classes.file.EditableLocaleFile');
			if (!$fileManager->fileExists($customFilePath)) {

				$numParentDirs = substr_count($customFilePath, DIRECTORY_SEPARATOR);
				$parentDirs = '';
				for ($i=0; $i<$numParentDirs; $i++) {
					$parentDirs .= '..' . DIRECTORY_SEPARATOR;
				}

				$newFileContents = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
				$newFileContents .= '<!DOCTYPE locale SYSTEM "' . $parentDirs . 'lib/pkp/dtd/locale.dtd' . '">' . "\n";
				$newFileContents .= '<locale name="' . $locale . '">' . "\n";
				$newFileContents .= '</locale>';
				$fileManager->writeFile($customFilePath, $newFileContents);
			}

			if ($args['nextPage']) {
				$currentPage = $args['nextPage'];
			}

			$file = new EditableLocaleFile($locale, $customFilePath);

			while (!empty($changes)) {
				$key = array_shift($changes);
				$value = str_replace("\r\n", "\n", array_shift($changes));
				if (!empty($value)) {
					if (!$file->update($key, $value)) {
						$file->insert($key, $value);
					}
				} else {
					$file->delete($key);
				}
			}
			$file->write();
		}

		$context = $request->getContext();
		$this->setupTemplate($request);

		// Create and present the edit form
		import('plugins.generic.customLocale.controllers.grid.form.LocaleFileForm');

		$customLocalePlugin = self::$plugin;
		$localeFileForm = new LocaleFileForm(self::$plugin, $context->getId(), $filename, $locale);

		$localeFileForm->initData();
		return new JSONMessage(true, $localeFileForm->fetch($request,$currentPage,$searchKey,$searchString));
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc Gridhandler::initialize()
	 */
	function initialize($request, $args=null) {

		parent::initialize($request, $args);

		// Set the grid details.
		$this->setTitle('plugins.generic.customLocale.customLocaleFiles');
		$this->setEmptyRowText('plugins.generic.customLocale.noneCreated');

		// Columns
		$cellProvider = new CustomLocaleGridCellProvider();

		$this->addColumn(new GridColumn(
			'filetitle',
			'plugins.generic.customLocale.files.pageTitle',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));

		$this->addColumn(new GridColumn(
			'filepath',
			'plugins.generic.customLocale.path',
			null,
			'controllers/grid/gridCell.tpl', // Default null not supported in OMP 1.1
			$cellProvider
		));

	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	function loadData($request, $filter) {
		$context = $request->getContext();
		$locales = $context->getSupportedLocaleNames();

		$localeKeys = array_keys($locales);

		$locale = $localeKeys[$filter['locale']];
		$search = $filter['search'];

		$localeFiles = CustomLocaleAction::getLocaleFiles($locale);

		$localeFilesSelected = array();
		$count = 0;
		if ($search!=='') {
			for ($i=0; $i<sizeof($localeFiles); $i++) {
				if (strpos(strtolower($localeFiles[$i]),strtolower($search)) !== false) {
					$localeFilesSelected[$count] = $localeFiles[$i];
					$count++;
				}
			}
		} else {
			$localeFilesSelected = $localeFiles;
		}

		$gridDataElements = array();
		for ($i=0; $i<sizeof($localeFilesSelected); $i++) {
			$customLocale = new CustomLocale();
			$customLocale->setId($i);
			$customLocale->setLocale($locale);
			$customLocale->setFilePath($localeFilesSelected[$i]);
			$customLocale->setContextId($request->getContext()->getId());
			$customLocale->setFileTitle($localeFilesSelected[$i]);
			$gridDataElements[]=$customLocale;
		}

		return $gridDataElements;
	}

	/**
	 * @copydoc GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new PagingFeature());
	}


	//
	// Public Grid Actions
	//
	/**
	 * Display the grid's containing page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function index($args, $request) {
		$dispatcher = $request->getDispatcher();
		$templateMgr = TemplateManager::getManager($request);
		return $templateMgr->fetchAjax(
			'customLocaleGridUrlGridContainer',
			$dispatcher->url(
				$request, ROUTE_COMPONENT, null,
				'plugins.generic.customLocale.controllers.grid.CustomLocaleGridHandler', 'fetchGrid',
				null, array('state' => 'start')
			)
		);
	}


	/**
	 * @copydoc GridHandler::getFilterForm()
	 * @return string Filter template.
	 */
	function getFilterForm() {
		$customLocalePlugin = self::$plugin;
		/* UZH CHANGE OJS-74 2019/03/05/mb adapt to OJS 3.1.2 */
		/* $templatePath = $customLocalePlugin->getTemplatePath(); */
		/* return $templatePath . 'customLocaleGridFilter.tpl'; */
		return $customLocalePlugin->getTemplateResource('customLocaleGridFilter.tpl');
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request) {
		$context = $request->getContext();
		$locales = $context->getSupportedLocaleNames();

		$localeOptions = array();
		$keys = array_keys($locales);
		for ($i=0; $i<sizeof($locales); $i++) {
			$localeOptions[$i] = $keys[$i];
		}

		$fieldOptions = array(
			'CUSTOMLOCALE_FIELD_PATH' => 'fieldopt1',
		);

		$matchOptions = array(
			'contains' => 'form.contains',
			'is' => 'form.is'
		);

		$filterData = array(
			'localeOptions' => $localeOptions,
			'fieldOptions' => $fieldOptions,
			'matchOptions' => $matchOptions
		);

		return parent::renderFilter($request, $filterData);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		// Get the search terms.

		$locale = $request->getUserVar('locale') ? (int)$request->getUserVar('locale') : 0;
		$searchField = $request->getUserVar('searchField');
		$searchMatch = $request->getUserVar('searchMatch');
		$search = $request->getUserVar('search');

		return $filterSelectionData = array(
			'locale' => $locale,
			'searchField' => $searchField,
			'searchMatch' => $searchMatch,
			'search' => $search ? $search : ''
		);
	}

	function editLocaleFile($args, $request) {

		$context = $request->getContext();
		$this->setupTemplate($request);

		// Create and present the edit form
		import('plugins.generic.customLocale.controllers.grid.form.LocaleFileForm');

		$customLocalePlugin = self::$plugin;
		$localeFileForm = new LocaleFileForm(self::$plugin, $context->getId(), $args['filePath'], $args['locale']);

		$localeFileForm->initData();

		return new JSONMessage(true, $localeFileForm->fetch($request));
	}
}

