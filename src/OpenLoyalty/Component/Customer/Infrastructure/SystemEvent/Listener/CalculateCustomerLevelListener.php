<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Infrastructure\SystemEvent\Listener;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\ReadModel\Repository;
use OpenLoyalty\Bundle\UserBundle\Status\CustomerStatusProvider;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountCreatedSystemEvent;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AvailablePointsAmountChangedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\Command\MoveCustomerToLevel;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Account\Domain\CustomerId as AccountCustomerId;
use OpenLoyalty\Component\Customer\Domain\LevelId as CustomerLevelId;
use OpenLoyalty\Component\Customer\Domain\LevelIdProvider;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerLevelChangedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerRecalculateLevelRequestedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerRemovedManuallyLevelSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerSystemEvents;
use OpenLoyalty\Component\Customer\Domain\TransactionId;
use OpenLoyalty\Component\Level\Domain\LevelId;
use OpenLoyalty\Component\Customer\Infrastructure\Exception\LevelDowngradeModeNotSupportedException;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Customer\Infrastructure\ExcludeDeliveryCostsProvider;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use OpenLoyalty\Component\Level\Domain\Level;
use OpenLoyalty\Component\Level\Domain\LevelRepository;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\CustomerAssignedToTransactionSystemEvent;

/**
 * Class CalculateCustomerLevelListener.
 */
class CalculateCustomerLevelListener
{
    /**
     * @var LevelIdProvider
     */
    protected $levelIdProvider;

    /**
     * @var CustomerDetailsRepository
     */
    protected $customerDetailsRepository;

    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var TierAssignTypeProvider
     */
    protected $tierAssignTypeProvider;

    /**
     * @var ExcludeDeliveryCostsProvider
     */
    protected $excludeDeliveryCostsProvider;

    /**
     * @var LevelRepository
     */
    protected $levelRepository;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var CustomerStatusProvider
     */
    protected $customerStatusProvider;

    /**
     * @var LevelDowngradeModeProvider
     */
    protected $levelDowngradeModeProvider;

    /**
     * @var Repository
     */
    private $accountDetailsRepository;

    /**
     * CalculateCustomerLevelListener constructor.
     *
     * @param LevelIdProvider              $levelIdProvider
     * @param CustomerDetailsRepository    $customerDetailsRepository
     * @param CommandBus                   $commandBus
     * @param TierAssignTypeProvider       $tierAssignTypeProvider
     * @param ExcludeDeliveryCostsProvider $excludeDeliveryCostsProvider
     * @param LevelRepository              $levelRepository
     * @param EventDispatcher              $eventDispatcher
     * @param CustomerStatusProvider       $customerStatusProvider
     * @param LevelDowngradeModeProvider   $levelDowngradeModeProvider
     * @param Repository                   $accountDetailsRepository
     */
    public function __construct(
        LevelIdProvider $levelIdProvider,
        CustomerDetailsRepository $customerDetailsRepository,
        CommandBus $commandBus,
        TierAssignTypeProvider $tierAssignTypeProvider,
        ExcludeDeliveryCostsProvider $excludeDeliveryCostsProvider,
        LevelRepository $levelRepository,
        EventDispatcher $eventDispatcher,
        CustomerStatusProvider $customerStatusProvider,
        LevelDowngradeModeProvider $levelDowngradeModeProvider,
        Repository $accountDetailsRepository
    ) {
        $this->levelIdProvider = $levelIdProvider;
        $this->customerDetailsRepository = $customerDetailsRepository;
        $this->commandBus = $commandBus;
        $this->tierAssignTypeProvider = $tierAssignTypeProvider;
        $this->excludeDeliveryCostsProvider = $excludeDeliveryCostsProvider;
        $this->levelRepository = $levelRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->customerStatusProvider = $customerStatusProvider;
        $this->levelDowngradeModeProvider = $levelDowngradeModeProvider;
        $this->accountDetailsRepository = $accountDetailsRepository;
    }

