<?php

namespace OpenLoyalty\Component\Account\Tests\Infrastructure\SytemEvent\Listener;

use Broadway\CommandHandling\CommandBus;
use Broadway\ReadModel\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use OpenLoyalty\Bundle\PointsBundle\Service\PointsTransfersManager;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\Model\AddPointsTransfer;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountSystemEvents;
use OpenLoyalty\Component\Account\Domain\TransactionId;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerSystemEvents;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\TransactionSystemEvents;
use OpenLoyalty\Component\Account\Infrastructure\EarningRuleApplier;

/**
 * Class BaseApplyEarningRuleListenerTest.
 */
abstract class BaseApplyEarningRuleListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $uuid = '00000000-0000-0000-0000-000000000000';

    protected function getUuidGenerator()
    {
        $mock = $this->getMockBuilder(UuidGeneratorInterface::class)->getMock();
        $mock->method('generate')->willReturn($this->uuid);

        return $mock;
    }

    protected function getAccountDetailsRepository()
    {
        $account = $this->getMockBuilder(AccountDetails::class)->disableOriginalConstructor()->getMock();
        $account->method('getAccountId')->willReturn(new AccountId($this->uuid));

        $repo = $this->getMockBuilder(Repository::class)->getMock();
        $repo->method('findBy')->with($this->arrayHasKey('customerId'))->willReturn([$account]);

        return $repo;
    }

    /**
     * @param int         $value
     * @param int         $duration
     * @param string:null $comment
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPointsTransfersManager($value = 10, $duration = 0, $comment = null)
    {
        $pointsTransfer = new AddPointsTransfer(
            new PointsTransferId($this->uuid),
            $value,
            $duration,
            null,
            false,
            null,
            $comment
        );
        $manager = $this->getMockBuilder(PointsTransfersManager::class)->disableOriginalConstructor()->getMock();
        $manager->method('createAddPointsTransferInstance')->willReturn(
            $pointsTransfer
        );

        return $manager;
    }

    protected function getCommandBus($expected)
    {
        $mock = $this->getMockBuilder(CommandBus::class)->getMock();
        $mock->method('dispatch')->with($this->equalTo($expected));

        return $mock;
    }

    protected function getApplierForEvent($returnValue)
    {
        $mock = $this->getMockBuilder(EarningRuleApplier::class)->getMock();
        $mock->method('evaluateEventWithContext')->with($this->logicalOr(
            $this->equalTo(AccountSystemEvents::ACCOUNT_CREATED),
            $this->equalTo(TransactionSystemEvents::CUSTOMER_FIRST_TRANSACTION),
            $this->equalTo(CustomerSystemEvents::CUSTOMER_LOGGED_IN),
            $this->equalTo(CustomerSystemEvents::CUSTOMER_REFERRAL),
            $this->equalTo(CustomerSystemEvents::NEWSLETTER_SUBSCRIPTION)
        ))->willReturn($returnValue);
        $mock->method('evaluateReferralEvent')->willReturn([]);

        return $mock;
    }

    protected function getApplierForTransaction($returnValue)
    {
        $mock = $this->getMockBuilder(EarningRuleApplier::class)->getMock();
        $mock->method('evaluateTransaction')->with($this->isInstanceOf(TransactionId::class))
            ->willReturn($returnValue);

        return $mock;
    }
}
