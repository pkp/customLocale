<?php

/**
 * @file plugins/generic/customLocale/controllers/grid/form/LocaleFileForm.inc.php
 *
 * Copyright (c) 2016 Language Science Press
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LocaleFileForm
 */

import('lib.pkp.classes.form.Form');

class LocaleFileForm extends Form {

	/** @var $contextId int */
	var $contextId;

	/** @var $filePath string */
	var $filePath;

	/** @var $locale string */
	var $locale;

	/** Custom locale plugin */
	var $plugin;

	/**
	 * Constructor
	 * @param $customLocalePlugin object
	 * @param $contextId int Context ID
	 * @param $filePath string
	 * @param $locale string
	 */
	function __construct($customLocalePlugin, $contextId, $filePath, $locale) {
		/* UZH CHANGE OJS-74 2019/03/05/mb adapt to OJS 3.1.2 */
		/* parent::__construct($customLocalePlugin->getTemplatePath() . 'localeFile.tpl'); */
		parent::__construct($customLocalePlugin->getTemplateResource('localeFile.tpl'));
		$this->filePath = $filePath;
		$this->locale = $locale;

		$this->plugin = $customLocalePlugin;
	}

	/**
	 * @copydoc Form::fetch
	 * @param $currentPage int
	 * @param $searchKey string
	 * @param $searchString string
	 */
	function fetch($request, $currentPage=0, $searchKey='', $searchString='') {
		$file =  $this->filePath;
		$locale = $this->locale;

		$templateMgr =& TemplateManager::getManager();

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();

		import('lib.pkp.classes.file.EditableLocaleFile');
		$context = $request->getContext();
		$contextId = $context->getId();

		/* UZH CHANGE OJS-71 2019/03/29/mb set path according to application (OJS or OMP) */
		/* $publicFilesDir = Config::getVar('files', 'public_files_dir'); */
		/* $customLocaleDir = $publicFilesDir . "/presses/$contextId/" . CUSTOM_LOCALE_DIR; */
		/* $customLocalePath = "$customLocaleDir/$locale/$file"; */
		$contextDir = PublicFileManager::getContextFilesPath(Application::getContextAssocType(), $contextId);
		$customLocalePath = "$contextDir/" . CUSTOM_LOCALE_DIR . "/$locale/$file";
		/* END UZH CHANGE OJS-71 */
		

		if ($fileManager->fileExists($customLocalePath)) {
			$localeContents = EditableLocaleFile::load($customLocalePath);
		} else {
			$localeContents = null;
		}

		if (!CustomLocaleAction::isLocaleFile($locale, $file)) {
			fatalError("$file is not a locale file!");
		}

		$referenceLocaleContents = EditableLocaleFile::load($file);
		$referenceLocaleContentsRangeInfo = Handler::getRangeInfo($request,'referenceLocaleContents');

		$numberOfItemsPerPage = 30;
		$numberOfPages = ceil(sizeof($referenceLocaleContents) / $numberOfItemsPerPage);

		if ($searchKey) {

			$keysReferenceLocaleContents = array_keys($referenceLocaleContents);
			$keyPosition = array_search($searchString, $keysReferenceLocaleContents);

			if ($keyPosition==0) {
				$currentPage = 1;
			}

			if ($keyPosition>0) {
				$currentPage = floor($keyPosition/$numberOfItemsPerPage)+1;
			}

		}

		// set page number, default: go to first page
		if (!$currentPage){
			$currentPage=1;
		}

		$dropdownEntries = array();
		for ($i=1; $i<=$numberOfPages; $i++) {
			if ($i==$currentPage) {
				$dropdownEntries[$i] = "stay on page " . $i;
			} else {
				$dropdownEntries[$i] = "go to page " . $i;
			}

		}

		import('lib.pkp.classes.core.ArrayItemIterator');
		$templateMgr->assign(array(
			'filePath' => $this->filePath,
			'localeContents' => $localeContents,
			'locale' => $locale,
			'currentPage' => $currentPage,
			'dropdownEntries' => $dropdownEntries,
			'searchString' => $searchString,
			'referenceLocaleContents' => new ArrayItemIterator($referenceLocaleContents, $currentPage, $numberOfItemsPerPage),
		));

		return parent::fetch($request);
	}
}

