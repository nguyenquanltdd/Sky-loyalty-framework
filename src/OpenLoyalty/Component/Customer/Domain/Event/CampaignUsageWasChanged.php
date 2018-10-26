<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\Event;

use OpenLoyalty\Component\Customer\Domain\CampaignId;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\TransactionId;

/**
 * Class CampaignUsageWasChanged.
 */
class CampaignUsageWasChanged extends CustomerEvent
{
    /**
     * @var CampaignId
     */
    private $campaignId;

    /**
     * @var Coupon
     */
    private $coupon;

    /**
     * @var bool
     */
    private $used;

    /**
     * @var TransactionId|null
     */
    private $transactionId;

    public function __construct(
        CustomerId $customerId,
        CampaignId $campaignId,
        Coupon $coupon,
        bool $used,
        ?TransactionId $transactionId = null
    ) {
        parent::__construct($customerId);
        $this->campaignId = $campaignId;
        $this->used = $used;
        $this->coupon = $coupon;
        $this->transactionId = $transactionId;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): array
    {
        return array_merge(
            parent::serialize(),
            [
                'campaignId' => $this->campaignId->__toString(),
                'used' => $this->used,
                'coupon' => $this->coupon->getCode(),
                'transactionId' => $this->transactionId ? (string) $this->transactionId : null,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function deserialize(array $data)
    {
        return new self(
            new CustomerId($data['customerId']),
            new CampaignId($data['campaignId']),
            new Coupon($data['coupon']),
            $data['used'],
            isset($data['transactionId']) ? new TransactionId($data['transactionId']) : null
        );
    }

    /**
     * @return CampaignId
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @return bool
     */
    public function isUsed()
    {
        return $this->used;
    }

    /**
     * @return Coupon
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @return null|TransactionId
     */
    public function getTransactionId(): ?TransactionId
    {
        return $this->transactionId;
    }
}
