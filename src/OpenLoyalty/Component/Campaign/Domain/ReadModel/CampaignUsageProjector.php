<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\ReadModel;

use Broadway\Domain\DomainMessage;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\EventHandling\EventListener;
use Broadway\ReadModel\Repository;
use Broadway\ReadModel\SerializableReadModel;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use Psr\Log\LoggerInterface;

/**
 * Class CampaignUsageProjector.
 */
class CampaignUsageProjector implements EventListener
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * CampaignUsageProjector constructor.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();
        if ($event instanceof CampaignWasBoughtByCustomer) {
            $this->storeCampaignUsages(new CampaignId($event->getCampaignId()->__toString()));
        }
    }

    /**
     * @param CampaignId $campaignId
     */
    public function storeCampaignUsages(CampaignId $campaignId)
    {
        $readModel = $this->getReadModel($campaignId);
        if ($readModel->getCampaignUsage() !== null) {
            $readModel->setCampaignUsage($readModel->getCampaignUsage() + 1);
        } else {
            $readModel->setCampaignUsage(1);
        }
        $this->repository->save($readModel);
    }

    /**
     * @param CampaignId $campaignId
     *
     * @return SerializableReadModel|null|CampaignUsage
     */
    private function getReadModel(CampaignId $campaignId)
    {
        $readModel = $this->repository->find($campaignId);
        if (null === $readModel) {
            $readModel = new CampaignUsage($campaignId);
        }

        return $readModel;
    }
}
