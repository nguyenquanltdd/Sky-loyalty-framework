<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use OpenLoyalty\Component\Account\Domain\Event\AccountWasCreated;
use OpenLoyalty\Component\Account\Domain\Event\PointsHasBeenReset;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenCanceled;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenExpired;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenUnlocked;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereAdded;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereSpent;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereTransferred;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeCanceledException;
use OpenLoyalty\Component\Account\Domain\Exception\NotEnoughPointsException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferAlreadyExistException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeExpiredException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeUnlockedException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferNotExistException;
use OpenLoyalty\Component\Account\Domain\Model\AddPointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\P2PAddPointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\P2PSpendPointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\PointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\SpendPointsTransfer;

/**
 * Class Account.
 */
class Account extends EventSourcedAggregateRoot
{
    /**
     * @var AccountId
     */
    protected $id;

    /**
     * @var CustomerId
     */
    protected $customerId;

    /**
     * @var PointsTransfer[]
     */
    protected $pointsTransfers = [];

    /**
     * @param PointsTransferId $pointsTransferId
     *
     * @return PointsTransfer|null
     */
    public function getTransferById(PointsTransferId $pointsTransferId): PointsTransfer
    {
        if (!isset($this->pointsTransfers[$pointsTransferId->__toString()])) {
            return null;
        }

        return $this->pointsTransfers[$pointsTransferId->__toString()];
    }

    /**
     * @param AccountId  $accountId
     * @param CustomerId $customerId
     *
     * @return Account
     */
    public static function createAccount(AccountId $accountId, CustomerId $customerId): Account
    {
        $account = new self();
        $account->create($accountId, $customerId);

        return $account;
    }

    /**
     * @param AddPointsTransfer $pointsTransfer
     */
    public function addPoints(AddPointsTransfer $pointsTransfer): void
    {
        $this->apply(
            new PointsWereAdded($this->id, $pointsTransfer)
        );
    }

    /**
     * @param SpendPointsTransfer $pointsTransfer
     */
    public function spendPoints(SpendPointsTransfer $pointsTransfer): void
    {
        if (!$pointsTransfer->getTransactionId() && $this->getAvailableAmount() < $pointsTransfer->getValue()) {
            throw new NotEnoughPointsException();
        }
        $this->apply(
            new PointsWereSpent($this->id, $pointsTransfer)
        );
    }

    /**
     * @param PointsTransferId $pointsTransferId
     */
    public function cancelPointsTransfer(PointsTransferId $pointsTransferId): void
    {
        $this->apply(
            new PointsTransferHasBeenCanceled($this->id, $pointsTransferId)
        );
    }

    /**
     * @param PointsTransferId $pointsTransferId
     */
    public function expirePointsTransfer(PointsTransferId $pointsTransferId): void
    {
        $this->apply(
            new PointsTransferHasBeenExpired($this->id, $pointsTransferId)
        );
    }

    /**
     * @param \DateTime $date
     */
    public function resetPoints(\DateTime $date): void
    {
        $this->apply(
            new PointsHasBeenReset($this->id, $date)
        );
    }

    /**
     * @param PointsTransferId $pointsTransferId
     */
    public function unlockPointsTransfer(PointsTransferId $pointsTransferId): void
    {
        $this->apply(
            new PointsTransferHasBeenUnlocked($this->id, $pointsTransferId)
        );
    }

    /**
     * @return string
     */
    public function getAggregateRootId(): string
    {
        return $this->id;
    }

    /**
     * @return AccountId
     */
    public function getId(): AccountId
    {
        return $this->id;
    }

    /**
     * @return CustomerId
     */
    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    /**
     * @param array $pointsTransfers
     */
    public function setPointsTransfers($pointsTransfers): void
    {
        $this->pointsTransfers = $pointsTransfers;
    }

