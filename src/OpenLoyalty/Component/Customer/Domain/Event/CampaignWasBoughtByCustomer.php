<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\Event;

use OpenLoyalty\Component\Customer\Domain\CampaignId;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;

/**
 * Class CampaignWasBoughtByCustomer.
 */
class CampaignWasBoughtByCustomer extends CustomerEvent
{
    /**
     * @var CampaignId
     */
    protected $campaignId;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var float
     */
    protected $costInPoints;

    /**
     * @var Coupon
     */
    protected $coupon;

    /**
     * @var string
     */
    protected $campaignName;

    /**
     * @var string
     */
    protected $reward;

    /**
     * CampaignWasBoughtByCustomer constructor.
     *
     * @param CustomerId $customerId
     * @param CampaignId $campaignId
     * @param $campaignName
     * @param $costInPoints
     * @param Coupon $coupon
     * @param $reward
     */
    public function __construct(CustomerId $customerId, CampaignId $campaignId, $campaignName, $costInPoints, Coupon $coupon, $reward)
    {
        parent::__construct($customerId);
        $this->campaignId = $campaignId;
        $this->createdAt = new \DateTime();
        $this->createdAt->setTimestamp(time());
        $this->costInPoints = $costInPoints;
        $this->coupon = $coupon;
        $this->campaignName = $campaignName;
        $this->reward = $reward;
    }

    /**
     * @return CampaignId
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return array_merge(
            parent::serialize(),
            [
                'campaignId' => $this->campaignId->__toString(),
                'costInPoints' => $this->costInPoints,
                'createdAt' => $this->createdAt->getTimestamp(),
                'coupon' => $this->coupon->getCode(),
                'campaignName' => $this->campaignName,
                'reward' => $this->reward,
            ]
        );
    }

    /**
     * @param array $data
     *
     * @return CampaignWasBoughtByCustomer
     */
    public static function deserialize(array $data)
    {
        $bought = new self(new CustomerId($data['customerId']), new CampaignId($data['campaignId']), $data['campaignName'], $data['costInPoints'], new Coupon($data['coupon']), $data['reward']);
        $date = new \DateTime();
        $date->setTimestamp($data['createdAt']);
        $bought->createdAt = $date;

        return $bought;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return float
     */
    public function getCostInPoints()
    {
        return $this->costInPoints;
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
    public function getCampaignName()
    {
        return $this->campaignName;
    }

    /**
     * @return string
     */
    public function getReward()
    {
        return $this->reward;
    }
}
