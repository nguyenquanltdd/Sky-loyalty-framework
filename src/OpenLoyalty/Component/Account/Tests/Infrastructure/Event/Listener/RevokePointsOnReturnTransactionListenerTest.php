<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Tests\Infrastructure\Event\Listener;

use Broadway\CommandHandling\CommandBus;
use Broadway\ReadModel\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\Command\SpendPoints;
use OpenLoyalty\Component\Account\Domain\Model\PointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\SpendPointsTransfer;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetailsRepository;
use OpenLoyalty\Component\Account\Domain\TransactionId as AccountTransactionId;
use OpenLoyalty\Component\Account\Infrastructure\Event\Listener\RevokePointsOnReturnTransactionListener;
use OpenLoyalty\Component\Transaction\Domain\CustomerId;
use OpenLoyalty\Component\Transaction\Domain\Event\CustomerWasAssignedToTransaction;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Transaction\Domain\Transaction;
use OpenLoyalty\Component\Transaction\Domain\TransactionId;
use OpenLoyalty\Component\Account\Domain\CustomerId as AccountCustomerId;

/**
 * Class RevokePointsOnReturnTransactionListenerTest.
 */
class RevokePointsOnReturnTransactionListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $uuid = '00000000-0000-0000-0000-000000000000';
    protected $newUuid = '00000000-0000-0000-0000-000000000001';
    protected $documentNumber = '123';

    protected $transaction;
    protected $revisedTransaction;

    /**
     * @test
     */
    public function it_revokes_points_for_return_transaction()
    {
        $listener = new RevokePointsOnReturnTransactionListener(
            $this->getTransactionDetailsRepository(),
            $this->getPointsTransferRepository(1000, 0),
            $this->getAccountDetailsRepository(),
            $this->getCommandBus(
                new SpendPoints(
                    new AccountId($this->uuid),
                    new SpendPointsTransfer(
                        new PointsTransferId($this->uuid),
                        500.0,
                        null,
                        false,
                        null,
                        PointsTransfer::ISSUER_SYSTEM,
                        new AccountTransactionId($this->newUuid),
                        new AccountTransactionId($this->uuid)
                    )
                ),
                1
            ),
            $this->getUuidGenerator()
        );

        $listener->onCustomerWasAssignedToTransaction(new CustomerWasAssignedToTransaction(
            new TransactionId($this->newUuid),
            new CustomerId($this->uuid)
        ));
    }

    /**
     * @test
     */
    public function it_not_revokes_points_for_return_transaction_if_already_revoked()
    {
        $listener = new RevokePointsOnReturnTransactionListener(
            $this->getTransactionDetailsRepository(),
            $this->getPointsTransferRepository(1000, 1000),
            $this->getAccountDetailsRepository(),
            $this->getCommandBus(
                new SpendPoints(
                    new AccountId($this->uuid),
                    new SpendPointsTransfer(
                        new PointsTransferId($this->uuid),
                        500.0,
                        null,
                        false,
                        null,
                        PointsTransfer::ISSUER_SYSTEM,
                        new AccountTransactionId($this->newUuid),
                        new AccountTransactionId($this->uuid)
                    )
                ),
                0
            ),
            $this->getUuidGenerator()
        );

        $listener->onCustomerWasAssignedToTransaction(new CustomerWasAssignedToTransaction(
            new TransactionId($this->newUuid),
            new CustomerId($this->uuid)
        ));
    }

    protected function setUp()
    {
        parent::setUp();
        $this->transaction = $this->getMockBuilder(TransactionDetails::class)->disableOriginalConstructor()->getMock();
        $this->revisedTransaction = $this->getMockBuilder(TransactionDetails::class)->disableOriginalConstructor()->getMock();

        $this->transaction->method('getTransactionId')->willReturn(new TransactionId($this->newUuid));
        $this->revisedTransaction->method('getTransactionId')->willReturn(new TransactionId($this->uuid));

        $this->transaction->method('getId')->willReturn($this->newUuid);
        $this->transaction->method('getGrossValue')->willReturn(100);
        $this->transaction->method('getRevisedDocument')->willReturn($this->documentNumber);
        $this->transaction->method('getDocumentType')->willReturn(Transaction::TYPE_RETURN);

        $this->revisedTransaction->method('getGrossValue')->willReturn(200);
        $this->revisedTransaction->method('getId')->willReturn($this->uuid);
    }

    protected function getUuidGenerator()
    {
        $mock = $this->getMockBuilder(UuidGeneratorInterface::class)->getMock();
        $mock->method('generate')->willReturn($this->uuid);

        return $mock;
    }

    protected function getTransactionDetailsRepository()
    {
        $repo = $this->getMockBuilder(TransactionDetailsRepository::class)->getMock();
        $repo->method('find')->with($this->isType('string'))->willReturnCallback(function ($id) {
            switch ($id) {
                case $this->uuid:
                    return $this->revisedTransaction;
                case $this->newUuid:
                    return $this->transaction;
            }

            return;
        });
        $repo->method('findBy')->with($this->arrayHasKey('documentNumberRaw'))->willReturnCallback(function ($params) {
            switch ($params['documentNumberRaw']) {
                case $this->documentNumber:
                    return [$this->revisedTransaction];
            }

            return;
        });

        return $repo;
    }

    protected function getPointsTransferRepository($all, $alreadyRevoked = null)
    {
        $repo = $this->getMockBuilder(PointsTransferDetailsRepository::class)->getMock();
        $repo->method('findBy')->with($this->arrayHasKey('type'))->will($this->returnCallback(function ($params) use ($all, $alreadyRevoked) {
            switch ($params['type']) {
                case PointsTransferDetails::TYPE_ADDING:
                    $transfer = new PointsTransferDetails(
                        new PointsTransferId($this->uuid),
                        new AccountCustomerId($this->uuid),
                        new AccountId($this->uuid)
                    );
                    $transfer->setValue($all);

                    return [$transfer];
                case PointsTransferDetails::TYPE_SPENDING:
                    if (!$alreadyRevoked) {
                        return [];
                    }
                    $transfer = new PointsTransferDetails(
                        new PointsTransferId($this->uuid),
                        new AccountCustomerId($this->uuid),
                        new AccountId($this->uuid)
                    );
                    $transfer->setValue($alreadyRevoked);

                    return [$transfer];
            }

            return [];
        }));

        return $repo;
    }

    protected function getAccountDetailsRepository()
    {
        $account = $this->getMockBuilder(AccountDetails::class)->disableOriginalConstructor()->getMock();
        $account->method('getAccountId')->willReturn(new AccountId($this->uuid));

        $repo = $this->getMockBuilder(Repository::class)->getMock();
        $repo->method('findBy')->with($this->arrayHasKey('customerId'))->willReturn([$account]);

        return $repo;
    }

    protected function getCommandBus($expected, $times)
    {
        $mock = $this->getMockBuilder(CommandBus::class)->getMock();
        $mock->expects($this->exactly($times))->method('dispatch')->with($this->equalTo($expected));

        return $mock;
    }
}
