<?php

/**
 * @file plugins/generic/customLocale/CustomLocalePlugin.inc.php
 *
 * Copyright (c) 2016 Language Science Press
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocalePlugin
 *
 */

define('CUSTOM_LOCALE_DIR', 'customLocale');
import('lib.pkp.classes.plugins.GenericPlugin');

class CustomLocalePlugin extends GenericPlugin {

	/**
	 * Register the plugin.
	 * @param $category string Plugin category
	 * @param $path string Plugin path
	 */
	/* UZH CHANGE OJS-74 2019/03/05/mb adapt to OJS 3.1.2 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);
		if ($success && $this->getEnabled()) {

			// Add custom locale data for already registered locale files.
			$locale = AppLocale::getLocale();
			$localeFiles = AppLocale::getLocaleFiles($locale);

			$context = Request::getContext();
			$contextId = $context->getId();

			$publicFilesDir = Config::getVar('files', 'public_files_dir');
			/* UZH CHANGE OJS-71 2019/03/22/mb set path according to application name (OJS or OMP) */
			/* $customLocalePathBase = "$publicFilesDir/presses/$contextId/" . CUSTOM_LOCALE_DIR . "/$locale/"; */
			$fileDirectories = Application::getFileDirectories();
			$contextDir = $fileDirectories['context'];
			$customLocalePathBase = "$publicFilesDir" . $contextDir . "$contextId/" . CUSTOM_LOCALE_DIR . "/$locale/";
			/* END UZH CHANGE OJS-71 */

			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			foreach ($localeFiles as $localeFile) {
				$customLocalePath = $customLocalePathBase . $localeFile->getFilename();
				if ($fileManager->fileExists($customLocalePath)) {
					AppLocale::registerLocaleFile($locale, $customLocalePath, false);
				}
			}

			// Add custom locale data for all locale files registered after this plugin
			HookRegistry::register('PKPLocale::registerLocaleFile', array(&$this, 'addCustomLocale'));
			HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			HookRegistry::register('Templates::Management::Settings::website', array($this, 'callbackShowWebsiteSettingsTabs'));
			HookRegistry::register('LoadHandler', array($this, 'handleLoadRequest'));
		}
		return $success;
	}
	/* END UZH CHANGE OJS-74 */

	/**
	 * Permit requests to the custom locale grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $args) {
		$component = $args[0];
		if ($component == 'plugins.generic.customLocale.controllers.grid.CustomLocaleGridHandler') {
			// Allow the custom locale grid handler to get the plugin object
			import($component);
			CustomLocaleGridHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * Hook callback: Handle a request for a page load
	 * @param $hookName string Hook name
	 * @param $args array Hook arguments
	 */
	function handleLoadRequest($hookName, $args) {
		$request = $this->getRequest();
		$templateMgr = TemplateManager::getManager($request);

		// get url path components
		$page =& $args[0];
		$op =& $args[1];
		$tail = '/'.implode('/',$request->getRequestedArgs());

		if ($page=='management' && $op=='settings' && $tail=='/printCustomLocaleChanges') {
			$op = 'printCustomLocaleChanges';
			define('HANDLER_CLASS', 'CustomLocaleHandler');
			define('CUSTOMLOCALE_PLUGIN_NAME', $this->getName());
			$this->import('CustomLocaleHandler');
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $actionArgs) {
		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		return array_merge(
			$this->getEnabled()?array(
				new LinkAction(
					'customize',
					new RedirectAction($dispatcher->url(
						$request, ROUTE_PAGE,
						null, 'management', 'settings', 'website',
						array('uid' => uniqid()), // Force reload
						'customLocale' // Anchor for tab
					)),
					__('plugins.generic.customLocale.customize'),
					null
				),
				new LinkAction(
					'printChanges',
					new RedirectAction($dispatcher->url(
						$request, ROUTE_PAGE,
						null, 'management', 'settings', 'printCustomLocaleChanges',
						array('uid' => uniqid()), // Force reload
						null // Anchor for tab
					)),
					__('plugins.generic.customLocale.printChanges'),
					null
				),
			):array(),
			parent::getActions($request, $actionArgs)
		);
	}

	/**
	 * Add custom locale data.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean Hook processing status
	 */
	function addCustomLocale($hookName, $args) {
		$locale =& $args[0];
		$localeFilename =& $args[1];
		$request =& Registry::get('request');
		$context = $request->getContext();
		$contextId = $context->getId();

		$publicFilesDir = Config::getVar('files', 'public_files_dir');
		/* UZH CHANGE OJS-71 2019/03/22/mb set path according to application name (OJS or OMP) */
		/* $customLocalePath = "$publicFilesDir/presses/$contextId/" . CUSTOM_LOCALE_DIR . "/$locale/$localeFilename"; */
		$fileDirectories = Application::getFileDirectories();
		$contextDir = $fileDirectories['context'];
		$customLocalePath = "$publicFilesDir" . $contextDir . "$contextId/" . CUSTOM_LOCALE_DIR . "/$locale/$localeFilename";
		/* END UZH CHANGE OJS-71 */

		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();
		if ($fileManager->fileExists($customLocalePath)) {
			AppLocale::registerLocaleFile($locale, $customLocalePath, false);
		}

		return true;
	}

	/**
	 * Get the plugin display name.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.customLocale.name');
	}

	/**
	 * Get the plugin display status.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.customLocale.description');
	}

	/**
	 * Extend the website settings tabs to include custom locale
	 * @param $hookName string The name of the invoked hook
	 * @param $args array Hook parameters
	 * @return boolean Hook handling status
	 */
	function callbackShowWebsiteSettingsTabs($hookName, $args) {
		$output =& $args[2];
		$request =& Registry::get('request');
		$dispatcher = $request->getDispatcher();

		// Add a new tab for custom locale
		$output .= '<li><a name="customLocale" href="' . $dispatcher->url($request, ROUTE_COMPONENT, null, 'plugins.generic.customLocale.controllers.grid.CustomLocaleGridHandler', 'index') . '">' . __('plugins.generic.customLocale.customLocale') . '</a></li>';

		// Permit other plugins to continue interacting with this hook
		return false;
	}

}

