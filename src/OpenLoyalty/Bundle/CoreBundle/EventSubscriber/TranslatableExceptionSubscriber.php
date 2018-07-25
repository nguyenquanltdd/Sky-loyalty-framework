<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CoreBundle\EventSubscriber;

use OpenLoyalty\Bundle\CoreBundle\Exception\TranslatedException;
use OpenLoyalty\Component\Core\Domain\Exception\Translatable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TranslatableExceptionSubscriber.
 */
class TranslatableExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * TranslatableExceptionSubscriber constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [
                ['translateException', 200],
            ],
        ];
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function translateException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if (!$exception instanceof Translatable) {
            return;
        }

        $message = $this->translator->trans($exception->getMessageKey(), $exception->getMessageParams(), 'exception');
        $translated = new TranslatedException($message, $exception);
        $event->setException($translated);
    }
}
