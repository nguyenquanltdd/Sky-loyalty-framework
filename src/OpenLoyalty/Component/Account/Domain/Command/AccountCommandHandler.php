<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\Command;

use Broadway\CommandHandling\SimpleCommandHandler;
use Broadway\EventDispatcher\EventDispatcher;
use OpenLoyalty\Component\Account\Domain\Account;
use OpenLoyalty\Component\Account\Domain\AccountRepository;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountCreatedSystemEvent;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountSystemEvents;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AvailablePointsAmountChangedSystemEvent;

/**
 * Class AccountCommandHandler.
 */
class AccountCommandHandler extends SimpleCommandHandler
{
    /**
     * @var AccountRepository
     */
    protected $repository;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * AccountCommandHandler constructor.
     *
     * @param AccountRepository $repository
     * @param EventDispatcher   $eventDispatcher
     */
    public function __construct(AccountRepository $repository, EventDispatcher $eventDispatcher = null)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param CreateAccount $command
     */
    public function handleCreateAccount(CreateAccount $command)
    {
        /** @var Account $account */
        $account = Account::createAccount($command->getAccountId(), $command->getCustomerId());
        $this->repository->save($account);
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                AccountSystemEvents::ACCOUNT_CREATED,
                [new AccountCreatedSystemEvent($account->getId(), $command->getCustomerId())]
            );
        }
    }

    /**
     * @param AddPoints $command
     */
    public function handleAddPoints(AddPoints $command)
    {
        /** @var Account $account */
        $account = $this->repository->load($command->getAccountId());
        $pointsTransfer = $command->getPointsTransfer();
        $account->addPoints($pointsTransfer);
        $this->repository->save($account);
        if ($this->eventDispatcher && !$pointsTransfer->isLocked()) {
            $this->eventDispatcher->dispatch(
                AccountSystemEvents::AVAILABLE_POINTS_AMOUNT_CHANGED,
                [
                    new AvailablePointsAmountChangedSystemEvent(
                        $account->getId(),
                        $account->getCustomerId(),
                        $account->getAvailableAmount(),
                        $pointsTransfer->getValue(),
                        AvailablePointsAmountChangedSystemEvent::OPERATION_TYPE_ADD
                    ),
                ]
            );
        }
    }

    /**
     * @param SpendPoints $command
     */
    public function handleSpendPoints(SpendPoints $command)
    {
        /** @var Account $account */
        $account = $this->repository->load($command->getAccountId());
        $account->spendPoints($command->getPointsTransfer());
        $this->repository->save($account);
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                AccountSystemEvents::AVAILABLE_POINTS_AMOUNT_CHANGED,
                [
                    new AvailablePointsAmountChangedSystemEvent(
                        $account->getId(),
                        $account->getCustomerId(),
                        $account->getAvailableAmount(),
                        $command->getPointsTransfer()->getValue()
                    ),
                ]
            );
        }
    }

    /**
     * @param CancelPointsTransfer $command
     */
    public function handleCancelPointsTransfer(CancelPointsTransfer $command)
    {
        /** @var Account $account */
        $account = $this->repository->load($command->getAccountId());
        $account->cancelPointsTransfer($command->getPointsTransferId());
        $this->repository->save($account);
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                AccountSystemEvents::AVAILABLE_POINTS_AMOUNT_CHANGED,
                [
                    new AvailablePointsAmountChangedSystemEvent(
                        $account->getId(),
                        $account->getCustomerId(),
                        $account->getAvailableAmount()
                    ),
                ]
            );
        }
    }

    /**
     * @param ExpirePointsTransfer $command
     */
    public function handleExpirePointsTransfer(ExpirePointsTransfer $command)
    {
        /** @var Account $account */
        $account = $this->repository->load($command->getAccountId());
        $account->expirePointsTransfer($command->getPointsTransferId());
        $this->repository->save($account);
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                AccountSystemEvents::AVAILABLE_POINTS_AMOUNT_CHANGED,
                [
                    new AvailablePointsAmountChangedSystemEvent(
                        $account->getId(),
                        $account->getCustomerId(),
                        $account->getAvailableAmount()
                    ),
                ]
            );
        }
    }

    /**
     * @param UnlockPointsTransfer $command
     */
    public function handleUnlockPointsTransfer(UnlockPointsTransfer $command)
    {
        /** @var Account $account */
        $account = $this->repository->load($command->getAccountId());
        $account->unlockPointsTransfer($command->getPointsTransferId());
        $this->repository->save($account);
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                AccountSystemEvents::AVAILABLE_POINTS_AMOUNT_CHANGED,
                [
                    new AvailablePointsAmountChangedSystemEvent(
                        $account->getId(),
                        $account->getCustomerId(),
                        $account->getAvailableAmount()
                    ),
                ]
            );
        }
    }

    /**
     * @param ResetPoints $command
     */
    public function handleResetPoints(ResetPoints $command)
    {
        /** @var Account $account */
        $account = $this->repository->load($command->getAccountId());
        $account->resetPoints($command->getDate());
        $this->repository->save($account);
    }
}
