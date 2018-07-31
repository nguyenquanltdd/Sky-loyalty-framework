<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\Command;

use Broadway\CommandHandling\SimpleCommandHandler;
use Broadway\EventDispatcher\EventDispatcher;
use OpenLoyalty\Bundle\AuditBundle\Service\AuditManagerInterface;
use OpenLoyalty\Component\Customer\Domain\Customer;
use OpenLoyalty\Component\Customer\Domain\CustomerRepository;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerActivatedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerAgreementsUpdatedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerDeactivatedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerLevelChangedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerRegisteredSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerRemovedManuallyLevelSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerSystemEvents;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerUpdatedSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\NewsletterSubscriptionSystemEvent;
use OpenLoyalty\Component\Customer\Domain\Validator\CustomerUniqueValidator;

/**
 * Class CustomerCommandHandler.
 */
class CustomerCommandHandler extends SimpleCommandHandler
{
    /**
     * @var CustomerRepository
     */
    private $repository;

    /**
     * @var CustomerUniqueValidator
     */
    private $customerUniqueValidator;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var AuditManagerInterface
     */
    private $auditManager;

    /**
     * CustomerCommandHandler constructor.
     *
     * @param CustomerRepository      $repository
     * @param CustomerUniqueValidator $customerUniqueValidator
     * @param EventDispatcher         $eventDispatcher
     * @param AuditManagerInterface   $auditManager
     */
    public function __construct(
        CustomerRepository $repository,
        CustomerUniqueValidator $customerUniqueValidator,
        EventDispatcher $eventDispatcher,
        AuditManagerInterface $auditManager
    ) {
        $this->repository = $repository;
        $this->customerUniqueValidator = $customerUniqueValidator;
        $this->eventDispatcher = $eventDispatcher;
        $this->auditManager = $auditManager;
    }

