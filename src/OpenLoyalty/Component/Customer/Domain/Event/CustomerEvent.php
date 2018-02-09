<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\Event;

use Broadway\Serializer\Serializable;
use OpenLoyalty\Component\Customer\Domain\CustomerId;

/**
 * Class CustomerEvent.
 */
abstract class CustomerEvent implements Serializable
{
    private $customerId;

    public function __construct(CustomerId $customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return CustomerId
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): array
    {
        return array('customerId' => (string) $this->customerId);
    }
}
