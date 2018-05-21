<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Webhook\Domain\Command;

use Broadway\CommandHandling\SimpleCommandHandler;
use OpenLoyalty\Component\Webhook\Infrastructure\Client\WebhookClient;
use OpenLoyalty\Component\Webhook\Infrastructure\WebhookConfigProvider;

/**
 * Class WebhookCommandHandler.
 */
class WebhookCommandHandler extends SimpleCommandHandler
{
    /** @var WebhookClient */
    protected $client;

    /** @var WebhookConfigProvider */
    protected $configProvider;

    /**
     * WebhookCommandHandler constructor.
     *
     * @param WebhookClient         $client
     * @param WebhookConfigProvider $configProvider
     */
    public function __construct(WebhookClient $client, WebhookConfigProvider $configProvider)
    {
        $this->client = $client;
        $this->configProvider = $configProvider;
    }

    /**
     * Handle dispatch webhook.
     *
     * @param DispatchWebhook $command
     */
    public function handleDispatchWebhook(DispatchWebhook $command)
    {
        if ($this->configProvider->isEnabled()) {
            $this->client->postAction(
                $this->configProvider->getUri(),
                [
                    'type' => $command->getType(),
                    'data' => $command->getData(),
                ]
            );
        }
    }
}
