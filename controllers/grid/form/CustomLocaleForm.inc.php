<?php

/**
 * @file controllers/grid/form/CustomLocaleForm.inc.php
 *
 * Copyright (c) 2016-2020 Language Science Press
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleForm
 */

import('lib.pkp.classes.form.Form');

class CustomLocaleForm extends Form {
	/** @var $localeFiles array */
	var $localeFiles;

	/* @var $locales array */
	var $locales;

	/** Custom locale plugin */
	var $plugin;

	function setLocales($locales) {
		$this->locales = $locales;
	}

	function setLocaleFiles($localeFiles) {
		$this->localeFiles = $localeFiles;
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {

		// Set custom template.
		if (!is_null($template)) $this->_template = $template;

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->setCacheability(CACHEABILITY_NO_STORE);

		$templateMgr->assign($this->_data);
		$templateMgr->assign(array(
			'locales' => $this->locales,
			'localeFiles' => $this->localeFiles,
			'masterLocale' => MASTER_LOCALE,
		));

		return $templateMgr->fetch($request, $this->_template, $display);
	}
}

