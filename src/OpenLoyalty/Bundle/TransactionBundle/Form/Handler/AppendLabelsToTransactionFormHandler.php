<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\TransactionBundle\Form\Handler;

use Broadway\CommandHandling\CommandBus;
use OpenLoyalty\Bundle\TransactionBundle\Model\AppendLabels;
use OpenLoyalty\Component\Transaction\Domain\Command\AppendLabelsToTransaction;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class AppendLabelsToTransactionFormHandler.
 */
class AppendLabelsToTransactionFormHandler
{
    /**
     * @var TransactionDetailsRepository
     */
    protected $transactionDetailsRepository;

    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var AuthorizationChecker
     */
    protected $ac;

    /**
     * ManuallyAssignCustomerToTransactionFormHandler constructor.
     *
     * @param TransactionDetailsRepository $transactionDetailsRepository
     * @param CommandBus                   $commandBus
     * @param AuthorizationChecker         $ac
     */
    public function __construct(
        TransactionDetailsRepository $transactionDetailsRepository,
        CommandBus $commandBus,
        AuthorizationChecker $ac
    ) {
        $this->transactionDetailsRepository = $transactionDetailsRepository;
        $this->commandBus = $commandBus;
        $this->ac = $ac;
    }

    /**
     * @param FormInterface $form
     *
     * @return bool|\OpenLoyalty\Component\Transaction\Domain\TransactionId
     */
    public function onSuccess(FormInterface $form)
    {
        /** @var AppendLabels $data */
        $data = $form->getData();

        $documentNumber = $data->getTransactionDocumentNumber();

        $transactions = $this->transactionDetailsRepository->findBy(['documentNumberRaw' => $documentNumber]);
        if (count($transactions) == 0) {
            $form->get('transactionDocumentNumber')->addError(new FormError('No such transaction'));

            return false;
        }
        /** @var TransactionDetails $transaction */
        $transaction = reset($transactions);

        if (!$this->ac->isGranted('APPEND_LABELS_TO_TRANSACTION', $transaction)) {
            throw new AccessDeniedException();
        }

        $this->commandBus->dispatch(
            new AppendLabelsToTransaction(
                $transaction->getTransactionId(),
                $data->getLabels()
            )
        );

        return $transaction->getTransactionId();
    }
}
