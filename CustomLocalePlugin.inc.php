<?php

/**
 * @file CustomLocalePlugin.inc.php
 *
 * Copyright (c) 2016-2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocalePlugin
 */

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\PKPApplication;
use PKP\facades\Locale;
use PKP\file\ContextFileManager;
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
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!$success || Application::isUnderMaintenance()) {
            return $success;
        }

        if ($this->getEnabled()) {
            // Add custom locale data for already registered locale files.
            $request = Application::get()->getRequest();
            $context = $request->getContext();

            $contextFileManager = new ContextFileManager($context->getId());
            $customLocalePath = $contextFileManager->getBasePath() . static::LOCALE_FOLDER;
            if (!$contextFileManager->fileExists($customLocalePath, 'dir')) {
                $contextFileManager->mkdir($customLocalePath);
            }

            Locale::registerPath($customLocalePath, PHP_INT_MAX);

            $this->setupGridHandler();
            $this->callbackShowWebsiteSettingsTabs();
            $this->setupDocumentationEndpoint();

            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->addJavaScript(
                'customLocale',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/customLocale.js',
                ['contexts' => 'backend']
            );
        }

        return $success;
    }

    /**
     * Permit requests to the custom locale grid handler
     */
    public function setupGridHandler(): void
    {
        HookRegistry::register('LoadComponentHandler', function (string $hookName, array $args): bool {
            $component = $args[0];
            if ($component == 'plugins.generic.customLocale.controllers.grid.CustomLocaleGridHandler') {
                // Allow the custom locale grid handler to get the plugin object
                import($component);
                CustomLocaleGridHandler::setPlugin($this);
                return true;
            }
            return false;
        });
    }

    /**
     * Setup the hook to print the documentation
     */
    public function setupDocumentationEndpoint(): void
    {
        HookRegistry::register('LoadHandler', function (string $hookName, array $args): bool {
            $request = $this->getRequest();
            // Get url path components by reference
            [&$page, &$op] = $args;
            $tail = implode('/', $request->getRequestedArgs());

            if ([$page, $op, $tail] === ['management', 'settings', 'printCustomLocaleChanges']) {
                $op = 'printCustomLocaleChanges';
                $this->import('CustomLocaleHandler');
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
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $dispatcher = $request->getDispatcher();
        $actions = parent::getActions($request, $actionArgs);
        if ($this->getEnabled()) {
            array_unshift($actions, ...[
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
                    __('plugins.generic.customLocale.customize'),
                    null
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
                        ['uid' => uniqid()], // Force reload
                        null // Anchor for tab
                    )),
                    __('plugins.generic.customLocale.printChanges'),
                    null
                ),
            ]);
        }
        return $actions;
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
