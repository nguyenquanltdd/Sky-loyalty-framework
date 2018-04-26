<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\ReadModel;

use Broadway\EventDispatcher\EventDispatcher;
use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Repository;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignUsageWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;

/**
 * Class CampaignUsageProjector.
 */
class CampaignBoughtProjector extends Projector
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var Repository
     */
    private $customerRepository;

    /**
     * CampaignUsageProjector constructor.
     *
     * @param Repository         $repository
     * @param CampaignRepository $campaignRepository
     * @param Repository         $customerRepository
     */
    public function __construct(
        Repository $repository,
        CampaignRepository $campaignRepository,
        Repository $customerRepository
    ) {
        $this->repository = $repository;
        $this->campaignRepository = $campaignRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param CampaignWasBoughtByCustomer $event
     */
    protected function applyCampaignWasBoughtByCustomer(CampaignWasBoughtByCustomer $event)
    {
        $campainId = new CampaignId($event->getCampaignId()->__toString());
        $campain = $this->campaignRepository->byId($campainId);
        /* @var CustomerDetails $customer */
        $customer = $this->customerRepository->find($event->getCustomerId()->__toString());

        $this->storeCampaignUsages(
            $campainId,
            new CustomerId($event->getCustomerId()->__toString()),
            $event->getCreatedAt(),
            new Coupon($event->getCoupon()->getCode()),
            $campain->getReward(),
            $event->getCampaignName(),
            $customer->getEmail(),
            $customer->getPhone()
        );
    }

    private function storeCampaignUsages(
        CampaignId $campaignId,
        CustomerId $customerId,
        \DateTime $boughtAt,
        Coupon $coupon,
        string $couponType,
        string $campaignName,
        $customerEmail,
        $customerPhone
    ) {
        $readModel = new CampaignBought(
            $campaignId,
            $customerId,
            $boughtAt,
            $coupon,
            $couponType,
            $campaignName,
            $customerEmail,
            $customerPhone
        );
        $this->repository->save($readModel);
    }

    /**
     * @param CampaignUsageWasChanged $event
     */
    protected function applyCampaignUsageWasChanged(CampaignUsageWasChanged $event)
    {
        /* @var CampaignBought $readModel */
        $campaignBoughtId = CampaignBought::createId(
            new CampaignId($event->getCampaignId()->__toString()),
            new CustomerId($event->getCustomerId()->__toString()),
            new Coupon($event->getCoupon()->getCode())
        );
        $readModel = $this->repository->find($campaignBoughtId);

        if ($readModel instanceof CampaignBought) {
            $readModel->setUsed($event->isUsed());
            $this->repository->save($readModel);
        }
    }
}
