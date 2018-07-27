<?php

namespace OpenLoyalty\Component\Customer\Tests\Infrastructure\SystemEvent\Listener;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\ReadModel\Repository;
use OpenLoyalty\Bundle\UserBundle\Model\CustomerStatus;
use OpenLoyalty\Bundle\UserBundle\Status\CustomerStatusProvider;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Account\Domain\CustomerId as AccountCustomerId;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountCreatedSystemEvent;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AvailablePointsAmountChangedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\Command\MoveCustomerToLevel;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\LevelId;
use OpenLoyalty\Component\Customer\Domain\LevelId as CustomerLevelId;
use OpenLoyalty\Component\Customer\Domain\LevelIdProvider;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerRecalculateLevelRequestedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerRemovedManuallyLevelSystemEvent;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Customer\Infrastructure\ExcludeDeliveryCostsProvider;
use OpenLoyalty\Component\Customer\Infrastructure\SystemEvent\Listener\CalculateCustomerLevelListener;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use OpenLoyalty\Component\Level\Domain\Level;
use OpenLoyalty\Component\Level\Domain\LevelId as LevelLevelId;
use OpenLoyalty\Component\Level\Domain\LevelRepository;
use OpenLoyalty\Component\Level\Domain\Model\Reward;
use OpenLoyalty\Component\Transaction\Domain\CustomerId as TransactionCustomerId;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\CustomerAssignedToTransactionSystemEvent;
use OpenLoyalty\Component\Transaction\Domain\TransactionId;

/**
 * Class CalculateCustomerLevelListenerTest.
 */
class CalculateCustomerLevelListenerTest extends \PHPUnit_Framework_TestCase
{
    const LEVEL_WITH_REWARD_10_FROM_0 = '00000000-0000-0000-0000-000000000000';
    const LEVEL_WITH_REWARD_200_FROM_20 = '00000000-0000-0000-0000-000000000001';
    const LEVEL_WITH_REWARD_300_FROM_30 = '00000000-0000-0000-0000-000000000002';

    /**
     * @test
     */
    public function it_moves_customer_to_correct_level_after_remove_manually_level()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $level = new Level($levelId, 'test', 10);

