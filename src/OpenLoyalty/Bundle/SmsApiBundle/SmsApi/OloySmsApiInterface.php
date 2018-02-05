<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SmsApiBundle\SmsApi;

use OpenLoyalty\Bundle\SmsApiBundle\Model\MessageInterface;

/**
 * Interface OloySmsApiInterface.
 */
interface OloySmsApiInterface
{
    /**
     * @param MessageInterface $message
     *
     * @return mixed
     */
    public function send(MessageInterface $message);
}
