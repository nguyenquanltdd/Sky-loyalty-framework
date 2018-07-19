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
     * @var TransactionId
     */
    protected $transactionId;

    /**
     * PointsTransfer constructor.
     *
     * @param PointsTransferId $id
     * @param int              $value
     * @param int              $validityDuration
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
        int $validityDuration,
        \DateTime $createdAt = null,
        $canceled = false,
        TransactionId $transactionId = null,
        $comment = null,
        $issuer = self::ISSUER_SYSTEM
    ) {
        parent::__construct($id, $value, $validityDuration, $createdAt, $canceled, $comment, $issuer);
        $this->availableAmount = $value;
        $this->transactionId = $transactionId;
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
            $data['validityInDays'] ?? null,
            $createdAt,
            $data['canceled']
        );

        if (isset($data['validityInDays'])) {
            $transfer->validityInDays = $data['validityInDays'];
        }

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
    public function isExpired()
    {
        return $this->expired;
    }

    /**
     * @return TransactionId
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }
}
