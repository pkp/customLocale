<?php

use PKP\facades\Locale;
use PKP\plugins\PluginRegistry;

/**
 * @file controllers/grid/CustomLocaleAction.inc.php
 *
 * Copyright (c) 2016-2021 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocaleAction
 */

class CustomLocaleAction
{
    /**
     * Get a list of locale files.
     */
    public static function getLocaleFiles(string $locale): array
    {
        if (!Locale::isLocaleValid($locale)) {
            return [];
        }
        PluginRegistry::loadAllPlugins();
        return array_keys(Locale::getBundle($locale)->getEntries());
    }

    /**
     * Determine whether a specified file is a locale file.
     */
    public static function isLocaleFile(string $locale, string $filename): bool
    {
        return in_array($filename, self::getLocaleFiles($locale));
    }
}
