<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Tests\Unit\EventSubscriber;

use OpenLoyalty\Bundle\SettingsBundle\EventSubscriber\RequestListener;
use OpenLoyalty\Bundle\SettingsBundle\Service\LocaleProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class RequestListenerTest.
 */
class RequestListenerTest extends TestCase
{
    /**
     * @var RequestListener
     */
    private $requestListener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $localeProvider = $this->getMockForAbstractClass(LocaleProviderInterface::class);
        $localeProvider->expects($this->any())->method('getLocale')->willReturn('pl');

        $this->requestListener = new RequestListener($localeProvider);
    }

    /**
     * @test
     */
    public function it_sets_different_default_locale_if_not_exist()
    {
        $localeProvider = $this->getMockForAbstractClass(LocaleProviderInterface::class);
        $localeProvider->expects($this->any())->method('getDefaultLocale')->willReturn('cz');

        $listener = new RequestListener($localeProvider);
        $httpKernelInterface = $this->getMockForAbstractClass(HttpKernelInterface::class);
        $request = new Request();
        $event = new GetResponseEvent($httpKernelInterface, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener->onKernelRequest($event);
        $this->assertNotEquals('pl', $event->getRequest()->getLocale());
        $this->assertEquals('cz', $event->getRequest()->getDefaultLocale());
    }

    /**
     * @test
     */
    public function it_sets_right_locale()
    {
        $httpKernelInterface = $this->getMockForAbstractClass(HttpKernelInterface::class);
        $request = new Request();
        $event = new GetResponseEvent($httpKernelInterface, $request, HttpKernelInterface::MASTER_REQUEST);

        $this->requestListener->onKernelRequest($event);
        $this->assertEquals('pl', $event->getRequest()->getLocale());
    }

    /**
     * @test
     */
    public function it_does_not_change_locale_if_not_master_request()
    {
        $httpKernelInterface = $this->getMockForAbstractClass(HttpKernelInterface::class);
        $request = new Request();
        $event = new GetResponseEvent($httpKernelInterface, $request, HttpKernelInterface::SUB_REQUEST);

        $this->requestListener->onKernelRequest($event);
        $this->assertNotEquals('pl', $event->getRequest()->getLocale());
    }
}
