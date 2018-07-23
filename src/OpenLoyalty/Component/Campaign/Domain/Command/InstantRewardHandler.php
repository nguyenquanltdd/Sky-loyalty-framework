<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\Command;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\SimpleCommandHandler;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\Coupon\CouponCodeProvider;
use OpenLoyalty\Component\Campaign\Domain\Provider\CouponActivationDateProvider;
use OpenLoyalty\Component\Campaign\Domain\Provider\CouponExpirationDateProvider;
use OpenLoyalty\Component\Customer\Domain\CampaignId as CustomerCampaignId;
use OpenLoyalty\Component\Customer\Domain\Command\BuyCampaign;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\EarningRule\Domain\Command\ActivateInstantRewardRule;

/**
 * Class InstantRewardHandler.
 */
class InstantRewardHandler extends SimpleCommandHandler
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
     * @var CouponCodeProvider
     */
    private $couponCodeProvider;

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
     * @param CouponCodeProvider           $couponCodeProvider
     * @param CouponActivationDateProvider $activationDateProvider
     * @param CouponExpirationDateProvider $expirationDateProvider
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        CommandBus $commandBus,
        CouponCodeProvider $couponCodeProvider,
        CouponActivationDateProvider $activationDateProvider,
        CouponExpirationDateProvider $expirationDateProvider
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->commandBus = $commandBus;
        $this->couponCodeProvider = $couponCodeProvider;
        $this->activationDateProvider = $activationDateProvider;
        $this->expirationDateProvider = $expirationDateProvider;
    }

    /**
     * @param ActivateInstantRewardRule $command
     */
    public function handleActivateInstantRewardRule(ActivateInstantRewardRule $command)
    {
        $campaign = $this->campaignRepository->byId(new CampaignId($command->getCampaignId()));
        $coupon = $this->couponCodeProvider->getCoupon($campaign, $command->getTransactionValue());
        if (!$coupon) {
            return;
        }

        $status = CampaignPurchase::STATUS_ACTIVE;
        $activeSince = null;
        $activeTo = null;

        if ($campaign->getReward() === Campaign::REWARD_TYPE_PERCENTAGE_DISCOUNT_CODE) {
            $status = CampaignPurchase::STATUS_INACTIVE;
            $activeSince = $this->activationDateProvider->getActivationDate($campaign, new \DateTime());
            $activeTo = $this->expirationDateProvider->getExpirationDate($campaign, new \DateTime());
        }

        $this->commandBus->dispatch(
            new BuyCampaign(
                new CustomerId($command->getCustomerId()),
                new CustomerCampaignId($campaign->getCampaignId()->__toString()),
                $campaign->getName(),
                $campaign->getCostInPoints(),
                $coupon,
                $campaign->getReward(),
                $status,
                $activeSince,
                $activeTo
            )
        );
    }
}
