<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Tests\Domain\Command;

use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Customer\Domain\CampaignId;
use OpenLoyalty\Component\Customer\Domain\Command\ActivateBoughtCampaign;
use OpenLoyalty\Component\Customer\Domain\Command\ExpireBoughtCampaign;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignStatusWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasRegistered;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;

/**
 * Class ChangeBoughtCampaignStatusTest.
 */
class ChangeBoughtCampaignStatusTest extends CustomerCommandHandlerTest
{
    /**
     * @test
     */
    public function it_activate_bought_campaign()
    {
        $campaignId = new CampaignId('00000000-0000-0000-0000-000000000000');
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000001');
        $coupon = new Coupon('test');

        $this->scenario
            ->withAggregateId($customerId)
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
                new CampaignWasBoughtByCustomer($customerId, $campaignId, 'test', 99, $coupon, Campaign::REWARD_TYPE_DISCOUNT_CODE),
            ])
            ->when(new ActivateBoughtCampaign($customerId, $campaignId, $coupon))
            ->then([
                new CampaignStatusWasChanged($customerId, $campaignId, $coupon, CampaignPurchase::STATUS_ACTIVE),
            ]);
    }

    /**
     * @test
     */
    public function it_expire_bought_campaign()
    {
        $campaignId = new CampaignId('00000000-0000-0000-0000-000000000000');
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000001');
        $coupon = new Coupon('test');

        $this->scenario
            ->withAggregateId($customerId)
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
                new CampaignWasBoughtByCustomer($customerId, $campaignId, 'test', 99, $coupon, Campaign::REWARD_TYPE_DISCOUNT_CODE),
            ])
            ->when(new ExpireBoughtCampaign($customerId, $campaignId, $coupon))
            ->then([
                new CampaignStatusWasChanged($customerId, $campaignId, $coupon, CampaignPurchase::STATUS_EXPIRED),
            ]);
    }
}
