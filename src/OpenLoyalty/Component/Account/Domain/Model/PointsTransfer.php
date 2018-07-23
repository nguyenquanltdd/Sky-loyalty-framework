<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\Model;

use Broadway\Serializer\Serializable;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use Assert\Assertion as Assert;

/**
 * Class PointsTransfer.
 */
abstract class PointsTransfer implements Serializable
{
    const ISSUER_ADMIN = 'admin';
    const ISSUER_SELLER = 'seller';
    const ISSUER_SYSTEM = 'system';
    const ISSUER_API = 'api';

    /**
     * @var PointsTransferId
     */
    protected $id;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $expiresAt;

    /**
     * @var int
     */
    protected $validityInDays;

    /**
     * @var float
     */
    protected $value;

    /**
     * @var bool
     */
    protected $canceled = false;

    /**
     * @var string
     */
    protected $issuer = self::ISSUER_SYSTEM;

    /**
     * PointsTransfer constructor.
     *
     * @param PointsTransferId $id
     * @param int              $value
     * @param int|null         $validityDuration
     * @param \DateTime        $createdAt
     * @param bool             $canceled
     * @param string|null      $comment
     * @param string           $issuer
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        PointsTransferId $id,
        $value,
        ?int $validityDuration,
        \DateTime $createdAt = null,
        $canceled = false,
        $comment = null,
        $issuer = self::ISSUER_SYSTEM
    ) {
        $this->id = $id;
        Assert::notBlank($value);
        Assert::numeric($value);
        Assert::min($value, 1);

        $this->validityInDays = (int) $validityDuration;
        $this->value = $value;

        if ($createdAt) {
            $this->createdAt = $createdAt;
        } else {
            $this->createdAt = new \DateTime();
            $this->createdAt->setTimestamp(time());
        }

        $this->expiresAt = $this->getExpiresAtDate($this->validityInDays);
        $this->comment = $comment;
        $this->canceled = $canceled;
        $this->issuer = $issuer;
    }

    /**
     * @return PointsTransferId
     */
    public function getId(): PointsTransferId
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt(): ? \DateTime
    {
        return $this->expiresAt;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return (float) $this->value;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'id' => $this->id->__toString(),
            'value' => $this->value,
            'createdAt' => $this->createdAt->getTimestamp(),
            'expiresAt' => $this->expiresAt->getTimestamp(),
            'validityInDays' => $this->validityInDays,
            'canceled' => $this->canceled,
            'comment' => $this->comment,
            'issuer' => $this->issuer,
        ];
    }

    /**
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->canceled;
    }

    /**
     * @return string
     */
    public function getComment(): ? string
    {
        return $this->comment;
    }

    /**
     * @return string
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }

    /**
     * @return int
     */
    public function getValidityInDays(): int
    {
        return $this->validityInDays;
    }

    /**
     * @param int $days
     *
     * @return \DateTime
     */
    private function getExpiresAtDate(int $days): \DateTime
    {
        $startDate = clone $this->getCreatedAt();

        return $startDate->modify(sprintf('+%u days', abs($days)));
    }
}
