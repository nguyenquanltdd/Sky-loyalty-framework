<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Form\Handler;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use Doctrine\ORM\EntityManager;
use OpenLoyalty\Bundle\ActivationCodeBundle\Service\ActionTokenManager;
use OpenLoyalty\Bundle\UserBundle\Entity\Customer;
use OpenLoyalty\Bundle\UserBundle\Entity\Status;
use OpenLoyalty\Bundle\UserBundle\Entity\User;
use OpenLoyalty\Bundle\UserBundle\Service\UserManager;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Customer\Domain\Command\MoveCustomerToLevel;
use OpenLoyalty\Component\Customer\Domain\Command\RegisterCustomer;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerAddress;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerCompanyDetails;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerLoyaltyCardNumber;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Exception\EmailAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Exception\LoyaltyCardNumberAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Exception\PhoneAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\LevelId;
use OpenLoyalty\Component\Customer\Domain\Validator\CustomerUniqueValidator;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * Class CustomerRegistrationFormHandler.
 */
class CustomerRegistrationFormHandler
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
     * @var UuidGeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * @var CustomerUniqueValidator
     */
    protected $customerUniqueValidator;

    /**
     * @var ActionTokenManager
     */
    private $actionTokenManager;

    /**
     * CustomerRegistrationFormHandler constructor.
     *
     * @param CommandBus              $commandBus
     * @param UserManager             $userManager
     * @param EntityManager           $em
     * @param UuidGeneratorInterface  $uuidGenerator
     * @param CustomerUniqueValidator $customerUniqueValidator
     * @param ActionTokenManager      $actionTokenManager
     */
    public function __construct(
        CommandBus $commandBus,
        UserManager $userManager,
        EntityManager $em,
        UuidGeneratorInterface $uuidGenerator,
        CustomerUniqueValidator $customerUniqueValidator,
        ActionTokenManager $actionTokenManager
    ) {
        $this->commandBus = $commandBus;
        $this->userManager = $userManager;
        $this->em = $em;
        $this->uuidGenerator = $uuidGenerator;
        $this->customerUniqueValidator = $customerUniqueValidator;
        $this->actionTokenManager = $actionTokenManager;
    }

    /**
     * @param CustomerId    $customerId
     * @param FormInterface $form
     *
     * @return Customer
     */
    public function onSuccess(CustomerId $customerId, FormInterface $form)
    {
        $customerData = $form->getData();
        if (!$customerData['company']['name'] && !$customerData['company']['nip']) {
            unset($customerData['company']);
        }
        $password = null;
        if ($form->has('plainPassword')) {
            $password = $customerData['plainPassword'];
            unset($customerData['plainPassword']);
        }
        $labels = [];

        /** @var Label $label */
        foreach ($form->get('labels')->getData() as $label) {
            $labels[] = $label->serialize();
        }

        $customerData['labels'] = $labels;

        $command = new RegisterCustomer($customerId, $customerData);

        $email = $customerData['email'];
        $emailExists = false;
        if ($email) {
            if ($this->userManager->isCustomerExist($email)) {
                $emailExists = 'This email is already taken';
            }
            try {
                $this->customerUniqueValidator->validateEmailUnique($email, $customerId);
            } catch (EmailAlreadyExistsException $e) {
                $emailExists = $e->getMessage();
            }
        }
        if ($emailExists) {
            $form->get('email')->addError(new FormError($emailExists));
        }
        if (isset($customerData['loyaltyCardNumber'])) {
            try {
                $this->customerUniqueValidator->validateLoyaltyCardNumberUnique(
                    $customerData['loyaltyCardNumber'],
                    $customerId
                );
            } catch (LoyaltyCardNumberAlreadyExistsException $e) {
                $form->get('loyaltyCardNumber')->addError(new FormError($e->getMessage()));
            }
        }
        if (isset($customerData['phone']) && $customerData['phone']) {
            try {
                $this->customerUniqueValidator->validatePhoneUnique($customerData['phone']);
            } catch (PhoneAlreadyExistsException $e) {
                $form->get('phone')->addError(new FormError($e->getMessage()));
            }
        }

        if ($form->getErrors(true)->count() > 0) {
            return $form->getErrors();
        }

        $this->commandBus->dispatch($command);
        if (isset($customerData['address'])) {
            $updateAddressCommand = new UpdateCustomerAddress($customerId, $customerData['address']);
            $this->commandBus->dispatch($updateAddressCommand);
        }
        if (isset($customerData['company']) && $customerData['company'] && $customerData['company']['name'] && $customerData['company']['nip']) {
            $updateCompanyDataCommand = new UpdateCustomerCompanyDetails($customerId, $customerData['company']);
            $this->commandBus->dispatch($updateCompanyDataCommand);
        }
        if (isset($customerData['loyaltyCardNumber'])) {
            $loyaltyCardCommand = new UpdateCustomerLoyaltyCardNumber($customerId, $customerData['loyaltyCardNumber']);
            $this->commandBus->dispatch($loyaltyCardCommand);
        }

        if (isset($customerData['level'])) {
            $this->commandBus->dispatch(
                new MoveCustomerToLevel($customerId, new LevelId($customerData['level']), true)
            );
        }

        return $this->userManager->createNewCustomer(
            $customerId,
            $email,
            $password,
            isset($customerData['phone']) ? $customerData['phone'] : null);
    }

    /**
     * @param User   $user
     * @param string $referralCustomerEmail
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleCustomerRegisteredByHimself(User $user, $referralCustomerEmail)
    {
        $user->setIsActive(false);
        if ($user instanceof Customer) {
            $user->setStatus(Status::typeNew());
            $user->setActionToken(substr(md5(uniqid(null, true)), 0, 20));
            $user->setReferralCustomerEmail($referralCustomerEmail);
            $this->actionTokenManager
                ->sendActivationMessage($user);
        }

        $this->em->flush();
    }
}
