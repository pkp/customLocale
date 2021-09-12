<?php

/**
 * @file CustomLocaleHandler.inc.php
 *
 * Copyright (c) 2016-2021 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleHandler
 */

use APP\handler\Handler;
use PKP\core\PKPRequest;
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
        $customLocaleDir = $contextFileManager->getBasePath() . CustomLocalePlugin::LOCALE_FOLDER;
        if (!$contextFileManager->fileExists($customLocaleDir, 'dir')) {
            throw new Exception("Path \"${customLocaleDir}\" does not exist");
        }

        // get all po-files in the custom locale directory
        $directory = new RecursiveDirectoryIterator($customLocaleDir);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/^.+\.po$/i', RecursiveRegexIterator::GET_MATCH);
        $files = iterator_to_array($regex);
        $fileKeys = array_keys($files);

        $output = '';

        // iterate through all customized files
        foreach ($fileKeys as $pathToFile) {
            $posLib = strpos($pathToFile, 'lib');
            $posLocale = strpos($pathToFile, 'locale');
            $posPlugins = strpos($pathToFile, 'plugins');

            $localeFile = '';
            if ($posLib !== false) {
                $localeFile = substr($pathToFile, $posLib);
            } elseif ($posPlugins !== false) {
                $localeFile = substr($pathToFile, $posPlugins);
            } else {
                $localeFile = substr($pathToFile, $posLocale);
            }

            $localeContentsCustomized = null;
            if ($contextFileManager->fileExists($pathToFile)) {
                $localeContentsCustomized = LocaleFile::loadArray($pathToFile);
            }

            $localeContents = null;
            if ($contextFileManager->fileExists($localeFile)) {
                $localeContents = LocaleFile::load($localeFile);
            }

            $localeKeys = array_keys($localeContentsCustomized);

            if (count($localeKeys)) {
                $output = $output . "\nFile: " . $localeFile;
            }

            foreach ($localeKeys as $index => $localeKey) {
                $output = $output . "\n\n" . ($index + 1) . '. locale key: ' . $localeKey;
                $output = $output . "\n\n	original content:   " . $localeContents[$localeKey];
                $output = $output . "\n	customized content: " . $localeContentsCustomized[$localeKey];
            }
            if (!empty($localeKeys)) {
                $output = $output . "\n\n__________________________________________________________________________________\n\n";
            }
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="customLocale-changes.txt"');
        header('Content-Length: ' . strlen($output));
        echo $output;
    }
}
