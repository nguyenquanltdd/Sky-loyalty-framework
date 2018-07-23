<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\Command;

use OpenLoyalty\Component\Customer\Domain\CampaignId;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;

/**
 * Class ChangeBoughtCampaignStatus.
 */
abstract class ChangeBoughtCampaignStatus
{
    /**
     * @var CustomerId
     */
    protected $customerId;

    /**
     * @var CampaignId
     */
    protected $campaignId;

    /**
     * @var Coupon
     */
    protected $coupon;

    /**
     * @var string
     */
    protected $status;

    /**
     * ChangeBoughtCampaignStatus constructor.
     *
     * @param CustomerId $customerId
     * @param CampaignId $campaignId
     * @param Coupon     $coupon
     */
    public function __construct(CustomerId $customerId, CampaignId $campaignId, Coupon $coupon)
    {
        $this->customerId = $customerId;
        $this->campaignId = $campaignId;
        $this->coupon = $coupon;
    }

    /**
     * @return CustomerId
     */
    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    /**
     * @return CampaignId
     */
    public function getCampaignId(): CampaignId
    {
        return $this->campaignId;
    }

    /**
     * @return Coupon
     */
    public function getCoupon(): Coupon
    {
        return $this->coupon;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}
