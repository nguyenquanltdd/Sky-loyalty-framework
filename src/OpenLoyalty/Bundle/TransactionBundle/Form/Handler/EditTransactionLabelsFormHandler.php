<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\TransactionBundle\Form\Handler;

use Broadway\CommandHandling\CommandBus;
use OpenLoyalty\Bundle\TransactionBundle\Model\EditLabels;
use OpenLoyalty\Component\Transaction\Domain\Command\EditTransactionLabels;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Transaction\Domain\TransactionId;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class EditTransactionLabelsFormHandler.
 */
class EditTransactionLabelsFormHandler
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
        /** @var EditLabels $data */
        $data = $form->getData();

        if (!$this->ac->isGranted('EDIT_TRANSACTION_LABELS')) {
            throw new AccessDeniedException();
        }

        $transactionId = new TransactionId($data->getTransactionId());
        $this->commandBus->dispatch(
            new EditTransactionLabels(
                $transactionId,
                $data->getLabels()
            )
        );

        return $transactionId;
    }
}
