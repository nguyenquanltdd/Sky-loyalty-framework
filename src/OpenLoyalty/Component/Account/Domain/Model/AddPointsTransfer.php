<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\Model;

use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use Assert\Assertion as Assert;
use OpenLoyalty\Component\Account\Domain\TransactionId;

/**
 * Class AddPointsTransfer.
 */
class AddPointsTransfer extends PointsTransfer
{
    /**
     * @var float
     */
    protected $availableAmount;

    /**
     * @var bool
     */
    protected $expired = false;

    /**
     * @var \DateTime
     */
    protected $expiresAt;

    /**
     * @var TransactionId
     */
    protected $transactionId;

    /**
     * @var \DateTime|null
     */
    protected $lockedUntil;

    /**
     * @var bool
     */
    protected $locked = false;

    /**
     * PointsTransfer constructor.
     *
     * @param PointsTransferId $id
     * @param int              $value
     * @param int|null         $validityDuration
     * @param int|null         $lockDaysDuration
     * @param \DateTime        $createdAt
     * @param bool             $canceled
     * @param TransactionId    $transactionId
     * @param string           $comment
     * @param string           $issuer
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        PointsTransferId $id,
        $value,
        int $validityDuration = null,
        int $lockDaysDuration = null,
        \DateTime $createdAt = null,
        $canceled = false,
        TransactionId $transactionId = null,
        $comment = null,
        $issuer = self::ISSUER_SYSTEM
    ) {
        parent::__construct($id, $value, $createdAt, $canceled, $comment, $issuer);
        $this->availableAmount = $value;
        $this->transactionId = $transactionId;
        $this->lockedUntil = null !== $lockDaysDuration ? (clone $this->createdAt)->modify(sprintf('+%d days', $lockDaysDuration)) : null;
        if (null !== $this->lockedUntil) {
            $this->locked = true;
        }
        if (null !== $validityDuration) {
            $this->expiresAt = $this->getExpiresAtDate($validityDuration);
        }
    }

    /**
     * @param array $data
     *
     * @return mixed The object instance
     *
     * @throws \Assert\AssertionFailedException
     */
    public static function deserialize(array $data)
    {
        $createdAt = null;
        if (isset($data['createdAt'])) {
            $createdAt = new \DateTime();
            $createdAt->setTimestamp($data['createdAt']);
        }

        $transfer = new self(
            new PointsTransferId($data['id']),
            $data['value'],
            null,
            null,
            $createdAt,
            isset($data['canceled']) ? $data['canceled'] : false
        );

        if (isset($data['expiresAt'])) {
            $expiresAt = new \DateTime();
            $expiresAt->setTimestamp($data['expiresAt']);
            $transfer->expiresAt = $expiresAt;
        }

        if (isset($data['lockedUntil'])) {
            $lockedUntil = new \DateTime();
            $lockedUntil->setTimestamp($data['lockedUntil']);
            $transfer->lockUntil($lockedUntil);
        }

        $transfer->locked = isset($data['locked']) ? $data['locked'] : false;

        if (isset($data['availableAmount'])) {
            Assert::numeric($data['availableAmount']);
            Assert::min($data['availableAmount'], 0);
            $transfer->availableAmount = $data['availableAmount'];
        }
        if (isset($data['expired'])) {
            Assert::boolean($data['expired']);
            $transfer->expired = $data['expired'];
        }

        if (isset($data['transactionId'])) {
            $transfer->transactionId = new TransactionId($data['transactionId']);
        }

        if (isset($data['comment'])) {
            $transfer->comment = $data['comment'];
        }
        if (isset($data['issuer'])) {
            $transfer->issuer = $data['issuer'];
        }

        return $transfer;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return array_merge(
            parent::serialize(),
            [
                'availableAmount' => $this->availableAmount,
                'expired' => $this->expired,
                'transactionId' => $this->transactionId ? $this->transactionId->__toString() : null,
                'lockedUntil' => null !== $this->lockedUntil ? $this->lockedUntil->getTimestamp() : null,
                'locked' => $this->locked,
                'expiresAt' => $this->expiresAt->getTimestamp(),
            ]
        );
    }

    /**
     * @param $value
     *
     * @return $this
     *
     * @throws \Assert\AssertionFailedException
     */
    public function updateAvailableAmount($value)
    {
        Assert::notBlank($value);
        Assert::numeric($value);
        Assert::max($value, $this->value);
        $this->availableAmount = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function cancel()
    {
        $this->canceled = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function expire()
    {
        $this->expired = true;

        return $this;
    }

    /**
     * @return float
     */
    public function getAvailableAmount()
    {
        return $this->availableAmount;
    }

    /**
     * @return float
     */
    public function getUsedAmount()
    {
        return $this->value - $this->availableAmount;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expired;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @return \DateTime|null
     */
    public function getLockedUntil(): ?\DateTime
    {
        return $this->lockedUntil;
    }

    /**
     * @return TransactionId
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param \DateTime|null $date
     */
    public function lockUntil(\DateTime $date = null)
    {
        $this->lockedUntil = $date;
    }

    /**
     * Unlock points.
     */
    public function unlock()
    {
        $this->locked = false;

        return $this;
    }

    /**
     * @param int $days
     *
     * @return \DateTime
     */
    private function getExpiresAtDate(int $days): \DateTime
    {
        $startDate = null !== $this->lockedUntil ? clone $this->lockedUntil : clone $this->getCreatedAt();

        return $startDate->modify(sprintf('+%u days', abs($days)));
    }
}
