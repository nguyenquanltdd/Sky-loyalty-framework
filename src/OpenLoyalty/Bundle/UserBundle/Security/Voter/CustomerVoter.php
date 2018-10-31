<?php
/*
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Security\Voter;

use OpenLoyalty\Bundle\SettingsBundle\Entity\BooleanSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Bundle\UserBundle\Entity\User;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Seller\Domain\ReadModel\SellerDetailsRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class CustomerVoter.
 */
class CustomerVoter extends Voter
{
    const ACTIVATE = 'ACTIVATE';
    const ASSIGN_POS = 'ASSIGN_POS';
    const ASSIGN_CUSTOMER_LEVEL = 'ASSIGN_CUSTOMER_LEVEL';
    const CREATE_CUSTOMER = 'CREATE_CUSTOMER';
    const DEACTIVATE = 'DEACTIVATE';
    const EDIT = 'EDIT';
    const EDIT_PROFILE = 'EDIT_PROFILE';
    const LIST_CUSTOMERS = 'LIST_CUSTOMERS';
    const VIEW = 'VIEW';
    const VIEW_STATUS = 'VIEW_STATUS';

    /**
     * @var SellerDetailsRepository
     */
    private $sellerDetailsRepository;

    /**
     * @var SettingsManager
     */
    private $settingsManager;

    /**
     * CustomerVoter constructor.
     *
     * @param SellerDetailsRepository $sellerDetailsRepository
     * @param SettingsManager         $settingsManager
     */
    public function __construct(SellerDetailsRepository $sellerDetailsRepository, SettingsManager $settingsManager)
    {
        $this->sellerDetailsRepository = $sellerDetailsRepository;
        $this->settingsManager = $settingsManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($attribute, $subject)
    {
        return $this->supportsCustomerDetails($subject, $attribute) || $this->supportsAnonymous($subject, $attribute);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::ACTIVATE:
                return $this->isSellerOrAdmin($user);
            case self::ASSIGN_CUSTOMER_LEVEL:
                return $this->canAddToLevel($user);
            case self::ASSIGN_POS:
                return $this->canAssignPos($user);
            case self::CREATE_CUSTOMER:
                return $this->canCreate($user);
            case self::DEACTIVATE:
                return $this->isSellerOrAdmin($user);
            case self::EDIT:
                return $this->canEdit($user, $subject);
            case self::EDIT_PROFILE:
                return $this->canEditProfile($user);
            case self::LIST_CUSTOMERS:
                return $this->isSellerOrAdmin($user);
            case self::VIEW:
                return $this->canView($user, $subject);
            case self::VIEW_STATUS:
                return $this->canView($user, $subject);
        }

        return false;
    }

    /**
     * @param User            $user
     * @param CustomerDetails $customerDetails
     *
     * @return bool
     */
    private function canView(User $user, CustomerDetails $customerDetails): bool
    {
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        if ($user->hasRole('ROLE_PARTICIPANT') && $customerDetails->getCustomerId() && (string) $customerDetails->getCustomerId() === $user->getId()) {
            return true;
        }

        if ($user->hasRole('ROLE_SELLER')) {
            return true;
        }

        return false;
    }

    /**
     * @param User            $user
     * @param CustomerDetails $subject
     *
     * @return bool
     */
    private function canAssignPos(User $user): bool
    {
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        if ($user->hasRole('ROLE_SELLER')) {
            return true;
        }

        return false;
    }

    /**
     * @param User            $user
     * @param CustomerDetails $subject
     *
     * @return bool
     */
    private function canAddToLevel(User $user): bool
    {
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        if ($user->hasRole('ROLE_SELLER')) {
            return true;
        }

        return false;
    }

    /**
     * @param User            $user
     * @param CustomerDetails $customerDetails
     *
     * @return bool
     */
    private function canEdit(User $user, CustomerDetails $customerDetails): bool
    {
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        if ($user->hasRole('ROLE_PARTICIPANT') && $customerDetails->getCustomerId() && (string) $customerDetails->getCustomerId() === $user->getId()) {
            return true;
        }

        if ($user->hasRole('ROLE_SELLER')) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    private function canCreate(User $user): bool
    {
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        if ($user->hasRole('ROLE_SELLER')) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    private function canEditProfile(User $user): bool
    {
        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_SELLER')) {
            return true;
        }

        /** @var null|BooleanSettingEntry $settingEntry */
        $settingEntry = $this->settingsManager->getSettingByKey('allowCustomersProfileEdits');

        if (null === $settingEntry) {
            return true;
        }

        return $settingEntry->getValue();
    }

    /**
     * @param $user
     *
     * @return bool
     */
    private function isSellerOrAdmin(User $user): bool
    {
        return $user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_SELLER');
    }

    /**
     * @param mixed  $subject
     * @param string $attribute
     *
     * @return bool
     */
    private function supportsCustomerDetails($subject, string $attribute): bool
    {
        return
            $subject instanceof CustomerDetails
            && \in_array($attribute, [
                self::ACTIVATE,
                self::ASSIGN_CUSTOMER_LEVEL,
                self::ASSIGN_POS,
                self::DEACTIVATE,
                self::EDIT,
                self::VIEW,
                self::VIEW_STATUS,
            ], true)
        ;
    }

    /**
     * @param mixed  $subject
     * @param string $attribute
     *
     * @return bool
     */
    private function supportsAnonymous($subject, string $attribute): bool
    {
        return
            $subject === null
            && \in_array($attribute, [
                self::LIST_CUSTOMERS,
                self::CREATE_CUSTOMER,
            ], true)
        ;
    }
}
