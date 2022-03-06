<?php

/**
 * @file CustomLocaleHandler.inc.php
 *
 * Copyright (c) 2016-2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleHandler
 */

use APP\handler\Handler;
use Gettext\Translation;
use PKP\core\PKPRequest;
use PKP\i18n\translation\LocaleFile;

class CustomLocaleHandler extends Handler
{
    /**
     * Print the custom locale changes.
     */
    public function printCustomLocaleChanges(array $args, PKPRequest $request): void
    {
        $customLocalePath = CustomLocalePlugin::getStoragePath();

        // Get all po-files in the custom locale directory
        $directory = new RecursiveDirectoryIterator($customLocalePath);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/^.+\.po$/i', RecursiveRegexIterator::GET_MATCH);
        $files = array_keys(iterator_to_array($regex));

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="custom-locale.txt"');

        // iterate through all customized files
        foreach ($files as $localeFile) {
            /** @var Translation[] */
            $customTranslations = LocaleFile::loadTranslations($localeFile)->getTranslations();
            if (!count($customTranslations)) {
                continue;
            }

            $locale = explode('/', substr($localeFile, strlen($customLocalePath) + 1))[0];
            $translator = CustomLocalePlugin::getTranslator($locale);
            echo "${locale}\n\n";
            foreach ($customTranslations as $translation) {
                $localeKey = $translation->getOriginal();
                echo __('common.id') . ": ${localeKey}"
                    . "\n" . __('plugins.generic.customLocale.file.reference') . ": {$translator->getSingular($localeKey)}"
                    . "\n" . __('plugins.generic.customLocale.file.custom') . ": {$translation->getTranslation()}\n\n";
            }
        }
    }
}
