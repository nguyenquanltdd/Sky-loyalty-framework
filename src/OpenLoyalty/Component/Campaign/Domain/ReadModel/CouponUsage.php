<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\ReadModel;

use Broadway\ReadModel\SerializableReadModel;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon;

/**
 * Class CouponUsage.
 */
class CouponUsage implements SerializableReadModel
{
    /**
     * @var int
     */
    protected $usage;

    /**
     * @var CampaignId
     */
    protected $campaignId;

    /**
     * @var CustomerId
     */
    protected $customerId;

    /**
     * @var Coupon
     */
    protected $coupon;

    /**
     * CouponUsage constructor.
     *
     * @param CampaignId $campaignId
     * @param CustomerId $customerId
     * @param Coupon     $coupon
     * @param int        $usage
     */
    public function __construct(CampaignId $campaignId, CustomerId $customerId, Coupon $coupon, $usage = 1)
    {
        $this->campaignId = $campaignId;
        $this->customerId = $customerId;
        $this->coupon = $coupon;
        $this->usage = $usage;
    }

    /**
     * @param array $data
     *
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        if (isset($data['usage'])) {
            $usage = $data['usage'];
        } else {
            $usage = 1;
        }

        return new self(new CampaignId($data['campaignId']), new CustomerId($data['customerId']), new Coupon($data['coupon']), $usage);
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'campaignId' => $this->campaignId->__toString(),
            'customerId' => $this->customerId->__toString(),
            'coupon' => $this->coupon->getCode(),
            'usage' => $this->getUsage(),
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return sprintf(
            '%s_%s_%s',
            $this->campaignId->__toString(),
            $this->customerId->__toString(),
            $this->coupon->getCode()
        );
    }

    /**
     * @return CampaignId
     */
    public function getCampaignId(): CampaignId
    {
        return $this->campaignId;
    }

    /**
     * @return CustomerId
     */
    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    /**
     * @return Coupon
     */
    public function getCoupon(): Coupon
    {
        return $this->coupon;
    }

    /**
     * @return mixed
     */
    public function getUsage()
    {
        return $this->usage;
    }
}
