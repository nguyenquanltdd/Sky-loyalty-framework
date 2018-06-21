<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Service;

use OpenLoyalty\Bundle\SettingsBundle\Exception\AlreadyExistException;
use OpenLoyalty\Bundle\SettingsBundle\Exception\NotExistException;
use OpenLoyalty\Bundle\SettingsBundle\Model\TranslationsEntry;

/**
 * Interface TranslationsProvider.
 */
interface TranslationsProvider
{
    /**
     * @return TranslationsEntry
     */
    public function getCurrentTranslations();

    /**
     * @param string $key
     *
     * @return TranslationsEntry
     */
    public function getTranslationsByKey($key);

    /**
     * @return array
     */
    public function getAvailableTranslationsList();

    /**
     * @param $key
     *
     * @return bool
     */
    public function hasTranslation($key);

    /**
     * @param TranslationsEntry $entry
     * @param null              $key
     * @param bool              $overwrite
     *
     * @throws AlreadyExistException
     * @throws NotExistException
     */
    public function save(TranslationsEntry $entry, $key = null, $overwrite = true);
}
