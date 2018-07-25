<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\ReadModel;

use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Repository;
use OpenLoyalty\Component\Account\Domain\Account;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenCanceled;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenExpired;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenUnlocked;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereAdded;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereSpent;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeCanceledException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeExpiredException;
use OpenLoyalty\Component\Account\Domain\Exception\PointsTransferCannotBeUnlockedException;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Pos\Domain\Pos;
use OpenLoyalty\Component\Pos\Domain\PosId;
use OpenLoyalty\Component\Pos\Domain\PosRepository;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;

/**
 * Class PointsTransferDetailsProjector.
 */
class PointsTransferDetailsProjector extends Projector
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var Repository
     */
    private $accountRepository;

    /**
     * @var Repository
     */
    private $customerRepository;

    /**
     * @var TransactionDetailsRepository
     */
    private $transactionDetailsRepository;

    /**
     * @var PosRepository
     */
    private $posRepository;

    /**
     * PointsTransferDetailsProjector constructor.
     *
     * @param Repository                      $repository
     * @param Repository                      $accountRepository
     * @param Repository                      $customerRepository
     * @param TransactionDetailsRepository    $transactionDetailsRepository
     * @param PosRepository                   $posRepository
     * @param GeneralSettingsManagerInterface $settingsManager
     */
    public function __construct(
        Repository $repository,
        Repository $accountRepository,
        Repository $customerRepository,
        TransactionDetailsRepository $transactionDetailsRepository,
        PosRepository $posRepository
    ) {
        $this->repository = $repository;
        $this->accountRepository = $accountRepository;
        $this->customerRepository = $customerRepository;
        $this->transactionDetailsRepository = $transactionDetailsRepository;
        $this->posRepository = $posRepository;
    }

    /**
     * @param PointsWereAdded $event
     */
    protected function applyPointsWereAdded(PointsWereAdded $event)
    {
        $transfer = $event->getPointsTransfer();
        $id = $transfer->getId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        $readModel->setValue($transfer->getValue());
        $readModel->setCreatedAt($transfer->getCreatedAt());
        $readModel->setExpiresAt($transfer->getExpiresAt());
        $readModel->setLockedUntil($transfer->getLockedUntil());

        if ($transfer->isCanceled()) {
            $state = PointsTransferDetails::STATE_CANCELED;
        } elseif ($transfer->isExpired()) {
            $state = PointsTransferDetails::STATE_EXPIRED;
        } elseif ($transfer->isLocked()) {
            $state = PointsTransferDetails::STATE_PENDING;
        } else {
            $state = PointsTransferDetails::STATE_ACTIVE;
        }
        $readModel->setState($state);
        $readModel->setType(PointsTransferDetails::TYPE_ADDING);
        $readModel->setTransactionId($transfer->getTransactionId());
        $readModel->setIssuer($transfer->getIssuer());
        if ($transfer->getTransactionId()) {
            $transaction = $this->transactionDetailsRepository->find($transfer->getTransactionId()->__toString());
            if ($transaction instanceof TransactionDetails && $transaction->getPosId()) {
                $pos = $this->posRepository->byId(new PosId($transaction->getPosId()->__toString()));
                if ($pos instanceof Pos) {
                    $readModel->setPosIdentifier($pos->getIdentifier());
                }
            }
        }
        $readModel->setComment($transfer->getComment());
        $this->repository->save($readModel);
    }

    /**
     * @param PointsWereSpent $event
     */
    protected function applyPointsWereSpent(PointsWereSpent $event)
    {
        $transfer = $event->getPointsTransfer();
        $id = $transfer->getId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        $readModel->setValue($transfer->getValue());
        $readModel->setCreatedAt($transfer->getCreatedAt());
        $readModel->setExpiresAt($transfer->getCreatedAt());
        $readModel->setState($transfer->isCanceled() ? PointsTransferDetails::STATE_CANCELED : PointsTransferDetails::STATE_ACTIVE);
        $readModel->setType(PointsTransferDetails::TYPE_SPENDING);
        $readModel->setComment($transfer->getComment());
        $readModel->setIssuer($transfer->getIssuer());
        $readModel->setTransactionId($transfer->getTransactionId());
        $readModel->setRevisedTransactionId($transfer->getRevisedTransactionId());
        if ($transfer->getTransactionId()) {
            $transaction = $this->transactionDetailsRepository->find($transfer->getTransactionId()->__toString());
            if ($transaction instanceof TransactionDetails && $transaction->getPosId()) {
                $pos = $this->posRepository->byId(new PosId($transaction->getPosId()->__toString()));
                if ($pos instanceof Pos) {
                    $readModel->setPosIdentifier($pos->getIdentifier());
                }
            }
        }
        $this->repository->save($readModel);
    }

    /**
     * @param PointsTransferHasBeenCanceled $event
     *
     * @throws PointsTransferCannotBeCanceledException
     */
    protected function applyPointsTransferHasBeenCanceled(PointsTransferHasBeenCanceled $event)
    {
        $id = $event->getPointsTransferId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        if ($readModel->getType() !== PointsTransferDetails::TYPE_ADDING) {
            throw new PointsTransferCannotBeCanceledException($id->__toString());
        }
        $readModel->setState(PointsTransferDetails::STATE_CANCELED);
        $this->repository->save($readModel);
    }

    /**
     * @param PointsTransferHasBeenExpired $event
     */
    protected function applyPointsTransferHasBeenExpired(PointsTransferHasBeenExpired $event)
    {
        $id = $event->getPointsTransferId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        if ($readModel->getType() != PointsTransferDetails::TYPE_ADDING) {
            throw new PointsTransferCannotBeExpiredException($id->__toString());
        }
        $readModel->setState(PointsTransferDetails::STATE_EXPIRED);
        $this->repository->save($readModel);
    }

    /**
     * @param PointsTransferHasBeenUnlocked $event
     */
    protected function applyPointsTransferHasBeenUnlocked(PointsTransferHasBeenUnlocked $event)
    {
        $id = $event->getPointsTransferId();
        /** @var PointsTransferDetails $readModel */
        $readModel = $this->getReadModel($id, $event->getAccountId());
        if ($readModel->getType() != PointsTransferDetails::TYPE_ADDING) {
            throw new PointsTransferCannotBeUnlockedException($id->__toString());
        }
        $readModel->setState(PointsTransferDetails::STATE_ACTIVE);
        $this->repository->save($readModel);
    }

    /**
     * @param PointsTransferId $pointsTransferId
     * @param AccountId        $accountId
     *
     * @return PointsTransferDetails
     */
    private function getReadModel(PointsTransferId $pointsTransferId, AccountId $accountId)
    {
        $readModel = $this->repository->find($pointsTransferId->__toString());

        if (null === $readModel) {
            /** @var Account $account */
            $account = $this->accountRepository->find($accountId->__toString());
            /** @var CustomerDetails $customer */
            $customer = $this->customerRepository->find($account->getCustomerId()->__toString());
            $readModel = new PointsTransferDetails($pointsTransferId, $account->getCustomerId(), $accountId);
            $readModel->setCustomerEmail($customer->getEmail());
            $readModel->setCustomerFirstName($customer->getFirstName());
            $readModel->setCustomerLastName($customer->getLastName());
            $readModel->setCustomerPhone($customer->getPhone());
            $readModel->setCustomerLoyaltyCardNumber($customer->getLoyaltyCardNumber());
        }

        return $readModel;
    }
}
