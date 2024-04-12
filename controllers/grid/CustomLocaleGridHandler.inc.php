<?php

/**
 * @file controllers/grid/CustomLocaleGridHandler.inc.php
 *
 * Copyright (c) 2016-2023 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleGridHandler
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.customLocale.controllers.grid.CustomLocaleGridCellProvider');
import('classes.handler.Handler');
import('plugins.generic.customLocale.classes.CustomLocale');

import('plugins.generic.customLocale.controllers.grid.CustomLocaleAction');

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
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN),
			array('fetchGrid', 'editLocaleFile', 'updateLocale')
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

		if (!$request->checkCSRF()) return new JSONMessage(false);

		// save changes
		$changes = (array) $args['changes'];
		if (!empty($changes)) {
			import('lib.pkp.classes.file.ContextFileManager');
			$contextFileManager = new ContextFileManager($context->getId());
			$customFilesDir = $contextFileManager->getBasePath() . "customLocale/$locale/";
			$customFilePath = "$customFilesDir/$filename";

			if ($contextFileManager->fileExists($customFilePath)) {
				$translations = Gettext\Translations::fromPoFile($customFilePath);
			} else {
				$translations = new \Gettext\Translations();
			}

			foreach ($changes as $key => $value) {
				$value = str_replace("\r\n", "\n", $value);
				if (!empty($value)) {
					$translation = $translations->find('', $key);
					if ($translation) {
						$translation->setTranslation($value);
					} else {
						$translation = $translations->insert('', $key);
						$translation->setTranslation($value);
					}
				} else {
					if ($translation = $translations->find('', $key)) {
						$translations->offsetUnset($translation->getId());
					}
				}
			}

			$contextFileManager->mkdirtree(dirname($customFilePath));
			$translations->toPoFile($customFilePath);

			// Create success notification and close modal on save
			$notificationMgr = new NotificationManager();
			$notificationMgr->createTrivialNotification($request->getUser()->getId());
			return new JSONMessage(false);
		}


		$context = $request->getContext();
		$this->setupTemplate($request);

		// Create and present the edit form
		import('plugins.generic.customLocale.controllers.grid.form.LocaleFileForm');

		$localeFileForm = new LocaleFileForm(self::$plugin, $filename, $locale);

		$localeFileForm->initData();
		return new JSONMessage(true, $localeFileForm->fetch($request));
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

		$locale = $filter['locale'];
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
	 * @copydoc GridHandler::getFilterForm()
	 * @return string Filter template.
	 */
	function getFilterForm() {
		return self::$plugin->getTemplateResource('customLocaleGridFilter.tpl');
	}

	/**
	 * @copydoc GridHandler::renderFilter()
	 */
	function renderFilter($request, $filterData = array()) {
		$locales = $request->getContext()->getSupportedLocaleNames();
		return parent::renderFilter(
			$request,
			array_merge_recursive(
				$filterData,
				[
					'localeOptions' => $locales,
				]
			)
		);
	}

	/**
	 * @copydoc GridHandler::getFilterSelectionData()
	 */
	function getFilterSelectionData($request) {
		// Get the search terms.

		$locales = $request->getContext()->getSupportedLocaleNames();
		$locale = $request->getUserVar('locale');
		// consider app locale as well
		if (!in_array($locale, array_keys($locales))) {
			$locale = AppLocale::getLocale();
			if (!in_array($locale, array_keys($locales))) $locale = LOCALE_DEFAULT;
		}

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

	/**
	 * Edit a locale file.
	 * @param $args array
	 * @param $request PKPKRequest
	 */
	function editLocaleFile($args, $request) {
		$context = $request->getContext();
		$this->setupTemplate($request);

		// Create and present the edit form
		import('plugins.generic.customLocale.controllers.grid.form.LocaleFileForm');

		$localeFileForm = new LocaleFileForm(self::$plugin, $args['filePath'], $args['locale']);
		$localeFileForm->initData();
		return new JSONMessage(true, $localeFileForm->fetch($request));
	}
}

