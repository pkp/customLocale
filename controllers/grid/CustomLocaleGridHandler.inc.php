<?php

/**
 * @file controllers/grid/CustomLocaleGridHandler.inc.php
 *
 * Copyright (c) 2016-2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleGridHandler
 */

use APP\notification\NotificationManager;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Translation;
use Gettext\Translations;
use PKP\controllers\grid\feature\PagingFeature;
use PKP\controllers\grid\GridColumn;

use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\facades\Locale;
use PKP\file\ContextFileManager;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

import('plugins.generic.customLocale.controllers.grid.CustomLocaleGridCellProvider');
import('plugins.generic.customLocale.classes.CustomLocale');
import('plugins.generic.customLocale.controllers.grid.CustomLocaleAction');

class CustomLocaleGridHandler extends GridHandler
{
    /** @var Form */
    protected $form;

    /** @var CustomLocalePlugin */
    protected static $plugin;

    /**
     * Set the custom locale plugin.
     */
    public static function setPlugin(CustomLocalePlugin $plugin): void
    {
        self::$plugin = $plugin;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            ['fetchGrid', 'editLocaleFile', 'updateLocale']
        );
    }

    /**
     * Edit a locale file.
     */
    public function editLocaleFile(array $args, PKPRequest $request): JSONMessage
    {
        $this->setupTemplate($request);

        // Create and present the edit form
        import('plugins.generic.customLocale.controllers.grid.form.LocaleFileForm');
        $localeFileForm = new LocaleFileForm(self::$plugin, $args['filePath'], $args['locale']);
        $localeFileForm->initData();
        return new JSONMessage(true, $localeFileForm->fetch($request));
    }

    /**
     * Update the custom locale data.
     */
    public function updateLocale(array $args, PKPRequest $request): JSONMessage
    {
        $context = $request->getContext();
        ['locale' => $locale, 'key' => $filename, 'changes' => $changes] = $args;

        // save changes
        if (count($changes)) {
            $contextFileManager = new ContextFileManager($context->getId());
            $customFilePath = $contextFileManager->getBasePath() . CustomLocalePlugin::LOCALE_FOLDER . "/${locale}" . $filename;

            if ($contextFileManager->fileExists($customFilePath)) {
                $loader = new PoLoader();
                $translations = $loader->loadFile($customFilePath);
            } else {
                $translations = Translations::create(null, $locale);
            }

            foreach ($changes as $key => $value) {
                $value = str_replace("\r\n", "\n", $value);
                $translation = $translations->find('', $key);
                if (strlen($value)) {
                    if (!$translation) {
                        $translation = Translation::create('', $key);
                        $translations->add($translation);
                    }
                    $translation->translate($value);
                } elseif ($translation) {
                    $translations->remove($translation);
                }
            }

            $poGenerator = new PoGenerator();
            $contextFileManager->mkdirtree(dirname($customFilePath));
            $poGenerator->generateFile($translations, $customFilePath);

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
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments): bool
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @copydoc Gridhandler::initialize()
     *
     * @param null|mixed $args
     */
    public function initialize($request, $args = null): void
    {
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
            'controllers/grid/gridCell.tpl', // Default null not supported
            $cellProvider
        ));

        $this->addColumn(new GridColumn(
            'filepath',
            'plugins.generic.customLocale.path',
            null,
            'controllers/grid/gridCell.tpl', // Default null not supported
            $cellProvider
        ));
    }

    /**
     * @copydoc GridHandler::loadData()
     */
    public function loadData($request, $filter): array
    {
        ['locale' => $locale, 'search' => $search] = $filter;
        $gridDataElements = [];
        $localeFiles = CustomLocaleAction::getLocaleFiles($locale);
        foreach ($localeFiles as $i => $localeFile) {
            if ($search !== '' && stripos($localeFile, $search) === false) {
                continue;
            }
            $customLocale = new CustomLocale();
            $customLocale->setId($i);
            $customLocale->setLocale($locale);
            $customLocale->setFilePath(str_replace(BASE_SYS_DIR, '', $localeFile));
            $gridDataElements[] = $customLocale;
        }

        return $gridDataElements;
    }

    /**
     * @copydoc GridHandler::initFeatures()
     */
    public function initFeatures($request, $args): array
    {
        return [new PagingFeature()];
    }

    //
    // Public Grid Actions
    //
    /**
     * @copydoc GridHandler::getFilterForm()
     */
    public function getFilterForm(): string
    {
        return self::$plugin->getTemplateResource('customLocaleGridFilter.tpl');
    }

    /**
     * @copydoc GridHandler::renderFilter()
     */
    public function renderFilter($request, $filterData = []): string
    {
        $locales = $request->getContext()->getSupportedLocaleNames();
        return parent::renderFilter($request, array_merge_recursive($filterData, ['localeOptions' => $locales]));
    }

    /**
     * @copydoc GridHandler::getFilterSelectionData()
     */
    public function getFilterSelectionData($request): array
    {
        // Get the search terms.
        $locales = $request->getContext()->getSupportedLocaleNames();
        $locale = $request->getUserVar('locale');
        if (!in_array($locale, array_keys($locales))) {
            $locale = Locale::getLocale();
        }

        $searchField = $request->getUserVar('searchField');
        $searchMatch = $request->getUserVar('searchMatch');
        $search = $request->getUserVar('search');

        return [
            'locale' => $locale,
            'searchField' => $searchField,
            'searchMatch' => $searchMatch,
            'search' => $search ?: ''
        ];
    }
}
