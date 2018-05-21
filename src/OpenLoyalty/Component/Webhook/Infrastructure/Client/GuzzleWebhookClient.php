<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Webhook\Infrastructure\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Class GuzzleWebhookClient.
 */
class GuzzleWebhookClient implements WebhookClient
{
    /**
     * Timeout for guzzle request.
     */
    const TIMEOUT_SEC = 0.5;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * GuzzleWebhookClient constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function postAction(string $uri, array $data, array $config = []): void
    {
        $clientConfig = [
            'headers' => [
                'Content-type' => 'application/json',
                'User-Agent' => 'OpenLoyalty',
            ],
        ];

        $client = new Client($clientConfig);

        try {
            // Perform simulating async request with low timeout.
            // Response does not matter for us.
            $client->post(
                $uri,
                [
                    'json' => $data,
                    'timeout' => self::TIMEOUT_SEC,
                ]
            );
        } catch (ConnectException $ex) {
            $this->logger->debug(sprintf('[Webhooks] Request timeout: %s', $ex->getMessage()));
        } catch (RequestException $ex) {
            $this->logger->warning(sprintf('[Webhooks] Request problem: %s', $ex->getMessage()));
        } catch (\Exception $ex) {
            $this->logger->error(sprintf('[Webhooks] Error request: %s', $ex->getMessage()));
        }
    }
}
