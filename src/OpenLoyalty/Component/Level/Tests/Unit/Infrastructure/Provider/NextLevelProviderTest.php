<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace OpenLoyalty\Component\Level\Tests\Unit\Infrastructure\Provider;

use OpenLoyalty\Component\Account\Infrastructure\Provider\AccountDetailsProviderInterface;
use OpenLoyalty\Component\Customer\Infrastructure\ExcludeDeliveryCostsProvider;
use OpenLoyalty\Component\Customer\Infrastructure\Provider\CustomerDetailsProviderInterface;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use OpenLoyalty\Component\Level\Domain\LevelRepository;

/**
 * Class NextLevelProviderTest.
 */
class NextLevelProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LevelRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $levelRepositoryMock;

    /**
     * @var CustomerDetailsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerDetailsProviderMock;

    /**
     * @var AccountDetailsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $accountDetailsProviderMock;

    /**
     * @var TierAssignTypeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tierAssignTypeProviderMock;

    /**
     * @var ExcludeDeliveryCostsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $excludeDeliveryCostProviderMock;

    /**
     * {@inheritdoc}
     */
    public function setup(): void
    {
        $this->levelRepositoryMock = $this->getMockForAbstractClass(LevelRepository::class);
        $this->customerDetailsProviderMock = $this->getMockForAbstractClass(CustomerDetailsProviderInterface::class);
        $this->accountDetailsProviderMock = $this->getMockForAbstractClass(AccountDetailsProviderInterface::class);
        $this->tierAssignTypeProviderMock = $this->getMockForAbstractClass(TierAssignTypeProvider::class);
        $this->excludeDeliveryCostProviderMock = $this->getMockForAbstractClass(ExcludeDeliveryCostsProvider::class);
    }

    /**
     * @test
     */
    public function it_provides_next_level_for_given_customer(): void
    {
    }
}
