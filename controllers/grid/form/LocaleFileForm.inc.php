<?php

use APP\template\TemplateManager;
use PKP\file\ContextFileManager;
use PKP\form\Form;
use PKP\i18n\translation\LocaleFile;

/**
 * @file controllers/grid/form/LocaleFileForm.inc.php
 *
 * Copyright (c) 2016-2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LocaleFileForm
 */

class LocaleFileForm extends Form
{
    /** @var string $filePath */
    protected $filePath;

    /** @var string $locale */
    protected $locale;

    /** @var CustomLocalePlugin Custom locale plugin */
    protected $plugin;

    /**
     * Constructor
     */
    public function __construct(CustomLocalePlugin $customLocalePlugin, string $filePath, string $locale)
    {
        parent::__construct($customLocalePlugin->getTemplateResource('localeFile.tpl'));
        $this->plugin = $customLocalePlugin;
        $this->filePath = $filePath;
        $this->locale = $locale;
    }

    /**
     * @copydoc Form::fetch
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false): string
    {
        $file = '/' . ltrim($this->filePath, '/');
        $locale = $this->locale;
        if (!CustomLocaleAction::isLocaleFile($locale, BASE_SYS_DIR . $file)) {
            throw new Exception("${file} is not a locale file");
        }

        $contextFileManager = new ContextFileManager($request->getContext()->getId());
        $customLocalePath = $contextFileManager->getBasePath() . CustomLocalePlugin::LOCALE_FOLDER . "/${locale}" . $file;

        $localeContents = null;
        if ($contextFileManager->fileExists($customLocalePath)) {
            $localeContents = reset(LocaleFile::loadArray($customLocalePath)['messages']);
        }
        $referenceLocale = LocaleFile::loadArray(BASE_SYS_DIR . $file);
        $referenceLocaleContents = [];
        foreach (reset($referenceLocale['messages']) as $key => $value) {
            $referenceLocaleContents[] = [
                'localeKey' => $key,
                'value' => $value,
            ];
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'filePath' => $this->filePath,
            'localeContents' => $localeContents,
            'locale' => $locale,
            'referenceLocaleContents' => $referenceLocaleContents,
        ]);

        return parent::fetch($request, $template, $display);
    }
}
