<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\Model;

use Broadway\Serializer\Serializable;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Customer\Domain\CampaignId;

/**
 * Class CampaignPurchase.
 */
class CampaignPurchase implements Serializable
{
    /**
     * @var \DateTime
     */
    protected $purchaseAt;

    /**
     * @var float
     */
    protected $costInPoints;

    /**
     * @var CampaignId
     */
    protected $campaignId;

    /**
     * @var string
     */
    protected $reward;

    /**
     * @var string
     */
    protected $campaign;

    /**
     * @var bool
     */
    protected $used = false;

    /**
     * @var Coupon
     */
    protected $coupon;

    /**
     * CampaignPurchase constructor.
     *
     * @param \DateTime  $purchaseAt
     * @param int        $costInPoints
     * @param CampaignId $campaignId
     * @param Coupon     $coupon
     * @param $reward
     */
    public function __construct(\DateTime $purchaseAt, $costInPoints, CampaignId $campaignId, Coupon $coupon, $reward)
    {
        $this->purchaseAt = $purchaseAt;
        $this->costInPoints = $costInPoints;
        $this->campaignId = $campaignId;
        $this->coupon = $coupon;
        $this->reward = $reward;
    }

    /**
     * @return \DateTime
     */
    public function getPurchaseAt()
    {
        return $this->purchaseAt;
    }

    /**
     * @return float
     */
    public function getCostInPoints()
    {
        return $this->costInPoints;
    }

    /**
     * @return CampaignId
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param array $data
     *
     * @return CampaignPurchase
     */
    public static function deserialize(array $data)
    {
        $date = new \DateTime();
        $date->setTimestamp($data['purchaseAt']);

        $purchase = new self($date, $data['costInPoints'], new CampaignId($data['campaignId']), new Coupon($data['coupon']), $data['reward']);
        $purchase->setUsed($data['used']);

        return $purchase;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'costInPoints' => $this->costInPoints,
            'purchaseAt' => $this->purchaseAt->getTimestamp(),
            'campaignId' => $this->campaignId->__toString(),
            'coupon' => $this->coupon->getCode(),
            'used' => $this->used,
            'reward' => $this->reward,
            'isNotCashback' => $this->reward == Campaign::REWARD_TYPE_CASHBACK ? 0 : 1,
        ];
    }

    /**
     * @return bool
     */
    public function isUsed()
    {
        return $this->used;
    }

    /**
     * @param bool $used
     */
    public function setUsed($used)
    {
        $this->used = $used;
    }

    /**
     * @return string
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param string $campaign
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return Coupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @return string
     */
    public function getReward()
    {
        return $this->reward;
    }

    /**
     * @param string $reward
     */
    public function setReward($reward)
    {
        $this->reward = $reward;
    }
}
