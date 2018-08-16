<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Service;

/**
 * Class LocaleProvider.
 */
class LocaleProvider implements LocaleProviderInterface
{
    /**
     * @var GeneralSettingsManagerInterface
     */
    private $settings;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var array
     */
    private $localeMap = [];

    /**
     * LocaleProvider constructor.
     *
     * @param GeneralSettingsManagerInterface $manager
     * @param string                          $defaultLocale
     * @param array                           $localeMap
     */
    public function __construct(GeneralSettingsManagerInterface $manager, string $defaultLocale, array $localeMap)
    {
        $this->settings = $manager;
        $this->defaultLocale = $defaultLocale;
        $this->localeMap = $localeMap;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(): string
    {
        $language = $this->settings->getLanguage();

        return $this->mapLanguageToLocale($language);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * @param string $language
     *
     * @return string
     */
    private function mapLanguageToLocale(string $language): string
    {
        if (!isset($this->localeMap[$language])) {
            return $this->defaultLocale;
        }

        return $this->localeMap[$language]['locale'] ?? $this->getDefaultLocale();
    }
}
