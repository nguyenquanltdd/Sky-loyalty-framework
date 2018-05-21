<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Tests\Domain\Command;

use OpenLoyalty\Component\Customer\Domain\Command\MoveCustomerToLevel;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasMovedToLevel;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasRegistered;
use OpenLoyalty\Component\Customer\Domain\LevelId;

/**
 * Class MoveCustomerToLevelTest.
 */
class MoveCustomerToLevelTest extends CustomerCommandHandlerTest
{
    /**
     * @test
     */
    public function it_moves_customer_to_level_automatically()
    {
        $levelId = new LevelId('00000000-2222-0000-0000-000000000000');
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');
        $this->scenario
            ->withAggregateId($customerId)
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
            ])
            ->when(new MoveCustomerToLevel($customerId, $levelId))
            ->then([
                new CustomerWasMovedToLevel($customerId, $levelId),
            ]);
    }

    /**
     * @test
     */
    public function it_moves_customer_to_level_manually()
    {
        $levelId = new LevelId('00000000-2222-0000-0000-000000000000');
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');
        $this->scenario
            ->withAggregateId($customerId)
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
            ])
            ->when(new MoveCustomerToLevel($customerId, $levelId, true))
            ->then([
                new CustomerWasMovedToLevel($customerId, $levelId, true),
            ]);
    }

    /**
     * @test
     */
    public function it_remove_customer_from_manually_assigned_level()
    {
        $levelId = new LevelId('00000000-2222-0000-0000-000000000000');
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');
        $this->scenario
            ->withAggregateId($customerId)
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
            ])
            ->when(new MoveCustomerToLevel($customerId, $levelId, true, true))
            ->then([
                new CustomerWasMovedToLevel($customerId, $levelId, true, true),
            ]);
    }
}
