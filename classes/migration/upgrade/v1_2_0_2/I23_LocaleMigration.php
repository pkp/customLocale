<?php

/**
 * @file classes/migration/upgrade/v1_2_0_2/I23_LocaleMigration.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I23_LocaleMigration
 *
 * @brief Updates the locale files to the new structure (single file per locale) and also renames old locale folders.
 * @see https://github.com/pkp/customLocale/issues/15
 * @see https://github.com/pkp/customLocale/issues/23
 */

namespace APP\plugins\generic\customLocale\classes\migration\upgrade\v1_2_0_2;

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

class I23_LocaleMigration extends Migration
{
    public function __construct(private CustomLocalePlugin $plugin)
    {
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure this process is executed only once (re-running will just cause a performance penalty though)
        $lastMigration = $this->plugin->getSetting($this->plugin->getCurrentContextId(), 'lastMigration');
        if (version_compare($lastMigration ?: '0', '1.2.0.2', '>=')) {
            return;
        }

        // This will retrieve the path for the current context, eventually all contexts will be upgraded
        $customLocalePath = CustomLocalePlugin::getStoragePath();
        // Check if old lock file from a previous migration attempt exists and remove it
        $oldLockFilePath = $customLocalePath . '/migration-1_1_0.lock';
        if (file_exists($oldLockFilePath)) {
            unlink($oldLockFilePath);
        }

        // Get all locale files in the custom locale directory
        $directory = new RecursiveDirectoryIterator($customLocalePath);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, '/^.+\.po$/i', RecursiveRegexIterator::GET_MATCH);
        $files = array_keys(iterator_to_array($regex));
        /** @var Translations[] $translationsByLocale */
        $translationsByLocale = [];
        $pathsToUnlink = [];

        foreach ($files as $path) {
            if ($this->processLocaleFile($customLocalePath, $path, $translationsByLocale)) {
                // Keeps track of the locale files that we merged, so we can remove them later
                $pathsToUnlink[] = $path;
            }
        }

        $contextFileManager = CustomLocalePlugin::getContextFileManager();
        // Generates the merged and unified locale files
        foreach ($translationsByLocale as $locale => $translations) {
            $basePath = "{$customLocalePath}/{$locale}";
            if (!is_dir($basePath)) {
                $contextFileManager->mkdir($basePath);
            }

            $customFilePath = "{$basePath}/locale.po";
            if (!(new PoGenerator())->generateFile($translations, $customFilePath)) {
                throw new Exception("Failed to serialize translations to {$customFilePath}");
            }
        }

        // Removes locale files which were merged
        foreach ($pathsToUnlink as $path) {
            if (!unlink($path)) {
                throw new Exception("Failed to remove locale file {$path}");
            }
        }

        // Attempts to remove empty folders
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($customLocalePath), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if (!in_array($file->getBasename(), ['.', '..']) && $file->isDir()) {
                @rmdir($file->getPathName());
            }
        }

        // Setup the last migration
        $this->plugin->updateSetting($this->plugin->getCurrentContextId(), 'lastMigration', $this->plugin->getCurrentVersion()->getVersionString());
    }

    /**
     * Attempts to merge the contents of the given locale file with the unified locale file (locale.po)
     * If the unified file doesn't exist, it will be created, it also considers renamed locales (see method getRenamedLocales())
     *
     * @param string $path
     * @param array<string, Translations> $translationsByLocale
     * @return bool Returns true if the file needed to be processed (if the file isn't unified locale or if it's placed in the wrong path), otherwise false
     */
    private function processLocaleFile(string $customLocalePath, string $path, array &$translationsByLocale): bool
    {
        $renamedLocales = $this->getRenamedLocales();
        // Removes the base folder from the path
        $trailingPath = substr($path, strlen($customLocalePath) + 1);
        $parts = preg_split('#[/\\\]#', $trailingPath, 2);
        // The first folder holds the locale name
        $locale = $parts[0];
        $filename = $parts[1] ?? '';

        $newLocale = $renamedLocales[$locale] ?? $locale;
        $customFilePath = $customLocalePath . "/{$newLocale}/locale.po";
        if (!Locale::isLocaleValid($newLocale)) {
            error_log("An invalid locale \"{$newLocale}\" was processed by the custom locale plugin migration, you might review it at {$customFilePath}");
        }

        // If we're processing the unified file (locale.po) and the locale folder wasn't changed, then we're done with this entry
        if ($filename === 'locale.po' && $newLocale === $locale) {
            return false;
        }

        // Attempts to load existing translations, otherwise create a new set
        $translationsByLocale[$newLocale] ??= file_exists($customFilePath)
            ? LocaleFile::loadTranslations($customFilePath)
            : Translations::create(null, $newLocale);
        // Loads the translations from the outdated locale files and merge all of them into a single Translations object
        $newTranslations = LocaleFile::loadTranslations($path);
        $translationsByLocale[$newLocale] = $translationsByLocale[$newLocale]->mergeWith($newTranslations);
        return true;
    }

    /**
     * Retrieves a list of locales which were renamed in the 3.4 release
     */
    private static function getRenamedLocales(): array
    {
        return [
            'es_ES' => 'es',
            'en_US' => 'en',
            'sr_RS@cyrillic' => 'sr@cyrillic',
            'sr_RS@latin' => 'sr@latin',
            'el_GR' => 'el',
            'de_DE' => 'de',
            'da_DK' => 'da',
            'cs_CZ' => 'cs',
            'ca_ES' => 'ca',
            'bs_BA' => 'bs',
            'bg_BG' => 'bg',
            'be_BY@cyrillic' => 'be@cyrillic',
            'az_AZ' => 'az',
            'ar_IQ' => 'ar',
            'fa_IR' => 'fa',
            'fi_FI' => 'fi',
            'gd_GB' => 'gd',
            'gl_ES' => 'gl',
            'he_IL' => 'he',
            'hi_IN' => 'hi',
            'hr_HR' => 'hr',
            'hu_HU' => 'hu',
            'hy_AM' => 'hy',
            'id_ID' => 'id',
            'is_IS' => 'is',
            'it_IT' => 'it',
            'ja_JP' => 'ja',
            'ka_GE' => 'ka',
            'kk_KZ' => 'kk',
            'ko_KR' => 'ko',
            'ku_IQ' => 'ku',
            'lt_LT' => 'lt',
            'lv_LV' => 'lv',
            'mk_MK' => 'mk',
            'mn_MN' => 'mn',
            'ms_MY' => 'ms',
            'nb_NO' => 'nb',
            'nl_NL' => 'nl',
            'pl_PL' => 'pl',
            'ro_RO' => 'ro',
            'ru_RU' => 'ru',
            'si_LK' => 'si',
            'sk_SK' => 'sk',
            'sl_SI' => 'sl',
            'sv_SE' => 'sv',
            'tr_TR' => 'tr',
            'uk_UA' => 'uk',
            'ur_PK' => 'ur',
            'uz_UZ@cyrillic' => 'uz@cyrillic',
            'uz_UZ@latin' => 'uz@latin',
            'vi_VN' => 'vi',
            'eu_ES' => 'eu',
            'sw_KE' => 'sw',
            'zh_TW' => 'zh_Hant'
        ];
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
