<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\ReadModel;

use Broadway\ReadModel\Repository;

interface CampaignBoughtRepository extends Repository
{
    /**
     * @param string $transactionId
     * @param string $customerId
     *
     * @return CampaignBought[]
     */
    public function findByTransactionIdAndCustomerId(string $transactionId, string $customerId): array;

    /**
     * @param string $customerId
     * @param bool   $used
     *
     * @return CampaignBought[]
     */
    public function findByCustomerIdAndUsed(string $customerId, bool $used): array;

    /**
     * @param string $customerId
     *
     * @return CampaignBought[]
     */
    public function findByCustomerId(string $customerId): array;
}
