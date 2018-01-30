<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SmsApiBundle\SmsApi;

use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Bundle\SmsApiBundle\Model\MessageInterface;
use Psr\Log\LoggerInterface;
use SMSApi\Client;
use SMSApi\Api\SmsFactory;
use SMSApi\Exception\SmsapiException;

/**
 * Class OloySmsApi.
 */
class OloySmsApi implements OloySmsApiInterface
{
    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OloySmsApi constructor.
     *
     * @param SettingsManager $settingsManager
     */
    public function __construct(SettingsManager $settingsManager, LoggerInterface $logger)
    {
        $this->settingsManager = $settingsManager;
        $this->logger = $logger;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        $token = $this->settingsManager->getSettingByKey('smsApiToken');
        if (!$token || !$token->getValue()) {
            throw new \InvalidArgumentException(
                'Setting "smsApiToken" is not set'
            );
        }

        return Client::createFromToken($token->getValue());
    }

    /**
     * @param MessageInterface $message
     *
     * @return bool
     *
     * @throws SmsapiException
     */
    public function send(MessageInterface $message)
    {
        $smsapi = new SmsFactory();
        $smsapi->setClient($this->getClient());

        try {
            $actionSend = $smsapi->actionSend();

            $actionSend->setTo($message->getRecipient());
            $actionSend->setText($message->getContent());
            $actionSend->setSender($message->getSenderName());

            $response = $actionSend->execute();

            if ($response->getLength() < 1) {
                return false;
            }

            foreach ($response->getList() as $status) {
                return in_array($status->getStatus(), [
                    'DELIVERED',
                    'SENT',
                    'PENDING',
                    'QUEUE',
                    'ACCEPTED',
                    'RENEWAL',
                ]);
            }
        } catch (SmsapiException $e) {
            $this->logger->error('Send sms failed: '.$e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
