<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\Command;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\SimpleCommandHandler;
use OpenLoyalty\Bundle\CampaignBundle\Exception\CampaignLimitException;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\Coupon\CouponCodeProvider;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\TransactionId;
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
     * InstantRewardHandler constructor.
     *
     * @param CampaignRepository $campaignRepository
     * @param CommandBus         $commandBus
     * @param CouponCodeProvider $couponCodeProvider
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        CommandBus $commandBus,
        CouponCodeProvider $couponCodeProvider
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->commandBus = $commandBus;
        $this->couponCodeProvider = $couponCodeProvider;
    }

    /**
     * @param ActivateInstantRewardRule $command
     */
    public function handleActivateInstantRewardRule(ActivateInstantRewardRule $command)
    {
        $campaign = $this->campaignRepository->byId(new CampaignId($command->getCampaignId()));
        try {
            $coupon = $this->couponCodeProvider->getCoupon($campaign, $command->getTransactionValue());
        } catch (CampaignLimitException $e) {
            return;
        }

        $this->commandBus->dispatch(
            new BuyCampaign(
                $campaign->getCampaignId(),
                new CustomerId($command->getCustomerId()),
                $coupon,
                $campaign->getCostInPoints(),
                new TransactionId($command->getTransactionId())
            )
        );
    }
}