    /**
     * @param $event
     */
    public function handle($event)
    {
        if ($event instanceof CustomerRecalculateLevelRequestedSystemEvent) {
            $this->handleRecalculateLevel($event);
        } elseif ($event instanceof AccountCreatedSystemEvent) {
            $this->handleAccountCreated($event);
        } elseif ($event instanceof CustomerRemovedManuallyLevelSystemEvent) {
            $this->handleRemovedManuallyLevel($event);
        } elseif ($this->tierAssignTypeProvider->getType() == TierAssignTypeProvider::TYPE_POINTS && $event instanceof AvailablePointsAmountChangedSystemEvent) {
            $this->handlePoints($event->getCustomerId(), $event->getCurrentAmount());
        } elseif ($this->tierAssignTypeProvider->getType() == TierAssignTypeProvider::TYPE_TRANSACTIONS && $event instanceof CustomerAssignedToTransactionSystemEvent) {
            $this->handleTransaction($event);
        }
    }

    /**
     * @param CustomerRecalculateLevelRequestedSystemEvent $event
     */
    protected function handleRecalculateLevel(CustomerRecalculateLevelRequestedSystemEvent $event): void
    {
        $customerIdString = $event->getCustomerId()->__toString();
        /** @var CustomerDetails $customer */
        $customer = $this->customerDetailsRepository->find($customerIdString);
        $account = $this->getAccountDetails($customerIdString);
        if (!$account) {
            return;
        }
        if ($this->levelDowngradeModeProvider->getBase() === LevelDowngradeModeProvider::BASE_ACTIVE_POINTS) {
            $currentAmount = $account->getAvailableAmount();
        } elseif ($this->levelDowngradeModeProvider->getBase() == LevelDowngradeModeProvider::BASE_EARNED_POINTS) {
            $currentAmount = $account->getEarnedAmountSince($customer->getLastLevelRecalculation() ?: $customer->getCreatedAt());
        } elseif ($this->levelDowngradeModeProvider->getBase() == LevelDowngradeModeProvider::BASE_EARNED_POINTS_SINCE_LAST_LEVEL_CHANGE) {
            $currentAmount = $account->getEarnedAmountSince($customer->getLastLevelRecalculation() ?: $customer->getCreatedAt());
        }

        if (isset($currentAmount)) {
            $this->handlePoints(new AccountCustomerId($customerIdString), $currentAmount, true);
        }
    }

    /**
     * @param string $customerId
     *
     * @return AccountDetails|null
     */
    protected function getAccountDetails(string $customerId): ?AccountDetails
    {
        $accounts = $this->accountDetailsRepository->findBy(['customerId' => $customerId]);
        if (count($accounts) == 0) {
            return null;
        }
        /** @var AccountDetails $account */
        $account = reset($accounts);

        if (!$account instanceof AccountDetails) {
            return null;
        }

        return $account;
    }

    /**
     * @param CustomerRemovedManuallyLevelSystemEvent $event
     */
    protected function handleRemovedManuallyLevel(CustomerRemovedManuallyLevelSystemEvent $event): void
    {
        $customerId = $event->getCustomerId();
        $status = $this->customerStatusProvider->getStatus($customerId);
        $currentAmount = $status->getPoints() ?? 0;

        /** @var CustomerDetails $customer */
        $customer = $this->customerDetailsRepository->find($customerId->__toString());

        $levelId = $this->levelIdProvider->findLevelIdByConditionValueWithTheBiggestReward($currentAmount);

        /** @var Level $level */
        $level = $this->levelRepository->byId(new LevelId($levelId));

        $this->commandBus->dispatch(
            new MoveCustomerToLevel(
                new CustomerId($customerId->__toString()),
                $levelId ? new CustomerLevelId($levelId) : null,
                $levelId ? $level->getName() : null,
                true,
                true
            )
        );

        $this->eventDispatcher->dispatch(CustomerSystemEvents::CUSTOMER_LEVEL_CHANGED, [
            new CustomerLevelChangedSystemEvent(
                $customer->getCustomerId(),
                new CustomerLevelId($levelId),
                $level->getName()
            ),
        ]);
    }

