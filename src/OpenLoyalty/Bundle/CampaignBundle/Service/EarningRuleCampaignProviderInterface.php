<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\Service;

/**
 * Interface EarningRuleCampaignProviderInterface.
 */
interface EarningRuleCampaignProviderInterface
{
    /**
     * @param string $campaignId
     *
     * @return bool
     */
    public function isActive(string $campaignId): bool;

    /**
     * @param string $campaignId
     * @param string $customerId
     *
     * @return bool
     */
    public function isValidForCustomer(string $campaignId, string $customerId): bool;
}