    /**
     * @return float
     */
    public function getAvailableAmount(): float
    {
        $sum = 0.0;

        foreach ($this->getAllActiveAddPointsTransfers() as $pointsTransfer) {
            $sum += $pointsTransfer->getAvailableAmount();
        }

        return $sum;
    }

    /**
     * @param PointsTransfer $pointsTransfer
     */
    private function addPointsTransfer(PointsTransfer $pointsTransfer): void
    {
        if (isset($this->pointsTransfers[$pointsTransfer->getId()->__toString()])) {
            throw new PointsTransferAlreadyExistException($pointsTransfer->getId()->__toString());
        }
        $this->pointsTransfers[$pointsTransfer->getId()->__toString()] = $pointsTransfer;
    }

    /**
     * @param AccountId  $accountId
     * @param CustomerId $customerId
     */
    private function create(AccountId $accountId, CustomerId $customerId)
    {
        $this->apply(
            new AccountWasCreated($accountId, $customerId)
        );
    }

    /**
     * @param P2PSpendPointsTransfer $pointsTransfer
     *
     * @return array
     *
     * @throws \Assert\AssertionFailedException
     */
    public function transferPoints(P2PSpendPointsTransfer $pointsTransfer): array
    {
        if ($this->getAvailableAmount() < $pointsTransfer->getValue()) {
            throw new NotEnoughPointsException();
        }

        $transfers = [];
        $pointsToTransfer = $pointsTransfer->getValue();
        foreach ($this->getAllActiveAddPointsTransfers() as $transfer) {
            if ($pointsToTransfer <= 0) {
                break;
            }
            $availableAmount = $transfer->getAvailableAmount();
            if ($availableAmount > $pointsToTransfer) {
                $transfers[] = [$transfer, $pointsToTransfer];
                $pointsToTransfer = 0;
            } else {
                $pointsToTransfer -= $availableAmount;
                $transfers[] = [$transfer, $availableAmount];
            }
        }

        $this->apply(
            new PointsWereTransferred($this->id, $pointsTransfer)
        );

        return $transfers;
    }

    /**
     * @param AccountWasCreated $event
     */
    protected function applyAccountWasCreated(AccountWasCreated $event): void
    {
        $this->id = $event->getAccountId();
        $this->customerId = $event->getCustomerId();
    }

    /**
     * @param PointsWereAdded $event
     */
    protected function applyPointsWereAdded(PointsWereAdded $event): void
    {
        $this->addPointsTransfer($event->getPointsTransfer());
    }

    /**
     * @param PointsWereSpent $event
     */
    protected function applyPointsWereSpent(PointsWereSpent $event): void
    {
        $this->addPointsTransfer($event->getPointsTransfer());
        $amount = $event->getPointsTransfer()->getValue();
        foreach ($this->getAllActiveAddPointsTransfers() as $pointsTransfer) {
            if ($amount <= 0) {
                break;
            }
            $availableAmount = $pointsTransfer->getAvailableAmount();
            if ($availableAmount > $amount) {
                $availableAmount -= $amount;
                $amount = 0;
            } else {
                $amount -= $availableAmount;
                $availableAmount = 0;
            }
            $this->pointsTransfers[$pointsTransfer->getId()->__toString()] = $pointsTransfer->updateAvailableAmount($availableAmount);
        }
    }

