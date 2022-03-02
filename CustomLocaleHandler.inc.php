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
use PKP\facades\Locale;
use PKP\file\ContextFileManager;
use PKP\i18n\translation\LocaleFile;

class CustomLocaleHandler extends Handler
{
    /**
     * Print the custom locale changes.
     */
    public function printCustomLocaleChanges(array $args, PKPRequest $request): void
    {
        $context = $request->getContext();

        $contextFileManager = new ContextFileManager($context->getId());
        $customLocalePath = realpath($contextFileManager->getBasePath() . CustomLocalePlugin::LOCALE_FOLDER);
        if (!$contextFileManager->fileExists($customLocalePath, 'dir')) {
            throw new Exception("Path \"${customLocalePath}\" does not exist");
        }

        // Get all po-files in the custom locale directory
        $directory = new RecursiveDirectoryIterator($customLocalePath);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/^.+\.po$/i', RecursiveRegexIterator::GET_MATCH);
        $files = array_keys(iterator_to_array($regex));

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="customLocale.txt"');

        // iterate through all customized files
        foreach ($files as $localeFile) {
            /** @var Translation[] */
            $customTranslations = LocaleFile::loadTranslations($localeFile)->getTranslations();
            if (!count($customTranslations)) {
                continue;
            }

            $locale = explode('/', substr($localeFile, strlen($customLocalePath) + 1))[0];
            $bundle = Locale::getBundle($locale);
            $entries = $bundle->getEntries();
            // Remove custom locale entries from the bundle in order to retrieve the original translation
            $entries = array_filter($entries, fn (string $path) => !str_starts_with($path, $customLocalePath), ARRAY_FILTER_USE_KEY);
            $bundle->setEntries($entries);
            $translator = $bundle->getTranslator();
            $sanitizedPath = str_replace($customLocalePath, '', $localeFile);
            echo "File: ${sanitizedPath}\n";
            echo "Locale: ${locale}\n\n";
            foreach ($customTranslations as $translation) {
                $localeKey = $translation->getOriginal();
                echo "Locale key: ${localeKey}";
                echo "\nOriginal: {$translator->getSingular($localeKey)}";
                echo "\nCustomized: {$translation->getTranslation()}\n\n";
            }
        }
    }
}
