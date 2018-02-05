<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SmsApiBundle\Service;

use OpenLoyalty\Bundle\SmsApiBundle\Model\MessageInterface;

/**
 * Interface MessageFactoryInterface.
 */
interface MessageFactoryInterface
{
    /**
     * Create message object.
     *
     * @return MessageInterface
     */
    public function create();
}