        /** @var CommandBus|\PHPUnit_Framework_MockObject_MockObject $commandBus */
        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->once())->method('dispatch')->with(
            $this->equalTo(
                new MoveCustomerToLevel(
                    new CustomerId($customerId),
                    new LevelId($levelId->__toString()),
                    $level->getName(),
                    true,
                    true
                )
            )
        );

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository(),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_TRANSACTIONS),
            $this->getExcludeDeliveryCostsProvider(false),
            $this->getLevelRepository(),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(100),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(100)
        );

        $listener->handle(new CustomerRemovedManuallyLevelSystemEvent(
            new CustomerId($customerId)
        ));
    }

    /**
     * @test
     */
    public function it_moves_customer_to_correct_level_by_transaction()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $level = new Level($levelId, 'test', 10);

        /** @var CommandBus|\PHPUnit_Framework_MockObject_MockObject $commandBus */
        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->once())->method('dispatch')->with(
            $this->equalTo(
                new MoveCustomerToLevel(
                    new CustomerId($customerId),
                    new LevelId($levelId->__toString()),
                    $level->getName(),
                    false,
                    false
                )
            )
        );

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository(),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_TRANSACTIONS),
            $this->getExcludeDeliveryCostsProvider(false),
            $this->getLevelRepository(),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(100)
        );

        $listener->handle(new CustomerAssignedToTransactionSystemEvent(
            new TransactionId('00000000-0000-0000-0000-000000000000'),
            new TransactionCustomerId($customerId),
            20,
            20
        ));
    }

    /**
     * @test
     */
    public function it_does_not_move_customer_level_by_transaction_if_cur_level_is_the_same_like_target()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelIdString = '00000000-0000-0000-0000-000000000003';
        $levelId = new LevelLevelId($levelIdString);
        $level = new Level($levelId, 'test', 10);
        $level->setReward(new Reward('level_0_reward', 10, 'level'));

        /** @var CommandBus|\PHPUnit_Framework_MockObject_MockObject $commandBus */
        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->never())->method('dispatch');

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository(new CustomerLevelId($levelIdString)),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_TRANSACTIONS),
            $this->getExcludeDeliveryCostsProvider(false),
            $this->getLevelRepository($level),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(100)
        );

        $listener->handle(new CustomerAssignedToTransactionSystemEvent(
            new TransactionId('00000000-0000-0000-0000-000000000000'),
            new TransactionCustomerId($customerId),
            20,
            20
        ));
    }

    /**
     * @test
     * @dataProvider getLevelsWithAssignedProvider
     *
     * @param CustomerLevelId $currentLevelId
     * @param CustomerLevelId $assignedLevelId
     * @param $transactionAmount
     * @param CustomerLevelId|null $resultLevelId
     */
    public function it_moves_customer_to_correct_level_by_transaction_with_manually_assigned_level(
        CustomerLevelId $currentLevelId,
        CustomerLevelId $assignedLevelId,
        $transactionAmount,
        string $levelName = null,
        CustomerLevelId $resultLevelId = null
    ): void {
        $levels = $this->getSampleLevels();
        $levelsRepo = $this->getLevelRepositoryWithArray($levels);

        $customerId = '00000000-0000-0000-0000-000000000000';

        /** @var CommandBus|\PHPUnit_Framework_MockObject_MockObject $commandBus */
        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        if ($resultLevelId == null) {
            $commandBus->expects($this->never())->method('dispatch');
        } else {
            $commandBus->expects($this->once())->method('dispatch')->with(
                $this->equalTo(
                    new MoveCustomerToLevel(
                        new CustomerId($customerId),
                        $resultLevelId,
                        $levelName,
                        false,
                        false
                    )
                )
            );
        }

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($levels),
            $this->getCustomerDetailsRepository($currentLevelId, $assignedLevelId),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_TRANSACTIONS),
            $this->getExcludeDeliveryCostsProvider(false),
            $levelsRepo,
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(100)
        );

        $listener->handle(new CustomerAssignedToTransactionSystemEvent(
            new TransactionId('00000000-0000-0000-0000-000000000000'),
            new TransactionCustomerId($customerId),
            $transactionAmount,
            $transactionAmount
        ));
    }

    /**
     * @test
     */
    public function it_moves_customer_to_correct_level_on_registration()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $level = new Level($levelId, 'test', 0);

        /** @var CommandBus|\PHPUnit_Framework_MockObject_MockObject $commandBus */
        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->once())->method('dispatch')->with(
            $this->equalTo(
                new MoveCustomerToLevel(
                    new CustomerId($customerId),
                    new LevelId($levelId->__toString()),
                    $level->getName(),
                    false,
                    false
                )
            )
        );

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository(),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_TRANSACTIONS),
            $this->getExcludeDeliveryCostsProvider(false),
            $this->getLevelRepository(),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(100)
        );

        $listener->handle(new AccountCreatedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId)
        ));
    }

    /**
     * @test
     */
    public function it_moves_customer_to_correct_level_by_points(): void
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $level = new Level($levelId, 'test', 10);

        /** @var CommandBus|\PHPUnit_Framework_MockObject_MockObject $commandBus */
        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->once())->method('dispatch')->with(
            $this->equalTo(
                new MoveCustomerToLevel(
                    new CustomerId($customerId),
                    new LevelId($levelId->__toString()),
                    $level->getName(),
                    false,
                    false
                )
            )
        );

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository(),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepository(),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(100)
        );
        $listener->handle(new AvailablePointsAmountChangedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId),
            20,
            20
        ));
    }

    /**
     * @test
     */
    public function it_does_not_downgrade_when_no_downgrade_mode()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $customerLevelId = new CustomerLevelId('00000000-0000-0000-0000-000000000002');
        $level = new Level($levelId, 'test', 10);
        $customerLevel = new Level(new LevelLevelId($customerLevelId->__toString()), 'test2', 15);
        $customerReward = new Reward('as2', 20, 'as');
        $reward = new Reward('as', 10, 'as');
        $level->setReward($reward);
        $customerLevel->setReward($customerReward);

        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->never())->method('dispatch');

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository($customerLevelId),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepositoryWithArray([
                $level->getLevelId()->__toString() => $level,
                $customerLevel->getLevelId()->__toString() => $customerLevel,
            ]),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(100)
        );
        $listener->handle(new AvailablePointsAmountChangedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId),
            11,
            20
        ));
    }

    /**
     * @test
     */
    public function it_does_not_downgrade_when_no_downgrade_mode_on_recalculate_event()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $customerLevelId = new CustomerLevelId('00000000-0000-0000-0000-000000000002');
        $level = new Level($levelId, 'test', 10);
        $customerLevel = new Level(new LevelLevelId($customerLevelId->__toString()), 'test2', 15);
        $customerReward = new Reward('as2', 20, 'as');
        $reward = new Reward('as', 10, 'as');
        $level->setReward($reward);
        $customerLevel->setReward($customerReward);

        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->never())->method('dispatch');

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository($customerLevelId),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepositoryWithArray([
                $level->getLevelId()->__toString() => $level,
                $customerLevel->getLevelId()->__toString() => $customerLevel,
            ]),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(11)
        );
        $listener->handle(new CustomerRecalculateLevelRequestedSystemEvent(
            new CustomerId($customerId)
        ));
    }

    /**
     * @test
     */
    public function it_downgrade_when_after_x_days_downgrade_mode_on_recalculate_event()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $customerLevelId = new CustomerLevelId('00000000-0000-0000-0000-000000000002');
        $level = new Level($levelId, 'test', 10);
        $customerLevel = new Level(new LevelLevelId($customerLevelId->__toString()), 'test2', 15);
        $customerReward = new Reward('as2', 20, 'as');
        $reward = new Reward('as', 10, 'as');
        $level->setReward($reward);
        $customerLevel->setReward($customerReward);

        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->once())->method('dispatch')->with(
            $this->equalTo(
                new MoveCustomerToLevel(
                    new CustomerId($customerId),
                    new LevelId($levelId->__toString()),
                    'test',
                    false,
                    false
                )
            )
        );

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository($customerLevelId),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepositoryWithArray([
                $level->getLevelId()->__toString() => $level,
                $customerLevel->getLevelId()->__toString() => $customerLevel,
            ]),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_X_DAYS),
            $this->getAccountDetailsRepository(11)
        );
        $listener->handle(new CustomerRecalculateLevelRequestedSystemEvent(
            new CustomerId($customerId)
        ));
    }

    /**
     * @test
     */
    public function it_downgrade_when_after_x_days_downgrade_mode_on_recalculate_event_using_earned_points()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $customerLevelId = new CustomerLevelId('00000000-0000-0000-0000-000000000002');
        $level = new Level($levelId, 'test', 10);
        $customerLevel = new Level(new LevelLevelId($customerLevelId->__toString()), 'test2', 15);
        $customerReward = new Reward('as2', 20, 'as');
        $reward = new Reward('as', 10, 'as');
        $level->setReward($reward);
        $customerLevel->setReward($customerReward);

        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->once())->method('dispatch')->with(
            $this->equalTo(
                new MoveCustomerToLevel(
                    new CustomerId($customerId),
                    new LevelId($levelId->__toString()),
                    'test',
                    false,
                    false
                )
            )
        );

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider([$level, $customerLevel]),
            $this->getCustomerDetailsRepository($customerLevelId),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepositoryWithArray([
                $level->getLevelId()->__toString() => $level,
                $customerLevel->getLevelId()->__toString() => $customerLevel,
            ]),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_X_DAYS, LevelDowngradeModeProvider::BASE_EARNED_POINTS),
            $this->getAccountDetailsRepository(1000, 11)
        );
        $listener->handle(new CustomerRecalculateLevelRequestedSystemEvent(
            new CustomerId($customerId)
        ));
    }

    /**
     * @test
     */
    public function it_does_not_downgrade_when_automatic_downgrade_mode_and_manually_assigned()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $customerLevelId = new CustomerLevelId('00000000-0000-0000-0000-000000000002');
        $level = new Level($levelId, 'test', 10);
        $customerLevel = new Level(new LevelLevelId($customerLevelId->__toString()), 'test2', 15);
        $customerReward = new Reward('as2', 20, 'as');
        $reward = new Reward('as', 10, 'as');
        $level->setReward($reward);
        $customerLevel->setReward($customerReward);

        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->never())->method('dispatch');

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository($customerLevelId, $customerLevelId),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepositoryWithArray([
                $level->getLevelId()->__toString() => $level,
                $customerLevel->getLevelId()->__toString() => $customerLevel,
            ]),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_AUTO),
            $this->getAccountDetailsRepository(100)
        );
        $listener->handle(new AvailablePointsAmountChangedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId),
            11,
            20
        ));
    }

    /**
     * @test
     */
    public function it_does_not_downgrade_when_x_days_downgrade_mode()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $customerLevelId = new CustomerLevelId('00000000-0000-0000-0000-000000000002');
        $level = new Level($levelId, 'test', 10);
        $customerLevel = new Level(new LevelLevelId($customerLevelId->__toString()), 'test2', 15);
        $customerReward = new Reward('as2', 20, 'as');
        $reward = new Reward('as', 10, 'as');
        $level->setReward($reward);
        $customerLevel->setReward($customerReward);

        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->never())->method('dispatch');

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository($customerLevelId, $customerLevelId),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepositoryWithArray([
                $level->getLevelId()->__toString() => $level,
                $customerLevel->getLevelId()->__toString() => $customerLevel,
            ]),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_X_DAYS),
            $this->getAccountDetailsRepository(100)
        );
        $listener->handle(new AvailablePointsAmountChangedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId),
            11,
            20
        ));
    }

    /**
     * @test
     */
    public function it_downgrades_when_automatic_downgrade_mode()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $customerLevelId = new CustomerLevelId('00000000-0000-0000-0000-000000000002');
        $level = new Level($levelId, 'test', 10);
        $customerLevel = new Level(new LevelLevelId($customerLevelId->__toString()), 'test2', 15);
        $customerReward = new Reward('as2', 20, 'as');
        $reward = new Reward('as', 10, 'as');
        $level->setReward($reward);
        $customerLevel->setReward($customerReward);

        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->once())->method('dispatch')->with(
            $this->equalTo(
                new MoveCustomerToLevel(
                    new CustomerId($customerId),
                    new LevelId($levelId->__toString()),
                    'test',
                    false,
                    false
                )
            )
        );

        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository($customerLevelId),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepositoryWithArray([
                $level->getLevelId()->__toString() => $level,
                $customerLevel->getLevelId()->__toString() => $customerLevel,
            ]),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_AUTO),
            $this->getAccountDetailsRepository(100)
        );
        $listener->handle(new AvailablePointsAmountChangedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId),
            11,
            20
        ));
    }

    /**
     * @test
     */
    public function it_do_not_move_customer_by_points_if_level_is_the_same()
    {
        $customerId = '00000000-0000-0000-0000-000000000000';
        $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
        $level = new Level($levelId, 'test', 10);
        $reward = new Reward('as', 10, 'as');
        $level->setReward($reward);

        $commandBus = $this->getMockBuilder(CommandBus::class)->getMock();
        $commandBus->expects($this->never())->method('dispatch');

        // none
        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository(new CustomerLevelId('00000000-0000-0000-0000-000000000003')),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepository($level),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_NONE),
            $this->getAccountDetailsRepository(100)
        );
        $listener->handle(new AvailablePointsAmountChangedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId),
            20,
            20
        ));
        // auto
        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository(new CustomerLevelId('00000000-0000-0000-0000-000000000003')),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepository($level),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_AUTO),
            $this->getAccountDetailsRepository(100)
        );
        $listener->handle(new AvailablePointsAmountChangedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId),
            20,
            20
        ));
        // x days
        $listener = new CalculateCustomerLevelListener(
            $this->getLevelIdProvider($level),
            $this->getCustomerDetailsRepository(new CustomerLevelId('00000000-0000-0000-0000-000000000003')),
            $commandBus,
            $this->getTierTypeAssignProvider(TierAssignTypeProvider::TYPE_POINTS),
            $this->getExcludeDeliveryCostsProvider(true),
            $this->getLevelRepository($level),
            $this->getDispatcher(),
            $this->getCustomerStatusProvider(),
            $this->getLevelDowngradeModeProvider(LevelDowngradeModeProvider::MODE_X_DAYS),
            $this->getAccountDetailsRepository(100)
        );

        $listener->handle(new AvailablePointsAmountChangedSystemEvent(
            new AccountId('00000000-0000-0000-0000-000000000000'),
            new AccountCustomerId($customerId),
            20,
            20
        ));
    }

    /**
     * @param $type
     *
     * @return TierAssignTypeProvider
     */
    protected function getTierTypeAssignProvider($type): TierAssignTypeProvider
    {
        /** @var TierAssignTypeProvider|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder(TierAssignTypeProvider::class)->getMock();
        $mock->method('getType')->willReturn($type);

        return $mock;
    }

    /**
     * @param $mode
     * @param string $base
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|LevelDowngradeModeProvider
     */
    protected function getLevelDowngradeModeProvider($mode, $base = LevelDowngradeModeProvider::BASE_ACTIVE_POINTS): LevelDowngradeModeProvider
    {
        $mock = $this->getMockBuilder(LevelDowngradeModeProvider::class)->getMock();
        $mock->method('getMode')->willReturn($mode);
        $mock->method('getBase')->willReturn($base);

        return $mock;
    }

    /**
     * @param CustomerLevelId|null $currentLevelId
     * @param CustomerLevelId|null $assignedLevelId
     *
     * @return CustomerDetailsRepository
     */
    protected function getCustomerDetailsRepository(
        CustomerLevelId $currentLevelId = null,
        CustomerLevelId $assignedLevelId = null
    ): CustomerDetailsRepository {
        /** @var CustomerDetailsRepository|\PHPUnit_Framework_MockObject_MockObject $customerDetailsRepository */
        $customerDetailsRepository = $this->getMockBuilder(CustomerDetailsRepository::class)->getMock();
        $customerDetailsRepository
            ->method('find')
            ->with($this->isType('string'))
            ->willReturnCallback(function ($id) use ($currentLevelId, $assignedLevelId): CustomerDetails {
                /** @var CustomerDetails|\PHPUnit_Framework_MockObject_MockBuilder $customer */
                $customer = $this->getMockBuilder(CustomerDetails::class);
                $customer->disableOriginalConstructor();
                $customer = $customer->getMock();
                $customer->method('getCustomerId')->willReturn(new CustomerId($id));
                $customer->method('getCreatedAt')->willReturn(new \DateTime());
                $customer->method('getLevelId')->willReturn($currentLevelId);
                $customer->method('getManuallyAssignedLevelId')->willReturn($assignedLevelId);

                return $customer;
            })
        ;

        return $customerDetailsRepository;
    }

    /**
     * @param $levels
     *
     * @return LevelIdProvider
     */
    protected function getLevelIdProvider($levels): LevelIdProvider
    {
        if (!is_array($levels)) {
            $levels = [$levels];
        }

        /** @var LevelIdProvider|\PHPUnit_Framework_MockObject_MockObject $levelIdProviderMock */
        $levelIdProviderMock = $this->getMockBuilder(LevelIdProvider::class)->getMock();
        $levelIdProviderMock->method('findLevelIdByConditionValueWithTheBiggestReward')
            ->with($this->greaterThanOrEqual(0))
            ->will($this->returnCallback(function ($conditionValue) use ($levels) {
                $current = null;
                if (count($levels) == 1) {
                    $level = reset($levels);
                    if ($level->getConditionValue() <= $conditionValue) {
                        return $level->getLevelId()->__toString();
                    } else {
                        return;
                    }
                }
                /** @var Level $level */
                foreach ($levels as $level) {
                    if ($level->getConditionValue() <= $conditionValue
                        && (!$current || $level->getReward()->getValue() > $current->getReward()->getValue())
                    ) {
                        $current = $level;
                    }
                }

                return $current ? $current->getLevelId()->__toString() : null;
            }))
        ;

        return $levelIdProviderMock;
    }

    /**
     * @param $returnValue
     *
     * @return ExcludeDeliveryCostsProvider
     */
    protected function getExcludeDeliveryCostsProvider($returnValue): ExcludeDeliveryCostsProvider
    {
        /** @var ExcludeDeliveryCostsProvider|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->getMockBuilder(ExcludeDeliveryCostsProvider::class)->getMock();
        $mock->method('areExcluded')->willReturn($returnValue);

        return $mock;
    }

    /**
     * @return EventDispatcher
     */
    protected function getDispatcher(): EventDispatcher
    {
        /** @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this
            ->getMockBuilder(EventDispatcher::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        return $mock;
    }

    /**
     * @param Level|null $givenLevel
     *
     * @return LevelRepository
     */
    protected function getLevelRepository(Level $givenLevel = null): LevelRepository
    {
        /** @var LevelRepository|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this
            ->getMockBuilder(LevelRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        if ($givenLevel) {
            $level = $givenLevel;
        } else {
            $levelId = new LevelLevelId('00000000-0000-0000-0000-000000000003');
            $level = new Level($levelId, 'test', 20);
        }

        $mock
            ->method('byId')
            ->will($this->returnValue($level))
        ;

        return $mock;
    }

    /**
     * @param array $levels
     *
     * @return LevelRepository
     */
    protected function getLevelRepositoryWithArray(array $levels): LevelRepository
    {
        /** @var LevelRepository|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this
            ->getMockBuilder(LevelRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $mock
            ->method('byId')
            ->with($this->isInstanceOf(LevelLevelId::class))
            ->will($this->returnCallback(function (LevelLevelId $id) use ($levels) {
                if (isset($levels[$id->__toString()])) {
                    return $levels[$id->__toString()];
                }

                return;
            }))
        ;

        return $mock;
    }

    /**
     * @param $points
     * @param int $earnedPoints
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Repository
     */
    public function getAccountDetailsRepository($points, $earnedPoints = 0): Repository
    {
        $mock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $details = $this->getMockBuilder(AccountDetails::class)
            ->disableOriginalConstructor()
            ->getMock();
        $details->method('getAvailableAmount')->willReturn($points);
        $details->method('getEarnedAmountSince')->with($this->isInstanceOf(\DateTime::class))->willReturn($earnedPoints);

        $mock
            ->method('findBy')
            ->with($this->isType('array'))
            ->willReturn([$details]);

        return $mock;
    }

    /**
     * @param float $points
     *
     * @return CustomerStatusProvider
     */
    protected function getCustomerStatusProvider(float $points = 0)
    {
        /** @var CustomerStatusProvider|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this
            ->getMockBuilder(CustomerStatusProvider::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $mock
            ->method('getStatus')
            ->with($this->isInstanceOf(CustomerId::class))
            ->will($this->returnCallback(function (CustomerId $id) use ($points): CustomerStatus {
                $status = new CustomerStatus($id);
                $status->setPoints($points);

                return $status;
            }))
        ;

        return $mock;
    }

    /**
     * @return array
     */
    public function getLevelsWithAssignedProvider(): array
    {
        return [
            [
                new CustomerLevelId(static::LEVEL_WITH_REWARD_200_FROM_20),
                new CustomerLevelId(static::LEVEL_WITH_REWARD_200_FROM_20),
                40,
                'level_2',
                new CustomerLevelId(static::LEVEL_WITH_REWARD_300_FROM_30),
            ],
            [
                new CustomerLevelId(static::LEVEL_WITH_REWARD_300_FROM_30),
                new CustomerLevelId(static::LEVEL_WITH_REWARD_200_FROM_20),
                0,
                'level_0',
                new CustomerLevelId(static::LEVEL_WITH_REWARD_200_FROM_20),
            ],
            [
                new CustomerLevelId(static::LEVEL_WITH_REWARD_200_FROM_20),
                new CustomerLevelId(static::LEVEL_WITH_REWARD_200_FROM_20),
                21,
                null,
                null, // do not change level
            ],
            [
                new CustomerLevelId(static::LEVEL_WITH_REWARD_300_FROM_30),
                new CustomerLevelId(static::LEVEL_WITH_REWARD_10_FROM_0),
                21,
                'level_1',
                new CustomerLevelId(static::LEVEL_WITH_REWARD_200_FROM_20),
            ],
        ];
    }

    /**
     * @return mixed
     */
    protected function getSampleLevels()
    {
        $level = [];
        $level[static::LEVEL_WITH_REWARD_10_FROM_0] = new Level(
            new LevelLevelId(static::LEVEL_WITH_REWARD_10_FROM_0),
            'level_0',
            0
        );
        $level[static::LEVEL_WITH_REWARD_10_FROM_0]->setReward(new Reward('level_0_reward', 10, 'level'));

        $level[static::LEVEL_WITH_REWARD_200_FROM_20] = new Level(
            new LevelLevelId(static::LEVEL_WITH_REWARD_200_FROM_20),
            'level_1',
            20
        );
        $level[static::LEVEL_WITH_REWARD_200_FROM_20]->setReward(new Reward('level_1_reward', 200, 'level'));

        $level[static::LEVEL_WITH_REWARD_300_FROM_30] = new Level(
            new LevelLevelId(static::LEVEL_WITH_REWARD_300_FROM_30),
            'level_2',
            30
        );
        $level[static::LEVEL_WITH_REWARD_300_FROM_30]->setReward(new Reward('level_2_reward', 300, 'level'));

        return $level;
    }
}
