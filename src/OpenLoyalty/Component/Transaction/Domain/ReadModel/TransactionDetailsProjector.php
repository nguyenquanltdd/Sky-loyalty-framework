<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Transaction\Domain\ReadModel;

use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Repository;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Pos\Domain\Pos;
use OpenLoyalty\Component\Pos\Domain\PosId;
use OpenLoyalty\Component\Pos\Domain\PosRepository;
use OpenLoyalty\Component\Transaction\Domain\Event\CustomerWasAssignedToTransaction;
use OpenLoyalty\Component\Transaction\Domain\Event\TransactionWasRegistered;
use OpenLoyalty\Component\Transaction\Domain\Model\CustomerBasicData;
use OpenLoyalty\Component\Transaction\Domain\TransactionId;

/**
 * Class TransactionDetailsProjector.
 */
class TransactionDetailsProjector extends Projector
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var PosRepository
     */
    private $posRepository;

    /**
     * @var CustomerDetailsRepository
     */
    private $customerDetailsRepository;

    /**
     * TransactionDetailsProjector constructor.
     *
     * @param Repository                $repository
     * @param PosRepository             $posRepository
     * @param CustomerDetailsRepository $customerDetailsRepository
     */
    public function __construct(
        Repository $repository,
        PosRepository $posRepository,
        CustomerDetailsRepository $customerDetailsRepository
    ) {
        $this->repository = $repository;
        $this->posRepository = $posRepository;
        $this->customerDetailsRepository = $customerDetailsRepository;
    }

    protected function applyTransactionWasRegistered(TransactionWasRegistered $event)
    {
        $readModel = $this->getReadModel($event->getTransactionId());
        $transactionData = $event->getTransactionData();
        $readModel->setDocumentType($transactionData['documentType']);
        $readModel->setDocumentNumber($transactionData['documentNumber']);
        $readModel->setPurchaseDate($transactionData['purchaseDate']);
        $readModel->setPurchasePlace($transactionData['purchasePlace']);
        $readModel->setCustomerData(CustomerBasicData::deserialize($event->getCustomerData()));
        $readModel->setItems($event->getItems());
        $readModel->setPosId($event->getPosId());
        $readModel->setExcludedDeliverySKUs($event->getExcludedDeliverySKUs());
        $readModel->setExcludedLevelSKUs($event->getExcludedLevelSKUs());
        $readModel->setExcludedLevelCategories($event->getExcludedLevelCategories());
        $readModel->setRevisedDocument($event->getRevisedDocument());

        if ($readModel->getPosId()) {
            /** @var Pos $pos */
            $pos = $this->posRepository->byId(new PosId($readModel->getPosId()->__toString()));
            if ($pos) {
                $pos->setTransactionsAmount($pos->getTransactionsAmount() + $readModel->getGrossValue());
                $pos->setTransactionsCount($pos->getTransactionsCount() + 1);
                $this->posRepository->save($pos);
            }
        }

        $this->repository->save($readModel);
    }

    public function applyCustomerWasAssignedToTransaction(CustomerWasAssignedToTransaction $event)
    {
        $readModel = $this->getReadModel($event->getTransactionId());
        $readModel->setCustomerId($event->getCustomerId());
        $customer = $this->customerDetailsRepository->find($event->getCustomerId()->__toString());
        if ($customer instanceof CustomerDetails) {
            $customerData = $readModel->getCustomerData();
            $customerData->updateEmailAndPhone($customer->getEmail(), $customer->getPhone());
        }
        $this->repository->save($readModel);
    }

    /**
     * @param TransactionId $transactionId
     *
     * @return TransactionDetails|null
     */
    private function getReadModel(TransactionId $transactionId)
    {
        /** @var TransactionDetails $readModel */
        $readModel = $this->repository->find($transactionId->__toString());

        if (null === $readModel) {
            $readModel = new TransactionDetails($transactionId);
        }

        return $readModel;
    }
}
