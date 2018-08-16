<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\EventSubscriber;

use OpenLoyalty\Bundle\SettingsBundle\Service\LocaleProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RequestListener.
 */
class RequestListener implements EventSubscriberInterface
{
    /**
     * @var LocaleProviderInterface
     */
    private $localeProvider;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 20],
            ],
        ];
    }

    /**
     * RequestListener constructor.
     *
     * @param LocaleProviderInterface $localeProvider
     */
    public function __construct(LocaleProviderInterface $localeProvider)
    {
        $this->localeProvider = $localeProvider;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->setLanguageContext($event);
    }

    /**
     * Sets locale mapped from translation input file.
     *
     * @param GetResponseEvent $event
     */
    private function setLanguageContext(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $request->setLocale($this->localeProvider->getLocale());
        $request->setDefaultLocale($this->localeProvider->getDefaultLocale());
    }
}
