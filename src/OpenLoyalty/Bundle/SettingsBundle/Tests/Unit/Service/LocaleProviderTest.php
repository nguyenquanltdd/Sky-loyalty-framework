<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Tests\Unit\Service;

use OpenLoyalty\Bundle\SettingsBundle\Service\GeneralSettingsManager;
use OpenLoyalty\Bundle\SettingsBundle\Service\LocaleProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class RequestListenerTest.
 */
class LocaleProviderTest extends TestCase
{
    /**
     * @var array
     */
    private $localeMap = [
        'english.json' => ['locale' => 'en'],
        'polish.json' => ['locale' => 'pl'],
    ];

    /**
     * @test
     */
    public function it_returns_default_en_locale()
    {
        $settingsManager = $this->getMockBuilder(GeneralSettingsManager::class)
            ->disableOriginalConstructor()->getMock();

        $defaultLocale = 'en';
        $localeProvider = new LocaleProvider($settingsManager, $defaultLocale, []);

        $this->assertSame($defaultLocale, $localeProvider->getDefaultLocale());
    }

    /**
     * @test
     */
    public function it_returns_right_mapped_locale_en()
    {
        $settingsManager = $this->getMockBuilder(GeneralSettingsManager::class)
            ->disableOriginalConstructor()->getMock();
        $settingsManager->expects($this->once())->method('getLanguage')->willReturn('english.json');

        $localeProvider = new LocaleProvider($settingsManager, 'us', $this->localeMap);
        $this->assertSame('en', $localeProvider->getLocale());
    }

    /**
     * @test
     */
    public function it_returns_default_locale_if_not_mapped()
    {
        $settingsManager = $this->getMockBuilder(GeneralSettingsManager::class)
            ->disableOriginalConstructor()->getMock();
        $settingsManager->expects($this->once())->method('getLanguage')->willReturn('unexisting.json');

        $localeProvider = new LocaleProvider($settingsManager, 'us', $this->localeMap);
        $this->assertSame('us', $localeProvider->getLocale());
        $this->assertEquals('us', $localeProvider->getDefaultLocale());
    }
}
