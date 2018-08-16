<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Service;

/**
 * Interface LocaleProviderInterface.
 */
interface LocaleProviderInterface
{
    /**
     * @return string
     */
    public function getLocale(): string;

    /**
     * @return string
     */
    public function getDefaultLocale(): string;
}
