<?php
/*
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\ReadModel;

use Broadway\EventDispatcher\EventDispatcher;
use Broadway\ReadModel\Repository;
use OpenLoyalty\Component\Core\Infrastructure\Projector\Projector;
use OpenLoyalty\Bundle\CampaignBundle\Model\Campaign;
use OpenLoyalty\Bundle\UserBundle\Service\AccountDetailsProviderInterface;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon;
use OpenLoyalty\Component\Campaign\Domain\TransactionId;
use OpenLoyalty\Component\Core\Domain\Model\Identifier;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignCouponWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignStatusWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignUsageWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasReturned;

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
     * @var CampaignBoughtRepository
     */
    protected $campaignBoughtRepository;

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
     * @param CampaignBoughtRepository        $campaignBoughtRepository
     * @param CampaignRepository              $campaignRepository
     * @param AccountDetailsProviderInterface $accountDetailsProvider
     */
    public function __construct(
        Repository $repository,
        CampaignBoughtRepository $campaignBoughtRepository,
        CampaignRepository $campaignRepository,
        AccountDetailsProviderInterface $accountDetailsProvider
    ) {
        $this->repository = $repository;
        $this->campaignBoughtRepository = $campaignBoughtRepository;
        $this->campaignRepository = $campaignRepository;
        $this->accountDetailsProvider = $accountDetailsProvider;
    }

    /**
     * @param CampaignWasBoughtByCustomer $event
     */
    protected function applyCampaignWasBoughtByCustomer(CampaignWasBoughtByCustomer $event)
    {
        $campaignId = new CampaignId((string) $event->getCampaignId());

        /** @var Campaign $campaign */
        $campaign = $this->campaignRepository->byId($campaignId);
        $customer = $this->accountDetailsProvider->getCustomerById($event->getCustomerId());
        $account = $this->accountDetailsProvider->getAccountByCustomer($customer);

        $this->storeCampaignUsages(
            $campaignId,
            new CustomerId((string) $event->getCustomerId()),
            $event->getCreatedAt(),
            new Coupon(
                $event->getCoupon()->getCode(),
                $event->getCoupon()->getId()
            ),
            $campaign->getReward(),
            $campaign->getName() ?? '',
            $customer->getEmail(),
            $customer->getPhone(),
            $customer->getFirstName(),
            $customer->getLastName(),
            $campaign->getCostInPoints(),
            (int) $account->getAvailableAmount(),
            $campaign->getTaxPriceValue(),
            $event->getStatus(),
            $event->getActiveSince(),
            $event->getActiveTo(),
            $event->getTransactionId()
        );
    }

    /**
     * @param CampaignId      $campaignId
     * @param CustomerId      $customerId
     * @param \DateTime       $boughtAt
     * @param Coupon          $coupon
     * @param string          $couponType
     * @param string          $campaignName
     * @param string|null     $customerEmail
     * @param string|null     $customerPhone
     * @param string          $customerName
     * @param string          $customerLastname
     * @param int             $costInPoints
     * @param int             $currentPointsAmount
     * @param float|null      $taxPriceValue
     * @param string          $status
     * @param \DateTime|null  $activeSince
     * @param \DateTime|null  $activeTo
     * @param null|Identifier $transactionId
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private function storeCampaignUsages(
        CampaignId $campaignId,
        CustomerId $customerId,
        \DateTime $boughtAt,
        Coupon $coupon,
        string $couponType,
        string $campaignName,
        ?string $customerEmail,
        ?string $customerPhone,
        string $customerName,
        string $customerLastname,
        int $costInPoints,
        int $currentPointsAmount,
        ?float $taxPriceValue,
        string $status,
        ?\DateTime $activeSince,
        ?\DateTime $activeTo,
        ?Identifier $transactionId
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
            $activeTo,
            $transactionId
        );

        $this->repository->save($readModel);
    }

    /**
     * @param CampaignUsageWasChanged $event
     */
    protected function applyCampaignUsageWasChanged(CampaignUsageWasChanged $event)
    {
        $campaigns = $this->campaignBoughtRepository->findByCustomerIdAndUsed((string) $event->getCustomerId(), !$event->isUsed());

        foreach ($campaigns as $campaign) {
            if ((string) $campaign->getCampaignId() === (string) $event->getCampaignId()
                && $campaign->getCoupon()->getCode() === $event->getCoupon()->getCode()
                && $campaign->getCoupon()->getId() === $event->getCoupon()->getId()) {
                $campaign->setUsed($event->isUsed());
                $campaign->setUsedForTransactionId($event->getTransactionId() ? new TransactionId((string) $event->getTransactionId()) : null);
                $this->repository->save($campaign);

                return;
            }
        }
    }

    /**
     * @param CampaignWasReturned $event
     */
    protected function applyCampaignWasReturned(CampaignWasReturned $event): void
    {
        $campaign = $this->campaignBoughtRepository->find($event->getPurchaseId());
        if (!$campaign instanceof CampaignBought) {
            return;
        }
        $campaign->setReturnedAmount($campaign->getReturnedAmount() + (float) $event->getCoupon()->getCode());
        $this->repository->save($campaign);
    }

    /**
     * @param CampaignStatusWasChanged $event
     */
    protected function applyCampaignStatusWasChanged(CampaignStatusWasChanged $event): void
    {
        $campaigns = $this->campaignBoughtRepository->findByCustomerId((string) $event->getCustomerId());
        $campaignId = (string) $event->getCampaignId();
        $transactionId = $event->getTransactionId() ? (string) $event->getTransactionId() : null;

        foreach ($campaigns as $campaign) {
            if ((string) $campaign->getCampaignId() === $campaignId
                && ($campaign->getTransactionId() ? (string) $campaign->getTransactionId() : null) === $transactionId
                && $campaign->getCoupon()->getCode() === $event->getCoupon()->getCode()
                && $campaign->getCoupon()->getId() === $event->getCoupon()->getId()) {
                $campaign->setStatus($event->getStatus());
                $this->repository->save($campaign);

                return;
            }
        }
    }

    /**
     * @param CampaignCouponWasChanged $event
     */
    protected function applyCampaignCouponWasChanged(CampaignCouponWasChanged $event): void
    {
        $campaigns = $this->campaignBoughtRepository->findByTransactionIdAndCustomerId(
            (string) $event->getTransactionId(),
            (string) $event->getCustomerId()
        );

        foreach ($campaigns as $readModel) {
            if ($readModel instanceof CampaignBought
                && $readModel->getPurchasedAt() == $event->getCreatedAt()
                && (string) $readModel->getCampaignId() === (string) $event->getCampaignId()) {
                $readModel->setCoupon(new Coupon($event->getNewCoupon()->getCode()));
                $this->repository->save($readModel);

                return;
            }
        }
    }
}
