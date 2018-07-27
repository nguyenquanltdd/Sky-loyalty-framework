<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\ReadModel;

use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Repository;
use Broadway\ReadModel\SerializableReadModel;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\Event\AccountWasCreated;
use OpenLoyalty\Component\Account\Domain\Event\PointsHasBeenReset;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenCanceled;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenExpired;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenUnlocked;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereAdded;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereSpent;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeCanceledException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeExpiredException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeUnlockedException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferNotExistException;
use OpenLoyalty\Component\Account\Domain\Model\AddPointsTransfer;
use OpenLoyalty\Component\Account\Domain\CustomerId;

/**
 * Class AccountDetailsProjector.
 */
class AccountDetailsProjector extends Projector
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * AccountDetailsProjector constructor.
     *
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param AccountWasCreated $event
     */
    protected function applyAccountWasCreated(AccountWasCreated $event)
    {
        $readModel = $this->getReadModel($event->getAccountId(), $event->getCustomerId());
        $this->repository->save($readModel);
    }

    protected function applyPointsWereAdded(PointsWereAdded $event)
    {
        /** @var AccountDetails $readModel */
        $readModel = $this->getReadModel($event->getAccountId());
        $readModel->addPointsTransfer($event->getPointsTransfer());
        $this->repository->save($readModel);
    }

    protected function applyPointsWereSpent(PointsWereSpent $event)
    {
        /** @var AccountDetails $readModel */
        $readModel = $this->getReadModel($event->getAccountId());
        $readModel->addPointsTransfer($event->getPointsTransfer());
        $amount = $event->getPointsTransfer()->getValue();
        foreach ($readModel->getAllActiveAddPointsTransfers() as $pointsTransfer) {
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
            $readModel->setTransfer($pointsTransfer->updateAvailableAmount($availableAmount));
        }
        $this->repository->save($readModel);
    }

    protected function applyPointsTransferHasBeenCanceled(PointsTransferHasBeenCanceled $event)
    {
        /** @var AccountDetails $readModel */
        $readModel = $this->getReadModel($event->getAccountId());
        $id = $event->getPointsTransferId();
        $transfer = $readModel->getTransfer($id);
        if (!$transfer) {
            throw new PointsTransferNotExistException($id->__toString());
        }
        if (!$transfer instanceof AddPointsTransfer) {
            throw new PointsTransferCannotBeCanceledException($id->__toString());
        }
        $readModel->setTransfer($transfer->cancel());
        $this->repository->save($readModel);
    }

    protected function applyPointsTransferHasBeenExpired(PointsTransferHasBeenExpired $event)
    {
        /** @var AccountDetails $readModel */
        $readModel = $this->getReadModel($event->getAccountId());
        $id = $event->getPointsTransferId();
        $transfer = $readModel->getTransfer($id);
        if (!$transfer) {
            throw new PointsTransferNotExistException($id->__toString());
        }
        if (!$transfer instanceof AddPointsTransfer) {
            throw new PointsTransferCannotBeExpiredException($id->__toString());
        }
        $readModel->setTransfer($transfer->expire());
        $this->repository->save($readModel);
    }

    protected function applyPointsTransferHasBeenUnlocked(PointsTransferHasBeenUnlocked $event)
    {
        /** @var AccountDetails $readModel */
        $readModel = $this->getReadModel($event->getAccountId());
        $id = $event->getPointsTransferId();
        $transfer = $readModel->getTransfer($id);
        if (!$transfer) {
            throw new PointsTransferNotExistException($id->__toString());
        }
        if (!$transfer instanceof AddPointsTransfer) {
            throw new PointsTransferCannotBeUnlockedException($id->__toString());
        }
        $readModel->setTransfer($transfer->unlock());
        $this->repository->save($readModel);
    }

    /**
     * @param PointsHasBeenReset $event
     */
    protected function applyPointsHasBeenReset(PointsHasBeenReset $event): void
    {
        /** @var AccountDetails $readModel */
        $readModel = $this->getReadModel($event->getAccountId());
        $readModel->setPointsResetAt($event->getDate());
        $transfers = $readModel->getAllActiveAndLockedAddPointsTransfers();
        foreach ($transfers as $transfer) {
            $readModel->setTransfer($transfer->expire());
        }
        $this->repository->save($readModel);
    }

    /**
     * @param AccountId       $accountId
     * @param CustomerId|null $customerId
     *
     * @return SerializableReadModel|null|PointsTransferDetails
     */
    private function getReadModel(AccountId $accountId, CustomerId $customerId = null)
    {
        $readModel = $this->repository->find($accountId->__toString());

        if (null === $readModel && $customerId) {
            $readModel = new AccountDetails($accountId, $customerId);
        }

        return $readModel;
    }
}
