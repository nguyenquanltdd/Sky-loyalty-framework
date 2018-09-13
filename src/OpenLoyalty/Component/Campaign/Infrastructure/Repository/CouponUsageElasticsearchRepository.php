<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Infrastructure\Repository;

use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CouponUsage;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CouponUsageRepository;
use OpenLoyalty\Component\Core\Infrastructure\Repository\OloyElasticsearchRepository;

/**
 * Class CouponUsageElasticsearchRepository.
 */
class CouponUsageElasticsearchRepository extends OloyElasticsearchRepository implements CouponUsageRepository
{
    public function countUsageForCampaign(CampaignId $campaignId)
    {
        $total = 0;
        $usages = $this->findBy(['campaignId' => $campaignId->__toString()]);
        /** @var CouponUsage $usage */
        foreach ($usages as $usage) {
            $total += $usage->getUsage();
        }

        return $total;
    }

    public function countUsageForCampaignAndCustomer(CampaignId $campaignId, CustomerId $customerId)
    {
        $total = 0;
        $all = $this->findBy([
            'campaignId' => $campaignId->__toString(),
            'customerId' => $customerId->__toString(),
        ]);

        /** @var CouponUsage $usage */
        foreach ($all as $usage) {
            $total += $usage->getUsage();
        }

        return $total;
    }

    public function findByCampaign(CampaignId $campaignId)
    {
        return $this->findBy(['campaignId' => $campaignId->__toString()]);
    }
}
