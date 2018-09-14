<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Form\Handler;

use Broadway\CommandHandling\CommandBus;
use Doctrine\ORM\EntityManager;
use OpenLoyalty\Bundle\UserBundle\Service\UserManager;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerAddress;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerCompanyDetails;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerDetails;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerLoyaltyCardNumber;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Exception\EmailAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Exception\LoyaltyCardNumberAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Exception\PhoneAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Validator\CustomerUniqueValidator;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CustomerEditFormHandler.
 */
class CustomerEditFormHandler
{
    /**
     * @var CommandBus
     */
    protected $commandBus;
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var CustomerUniqueValidator
     */
    protected $customerUniqueValidator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * CustomerEditFormHandler constructor.
     *
     * @param CommandBus              $commandBus
     * @param UserManager             $userManager
     * @param EntityManager           $em
     * @param CustomerUniqueValidator $customerUniqueValidator
     * @param TranslatorInterface     $translator
     */
    public function __construct(
        CommandBus $commandBus,
        UserManager $userManager,
        EntityManager $em,
        CustomerUniqueValidator $customerUniqueValidator,
        TranslatorInterface $translator
    ) {
        $this->commandBus = $commandBus;
        $this->userManager = $userManager;
        $this->em = $em;
        $this->customerUniqueValidator = $customerUniqueValidator;
        $this->translator = $translator;
    }

    /**
     * @param CustomerId    $customerId
     * @param FormInterface $form
     *
     * @return bool
     */
    public function onSuccess(CustomerId $customerId, FormInterface $form): bool
    {
        $email = null;
        $customerData = $form->getData();
        if (isset($customerData['email']) && !empty($customerData['email'])) {
            $email = $customerData['email'];
            $emailExists = false;
            if ($this->isUserExistAndIsDifferentThanEdited((string) $customerId, $email)) {
                $emailExists = $this->translator->trans('This email is already taken');
            }
            try {
                $this->customerUniqueValidator->validateEmailUnique($email, $customerId);
            } catch (EmailAlreadyExistsException $e) {
                $emailExists = $this->translator->trans($e->getMessageKey(), $e->getMessageParams());
            }
            if ($emailExists) {
                $form->get('email')->addError(new FormError($emailExists));
            }
        }

        if (isset($customerData['phone']) && $customerData['phone']) {
            try {
                $this->customerUniqueValidator->validatePhoneUnique($customerData['phone'], $customerId);
            } catch (PhoneAlreadyExistsException $e) {
                $form->get('phone')->addError(
                    new FormError($this->translator->trans($e->getMessageKey(), $e->getMessageParams()))
                );
            }
        }

        if (null === $customerData['phone']) {
            $customerData['phone'] = '';
        }

        if (isset($customerData['loyaltyCardNumber'])) {
            try {
                $this->customerUniqueValidator->validateLoyaltyCardNumberUnique(
                    $customerData['loyaltyCardNumber'],
                    $customerId
                );
            } catch (LoyaltyCardNumberAlreadyExistsException $e) {
                $form->get('loyaltyCardNumber')->addError(
                    new FormError($this->translator->trans($e->getMessageKey(), $e->getMessageParams()))
                );
            }
        }

        if ($form->getErrors(true)->count() > 0) {
            return false;
        }

        if (!$customerData['company']['name'] && !$customerData['company']['nip']) {
            unset($customerData['company']);
        }

        $labels = [];
        /** @var Label $label */
        foreach ($form->get('labels')->getData() as $label) {
            $labels[] = $label->serialize();
        }

        $customerData['labels'] = $labels;

        $command = new UpdateCustomerDetails($customerId, $customerData);
        $this->commandBus->dispatch($command);

        $addressData = [];
        if (isset($customerData['address'])) {
            $addressData = $customerData['address'];
        }

        $updateAddressCommand = new UpdateCustomerAddress($customerId, $addressData);
        $this->commandBus->dispatch($updateAddressCommand);

        $company = [];
        if (isset($customerData['company'])) {
            $company = $customerData['company'];
        }

        $updateCompanyDataCommand = new UpdateCustomerCompanyDetails($customerId, $company);
        $this->commandBus->dispatch($updateCompanyDataCommand);

        if (isset($customerData['loyaltyCardNumber'])) {
            $loyaltyCardCommand = new UpdateCustomerLoyaltyCardNumber($customerId, $customerData['loyaltyCardNumber']);
            $this->commandBus->dispatch($loyaltyCardCommand);
        }
        if (empty($email)) {
            return true;
        }

        $user = $this->em->getRepository('OpenLoyaltyUserBundle:Customer')->find($customerId->__toString());

        $user->setEmail($email);
        $this->userManager->updateUser($user);

        return true;
    }

    /**
     * @param string $id
     * @param string $email
     *
     * @return bool
     */
    private function isUserExistAndIsDifferentThanEdited(string $id, string $email): bool
    {
        $qb = $this->em->createQueryBuilder()->select('u')->from('OpenLoyaltyUserBundle:Customer', 'u');
        $qb->andWhere('u.email = :email')->setParameter('email', $email);
        $qb->andWhere('u.id != :id')->setParameter('id', $id);

        $result = $qb->getQuery()->getResult();

        return count($result) > 0;
    }
}
