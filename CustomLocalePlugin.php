<?php

/**
 * @file CustomLocalePlugin.php
 *
 * Copyright (c) 2016-2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocalePlugin
 */

namespace APP\plugins\generic\customLocale;

use APP\core\Application;
use APP\plugins\generic\customLocale\classes\migration\upgrade\v1_2_0\I15_LocaleMigration;
use APP\plugins\generic\customLocale\controllers\grid\CustomLocaleGridHandler;
use APP\template\TemplateManager;
use Exception;
use PKP\core\PKPApplication;
use PKP\facades\Locale;
use PKP\file\ContextFileManager;
use PKP\i18n\translation\Translator;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RedirectAction;
use PKP\plugins\GenericPlugin;
use PKP\plugins\HookRegistry;

class CustomLocalePlugin extends GenericPlugin
{
    /** @var string Keeps the folder where custom locale files will be stored */
    public const LOCALE_FOLDER = 'customLocale';

    /**
     * @copydoc Plugin::register
     *
     * @param null|int $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!$success || Application::isUnderMaintenance() || !$this->getEnabled()) {
            return $success;
        }
        $this->upgrade();
        // Add custom locale data for already registered locale files.
        $this->setupLocalizationOverriding();
        $this->setupGridHandler();
        $this->callbackShowWebsiteSettingsTabs();
        $this->setupDownloadChangesEndpoint();
        $this->setupTemplate();
        return $success;
    }

    /**
     * Starts the localization overriding
     */
    public function setupLocalizationOverriding(): void
    {
        Locale::registerPath(static::getStoragePath(), PHP_INT_MAX);
    }

    /**
     * Setups the template manager
     */
    public function setupTemplate(): void
    {
        $request = Application::get()->getRequest();
        TemplateManager::getManager($request)->addJavaScript(
            'customLocale',
            "{$request->getBaseUrl()}/{$this->getPluginPath()}/js/customLocale.js",
            ['contexts' => 'backend']
        );
    }

    /**
     * Permit requests to the custom locale grid handler
     */
    public function setupGridHandler(): void
    {
        HookRegistry::register('LoadComponentHandler', function (string $hookName, array $args): bool {
            $component = $args[0];
            if ($component !== 'plugins.generic.customLocale.controllers.grid.CustomLocaleGridHandler') {
                return false;
            }

            // Allow the custom locale grid handler to get the plugin object
            CustomLocaleGridHandler::setPlugin($this);
            return true;
        });
    }

    /**
     * Setup the hook to download the changes
     */
    public function setupDownloadChangesEndpoint(): void
    {
        HookRegistry::register('LoadHandler', function (string $hookName, array $args): bool {
            $request = $this->getRequest();
            // Get url path components by reference
            [&$page, &$op] = $args;
            $tail = implode('/', $request->getRequestedArgs());

            if ([$page, $op, $tail] === ['management', 'settings', 'printCustomLocaleChanges']) {
                $op = 'printCustomLocaleChanges';
                define('HANDLER_CLASS', CustomLocaleHandler::class);
            }

            return false;
        });
    }

    /**
     * Extend the website settings tabs to include the custom locale tab
     */
    public function callbackShowWebsiteSettingsTabs(): void
    {
        HookRegistry::register('Template::Settings::website', function (string $hookName, array $args): bool {
            [, $templateMgr, &$output] = $args;
            $output .= $templateMgr->fetch($this->getTemplateResource('customLocaleTab.tpl'));
            // Permit other plugins to continue interacting with this hook
            return false;
        });
    }

    /**
     * Retrieves the path where custom locales are stored
     */
    public static function getStoragePath(): string
    {
        static $path;
        if ($path) {
            return $path;
        }

        $contextFileManager = static::getContextFileManager();
        $path = $contextFileManager->getBasePath() . static::LOCALE_FOLDER;
        if (!is_dir($path)) {
            $contextFileManager->mkdir($path);
        }

        return realpath($path);
    }

    /**
     * Retrieves a translator instance without the localization overrides
     */
    public static function getTranslator(string $locale): Translator
    {
        static $translator;
        if ($translator) {
            return $translator;
        }

        $bundle = Locale::getBundle($locale, false);
        $entries = $bundle->getEntries();
        $customLocalePath = static::getStoragePath();
        // Remove custom locale entries from the bundle in order to retrieve the original translation
        $entries = array_filter($entries, fn (string $path) => !str_starts_with($path, $customLocalePath), ARRAY_FILTER_USE_KEY);
        $bundle->setEntries($entries);
        return $translator = $bundle->getTranslator();
    }

    /**
     * Retrieves an instance of the ContextFileManager
     */
    public static function getContextFileManager(): ContextFileManager
    {
        $context = Application::get()->getRequest()->getContext();
        return new ContextFileManager($context->getId());
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) {
            return $actions;
        }

        $dispatcher = $request->getDispatcher();
        array_unshift(
            $actions,
            new LinkAction(
                'customize',
                new RedirectAction($dispatcher->url(
                    $request,
                    PKPApplication::ROUTE_PAGE,
                    null,
                    'management',
                    'settings',
                    'website',
                    ['uid' => uniqid()], // Force reload
                    'customLocale' // Anchor for tab
                )),
                __('plugins.generic.customLocale.customize')
            ),
            new LinkAction(
                'printChanges',
                new RedirectAction($dispatcher->url(
                    $request,
                    PKPApplication::ROUTE_PAGE,
                    null,
                    'management',
                    'settings',
                    'printCustomLocaleChanges',
                    ['uid' => uniqid()] // Force reload
                )),
                __('plugins.generic.customLocale.printChanges')
            )
        );
        return $actions;
    }

    /**
     * Attempts to upgrade the plugin
     */
    public function upgrade(): void
    {
        try {
            (new I15_LocaleMigration())->up();
        }
        catch (Exception $e) {
            error_log("An exception happened while upgrading the customLocale plugin.\n" . $e);
        }
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName(): string
    {
        return __('plugins.generic.customLocale.name');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription(): string
    {
        return __('plugins.generic.customLocale.description');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\customLocale\CustomLocalePlugin', '\CustomLocalePlugin');
}
