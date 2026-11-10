<?php

/**
 * @file CustomLocalePlugin.php
 *
 * Copyright (c) 2016-2026 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocalePlugin
 */

namespace APP\plugins\generic\customLocale;

use APP\core\Application;
use APP\plugins\generic\customLocale\classes\migration\upgrade\v1_2_0_2\I23_LocaleMigration;
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
use PKP\plugins\Hook;
use Throwable;

class CustomLocalePlugin extends GenericPlugin
{
    /** @var string Keeps the folder where custom locale files will be stored */
    public const LOCALE_FOLDER = 'customLocale';

    /**
     * Register the plugin and, when it is enabled, set up the locale overrides,
     * the settings tab and the handlers that manage the custom locale files.
     *
     * Custom locales are stored per context, so nothing is set up when the
     * request has no context (site-wide pages and CLI tools). Nothing is set
     * up either when the folder that holds the custom locales can't be
     * created, and the reason is logged instead, so the rest of the site
     * keeps working.
     *
     * @param null|int $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if (
            !$success
            || Application::isUnderMaintenance()
            || !$this->getEnabled($mainContextId)
            || !Application::get()->getRequest()->getContext()
        ) {
            return $success;
        }
        try {
            static::getStoragePath();
        } catch (Exception $e) {
            error_log("The customLocale plugin could not be set up.\n" . $e);
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
        if (is_dir($path = static::getStoragePath())) {
            Locale::registerPath($path, PHP_INT_MAX);
        }
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
        Hook::add('LoadComponentHandler', function (string $hookName, array $args): bool {
            $component = &$args[0];
            $componentInstance = &$args[2];
            if ($component !== 'plugins.generic.customLocale.controllers.grid.CustomLocaleGridHandler') {
                return Hook::CONTINUE;
            }

            // Instantiate the handler and give it access to the plugin object
            CustomLocaleGridHandler::setPlugin($this);
            $componentInstance = new CustomLocaleGridHandler();
            return Hook::ABORT;
        });
    }

    /**
     * Setup the hook to download the changes
     */
    public function setupDownloadChangesEndpoint(): void
    {
        Hook::add('LoadHandler', function (string $hookName, array $args): bool {
            $request = $this->getRequest();
            // Get url path components by reference
            [&$page, &$op, &$sourceFile, &$handler] = $args;
            $tail = implode('/', $request->getRequestedArgs());

            if ([$page, $op, $tail] === ['management', 'settings', 'printCustomLocaleChanges']) {
                $op = 'printCustomLocaleChanges';
                $handler = new CustomLocaleHandler();
            }

            return Hook::CONTINUE;
        });
    }

    /**
     * Extend the website settings tabs to include the custom locale tab
     */
    public function callbackShowWebsiteSettingsTabs(): void
    {
        Hook::add('Template::Settings::website', function (string $hookName, array $args): bool {
            [, $templateMgr, &$output] = $args;
            $output .= $templateMgr->fetch($this->getTemplateResource('customLocaleTab.tpl'));
            // Permit other plugins to continue interacting with this hook
            return Hook::CONTINUE;
        });
    }

    /**
     * Retrieves the absolute path where custom locales are stored, creating
     * the folder if it doesn't exist yet.
     *
     * The path is cached for the rest of the request. It must always be the
     * absolute path, because it's matched against the paths of the files
     * registered with the locale system.
     *
     * @throws Exception When the folder doesn't exist and can't be created,
     *   for example because the web server can't write to the files directory
     */
    public static function getStoragePath(): string
    {
        static $path;
        if ($path) {
            return $path;
        }

        $contextFileManager = static::getContextFileManager();
        $folder = $contextFileManager->getBasePath() . static::LOCALE_FOLDER;
        if (!is_dir($folder)) {
            $contextFileManager->mkdir($folder);
        }

        $realPath = realpath($folder);
        if ($realPath === false || !is_dir($realPath)) {
            throw new Exception("Unable to create the custom locale folder {$folder}. Check that the web server can write to it.");
        }

        return $path = $realPath;
    }

    /**
     * Retrieves a translator for the given locale without the custom locale
     * overrides, so the original translations can be shown alongside the
     * custom ones.
     *
     * One translator is cached per locale for the rest of the request.
     */
    public static function getTranslator(string $locale): Translator
    {
        static $translators = [];
        if (isset($translators[$locale])) {
            return $translators[$locale];
        }

        $bundle = Locale::getBundle($locale, false);
        $entries = $bundle->getEntries();
        $customLocalePath = static::getStoragePath();
        // Remove custom locale entries from the bundle in order to retrieve the original translation
        $entries = array_filter($entries, fn (string $path) => !str_starts_with($path, $customLocalePath), ARRAY_FILTER_USE_KEY);
        $bundle->setEntries($entries);
        return $translators[$locale] = $bundle->getTranslator();
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
        if (!$this->getEnabled() || !$request->getContext()) {
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
                    ['website'],
                    ['uid' => uniqid()], // Force reload
                    'customLocale' // Anchor for tab
                )),
                __('plugins.generic.customLocale.customize')
            )
        );
        return $actions;
    }

    /**
     * Attempts to upgrade the plugin. Any error is logged rather than thrown,
     * so a failed upgrade doesn't stop the plugin from registering.
     */
    public function upgrade(): void
    {
        try {
            (new I23_LocaleMigration($this))->up();
        } catch (Throwable $e) {
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

    /**
     * @copydoc Plugin::getSeq()
     */
    public function getSeq(): int
    {
        return -1;
    }
}
