<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\Command;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\SimpleCommandHandler;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\Provider\CouponActivationDateProvider;
use OpenLoyalty\Component\Campaign\Domain\Provider\CouponExpirationDateProvider;
use OpenLoyalty\Component\Customer\Domain\Command\BuyCustomerCampaign;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\CampaignId as CustomerCampaignId;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;

/**
 * Class BuyCampaignHandler.
 */
class BuyCampaignHandler extends SimpleCommandHandler
{
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var CouponActivationDateProvider
     */
    private $activationDateProvider;

    /**
     * @var CouponExpirationDateProvider
     */
    private $expirationDateProvider;

    /**
     * InstantRewardHandler constructor.
     *
     * @param CampaignRepository           $campaignRepository
     * @param CommandBus                   $commandBus
     * @param CouponActivationDateProvider $activationDateProvider
     * @param CouponExpirationDateProvider $expirationDateProvider
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        CommandBus $commandBus,
        CouponActivationDateProvider $activationDateProvider,
        CouponExpirationDateProvider $expirationDateProvider
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->commandBus = $commandBus;
        $this->activationDateProvider = $activationDateProvider;
        $this->expirationDateProvider = $expirationDateProvider;
    }

    public function handleBuyCampaign(BuyCampaign $command): void
    {
        $campaign = $this->campaignRepository->byId($command->getCampaignId());

        $status = CampaignPurchase::STATUS_ACTIVE;
        $activeSince = null;
        $activeTo = null;

        if ($campaign->getReward() === Campaign::REWARD_TYPE_PERCENTAGE_DISCOUNT_CODE) {
            $status = CampaignPurchase::STATUS_INACTIVE;
            $activeSince = $this->activationDateProvider->getActivationDate($campaign, new \DateTime());
            $activeTo = $this->expirationDateProvider->getExpirationDate($campaign, new \DateTime());
        }

        $this->commandBus->dispatch(
            new BuyCustomerCampaign(
                new CustomerId($command->getCustomerId()->__toString()),
                new CustomerCampaignId($campaign->getCampaignId()->__toString()),
                $campaign->getName(),
                $command->getPointsValue() ?? $campaign->getCostInPoints(),
                new Coupon($command->getCoupon()->getCode()),
                $campaign->getReward(),
                $status,
                $activeSince,
                $activeTo
            )
        );
    }
}
