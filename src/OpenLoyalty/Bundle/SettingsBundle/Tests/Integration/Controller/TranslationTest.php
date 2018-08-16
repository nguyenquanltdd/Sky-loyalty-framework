<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Tests\Integration\Controller;

use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseApiTest;
use OpenLoyalty\Bundle\SettingsBundle\Service\LocaleProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TranslationTest.
 */
class TranslationTest extends BaseApiTest
{
    /**
     * @test
     * @dataProvider localeProvider
     *
     * @param string $locale
     * @param string $expectedString
     */
    public function it_returns_correct_translations_by_locale(string $locale, string $expectedString)
    {
        $localeProvider = $this->getMockBuilder(LocaleProvider::class)->disableOriginalConstructor()->getMock();
        $localeProvider->expects($this->any())->method('getLocale')->willReturn($locale);

        self::bootKernel();
        $client = $this->createAuthenticatedClient();
        self::$kernel->getContainer()->set(LocaleProvider::class, $localeProvider);

        $client->request(
            'POST',
            '/api/level/create',
            [
                'level' => [
                    'name' => null,
                    'description' => null,
                    'conditionValue' => null,
                ],
            ]
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($expectedString, $content['form']['children']['name']['errors'][0]);
    }

    /**
     * @dataProvider
     */
    public function localeProvider(): array
    {
        return [
            ['en', 'This value should not be blank.', 'This value should not be blank.'],
            ['pl', 'This value should not be blank.', 'Ta wartość nie powinna być pusta.'],
        ];
    }
}
