<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\Coupon;

use OpenLoyalty\Bundle\CampaignBundle\Service\CampaignProvider;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;

/**
 * Class CouponCodeProvider.
 */
class CouponCodeProvider
{
    /**
     * @var CampaignProvider
     */
    private $campaignProvider;

    /**
     * CouponCodeProvider constructor.
     *
     * @param CampaignProvider $campaignProvider
     */
    public function __construct(CampaignProvider $campaignProvider)
    {
        $this->campaignProvider = $campaignProvider;
    }

    /**
     * @param Campaign   $campaign
     * @param float|null $transactionValue
     *
     * @return Coupon
     */
    public function getCoupon(Campaign $campaign, float $transactionValue): ?Coupon
    {
        if ($campaign->getReward() === Campaign::REWARD_TYPE_PERCENTAGE_DISCOUNT_CODE) {
            $couponPercentage = $campaign->getTransactionPercentageValue();
            $couponValue = round($transactionValue * $couponPercentage / 100, 0);

            if ($couponValue === 0.0) {
                return null;
            }

            return new Coupon((string) $couponValue);
        }

        $freeCoupons = $this->campaignProvider->getFreeCoupons($campaign);
        if ($campaign->isSingleCoupon()) {
            $freeCoupons = $this->campaignProvider->getAllCoupons($campaign);
        }

        if (count($freeCoupons) === 0) {
            return null;
        }

        return new Coupon(reset($freeCoupons));
    }
}
