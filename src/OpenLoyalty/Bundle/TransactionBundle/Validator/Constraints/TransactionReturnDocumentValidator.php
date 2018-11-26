<?php
/*
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\TransactionBundle\Validator\Constraints;

use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class TransactionReturnDocumentValidator.
 */
class TransactionReturnDocumentValidator extends ConstraintValidator
{
    private const DOCUMENT_TYPE_RETURN = 'return';
    private const TRANSACTION_NOT_EXIST = 'Transaction not exist';
    private const TRANSACTION_WRONG_TYPE = 'Transaction wrong type';
    private const TRANSACTION_INCORRECT_OWNER = 'Incorrect owner of the transaction';

    /**
     * @var TransactionDetailsRepository
     */
    private $transactionDetailsRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var CustomerDetailsRepository
     */
    private $customerDetailsRepository;

    /**
     * TransactionReturnDocumentValidator constructor.
     *
     * @param TransactionDetailsRepository $transactionDetailsRepository
     * @param TranslatorInterface          $translator
     * @param CustomerDetailsRepository    $customerDetailsRepository
     */
    public function __construct(TransactionDetailsRepository $transactionDetailsRepository, TranslatorInterface $translator, CustomerDetailsRepository $customerDetailsRepository)
    {
        $this->transactionDetailsRepository = $transactionDetailsRepository;
        $this->translator = $translator;
        $this->customerDetailsRepository = $customerDetailsRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        $transaction = $this->transactionDetailsRepository->findTransactionByDocumentNumber($value);

        self::checkTransactionIsNull($transaction);

        if (self::DOCUMENT_TYPE_RETURN === $transaction->getDocumentType() && $transaction->getRevisedDocument() != null) {
            $basedTransaction = $this->transactionDetailsRepository->findTransactionByDocumentNumber($transaction->getRevisedDocument());

            self::checkTransactionIsNull($basedTransaction);
            self::checkTransactionType($transaction, $basedTransaction);
            self::checkTransactionOwner($constraint, $transaction, $basedTransaction);
        }
    }

    /**
     * @param TransactionDetails $transaction
     */
    private function checkTransactionIsNull(TransactionDetails $transaction): void
    {
        if (null === $transaction) {
            $this->context->buildViolation(self::TRANSACTION_NOT_EXIST)->addViolation();

            return;
        }
    }

    /**
     * @param TransactionDetails $transaction
     * @param TransactionDetails $basedTransaction
     */
    private function checkTransactionType(TransactionDetails $transaction, TransactionDetails $basedTransaction): void
    {
        if (self::DOCUMENT_TYPE_RETURN === $transaction->getDocumentType() && self::DOCUMENT_TYPE_RETURN === $basedTransaction->getDocumentType()) {
            $this->context->buildViolation(self::TRANSACTION_WRONG_TYPE)->addViolation();

            return;
        }
    }

    /**
     * @param Constraint         $constraint
     * @param TransactionDetails $transaction
     * @param TransactionDetails $basedTransaction
     */
    private function checkTransactionOwner(Constraint $constraint, TransactionDetails $transaction, TransactionDetails $basedTransaction): void
    {
        if ($constraint->getDefaultOption() && null != $transaction->getCustomerId() && null != $basedTransaction->getCustomerId() && (string) $transaction->getCustomerId() !== (string) $basedTransaction->getCustomerId()) {
            $this->context->buildViolation(self::TRANSACTION_INCORRECT_OWNER)->addViolation();

            return;
        }
    }
}
