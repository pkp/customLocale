<?php

/**
 * @file classes/CustomLocale.php
 *
 * Copyright (c) 2016-2022 Language Science Press
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CustomLocale
 */

namespace APP\plugins\generic\customLocale\classes;

use PKP\core\DataObject;

class CustomLocale extends DataObject
{
    /**
     * Constructor
     */
    public function __construct(int $id, string $locale, string $name)
    {
        $this->setId($id);
        $this->setLocale($locale);
        $this->setName($name);
    }

    /**
     * Get locale
     */
    public function getLocale(): string
    {
        return $this->getData('locale');
    }

    /**
     * Set locale
     */
    public function setLocale(string $locale): void
    {
        $this->setData('locale', $locale);
    }

    /**
     * Get name
     */
    public function getName(): string
    {
        return $this->getData('name');
    }

    /**
     * Set name
     */
    public function setName(string $name): void
    {
        $this->setData('name', $name);
    }
}
