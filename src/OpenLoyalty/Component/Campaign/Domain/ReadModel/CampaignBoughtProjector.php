<?php
/**
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
use OpenLoyalty\Component\Core\Domain\Model\Identifier;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignCouponWasChanged;
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
        $campaignId = new CampaignId($event->getCampaignId()->__toString());

        /** @var Campaign $campaign */
        $campaign = $this->campaignRepository->byId($campaignId);
        $customer = $this->accountDetailsProvider->getCustomerById($event->getCustomerId());
        $account = $this->accountDetailsProvider->getAccountByCustomer($customer);

        $this->storeCampaignUsages(
            $campaignId,
            new CustomerId($event->getCustomerId()->__toString()),
            $event->getCreatedAt(),
            new Coupon($event->getCoupon()->getCode()),
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
     * @param string          $customerEmail
     * @param string          $customerPhone
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
        $campaigns = $this->campaignBoughtRepository->findByCustomerIdAndUsed($event->getCustomerId()->__toString(), !$event->isUsed());

        foreach ($campaigns as $campaign) {
            if ($campaign->getCampaignId()->__toString() === $event->getCampaignId()->__toString()
                && $campaign->getCoupon()->getCode() === $event->getCoupon()->getCode()) {
                $campaign->setUsed($event->isUsed());
                $this->repository->save($campaign);

                return;
            }
        }
    }

    /**
     * @param CampaignStatusWasChanged $event
     */
    protected function applyCampaignStatusWasChanged(CampaignStatusWasChanged $event): void
    {
        $campaigns = $this->campaignBoughtRepository->findByCustomerId($event->getCustomerId()->__toString());
        $campaignId = $event->getCampaignId()->__toString();
        $coupon = $event->getCoupon()->getCode();
        $transactionId = $event->getTransactionId() ? $event->getTransactionId()->__toString() : null;

        foreach ($campaigns as $campaign) {
            if ($campaign->getCampaignId()->__toString() === $campaignId
                && ($campaign->getTransactionId() ? $campaign->getTransactionId()->__toString() : null) === $transactionId
                && $campaign->getCoupon()->getCode() === $coupon) {
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
            $event->getTransactionId()->__toString(),
            $event->getCustomerId()->__toString()
        );

        foreach ($campaigns as $readModel) {
            if ($readModel instanceof CampaignBought
                && $readModel->getPurchasedAt() == $event->getCreatedAt()
                && $readModel->getCampaignId()->__toString() === $event->getCampaignId()->__toString()) {
                $readModel->setCoupon(new Coupon($event->getNewCoupon()->getCode()));
                $this->repository->save($readModel);

                return;
            }
        }
    }
}
