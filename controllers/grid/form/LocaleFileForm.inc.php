<?php

/**
 * @file controllers/grid/form/LocaleFileForm.inc.php
 *
 * Copyright (c) 2016-2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LocaleFileForm
 */

use APP\template\TemplateManager;
use PKP\facades\Locale;
use PKP\file\ContextFileManager;
use PKP\form\Form;
use PKP\i18n\translation\LocaleFile;

class LocaleFileForm extends Form
{
    /**
     * Constructor
     */
    public function __construct(protected CustomLocalePlugin $plugin, protected string $locale)
    {
        parent::__construct($this->plugin->getTemplateResource('localeFile.tpl'));
    }

    /**
     * @copydoc Form::fetch
     *
     * @param null|string $template
     */
    public function fetch($request, $template = null, $display = false): string
    {
        if (!Locale::isSupported($this->locale)) {
            throw new Exception("The locale {$this->locale} is not supported");
        }

        $contextFileManager = new ContextFileManager($request->getContext()->getId());
        $customLocalePath = CustomLocalePlugin::getStoragePath() . "/{$this->locale}/locale.po";

        $referenceLocaleContents = [];
        foreach (CustomLocalePlugin::getTranslator($this->locale)->getEntries() as $key => $value) {
            $referenceLocaleContents[] = [
                'localeKey' => $key,
                'value' => $value
            ];
        }
        $localeContents = $contextFileManager->fileExists($customLocalePath)
            ? reset(LocaleFile::loadArray($customLocalePath)['messages'])
            : [];

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'name' => Locale::getMetadata($this->locale)->getDisplayName(),
            'localeContents' => $localeContents,
            'locale' => $this->locale,
            'referenceLocaleContents' => $referenceLocaleContents,
        ]);

        return parent::fetch($request, $template, $display);
    }
}
