<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Transaction\Domain\Provider;

use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Transaction\Domain\TransactionId;

/**
 * Class TransactionValueProvider.
 */
class TransactionValueProvider implements TransactionValueProviderInterface
{
    /**
     * @var TransactionDetailsRepository
     */
    private $transactionDetailsRepository;

    /**
     * TransactionValueProvider constructor.
     *
     * @param TransactionDetailsRepository $transactionDetailsRepository
     */
    public function __construct(TransactionDetailsRepository $transactionDetailsRepository)
    {
        $this->transactionDetailsRepository = $transactionDetailsRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionValue(TransactionId $transactionId, bool $includeReturns = false): ?float
    {
        /** @var TransactionDetails $transactionDetails */
        $transactionDetails = $this->transactionDetailsRepository->find($transactionId->__toString());

        $transactionValue = $transactionDetails ? $transactionDetails->getGrossValue() : null;

        if ($includeReturns) {
            $returns = $this->transactionDetailsRepository->findReturnsByDocumentNumber($transactionDetails->getDocumentNumber());
            foreach ($returns as $return) {
                $transactionValue -= $return->getGrossValue();
            }
        }

        return $transactionValue;
    }
}