    /**
     * @param PointsWereTransferred $event
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function applyPointsWereTransferred(PointsWereTransferred $event): void
    {
        $this->addPointsTransfer($event->getPointsTransfer());
        $amount = $event->getPointsTransfer()->getValue();
        foreach ($this->getAllActiveAddPointsTransfers() as $pointsTransfer) {
            if ($amount <= 0) {
                break;
            }
            $availableAmount = $pointsTransfer->getAvailableAmount();
            if ($availableAmount > $amount) {
                $availableAmount -= $amount;
                $amount = 0;
            } else {
                $amount -= $availableAmount;
                $availableAmount = 0;
            }
            $this->pointsTransfers[$pointsTransfer->getId()->__toString()] = $pointsTransfer->updateAvailableAmount($availableAmount);
        }
    }

    /**
     * @param PointsTransferHasBeenCanceled $event
     *
     * @throws PointsTransferCannotBeCanceledException
     */
    protected function applyPointsTransferHasBeenCanceled(PointsTransferHasBeenCanceled $event): void
    {
        $id = $event->getPointsTransferId();
        if (!isset($this->pointsTransfers[$id->__toString()])) {
            throw new PointsTransferNotExistException($id->__toString());
        }
        $transfer = $this->pointsTransfers[$id->__toString()];
        if (!$transfer instanceof AddPointsTransfer || $transfer instanceof P2PAddPointsTransfer) {
            throw new PointsTransferCannotBeCanceledException($id->__toString());
        }
        $this->pointsTransfers[$id->__toString()] = $transfer->cancel();
    }

    /**
     * @param PointsTransferHasBeenExpired $event
     */
    protected function applyPointsTransferHasBeenExpired(PointsTransferHasBeenExpired $event): void
    {
        $id = $event->getPointsTransferId();
        if (!isset($this->pointsTransfers[$id->__toString()])) {
            throw new PointsTransferNotExistException($id->__toString());
        }
        $transfer = $this->pointsTransfers[$id->__toString()];
        if (!$transfer instanceof AddPointsTransfer) {
            throw new PointsTransferCannotBeExpiredException($id->__toString());
        }
        $this->pointsTransfers[$id->__toString()] = $transfer->expire();
    }

    /**
     * @param PointsHasBeenReset $event
     */
    public function applyPointsHasBeenReset(PointsHasBeenReset $event): void
    {
        $transfers = $this->getAllActiveAndLockedAddPointsTransfers();

        foreach ($transfers as $transfer) {
            $transfer->expire();
        }
    }

    /**
     * @param PointsTransferHasBeenUnlocked $event
     */
    protected function applyPointsTransferHasBeenUnlocked(PointsTransferHasBeenUnlocked $event): void
    {
        $id = $event->getPointsTransferId();
        if (!isset($this->pointsTransfers[$id->__toString()])) {
            throw new PointsTransferNotExistException($id->__toString());
        }
        $transfer = $this->pointsTransfers[$id->__toString()];
        if (!$transfer instanceof AddPointsTransfer) {
            throw new PointsTransferCannotBeUnlockedException($id->__toString());
        }

        $transfer->unlock();
    }

    /**
     * @return AddPointsTransfer[]
     */
    protected function getAllActiveAddPointsTransfers(): array
    {
        $transfers = [];
        foreach ($this->pointsTransfers as $pointsTransfer) {
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
    protected function getAllActiveAndLockedAddPointsTransfers(): array
    {
        $transfers = [];
        foreach ($this->pointsTransfers as $pointsTransfer) {
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
     * @param $days
     *
     * @return Model\AddPointsTransfer[]
     */
    protected function getAllNotExpiredAddPointsTransfersOlderThan($days): array
    {
        $transfers = [];
        $date = new \DateTime('-'.$days.' days');
        $date->setTime(0, 0, 0);
        foreach ($this->pointsTransfers as $pointsTransfer) {
            if (!$pointsTransfer instanceof AddPointsTransfer) {
                continue;
            }
            if ($pointsTransfer->isExpired() || $pointsTransfer->isCanceled()) {
                continue;
            }
            if ($pointsTransfer->getCreatedAt() >= $date) {
                continue;
            }

            $transfers[] = $pointsTransfer;
        }

        return $transfers;
    }

    /**
     * @return Model\AddPointsTransfer[]
     */
    protected function getAllNotLockedPointsTransfers(): array
    {
        $transfers = [];
        foreach ($this->pointsTransfers as $pointsTransfer) {
            if (!$pointsTransfer instanceof AddPointsTransfer) {
                continue;
            }
            if (!$pointsTransfer->isLocked()) {
                continue;
            }

            $transfers[] = $pointsTransfer;
        }

        return $transfers;
    }
}
