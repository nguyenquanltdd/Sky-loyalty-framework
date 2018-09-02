<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\EarningRule\Domain\Returns;

use Broadway\CommandHandling\CommandBus;
use OpenLoyalty\Bundle\CampaignBundle\Exception\TooLowCouponValueException;
use OpenLoyalty\Bundle\CampaignBundle\Service\EarningRuleCampaignProviderInterface;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Customer\Domain\CampaignId;
use OpenLoyalty\Component\Customer\Domain\Command\CancelBoughtCampaign;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\TransactionId;
use OpenLoyalty\Component\EarningRule\Domain\Returns\Model\ReturnContext;
use OpenLoyalty\Component\Transaction\Domain\Provider\TransactionValueProviderInterface;
use OpenLoyalty\Component\Transaction\Domain\TransactionId as TransactionTransactionId;

/**
 * Class RefundInstantRewardAlgorithm.
 */
class RefundInstantRewardAlgorithm implements RefundAlgorithmInterface
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EarningRuleCampaignProviderInterface
     */
    private $earningRuleCampaignProvider;

    /**
     * @var TransactionValueProviderInterface
     */
    private $transactionValueProvider;

    /**
     * RefundInstantRewardAlgorithm constructor.
     *
     * @param CommandBus                           $commandBus
     * @param EarningRuleCampaignProviderInterface $earningRuleCampaignProvider
     * @param TransactionValueProviderInterface    $transactionValueProvider
     */
    public function __construct(
        CommandBus $commandBus,
        EarningRuleCampaignProviderInterface $earningRuleCampaignProvider,
        TransactionValueProviderInterface $transactionValueProvider
    ) {
        $this->commandBus = $commandBus;
        $this->earningRuleCampaignProvider = $earningRuleCampaignProvider;
        $this->transactionValueProvider = $transactionValueProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate(ReturnContext $refundContext): bool
    {
        foreach ($refundContext->getCampaigns() as $campaign) {
            if ($campaign->getType() === Campaign::REWARD_TYPE_PERCENTAGE_DISCOUNT_CODE) {
                $couponValue = $this->transactionValueProvider->getTransactionValue(new TransactionTransactionId($refundContext->getBaseTransactionId()->__toString()), true);

                try {
                    $newCouponCode = $this->earningRuleCampaignProvider->getNewCouponCodeForDiscountPercentageCode($campaign->getCampaignId()->__toString(), $couponValue);
                } catch (TooLowCouponValueException $exception) {
                    $newCouponCode = null;
                }
                if ($newCouponCode !== $campaign->getCoupon()->getCode()) {
                    if ($newCouponCode === null) {
                        $this->commandBus->dispatch(
                            new CancelBoughtCampaign(
                                new CustomerId($refundContext->getCustomerId()->__toString()),
                                new CampaignId($campaign->getCampaignId()->__toString()),
                                new Coupon($campaign->getCoupon()->getCode()),
                                new TransactionId($refundContext->getBaseTransactionId()->__toString())
                            )
                        );

                        continue;
                    }
                }
            }
        }

        return true;
    }
}
