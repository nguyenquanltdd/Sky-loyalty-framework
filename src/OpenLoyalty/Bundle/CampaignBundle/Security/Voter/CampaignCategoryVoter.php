<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\Security\Voter;

use OpenLoyalty\Bundle\UserBundle\Entity\User;
use OpenLoyalty\Component\Campaign\Domain\CampaignCategory;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class CampaignCategoryVoter.
 */
class CampaignCategoryVoter extends Voter
{
    const CREATE_CAMPAIGN_CATEGORY = 'CREATE_CAMPAIGN_CATEGORY';
    const EDIT = 'EDIT';
    const LIST_ALL_CAMPAIGN_CATEGORIES = 'LIST_ALL_CAMPAIGN_CATEGORIES';
    const VIEW = 'VIEW';

    /**
     * {@inheritdoc}
     */
    public function supports($attribute, $subject)
    {
        $allowEntity = $subject instanceof CampaignCategory && in_array($attribute, [self::EDIT, self::VIEW]);
        $allowGrid = $subject == null && in_array($attribute, [self::CREATE_CAMPAIGN_CATEGORY, self::LIST_ALL_CAMPAIGN_CATEGORIES]);

        return $allowEntity || $allowGrid;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::CREATE_CAMPAIGN_CATEGORY:
                return $user->hasRole('ROLE_ADMIN');
            case self::LIST_ALL_CAMPAIGN_CATEGORIES:
                return $user->hasRole('ROLE_ADMIN');
            case self::EDIT:
                return $user->hasRole('ROLE_ADMIN');
            case self::VIEW:
                return $user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_SELLER');
            default:
                return false;
        }
    }
}