    /**
     * @param RegisterCustomer $command
     */
    public function handleRegisterCustomer(RegisterCustomer $command)
    {
        $customerData = $command->getCustomerData();
        if (isset($customerData['email']) && $customerData['email']) {
            $this->customerUniqueValidator->validateEmailUnique($customerData['email']);
        }
        if (isset($customerData['phone']) && $customerData['phone']) {
            $this->customerUniqueValidator->validatePhoneUnique($customerData['phone']);
        }
        /** @var Customer $customer */
        $customer = Customer::registerCustomer($command->getCustomerId(), $customerData);
        $this->repository->save($customer);

        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_REGISTERED,
            [new CustomerRegisteredSystemEvent($command->getCustomerId(), $customerData)]
        );
    }

    /**
     * @param UpdateCustomerAddress $command
     */
    public function handleUpdateCustomerAddress(UpdateCustomerAddress $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId);
        $customer->updateAddress($command->getAddressData());
        $this->repository->save($customer);
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_UPDATED,
            [new CustomerUpdatedSystemEvent($customerId)]
        );
    }

    /**
     * @param UpdateCustomerCompanyDetails $command
     */
    public function handleUpdateCustomerCompanyDetails(UpdateCustomerCompanyDetails $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId);
        $customer->updateCompanyDetails($command->getCompanyData());
        $this->repository->save($customer);
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_UPDATED,
            [new CustomerUpdatedSystemEvent($customerId)]
        );
    }

    /**
     * @param UpdateCustomerLoyaltyCardNumber $command
     */
    public function handleUpdateCustomerLoyaltyCardNumber(UpdateCustomerLoyaltyCardNumber $command)
    {
        $customerId = $command->getCustomerId();
        $this->customerUniqueValidator->validateLoyaltyCardNumberUnique($command->getCardNumber(), $customerId);
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId);
        $customer->updateLoyaltyCardNumber($command->getCardNumber());
        $this->repository->save($customer);
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_UPDATED,
            [new CustomerUpdatedSystemEvent($customerId)]
        );
    }

    /**
     * @param UpdateCustomerDetails $command
     */
    public function handleUpdateCustomerDetails(UpdateCustomerDetails $command)
    {
        $customerId = $command->getCustomerId();
        $customerData = $command->getCustomerData();
        if (isset($customerData['email'])) {
            $this->customerUniqueValidator->validateEmailUnique($customerData['email'], $customerId);
        }
        if (isset($customerData['phone']) && $customerData['phone']) {
            $this->customerUniqueValidator->validatePhoneUnique($customerData['phone'], $customerId);
        }
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $oldAgreements = [
            'agreement1' => $customer->isAgreement1(),
            'agreement2' => $customer->isAgreement2(),
            'agreement3' => $customer->isAgreement3(),
        ];

        $customer->updateCustomerDetails($customerData);
        $this->repository->save($customer);

        $newAgreements = [
            'agreement1' => [
                'new' => $customer->isAgreement1(),
                'old' => $oldAgreements['agreement1'],
            ],
            'agreement2' => [
                'new' => $customer->isAgreement2(),
                'old' => $oldAgreements['agreement2'],
            ],
            'agreement3' => [
                'new' => $customer->isAgreement3(),
                'old' => $oldAgreements['agreement3'],
            ],
        ];

        foreach ($newAgreements as $key => $agr) {
            if ($agr['new'] === $agr['old']) {
                unset($newAgreements[$key]);
            }
        }

        if (count($newAgreements) > 0) {
            $this->auditManager->auditCustomerEvent(AuditManagerInterface::AGREEMENTS_UPDATED_CUSTOMER_EVENT_TYPE, $customerId, $newAgreements);

            $this->eventDispatcher->dispatch(
                CustomerSystemEvents::CUSTOMER_AGREEMENTS_UPDATED,
                [new CustomerAgreementsUpdatedSystemEvent($customerId, $newAgreements)]
            );
        }

        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_UPDATED,
            [new CustomerUpdatedSystemEvent($customerId)]
        );
    }

    /**
     * @param MoveCustomerToLevel $command
     */
    public function handleMoveCustomerToLevel(MoveCustomerToLevel $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->addToLevel($command->getLevelId(), $command->isManually(), $command->isRemoveLevelManually());
        $this->repository->save($customer);
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_UPDATED,
            [new CustomerUpdatedSystemEvent($customerId)]
        );

        $this->eventDispatcher->dispatch(CustomerSystemEvents::CUSTOMER_LEVEL_CHANGED, [
            new CustomerLevelChangedSystemEvent($customerId, $command->getLevelId()),
        ]);
    }

    /**
     * @param AssignPosToCustomer $command'
     */
    public function handleAssignPosToCustomer(AssignPosToCustomer $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->assignPosToCustomer($command->getPosId());
        $this->repository->save($customer);
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_UPDATED,
            [new CustomerUpdatedSystemEvent($customerId)]
        );
    }

    /**
     * @param AssignSellerToCustomer $command
     */
    public function handleAssignSellerToCustomer(AssignSellerToCustomer $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->assignSellerToCustomer($command->getSellerId());
        $this->repository->save($customer);
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_UPDATED,
            [new CustomerUpdatedSystemEvent($customerId)]
        );
    }

    /**
     * @param BuyCampaign $command
     */
    public function handleBuyCampaign(BuyCampaign $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->buyCampaign(
            $command->getCampaignId(),
            $command->getCampaignName(),
            $command->getCostInPoints(),
            $command->getCoupon(),
            $command->getReward(),
            $command->getStatus(),
            $command->getActiveSince(),
            $command->getActiveTo()
        );
        $this->repository->save($customer);
    }

    /**
     * @param ChangeCampaignUsage $command
     */
    public function handleChangeCampaignUsage(ChangeCampaignUsage $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->changeCampaignUsage($command->getCampaignId(), $command->getCoupon(), $command->isUsed());
        $this->repository->save($customer);
    }

    /**
     * @param ActivateBoughtCampaign $command
     */
    public function handleActivateBoughtCampaign(ActivateBoughtCampaign $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->activateCampaignBought($command->getCampaignId(), $command->getCoupon());
        $this->repository->save($customer);
    }

    /**
     * @param ExpireBoughtCampaign $command
     */
    public function handleExpireBoughtCampaign(ExpireBoughtCampaign $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->expireCampaignBought($command->getCampaignId(), $command->getCoupon());
        $this->repository->save($customer);
    }

    /**
     * @param DeactivateCustomer $command
     */
    public function handleDeactivateCustomer(DeactivateCustomer $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->deactivate();
        $this->repository->save($customer);
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_DEACTIVATED,
            [new CustomerDeactivatedSystemEvent($customerId)]
        );
    }

    /**
     * @param ActivateCustomer $command
     */
    public function handleActivateCustomer(ActivateCustomer $command)
    {
        $customerId = $command->getCustomerId();
        /** @var Customer $customer */
        $customer = $this->repository->load($customerId->__toString());
        $customer->activate();
        $this->repository->save($customer);
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_ACTIVATED,
            [new CustomerActivatedSystemEvent($customerId)]
        );
    }

    /**
     * @param NewsletterSubscription $command
     */
    public function handleNewsletterSubscription(NewsletterSubscription $command)
    {
        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::NEWSLETTER_SUBSCRIPTION,
            [new NewsletterSubscriptionSystemEvent($command->getCustomerId())]
        );
    }

    /**
     * @param RemoveManuallyAssignedLevel $command
     */
    public function handleRemoveManuallyAssignedLevel(RemoveManuallyAssignedLevel $command)
    {
        $customerId = $command->getCustomerId();

        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_MANUALLY_LEVEL_REMOVED,
            [new CustomerRemovedManuallyLevelSystemEvent($customerId)]
        );

        $this->eventDispatcher->dispatch(
            CustomerSystemEvents::CUSTOMER_UPDATED,
            [new CustomerUpdatedSystemEvent($customerId)]
        );
    }
}
