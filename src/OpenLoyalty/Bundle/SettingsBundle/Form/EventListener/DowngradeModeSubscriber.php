<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Form\EventListener;

use OpenLoyalty\Bundle\SettingsBundle\Model\Settings;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Class DowngradeModeSubscriber.
 */
class DowngradeModeSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::SUBMIT => 'submit'];
    }

    /**
     * @param FormEvent $event
     */
    public function submit(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data instanceof Settings) {
            return;
        }
        if (!$data->getEntry('tierAssignType') || $data->getEntry('tierAssignType')->getValue() !== TierAssignTypeProvider::TYPE_POINTS) {
            return;
        }
        $mode = $data->getEntry('levelDowngradeMode');
        if (!$mode) {
            return;
        }
        $mode = $mode->getValue();
        if ($mode === LevelDowngradeModeProvider::MODE_AUTO || $mode === LevelDowngradeModeProvider::MODE_NONE) {
            return;
        }
        $downgradeDays = $data->getEntry('levelDowngradeDays');
        if (!$downgradeDays || null === $downgradeDays->getValue()) {
            $event->getForm()->get('levelDowngradeDays')->addError($this->getTranslatedError((new NotBlank())->message));
        }

        if ($downgradeDays && $downgradeDays->getValue() && $downgradeDays->getValue() < 1) {
            $minMessage = (new Range(['min' => 1]))->minMessage;
            $event->getForm()->get('levelDowngradeDays')->addError($this->getTranslatedError($minMessage, [
                '{{ limit }}' => 1,
            ]));
        }

        $downgradeBase = $data->getEntry('levelDowngradeBase');
        if (!$downgradeBase || !$downgradeBase->getValue()) {
            $event->getForm()->get('levelDowngradeBase')->addError($this->getTranslatedError((new NotBlank())->message));
        }
    }

    /**
     * @param string $message
     * @param array  $params
     *
     * @return FormError
     */
    private function getTranslatedError(string $message, array $params = []): FormError
    {
        return new FormError(
            $message,
            $message,
            $params
        );
    }
}
