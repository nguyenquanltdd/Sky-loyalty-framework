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
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;

/**
 * Class CampaignUsage.
 */
class CampaignBought implements SerializableReadModel
{
    /**
     * @var CampaignId
     */
    private $campaignId;

    /**
     * @var CustomerId
     */
    private $customerId;

    /**
     * @var \DateTime
     */
    private $purchasedAt;

    /**
     * @var Coupon
     */
    private $coupon;

    /**
     * @var string
     */
    private $campaignType;

    /**
     * @var string
     */
    private $campaignName;

    /**
     * @var string
     */
    private $customerEmail;

    /**
     * @var string
     */
    private $customerPhone;

    /**
     * @var string
     */
    private $customerName;

    /**
     * @var string
     */
    private $customerLastname;

    /**
     * @var int
     */
    private $costInPoints;

    /**
     * @var int
     */
    private $currentPointsAmount;

    /**
     * @var float
     */
    private $taxPriceValue;

    /**
     * @var bool
     */
    private $used;

    /**
     * @var string
     */
    private $status;

    /**
     * @var \DateTime|null
     */
    private $activeSince;

    /**
     * @var \DateTime|null
     */
    private $activeTo;

    /**
     * CampaignBought constructor.
     *
     * @param CampaignId     $campaignId
     * @param CustomerId     $customerId
     * @param \DateTime      $purchasedAt
     * @param Coupon         $coupon
     * @param string         $campaignType
     * @param string         $campaignName
     * @param string|null    $customerEmail
     * @param string|null    $customerPhone
     * @param string         $status
     * @param bool           $used
     * @param string         $customerName
     * @param string         $customerLastname
     * @param int            $costInPoints
     * @param int            $currentPointsAmount
     * @param float|null     $taxPriceValue
     * @param \DateTime|null $activeSince
     * @param \DateTime|null $activeTo
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CampaignId $campaignId,
        CustomerId $customerId,
        \DateTime $purchasedAt,
        Coupon $coupon,
        string $campaignType,
        string $campaignName,
        $customerEmail,
        $customerPhone,
        ?string $status = CampaignPurchase::STATUS_ACTIVE,
        ?bool $used = false,
        ?string $customerName = null,
        ?string $customerLastname = null,
        ?int $costInPoints = null,
        ?int $currentPointsAmount = null,
        ?float $taxPriceValue = null,
        ?\DateTime $activeSince = null,
        ?\DateTime $activeTo = null
    ) {
        $this->campaignId = $campaignId;
        $this->customerId = $customerId;
        $this->purchasedAt = $purchasedAt;
        $this->coupon = $coupon;
        $this->campaignType = $campaignType;
        $this->campaignName = $campaignName;
        $this->customerEmail = $customerEmail;
        $this->customerPhone = $customerPhone;
        $this->used = $used;
        $this->status = $status;
        $this->customerName = $customerName;
        $this->customerLastname = $customerLastname;
        $this->costInPoints = $costInPoints;
        $this->currentPointsAmount = $currentPointsAmount;
        $this->taxPriceValue = $taxPriceValue;
        $this->activeSince = $activeSince;
        $this->activeTo = $activeTo;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return self::createId($this->campaignId, $this->customerId, $this->coupon);
    }

    /**
     * @param array $data
     *
     * @return mixed The object instance
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

        return new self(
            new CampaignId($data['campaignId']),
            new CustomerId($data['customerId']),
            (new \DateTime())->setTimestamp((int) $data['purchasedAt']),
            new Coupon($data['coupon']),
            $data['campaignType'],
            $data['campaignName'],
            $data['customerEmail'],
            $data['customerPhone'],
            $data['status'] ?? CampaignPurchase::STATUS_ACTIVE,
            $data['used'],
            $data['customerName'] ?? null,
            $data['customerLastname'] ?? null,
            $data['costInPoints'] ?? null,
            $data['currentPointsAmount'] ?? null,
            $data['taxPriceValue'] ?? null,
            $activeSince ?? null,
            $activeTo ?? null
        );
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'campaignId' => $this->campaignId->__toString(),
            'customerId' => $this->customerId->__toString(),
            'purchasedAt' => $this->purchasedAt->getTimestamp(),
            'coupon' => $this->coupon->getCode(),
            'campaignType' => $this->campaignType,
            'campaignName' => $this->campaignName,
            'customerEmail' => $this->customerEmail,
            'customerPhone' => $this->customerPhone,
            'used' => $this->used,
            'status' => $this->status,
            'customerName' => $this->customerName,
            'customerLastname' => $this->customerLastname,
            'costInPoints' => $this->costInPoints,
            'currentPointsAmount' => $this->currentPointsAmount,
            'taxPriceValue' => $this->taxPriceValue,
            'activeSince' => $this->activeSince ? $this->activeSince->getTimestamp() : null,
            'activeTo' => $this->activeTo ? $this->activeTo->getTimestamp() : null,
        ];
    }

    /**
     * @param CampaignId $campaignId
     * @param CustomerId $customerId
     * @param Coupon     $coupon
     *
     * @return string
     */
    public static function createId(CampaignId $campaignId, CustomerId $customerId, Coupon $coupon): string
    {
        return $campaignId->__toString().'_'.$customerId->__toString().'_'.$coupon->getCode();
    }

    /**
     * @param bool $used
     */
    public function setUsed($used)
    {
        $this->used = $used;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
