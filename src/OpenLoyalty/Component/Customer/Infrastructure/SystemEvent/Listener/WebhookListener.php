<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Infrastructure\SystemEvent\Listener;

use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerLevelChangedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerRegisteredSystemEvent;
use OpenLoyalty\Component\Webhook\Infrastructure\SystemEvent\Listener\BaseWebhookListener;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerUpdatedSystemEvent;

/**
 * Class WebhookListener.
 */
class WebhookListener extends BaseWebhookListener
{
    /**
     * Customer updated webhook type (CustomerSystemEvents::CUSTOMER_UPDATED).
     */
    const CUSTOMER_UPDATED_WEBHOOK_TYPE = 'customer.updated';

    /**
     * Customer registered webhook type (CustomerSystemEvents::CUSTOMER_REGISTERED).
     */
    const CUSTOMER_REGISTERED_WEBHOOK_TYPE = 'customer.registered';

    /**
     * Customer level changed automatically webhook type (CustomerSystemEvents::CUSTOMER_LEVEL_CHANGED_AUTOMATICALLY).
     */
    const CUSTOMER_LEVEL_CHANGED_AUTOMATICALLY_WEBHOOK_TYPE = 'customer.level_changed_automatically';

    /**
     * Customer level changed webhook type (CustomerSystemEvents::CUSTOMER_LEVEL_CHANGED).
     */
    const CUSTOMER_LEVEL_CHANGED_WEBHOOK_TYPE = 'customer.level_changed';

    /**
     * @param CustomerUpdatedSystemEvent $event
     */
    public function onCustomerUpdated(CustomerUpdatedSystemEvent $event): void
    {
        $this->uniqueDispatchCommand(
            self::CUSTOMER_UPDATED_WEBHOOK_TYPE,
            ['customerId' => $event->getCustomerId()->__toString()]
        );
    }

    /**
     * @param CustomerRegisteredSystemEvent $event
     */
    public function onCustomerRegistered(CustomerRegisteredSystemEvent $event): void
    {
        $this->uniqueDispatchCommand(
            self::CUSTOMER_REGISTERED_WEBHOOK_TYPE,
            [
                'customerId' => $event->getCustomerId()->__toString(),
                'data' => $event->getCustomerData(),
            ]
        );
    }

    /**
     * @param CustomerLevelChangedSystemEvent $event
     */
    public function onCustomerLevelChangedAutomatically(CustomerLevelChangedSystemEvent $event): void
    {
        $this->uniqueDispatchCommand(
            self::CUSTOMER_LEVEL_CHANGED_AUTOMATICALLY_WEBHOOK_TYPE,
            [
                'customerId' => $event->getCustomerId()->__toString(),
                'levelId' => $event->getLevelId()->__toString(),
            ]
        );
    }

    /**
     * @param CustomerLevelChangedSystemEvent $event
     */
    public function onCustomerLevelChanged(CustomerLevelChangedSystemEvent $event): void
    {
        $this->uniqueDispatchCommand(
            self::CUSTOMER_LEVEL_CHANGED_WEBHOOK_TYPE,
            [
                'customerId' => $event->getCustomerId()->__toString(),
                'levelId' => $event->getLevelId()->__toString(),
            ]
        );
    }
}
