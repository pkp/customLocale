<?php

/**
 * @file CustomLocalePlugin.inc.php
 *
 * Copyright (c) 2016-2020 Language Science Press
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CustomLocalePlugin
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class CustomLocalePlugin extends GenericPlugin {

	/**
	 * @copydoc Plugin::register
	 */
	function register($category, $path, $mainContextId = null) {
		if (!parent::register($category, $path, $mainContextId)) return false;

		if ($this->getEnabled()) {
			// Add custom locale data for already registered locale files.
			$locale = AppLocale::getLocale();
			$localeFiles = AppLocale::getLocaleFiles($locale);

			$request = Application::get()->getRequest();
			$context = $request->getContext();

			import('lib.pkp.classes.file.ContextFileManager');
			$contextFileManager = new ContextFileManager($context->getId());
			$customLocalePathBase = $contextFileManager->getBasePath() . "customLocale/$locale/";

			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			foreach ($localeFiles as $localeFile) {
				$customLocalePath = $customLocalePathBase . $localeFile->getFilename();
				if ($contextFileManager->fileExists($customLocalePath)) {
					AppLocale::registerLocaleFile($locale, $customLocalePath, false);
				}
			}

			// Add custom locale data for all locale files registered after this plugin
			HookRegistry::register('PKPLocale::registerLocaleFile', array($this, 'addCustomLocale'));
			HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			HookRegistry::register('Template::Settings::website', array($this, 'callbackShowWebsiteSettingsTabs'));
			HookRegistry::register('LoadHandler', array($this, 'handleLoadRequest'));

			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addJavaScript(
				'customLocale',
				$request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/customLocale.js',
				['contexts' => 'backend']
			);
		}

		return true;
	}

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
		$tail = implode('/', $request->getRequestedArgs());

		if ($page=='management' && $op=='settings' && $tail=='printCustomLocaleChanges') {
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

		$contextFileManager = new ContextFileManager($context->getId());
		$customLocalePath = $contextFileManager->getBasePath() . "customLocale/$locale/$localeFilename";

		if ($contextFileManager->fileExists($customLocalePath)) {
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
		$templateMgr = $args[1];
		$output =& $args[2];

		$output .= $templateMgr->fetch($this->getTemplateResource('customLocaleTab.tpl'));

		// Permit other plugins to continue interacting with this hook
		return false;
	}

	/**
	 * @copydoc PKPPlugin::getSeq()
	 */
	function getSeq() {
		return -1;
	}
}

