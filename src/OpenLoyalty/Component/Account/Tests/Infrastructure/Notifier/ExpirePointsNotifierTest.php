<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace Infrastructure\Notifier;

use Broadway\CommandHandling\CommandBus;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetailsRepository;
use OpenLoyalty\Component\Account\Infrastructure\Notifier\ExpirePointsNotifier;

/**
 * Class ExpirePointsNotifierTest.
 */
class ExpirePointsNotifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_dispatches_webhook_dispatch_command(): void
    {
        /** @var CommandBus|\PHPUnit_Framework_MockObject_MockObject $commandBusMock */
        $commandBusMock = $this->getMockBuilder(CommandBus::class)->getMock();

        /** @var PointsTransferDetails|\PHPUnit_Framework_MockObject_MockObject $pointTransferMock */
        $pointTransferMock = $this
            ->getMockBuilder(PointsTransferDetails::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $pointTransferMock->method('getCustomerId')->willReturn('22200000-0000-474c-b092-b0dd880c07e2');
        $pointTransferMock->method('getCustomerEmail')->willReturn('test@doe.com');
        $pointTransferMock->method('getCustomerPhone')->willReturn('111222333');
        $pointTransferMock->method('getCustomerLoyaltyCardNumber')->willReturn('test');
        $pointTransferMock->method('getCustomerFirstName')->willReturn('John');
        $pointTransferMock->method('getCustomerLastName')->willReturn('Doe');
        $pointTransferMock->method('getValue')->willReturn(10);
        $pointTransferMock->method('getExpiresAt')->willReturn(new \DateTime());

        /** @var PointsTransferDetailsRepository|\PHPUnit_Framework_MockObject_MockObject $pointTransfersRepositoryMock */
        $pointTransfersRepositoryMock = $this
            ->getMockBuilder(PointsTransferDetailsRepository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $pointTransfersRepositoryMock
            ->method('findAllActiveAddingTransfersBeforeExpired')
            ->willReturn([$pointTransferMock])
        ;

        $expirePointsNotifier = new ExpirePointsNotifier($commandBusMock, $pointTransfersRepositoryMock);
        $expirePointsNotifier->sendNotificationsForPointsExpiringBefore(new \DateTime('now'));

        $this->assertEquals(1, $expirePointsNotifier->sentNotificationsCount());
    }
}
