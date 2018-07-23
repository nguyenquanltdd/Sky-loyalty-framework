<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\ReadModel;

use Broadway\EventDispatcher\EventDispatcher;
use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Repository;
use OpenLoyalty\Bundle\CampaignBundle\Model\Campaign;
use OpenLoyalty\Bundle\UserBundle\Service\AccountDetailsProviderInterface;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignStatusWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignUsageWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;

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
     * @var AccountDetailsProviderInterface
     */
    private $accountDetailsProvider;

    /**
     * CampaignUsageProjector constructor.
     *
     * @param Repository                      $repository
     * @param CampaignRepository              $campaignRepository
     * @param AccountDetailsProviderInterface $accountDetailsProvider
     */
    public function __construct(
        Repository $repository,
        CampaignRepository $campaignRepository,
        AccountDetailsProviderInterface $accountDetailsProvider
    ) {
        $this->repository = $repository;
        $this->campaignRepository = $campaignRepository;
        $this->accountDetailsProvider = $accountDetailsProvider;
    }

    /**
     * @param CampaignWasBoughtByCustomer $event
     */
    protected function applyCampaignWasBoughtByCustomer(CampaignWasBoughtByCustomer $event)
    {
        $campainId = new CampaignId($event->getCampaignId()->__toString());

        /** @var Campaign $campaign */
        $campaign = $this->campaignRepository->byId($campainId);
        $customer = $this->accountDetailsProvider->getCustomerById($event->getCustomerId());
        $account = $this->accountDetailsProvider->getAccountByCustomer($customer);

        $this->storeCampaignUsages(
            $campainId,
            new CustomerId($event->getCustomerId()->__toString()),
            $event->getCreatedAt(),
            new Coupon($event->getCoupon()->getCode()),
            $campaign->getReward(),
            $campaign->getName(),
            $customer->getEmail(),
            $customer->getPhone(),
            $customer->getFirstName(),
            $customer->getLastName(),
            $campaign->getCostInPoints(),
            (int) $account->getAvailableAmount(),
            $campaign->getTaxPriceValue(),
            $event->getStatus(),
            $event->getActiveSince(),
            $event->getActiveTo()
        );
    }

    /**
     * @param CampaignId     $campaignId
     * @param CustomerId     $customerId
     * @param \DateTime      $boughtAt
     * @param Coupon         $coupon
     * @param string         $couponType
     * @param string         $campaignName
     * @param string         $customerEmail
     * @param string         $customerPhone
     * @param string         $customerName
     * @param string         $customerLastname
     * @param int            $costInPoints
     * @param int            $currentPointsAmount
     * @param float|null     $taxPriceValue
     * @param string         $status
     * @param \DateTime|null $activeSince
     * @param \DateTime|null $activeTo
     */
    private function storeCampaignUsages(
        CampaignId $campaignId,
        CustomerId $customerId,
        \DateTime $boughtAt,
        Coupon $coupon,
        string $couponType,
        string $campaignName,
        ? string $customerEmail,
        ? string $customerPhone,
        string $customerName,
        string $customerLastname,
        int $costInPoints,
        int $currentPointsAmount,
        ? float $taxPriceValue,
        string $status,
        ?\DateTime $activeSince,
        ?\DateTime $activeTo
    ) {
        $readModel = new CampaignBought(
            $campaignId,
            $customerId,
            $boughtAt,
            $coupon,
            $couponType,
            $campaignName,
            $customerEmail,
            $customerPhone,
            $status,
            null,
            $customerName,
            $customerLastname,
            $costInPoints,
            $currentPointsAmount,
            $taxPriceValue,
            $activeSince,
            $activeTo
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

    /**
     * @param CampaignStatusWasChanged $event
     */
    protected function applyCampaignStatusWasChanged(CampaignStatusWasChanged $event): void
    {
        /* @var CampaignBought $readModel */
        $campaignBoughtId = CampaignBought::createId(
            new CampaignId($event->getCampaignId()->__toString()),
            new CustomerId($event->getCustomerId()->__toString()),
            new Coupon($event->getCoupon()->getCode())
        );
        $readModel = $this->repository->find($campaignBoughtId);

        if ($readModel instanceof CampaignBought) {
            $readModel->setStatus($event->getStatus());
            $this->repository->save($readModel);
        }
    }
}
