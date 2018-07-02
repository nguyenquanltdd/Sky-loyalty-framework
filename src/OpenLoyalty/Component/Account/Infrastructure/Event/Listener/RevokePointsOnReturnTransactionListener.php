<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Infrastructure\Event\Listener;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use Broadway\ReadModel\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use OpenLoyalty\Component\Account\Domain\Command\SpendPoints;
use OpenLoyalty\Component\Account\Domain\Model\PointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\SpendPointsTransfer;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetailsRepository;
use OpenLoyalty\Component\Account\Domain\TransactionId;
use OpenLoyalty\Component\Transaction\Domain\Event\CustomerWasAssignedToTransaction;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Transaction\Domain\Transaction;

/**
 * Class RevokePointsOnReturnTransactionListener.
 */
class RevokePointsOnReturnTransactionListener implements EventListener
{
    /**
     * @var TransactionDetailsRepository
     */
    private $transactionDetailsRepository;

    /**
     * @var PointsTransferDetailsRepository
     */
    private $transfersRepo;

    /**
     * @var Repository
     */
    private $accountDetailsRepository;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var UuidGeneratorInterface
     */
    private $uuidGenerator;

    /**
     * RevokePointsOnReturnTransactionListener constructor.
     *
     * @param TransactionDetailsRepository    $transactionDetailsRepository
     * @param PointsTransferDetailsRepository $transfersRepo
     * @param Repository                      $accountDetailsRepository
     * @param CommandBus                      $commandBus
     * @param UuidGeneratorInterface          $uuidGenerator
     */
    public function __construct(
        TransactionDetailsRepository $transactionDetailsRepository,
        PointsTransferDetailsRepository $transfersRepo,
        Repository $accountDetailsRepository,
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator
    ) {
        $this->transactionDetailsRepository = $transactionDetailsRepository;
        $this->transfersRepo = $transfersRepo;
        $this->accountDetailsRepository = $accountDetailsRepository;
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * @param DomainMessage $domainMessage
     */
    public function handle(DomainMessage $domainMessage)
    {
        $event = $domainMessage->getPayload();
        if ($event instanceof CustomerWasAssignedToTransaction) {
            $this->onCustomerWasAssignedToTransaction($event);
        }
    }

    public function onCustomerWasAssignedToTransaction(CustomerWasAssignedToTransaction $event)
    {
        $transaction = $this->transactionDetailsRepository->find($event->getTransactionId()->__toString());
        if (!$transaction instanceof TransactionDetails) {
            return;
        }
        $revisedTransaction = null;
        if ($transaction->getRevisedDocument() && $transaction->getDocumentType() == Transaction::TYPE_RETURN) {
            $tmp = $this->transactionDetailsRepository->findBy(['documentNumberRaw' => $transaction->getRevisedDocument()]);
            if (count($tmp) > 0) {
                $revisedTransaction = reset($tmp);
            }
        }
        if (!$revisedTransaction instanceof TransactionDetails) {
            return;
        }

        $amount = abs($revisedTransaction->getGrossValue());
        $points = $this->getPointsForTransaction($revisedTransaction);

        $pointsToRevoke = round($points / $amount * abs($transaction->getGrossValue()), 2);

        $account = $this->getAccountDetails($event->getCustomerId()->__toString());
        if (!$account) {
            return;
        }

        if ($this->getAlreadyRevokedPoints($revisedTransaction) >= $points) {
            return;
        }

        $this->commandBus->dispatch(
            new SpendPoints($account->getAccountId(), new SpendPointsTransfer(
                new PointsTransferId($this->uuidGenerator->generate()),
                $pointsToRevoke,
                null,
                false,
                null,
                PointsTransfer::ISSUER_SYSTEM,
                new TransactionId($transaction->getId()),
                new TransactionId($revisedTransaction->getId())
            ))
        );
    }

    private function getAlreadyRevokedPoints(TransactionDetails $transaction)
    {
        $transfers = $this->transfersRepo->findBy([
            'revisedTransactionId' => $transaction->getTransactionId()->__toString(),
            'state' => PointsTransferDetails::STATE_ACTIVE,
            'type' => PointsTransferDetails::TYPE_SPENDING,
        ]);

        return array_reduce($transfers, function ($carry, PointsTransferDetails $transfer) {
            $carry += $transfer->getValue();

            return $carry;
        });
    }

    private function getPointsForTransaction(TransactionDetails $transaction)
    {
        $transfers = $this->transfersRepo->findBy([
            'transactionId' => $transaction->getTransactionId()->__toString(),
            'state' => PointsTransferDetails::STATE_ACTIVE,
            'type' => PointsTransferDetails::TYPE_ADDING,
        ]);

        return array_reduce($transfers, function ($carry, PointsTransferDetails $transfer) {
            $carry += $transfer->getValue();

            return $carry;
        });
    }

    protected function getAccountDetails($customerId)
    {
        $accounts = $this->accountDetailsRepository->findBy(['customerId' => $customerId]);
        if (count($accounts) == 0) {
            return;
        }
        /** @var AccountDetails $account */
        $account = reset($accounts);

        if (!$account instanceof AccountDetails) {
            return;
        }

        return $account;
    }
}
