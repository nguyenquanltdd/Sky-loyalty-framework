<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\ReadModel;

use Broadway\ReadModel\SerializableReadModel;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\Model\AddPointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\PointsTransfer;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Account\Domain\CustomerId;

/**
 * Class AccountDetails.
 */
class AccountDetails implements SerializableReadModel
{
    /**
     * @var AccountId
     */
    protected $accountId;

    /**
     * @var CustomerId
     */
    protected $customerId;

    /**
     * @var PointsTransfer[]
     */
    protected $transfers = [];

    /**
     * @var \DateTime|null
     */
    protected $pointsResetAt;

    /**
     * AccountDetails constructor.
     *
     * @param AccountId  $id
     * @param CustomerId $customerId
     */
    public function __construct(AccountId $id, CustomerId $customerId)
    {
        $this->accountId = $id;
        $this->customerId = $customerId;
    }

    /**
     * @param \DateTime|null $pointsResetAt
     */
    public function setPointsResetAt(?\DateTime $pointsResetAt): void
    {
        $this->pointsResetAt = $pointsResetAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getPointsResetAt(): ?\DateTime
    {
        return $this->pointsResetAt;
    }

    /**
     * @param array $data
     *
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        $account = new self(new AccountId($data['accountId']), new CustomerId($data['customerId']));
        foreach ($data['transfers'] as $transfer) {
            $account->addPointsTransfer($transfer['type']::deserialize($transfer['data']));
        }

        if (isset($data['pointsResetAt'])) {
            $resetAt = new \DateTime();
            $resetAt->setTimestamp($data['pointsResetAt']);
            $account->setPointsResetAt($resetAt);
        }

        return $account;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        $transfers = [];
        foreach ($this->transfers as $transfer) {
            $transfers[] = [
                'type' => get_class($transfer),
                'data' => $transfer->serialize(),
            ];
        }

        return [
            'accountId' => $this->accountId->__toString(),
            'pointsResetAt' => $this->pointsResetAt ? $this->pointsResetAt->getTimestamp() : null,
            'customerId' => $this->customerId->__toString(),
            'transfers' => $transfers,
        ];
    }

    public function addPointsTransfer(PointsTransfer $pointsTransfer)
    {
        if (isset($this->transfers[$pointsTransfer->getId()->__toString()])) {
            throw new \InvalidArgumentException($pointsTransfer->getId()->__toString().' already exists');
        }
        $this->transfers[$pointsTransfer->getId()->__toString()] = $pointsTransfer;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->accountId->__toString();
    }

    /**
     * @return CustomerId
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param CustomerId $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return AccountId
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @return AddPointsTransfer[]
     */
    public function getAllActiveAddPointsTransfers()
    {
        $transfers = [];
        foreach ($this->transfers as $pointsTransfer) {
            if (!$pointsTransfer instanceof AddPointsTransfer) {
                continue;
            }
            if ($pointsTransfer->isLocked() || $pointsTransfer->isExpired() || $pointsTransfer->getAvailableAmount() == 0 || $pointsTransfer->isCanceled()) {
                continue;
            }

            $transfers[] = $pointsTransfer;
        }

        usort($transfers, function (PointsTransfer $a, PointsTransfer $b) {
            return $a->getCreatedAt() > $b->getCreatedAt();
        });

        return $transfers;
    }

    /**
     * @return AddPointsTransfer[]
     */
    public function getAllActiveAndLockedAddPointsTransfers(): array
    {
        $transfers = [];
        foreach ($this->transfers as $pointsTransfer) {
            if (!$pointsTransfer instanceof AddPointsTransfer) {
                continue;
            }
            if ($pointsTransfer->isExpired() || $pointsTransfer->getAvailableAmount() == 0 || $pointsTransfer->isCanceled()) {
                continue;
            }

            $transfers[] = $pointsTransfer;
        }

        usort($transfers, function (PointsTransfer $a, PointsTransfer $b) {
            return $a->getCreatedAt() > $b->getCreatedAt();
        });

        return $transfers;
    }

    /**
     * @return AddPointsTransfer[]
     */
    public function getAllExpiredAddPointsTransfers()
    {
        $transfers = [];
        foreach ($this->transfers as $pointsTransfer) {
            if (!$pointsTransfer instanceof AddPointsTransfer) {
                continue;
            }
            if (!$pointsTransfer->isExpired()) {
                continue;
            }

            $transfers[$pointsTransfer->getCreatedAt()->getTimestamp().'_'.$pointsTransfer->getId()->__toString()] = $pointsTransfer;
        }

        ksort($transfers);

        return $transfers;
    }

    /**
     * @return AddPointsTransfer[]
     */
    public function getAllLockedAddPointsTransfers(): array
    {
        $transfers = [];
        foreach ($this->transfers as $pointsTransfer) {
            if (!$pointsTransfer instanceof AddPointsTransfer) {
                continue;
            }
            if (!$pointsTransfer->isLocked()) {
                continue;
            }

            $transfers[$pointsTransfer->getCreatedAt()->getTimestamp().'_'.$pointsTransfer->getId()->__toString()] = $pointsTransfer;
        }

        ksort($transfers);

        return $transfers;
    }

    /**
     * @return AddPointsTransfer[]
     */
    public function getAllAddPointsTransfers()
    {
        $transfers = [];
        foreach ($this->transfers as $pointsTransfer) {
            if (!$pointsTransfer instanceof AddPointsTransfer) {
                continue;
            }

            $transfers[$pointsTransfer->getCreatedAt()->getTimestamp().'_'.$pointsTransfer->getId()->__toString()] = $pointsTransfer;
        }

        ksort($transfers);

        return $transfers;
    }

    public function getTransfer(PointsTransferId $pointsTransferId)
    {
        if (!isset($this->transfers[$pointsTransferId->__toString()])) {
            return;
        }

        return $this->transfers[$pointsTransferId->__toString()];
    }

    public function setTransfer(PointsTransfer $pointsTransfer)
    {
        $this->transfers[$pointsTransfer->getId()->__toString()] = $pointsTransfer;
    }

    public function getAvailableAmount()
    {
        $sum = 0;

        foreach ($this->getAllActiveAddPointsTransfers() as $pointsTransfer) {
            $sum += $pointsTransfer->getAvailableAmount();
        }

        return $sum;
    }

    /**
     * @return float
     */
    public function getEarnedAmount(): float
    {
        $sum = 0.0;

        foreach ($this->getAllAddPointsTransfers() as $pointsTransfer) {
            if ($pointsTransfer->isCanceled()) {
                continue;
            }
            $sum += $pointsTransfer->getValue();
        }

        return $sum;
    }

    /**
     * @param \DateTimeInterface $startDate
     *
     * @return float
     */
    public function getEarnedAmountSince(\DateTimeInterface $startDate): float
    {
        $sum = 0.0;

        foreach ($this->getAllAddPointsTransfers() as $pointsTransfer) {
            if ($pointsTransfer->isCanceled()) {
                continue;
            }
            if ($pointsTransfer->getCreatedAt() <= $startDate) {
                continue;
            }
            $sum += $pointsTransfer->getValue();
        }

        return $sum;
    }

    /**
     * @return float
     */
    public function getUsedAmount(): float
    {
        $sum = 0.0;

        foreach ($this->getAllAddPointsTransfers() as $pointsTransfer) {
            $sum += $pointsTransfer->getUsedAmount();
        }

        return $sum;
    }

    /**
     * @return float
     */
    public function getExpiredAmount(): float
    {
        $sum = 0.0;

        foreach ($this->getAllExpiredAddPointsTransfers() as $pointsTransfer) {
            $sum += $pointsTransfer->getAvailableAmount();
        }

        return $sum;
    }

    /**
     * @return float
     */
    public function getLockedAmount(): float
    {
        $sum = 0.0;

        foreach ($this->getAllLockedAddPointsTransfers() as $pointsTransfer) {
            $sum += $pointsTransfer->getAvailableAmount();
        }

        return $sum;
    }

    /**
     * @return \OpenLoyalty\Component\Account\Domain\Model\PointsTransfer[]
     */
    public function getTransfers()
    {
        return $this->transfers;
    }
}
