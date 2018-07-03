<?php

namespace OpenLoyalty\Component\Transaction\Tests\Command;

use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Transaction\Domain\Command\AppendLabelsToTransaction;
use OpenLoyalty\Component\Transaction\Domain\Event\LabelsWereAppendedToTransaction;
use OpenLoyalty\Component\Transaction\Domain\Event\TransactionWasRegistered;
use OpenLoyalty\Component\Transaction\Domain\TransactionId;

/**
 * Class AppendLabelsToTransactionTest.
 */
class AppendLabelsToTransactionTest extends TransactionCommandHandlerTest
{
    /**
     * @test
     */
    public function it_appends_labels_to_transaction()
    {
        $transactionId = new TransactionId('00000000-0000-0000-0000-000000000000');

        $labels = [['key' => 'added label', 'value' => 'with some value']];
        $appendedLabels = [
            new Label('added label', 'with some value'),
        ];

        $this->scenario
            ->withAggregateId($transactionId)
            ->given([
                new TransactionWasRegistered($transactionId, $this->getTransactionData(), $this->getCustomerData()),
            ])
            ->when(new AppendLabelsToTransaction($transactionId, $labels))
            ->then(array(
                new LabelsWereAppendedToTransaction(
                    $transactionId,
                    $appendedLabels
                ),
            ));
    }

    /**
     * @return array
     */
    protected function getTransactionData()
    {
        return [
            'documentNumber' => '123',
            'purchasePlace' => 'wroclaw',
            'purchaseDate' => '1471859115',
            'documentType' => 'sell',
        ];
    }

    /**
     * @return array
     */
    protected function getCustomerData()
    {
        return [
            'name' => 'Jan Nowak',
            'email' => 'ol@oy.com',
            'nip' => 'aaa',
            'phone' => '123',
            'loyaltyCardNumber' => '222',
            'address' => [
                'street' => 'Bagno',
                'address1' => '12',
                'city' => 'Warszawa',
                'country' => 'PL',
                'province' => 'Mazowieckie',
                'postal' => '00-800',
            ],
        ];
    }
}
