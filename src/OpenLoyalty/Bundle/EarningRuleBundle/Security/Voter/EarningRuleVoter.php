<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\EarningRuleBundle\Security\Voter;

use OpenLoyalty\Bundle\UserBundle\Entity\User;
use OpenLoyalty\Bundle\UserBundle\Security\PermissionAccess;
use OpenLoyalty\Component\EarningRule\Domain\EarningRule;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class EarningRuleVoter.
 */
class EarningRuleVoter extends Voter
{
    const PERMISSION_RESOURCE = 'EARNING_RULE';

    const CREATE_EARNING_RULE = 'CREATE_EARNING_RULE';
    const EDIT = 'EDIT';
    const LIST_ALL_EARNING_RULES = 'LIST_ALL_EARNING_RULES';
    const VIEW = 'VIEW';
    const USE = 'USE';
    const LIST_ACTIVE_EARNING_RULES = 'LIST_ACTIVE_EARNING_RULES';
    const ACTIVATE = 'ACTIVATE';

    public function supports($attribute, $subject)
    {
        return $subject instanceof EarningRule && in_array($attribute, [
            self::EDIT, self::VIEW, self::ACTIVATE,
        ]) || $subject == null && in_array($attribute, [
            self::CREATE_EARNING_RULE, self::LIST_ALL_EARNING_RULES, self::LIST_ACTIVE_EARNING_RULES, self::USE,
        ]);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $viewAdmin = $user->hasRole('ROLE_ADMIN')
            && $user->hasPermission(self::PERMISSION_RESOURCE, [PermissionAccess::VIEW]);

        $fullAdmin = $user->hasRole('ROLE_ADMIN')
            && $user->hasPermission(self::PERMISSION_RESOURCE, [PermissionAccess::VIEW, PermissionAccess::MODIFY]);

        switch ($attribute) {
            case self::CREATE_EARNING_RULE:
                return $fullAdmin;
            case self::USE:
                return $fullAdmin;
            case self::LIST_ALL_EARNING_RULES:
                return $viewAdmin || $user->hasRole('ROLE_SELLER');
            case self::EDIT:
                return $fullAdmin;
            case self::ACTIVATE:
                return $fullAdmin;
            case self::VIEW:
                return $viewAdmin || $user->hasRole('ROLE_SELLER');
            case self::LIST_ACTIVE_EARNING_RULES:
                return $viewAdmin || $user->hasRole('ROLE_PARTICIPANT');
            default:
                return false;
        }
    }
}
