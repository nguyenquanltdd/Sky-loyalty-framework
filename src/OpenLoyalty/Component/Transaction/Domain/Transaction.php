<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Transaction\Domain;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Transaction\Domain\Event\CustomerWasAssignedToTransaction;
use OpenLoyalty\Component\Transaction\Domain\Event\LabelsWereAppendedToTransaction;
use OpenLoyalty\Component\Transaction\Domain\Event\LabelsWereUpdated;
use OpenLoyalty\Component\Transaction\Domain\Event\TransactionWasRegistered;

/**
 * Class Transaction.
 */
class Transaction extends EventSourcedAggregateRoot
{
    const TYPE_RETURN = 'return';
    const TYPE_SELL = 'sell';

    /**
     * @var TransactionId
     */
    protected $transactionId;

    /**
     * @var CustomerId
     */
    protected $customerId;

    /**
     * @var PosId|null
     */
    protected $posId;

    /**
     * @var array
     */
    protected $excludedDeliverySKUs;

    /**
     * @var string|null
     */
    protected $revisedDocument;

    /**
     * @var Label[]
     */
    protected $labels;

    /**
     * @return string
     */
    public function getAggregateRootId(): string
    {
        return $this->transactionId;
    }

    /**
     * @param TransactionId $transactionId
     * @param array         $transactionData
     * @param array         $customerData
     * @param array         $items
     * @param PosId|null    $posId
     * @param array|null    $excludedDeliverySKUs
     * @param array|null    $excludedLevelSKUs
     * @param array|null    $excludedLevelCategories
     * @param string|null   $revisedDocument
     * @param array         $labels
     *
     * @return Transaction
     */
    public static function createTransaction(
        TransactionId $transactionId,
        array $transactionData,
        array $customerData,
        array $items,
        PosId $posId = null,
        array $excludedDeliverySKUs = null,
        array $excludedLevelSKUs = null,
        array $excludedLevelCategories = null,
        string $revisedDocument = null,
        array $labels = []
    ): Transaction {
        $transaction = new self();
        $transaction->create(
            $transactionId,
            $transactionData,
            $customerData,
            $items,
            $posId,
            $excludedDeliverySKUs,
            $excludedLevelSKUs,
            $excludedLevelCategories,
            $revisedDocument,
            $labels
        );

        return $transaction;
    }

    public function assignCustomerToTransaction(CustomerId $customerId)
    {
        $this->apply(
            new CustomerWasAssignedToTransaction($this->transactionId, $customerId)
        );
    }

    public function appendLabels(array $labels = [])
    {
        $this->apply(
            new LabelsWereAppendedToTransaction($this->transactionId, $labels)
        );
    }

    public function setLabels(array $labels = [])
    {
        $this->apply(
            new LabelsWereUpdated($this->transactionId, $labels)
        );
    }

    /**
     * @return PosId
     */
    public function getPosId(): ?PosId
    {
        return $this->posId;
    }

    private function create(
        TransactionId $transactionId,
        array $transactionData,
        array $customerData,
        array $items,
        PosId $posId = null,
        array $excludedDeliverySKUs = null,
        array $excludedLevelSKUs = null,
        array $excludedLevelCategories = null,
        $revisedDocument = null,
        array $labels = []
    ) {
        $this->apply(
            new TransactionWasRegistered(
                $transactionId,
                $transactionData,
                $customerData,
                $items,
                $posId,
                $excludedDeliverySKUs,
                $excludedLevelSKUs,
                $excludedLevelCategories,
                $revisedDocument,
                $labels
            )
        );
    }

    protected function applyTransactionWasRegistered(TransactionWasRegistered $event)
    {
        $this->transactionId = $event->getTransactionId();
        $this->posId = $event->getPosId();
        $this->labels = $event->getLabels();
    }

    protected function applyCustomerWasAssignedToTransaction(CustomerWasAssignedToTransaction $event)
    {
        $this->customerId = $event->getCustomerId();
    }

    protected function applyLabelsWereAppendedToTransaction(LabelsWereAppendedToTransaction $event)
    {
        $this->labels = array_merge($this->labels, $event->getLabels());
    }

    protected function applyLabelsWereUpdated(LabelsWereUpdated $event)
    {
        $this->labels = $event->getLabels();
    }
}
