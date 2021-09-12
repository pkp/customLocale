<?php

/**
 * @file classes/CustomLocale.inc.php
 *
 * Copyright (c) 2016-2021 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocale
 */

use PKP\core\DataObject;

class CustomLocale extends DataObject
{
    public function getLocale(): string
    {
        return $this->getData('locale');
    }

    public function setLocale($locale): void
    {
        $this->setData('locale', $locale);
    }

    public function getFilePath(): string
    {
        return $this->getData('filepath');
    }

    public function setFilePath($filepath): void
    {
        $this->setData('filepath', $filepath);
    }
}
