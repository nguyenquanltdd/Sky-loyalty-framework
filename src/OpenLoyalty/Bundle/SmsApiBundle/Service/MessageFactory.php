<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SmsApiBundle\Service;

use OpenLoyalty\Bundle\SmsApiBundle\Model\Message;

/**
 * Class MessageFactory.
 */
class MessageFactory implements MessageFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create()
    {
        return new Message();
    }
}
