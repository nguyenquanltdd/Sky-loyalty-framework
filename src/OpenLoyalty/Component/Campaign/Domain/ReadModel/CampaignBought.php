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
use OpenLoyalty\Component\Campaign\Domain\TransactionId;
use OpenLoyalty\Component\Core\Domain\Model\Identifier;
use OpenLoyalty\Component\Core\Domain\ReadModel\Versionable;
use OpenLoyalty\Component\Core\Domain\ReadModel\VersionableReadModel;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;

/**
 * Class CampaignUsage.
 */
class CampaignBought implements SerializableReadModel, VersionableReadModel
{
    use Versionable;

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
     * @var Identifier|null
     */
    private $transactionId;

    /**
     * @var TransactionId|null
     */
    private $usedForTransactionId;

    /**
     * @var float
     */
    private $returnedAmount = 0;

    /**
     * CampaignBought constructor.
     *
     * @param CampaignId      $campaignId
     * @param CustomerId      $customerId
     * @param \DateTime       $purchasedAt
     * @param Coupon          $coupon
     * @param string          $campaignType
     * @param string          $campaignName
     * @param string|null     $customerEmail
     * @param string|null     $customerPhone
     * @param string          $status
     * @param bool            $used
     * @param string          $customerName
     * @param string          $customerLastname
     * @param int             $costInPoints
     * @param int             $currentPointsAmount
     * @param float|null      $taxPriceValue
     * @param \DateTime|null  $activeSince
     * @param \DateTime|null  $activeTo
     * @param Identifier|null $transactionId
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
        ?\DateTime $activeTo = null,
        ?Identifier $transactionId = null
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
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return self::createId($this->campaignId, $this->customerId, $this->coupon, $this->transactionId);
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

        $self = new self(
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
            $activeTo ?? null,
            isset($data['transactionId']) ? new TransactionId($data['transactionId']) : null
        );

        $usedFor = isset($data['usedForTransactionId']) ? new TransactionId($data['usedForTransactionId']) : null;
        $self->setUsedForTransactionId($usedFor);
        $self->setReturnedAmount(isset($data['returnedAmount']) ? $data['returnedAmount'] : 0);

        return $self;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'campaignId' => (string) $this->campaignId,
            'customerId' => (string) $this->customerId,
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
            'transactionId' => $this->transactionId ? (string) $this->transactionId : null,
            'usedForTransactionId' => $this->usedForTransactionId ? (string) $this->usedForTransactionId : null,
            'returnedAmount' => $this->returnedAmount ?: 0,
        ];
    }

    /**
     * @param CampaignId      $campaignId
     * @param CustomerId      $customerId
     * @param Coupon          $coupon
     * @param null|Identifier $transactionId
     *
     * @return string
     */
    public static function createId(CampaignId $campaignId, CustomerId $customerId, Coupon $coupon, ?Identifier $transactionId = null): string
    {
        $transactionSuffix = $transactionId ? '_'.((string) $transactionId) : '';

        return ((string) $campaignId).'_'.((string) $customerId).'_'.$coupon->getCode().$transactionSuffix;
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
     * @return \DateTime
     */
    public function getPurchasedAt(): \DateTime
    {
        return $this->purchasedAt;
    }

    /**
     * @return Coupon
     */
    public function getCoupon(): Coupon
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
    public function getCampaignType(): string
    {
        return $this->campaignType;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return Identifier|null
     */
    public function getTransactionId(): ?Identifier
    {
        return $this->transactionId;
    }

    /**
     * @return bool
     */
    public function isUsed(): bool
    {
        return (is_bool($this->used)) ? $this->used : false;
    }

    /**
     * @return null|TransactionId
     */
    public function getUsedForTransactionId(): ?TransactionId
    {
        return $this->usedForTransactionId;
    }

    /**
     * @param null|TransactionId $usedForTransactionId
     */
    public function setUsedForTransactionId(?TransactionId $usedForTransactionId): void
    {
        $this->usedForTransactionId = $usedForTransactionId;
    }

    /**
     * @return float
     */
    public function getReturnedAmount(): float
    {
        return $this->returnedAmount;
    }

    /**
     * @param float $returnedAmount
     */
    public function setReturnedAmount(float $returnedAmount): void
    {
        $this->returnedAmount = $returnedAmount;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveTo(): ?\DateTime
    {
        return $this->activeTo;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveSince(): ?\DateTime
    {
        return $this->activeSince;
    }
}
