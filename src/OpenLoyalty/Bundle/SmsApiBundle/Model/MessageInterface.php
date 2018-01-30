<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SmsApiBundle\Model;

/**
 * Interface MessageInterface.
 */
interface MessageInterface
{
    /**
     * @param string $recipientPhone
     */
    public function setRecipient(string $recipientPhone);

    /**
     * @return string
     */
    public function getRecipient(): string;

    /**
     * @return string
     */
    public function getSenderName(): string;

    /**
     * @param string $senderName
     */
    public function setSenderName(string $senderName);

    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @param string $content
     */
    public function setContent(string $content);
}