    /**
     * @param CustomerAssignedToTransactionSystemEvent $event
     */
    protected function handleTransaction(CustomerAssignedToTransactionSystemEvent $event): void
    {
        $transactionId = $event->getTransactionId();
        $customerId = $event->getCustomerId();

        /** @var CustomerDetails $customer */
        $customer = $this->customerDetailsRepository->find($customerId->__toString());

        if (!$customer) {
            return;
        }

        if ($this->excludeDeliveryCostsProvider->areExcluded()) {
            $currentAmount = $customer->getTransactionsAmountWithoutDeliveryCosts() - $customer->getAmountExcludedForLevel();
            if (!$customer->hasTransactionId(new TransactionId($transactionId->__toString()))) {
                $currentAmount += $event->getGrossValueWithoutDeliveryCosts() - $event->getAmountExcludedForLevel();
            }
        } else {
            $currentAmount = $customer->getTransactionsAmount() - $customer->getAmountExcludedForLevel();

            if (!$customer->hasTransactionId(new TransactionId($transactionId->__toString()))) {
                $currentAmount += $event->getGrossValue() - $event->getAmountExcludedForLevel();
            }
        }

        /** @var Level $currentLevel */
        $currentLevel = $customer->getLevelId()
            ? $this->levelRepository->byId(new LevelId($customer->getLevelId()->__toString()))
            : null;

        if (!$levelId = $this->levelIdProvider->findLevelIdByConditionValueWithTheBiggestReward($currentAmount)) {
            return;
        }

        /** @var Level $level */
        $level = $this->levelRepository->byId(new LevelId($levelId));

        // if new level is better than old one -> move customer
        if (!$currentLevel || $currentLevel->getReward()->getValue() < $level->getReward()->getValue()) {
            if (!$customer->getLevelId() || (string) $customer->getLevelId() !== $levelId) {
                $this->commandBus->dispatch(
                    new MoveCustomerToLevel(
                        new CustomerId($customerId->__toString()),
                        new CustomerLevelId($levelId),
                        $level->getName()
                    )
                );

                $this->eventDispatcher->dispatch(CustomerSystemEvents::CUSTOMER_LEVEL_CHANGED_AUTOMATICALLY, [
                    new CustomerLevelChangedSystemEvent(
                        $customer->getCustomerId(),
                        new CustomerLevelId($levelId),
                        $level->getName(),
                        (!$currentLevel || $currentLevel->getReward()->getValue() < $level->getReward()->getValue())
                    ),
                ]);
            }

            return;
        }
        // new level is worst
        $newLevelId = $levelId;

        if ($customer->getManuallyAssignedLevelId()) {
            $manualId = $customer->getManuallyAssignedLevelId()->__toString();
            if ($manualId == $currentLevel->getLevelId()->__toString()) {
                return;
            }
            /** @var Level $manual */
            $manual = $this->levelRepository->byId(new \OpenLoyalty\Component\Level\Domain\LevelId($manualId));
            if ($manual->getReward()->getValue() > $level->getReward()->getValue()) {
                $newLevelId = $manualId;
            }
        }

        if (!$currentLevel || $currentLevel->getLevelId()->__toString() !== $newLevelId) {
            $this->commandBus->dispatch(
                new MoveCustomerToLevel(
                    new CustomerId($customerId->__toString()),
                    new CustomerLevelId($newLevelId),
                    $level->getName()
                )
            );

            $this->eventDispatcher->dispatch(CustomerSystemEvents::CUSTOMER_LEVEL_CHANGED_AUTOMATICALLY, [
                new CustomerLevelChangedSystemEvent(
                    $customer->getCustomerId(),
                    new CustomerLevelId($newLevelId),
                    $level->getName(),
                    (!$currentLevel || $currentLevel->getReward()->getValue() < $level->getReward()->getValue())
                ),
            ]);
        }
    }

