<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Transaction\Domain\Command;

use Broadway\CommandHandling\SimpleCommandHandler;
use Broadway\EventDispatcher\EventDispatcher;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\TransactionRegisteredEvent;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\TransactionSystemEvents;
use OpenLoyalty\Component\Transaction\Domain\Transaction;
use OpenLoyalty\Component\Transaction\Domain\TransactionRepository;

/**
 * Class TransactionCommandHandler.
 */
class TransactionCommandHandler extends SimpleCommandHandler
{
    /**
     * @var TransactionRepository
     */
    protected $repository;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * TransactionCommandHandler constructor.
     *
     * @param TransactionRepository $repository
     * @param EventDispatcher       $eventDispatcher
     */
    public function __construct(TransactionRepository $repository, EventDispatcher $eventDispatcher)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param RegisterTransaction $command
     */
    public function handleRegisterTransaction(RegisterTransaction $command)
    {
        $transaction = Transaction::createTransaction(
            $command->getTransactionId(),
            $command->getTransactionData(),
            $command->getCustomerData(),
            $command->getItems(),
            $command->getPosId(),
            $command->getExcludedDeliverySKUs(),
            $command->getExcludedLevelSKUs(),
            $command->getExcludedCategories(),
            $command->getRevisedDocument()
        );

        $this->repository->save($transaction);

        $this->eventDispatcher->dispatch(
            TransactionSystemEvents::TRANSACTION_REGISTERED,
            [new TransactionRegisteredEvent(
                $command->getTransactionId(),
                $command->getTransactionData(),
                $command->getCustomerData(),
                $command->getItems(),
                $command->getPosId()
            )]
        );
    }

    /**
     * @param AssignCustomerToTransaction $command
     */
    public function handleAssignCustomerToTransaction(AssignCustomerToTransaction $command)
    {
        /** @var Transaction $transaction */
        $transaction = $this->repository->load($command->getTransactionId()->__toString());
        $transaction->assignCustomerToTransaction($command->getCustomerId());
        $this->repository->save($transaction);
    }
}
