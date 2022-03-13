<?php

/**
 * @file classes/migration/upgrade/v1_2_0/I15_LocaleMigration.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I15_LocaleMigration
 * @brief Update the locale files to the new structure (single file per locale).
 */

namespace APP\plugins\generic\customLocale\classes\migration\upgrade\v1_2_0;

use APP\plugins\generic\customLocale\CustomLocalePlugin;
use Exception;
use Gettext\Generator\PoGenerator;
use Gettext\Translations;
use Illuminate\Database\Migrations\Migration;
use PKP\facades\Locale;
use PKP\i18n\translation\LocaleFile;
use PKP\install\DowngradeNotSupportedException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileObject;

class I15_LocaleMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // https://github.com/pkp/customLocale/issues/15
        $customLocalePath = CustomLocalePlugin::getStoragePath();
        // File lock to ensure this process is executed only once
        $lockFilePath = $customLocalePath . '/migration-1_1_0.lock';
        if (file_exists($lockFilePath)) {
            return;
        }

        $success = false;
        $lockFile = new SplFileObject($lockFilePath, 'x');
        try {
            if (!$lockFile->flock(LOCK_EX)) {
                throw new Exception('Failed to lock file');
            }

            // Get all po-files in the custom locale directory
            $directory = new RecursiveDirectoryIterator($customLocalePath);
            $iterator = new RecursiveIteratorIterator($directory);
            $regex = new RegexIterator($iterator, '/^.+\.po$/i', RecursiveRegexIterator::GET_MATCH);
            $files = array_keys(iterator_to_array($regex));

            /** @var Translations[] */
            $translationsByLocale = [];
            $pathsToUnlink = [];
            foreach ($files as $path) {
                // Removes the base folder from the path
                $trailingPath = substr($path, strlen($customLocalePath) + 1);
                $parts = explode('/', $trailingPath);
                // The first part should be the locale
                $locale = $parts[0];
                if (!Locale::isLocaleValid($locale)) {
                    continue;
                }
                $filename = $parts[1] ?? '';
                // If the second part is the file "locale.po" file, then we're done with this entry
                if ($filename === 'locale.po') {
                    continue;
                }

                // Attempts to load existing translations, otherwise create a new set
                $customFilePath = $customLocalePath . "/${locale}/locale.po";
                $translationsByLocale[$locale] ??= file_exists($customFilePath)
                    ? LocaleFile::loadTranslations($customFilePath)
                    : Translations::create(null, $locale);
                // Loads the translations from the outdated locale files and merge all of them into a single Translations object
                $newTranslations = LocaleFile::loadTranslations($path);
                $translationsByLocale[$locale] = $translationsByLocale[$locale]->mergeWith($newTranslations);
                // Keeps track of the locale files that we merged, so we can remove them later
                $pathsToUnlink[] = $path;
            }

            $contextFileManager = CustomLocalePlugin::getContextFileManager();
            // Generates the updated locale files
            foreach ($translationsByLocale as $locale => $translations) {
                $basePath = "${customLocalePath}/${locale}";
                if (!is_dir($basePath)) {
                    $contextFileManager->mkdir($basePath);
                }
                $customFilePath = "${basePath}/locale.po";
                if (!(new PoGenerator())->generateFile($translations, $customFilePath)) {
                    throw new Exception('Failed to serialize translations');
                }
            }

            // Removes outdated locale files
            foreach ($pathsToUnlink as $path) {
                if (!unlink($path)) {
                    throw new Exception('Failed to remove translations');
                }
            }

            // Attempts to remove empty folders
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($customLocalePath), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if (!in_array($file->getBasename(), ['.', '..']) && $file->isDir()) {
                    @rmdir($file->getPathName());
                }
            }

            $success = true;
        } finally {
            if ($lockFile) {
                $lockFile->flock(LOCK_UN);
                $lockFile = null;
                if (!$success) {
                    unlink($lockFilePath);
                }
            }
        }
    }

    /**
     * Reverse the upgrade
     *
     * @throws DowngradeNotSupportedException
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