    /**
     * @param AccountCustomerId $customerId
     * @param float             $currentAmount
     * @param bool              $isRecalculation
     */
    protected function handlePoints(AccountCustomerId $customerId, float $currentAmount, bool $isRecalculation = false): void
    {
        /** @var CustomerDetails $customer */
        $customer = $this->customerDetailsRepository->find($customerId->__toString());

        /** @var Level $currentLevel */
        $currentLevel = $customer->getLevelId()
            ? $this->levelRepository->byId(new LevelId((string) $customer->getLevelId()))
            : null;

        $levelId = $this->levelIdProvider->findLevelIdByConditionValueWithTheBiggestReward($currentAmount);
        if (!$levelId) {
            return;
        }

        /** @var Level $level */
        $level = $this->levelRepository->byId(new LevelId($levelId));

        if ($currentLevel && $currentLevel->getReward()->getValue() >= $level->getReward()->getValue()) {
            $downgradedLevelId = $this->handlePointsDowngrade($level, $customer, $currentLevel, $isRecalculation);

            if (null !== $downgradedLevelId) {
                $levelId = $downgradedLevelId;
            }
        }

        if (!$customer->getLevelId() || (string) $customer->getLevelId() !== $levelId) {
            $this->commandBus->dispatch(
                new MoveCustomerToLevel(
                    new CustomerId((string) $customerId),
                    new CustomerLevelId($levelId),
                    $level->getName()
                )
            );

            $this->eventDispatcher->dispatch(CustomerSystemEvents::CUSTOMER_LEVEL_CHANGED_AUTOMATICALLY, [
                new CustomerLevelChangedSystemEvent(
                    $customer->getCustomerId(),
                    new CustomerLevelId($levelId),
                    $level->getName(),
                    (!$currentLevel || $currentLevel->getReward()->getValue() < $level->getReward()->getValue())
                ),
            ]);
        }
    }

    /**
     * @param Level           $calculatedLevel
     * @param CustomerDetails $customer
     * @param Level           $currentLevel
     * @param bool            $isRecalculation
     *
     * @return null|string
     */
    protected function handlePointsDowngrade(Level $calculatedLevel, CustomerDetails $customer, Level $currentLevel, bool $isRecalculation = false): ?string
    {
        try {
            $mode = $this->levelDowngradeModeProvider->getMode();
        } catch (LevelDowngradeModeNotSupportedException $e) {
            $mode = LevelDowngradeModeProvider::MODE_NONE;
        }

        if (LevelDowngradeModeProvider::MODE_NONE === $mode) {
            return $currentLevel->getLevelId()->__toString();
        }

        if (LevelDowngradeModeProvider::MODE_AUTO === $mode) {
            if ($customer->getManuallyAssignedLevelId()) {
                $manualId = $customer->getManuallyAssignedLevelId()->__toString();
                /** @var Level $manual */
                $manual = $this->levelRepository->byId(new \OpenLoyalty\Component\Level\Domain\LevelId($manualId));
                if ($manual->getReward()->getValue() > $calculatedLevel->getReward()->getValue()) {
                    return $manualId;
                }
            }

            return $calculatedLevel->getLevelId()->__toString();
        }

        if (LevelDowngradeModeProvider::MODE_X_DAYS === $mode) {
            if (!$isRecalculation) {
                return $currentLevel->getLevelId()->__toString();
            } else {
                if ($customer->getManuallyAssignedLevelId()) {
                    $manualId = $customer->getManuallyAssignedLevelId()->__toString();
                    /** @var Level $manual */
                    $manual = $this->levelRepository->byId(new \OpenLoyalty\Component\Level\Domain\LevelId($manualId));
                    if ($manual->getReward()->getValue() > $calculatedLevel->getReward()->getValue()) {
                        return $manualId;
                    }
                }

                return $calculatedLevel->getLevelId()->__toString();
            }
        }

        return null;
    }

    /**
     * @param AccountCreatedSystemEvent $event
     */
    protected function handleAccountCreated(AccountCreatedSystemEvent $event): void
    {
        if (null === $customerId = $event->getCustomerId()) {
            return;
        }

        $currentAmount = 0;

        /** @var CustomerDetails $customer */
        $customer = $this->customerDetailsRepository->find((string) $customerId);

        $levelId = $this->levelIdProvider->findLevelIdByConditionValueWithTheBiggestReward($currentAmount);
        if (!$levelId) {
            $this->commandBus->dispatch(
                new MoveCustomerToLevel(new CustomerId((string) $customerId))
            );

            return;
        }

        /** @var Level $level */
        $level = $this->levelRepository->byId(new LevelId($levelId));

        if (!$customer->getLevelId() || (string) $customer->getLevelId() !== $levelId) {
            $this->commandBus->dispatch(
                new MoveCustomerToLevel(
                    new CustomerId((string) $customerId),
                    new CustomerLevelId($levelId),
                    $level->getName()
                )
            );
        }
    }
}
