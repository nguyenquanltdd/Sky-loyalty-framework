<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\Model;

use Broadway\Serializer\Serializable;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Core\Domain\Model\Identifier;
use OpenLoyalty\Component\Customer\Domain\CampaignId;
use OpenLoyalty\Component\Customer\Domain\TransactionId;

/**
 * Class CampaignPurchase.
 */
class CampaignPurchase implements Serializable
{
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

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
     * @var string
     */
    protected $status;

    /**
     * @var \DateTime|null
     */
    protected $activeSince;

    /**
     * @var \DateTime|null
     */
    protected $activeTo;

    /**
     * @var Identifier|null
     */
    private $transactionId;

    /**
     * CampaignPurchase constructor.
     *
     * @param \DateTime  $purchaseAt
     * @param int        $costInPoints
     * @param CampaignId $campaignId
     * @param Coupon     $coupon
     * @param $reward
     * @param string          $status
     * @param \DateTime|null  $activeSince
     * @param \DateTime|null  $activeTo
     * @param Identifier|null $transactionId
     */
    public function __construct(
        \DateTime $purchaseAt,
        $costInPoints,
        CampaignId $campaignId,
        Coupon $coupon,
        $reward,
        string $status = self::STATUS_ACTIVE,
        ?\DateTime $activeSince = null,
        ?\DateTime $activeTo = null,
        ?Identifier $transactionId = null
    ) {
        $this->purchaseAt = $purchaseAt;
        $this->costInPoints = $costInPoints;
        $this->campaignId = $campaignId;
        $this->coupon = $coupon;
        $this->reward = $reward;
        $this->status = $status;
        $this->activeSince = $activeSince;
        $this->activeTo = $activeTo;
        $this->transactionId = $transactionId;
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
        if (isset($data['activeSince'])) {
            $activeSince = new \DateTime();
            $activeSince->setTimestamp($data['activeSince']);
        }

        if (isset($data['activeTo'])) {
            $activeTo = new \DateTime();
            $activeTo->setTimestamp($data['activeTo']);
        }

        $date = new \DateTime();
        $date->setTimestamp($data['purchaseAt']);

        $purchase = new self(
            $date,
            $data['costInPoints'],
            new CampaignId($data['campaignId']),
            new Coupon($data['coupon']),
            $data['reward'],
            $data['status'] ?? self::STATUS_ACTIVE,
            $activeSince ?? null,
            $activeTo ?? null,
            isset($data['transactionId']) ? new TransactionId($data['transactionId']) : null
        );
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
            'status' => $this->status,
            'activeSince' => $this->activeSince ? $this->activeSince->getTimestamp() : null,
            'activeTo' => $this->activeTo ? $this->activeTo->getTimestamp() : null,
            'transactionId' => $this->transactionId ? $this->transactionId->__toString() : null,
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
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return self::STATUS_ACTIVE === $this->status;
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
     * @param Coupon $coupon
     */
    public function setCoupon(Coupon $coupon): void
    {
        $this->coupon = $coupon;
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

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveSince(): ?\DateTime
    {
        return $this->activeSince;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveTo(): ?\DateTime
    {
        return $this->activeTo;
    }

    /**
     * @return Identifier|null
     */
    public function getTransactionId(): ?Identifier
    {
        return $this->transactionId;
    }
}
