<?php
/*
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace OpenLoyalty\Bundle\CampaignBundle\Tests\Unit\Service;

use OpenLoyalty\Bundle\CampaignBundle\Service\CampaignProvider;
use OpenLoyalty\Bundle\CampaignBundle\Service\CampaignValidator;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignUsageRepository;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CouponUsageRepository;
use OpenLoyalty\Component\Campaign\Infrastructure\Persistence\Doctrine\Repository\DoctrineCampaignRepository;
use OpenLoyalty\Component\Customer\Infrastructure\Repository\CustomersBelongingToOneLevelElasticsearchRepository;
use OpenLoyalty\Component\Segment\Infrastructure\Repository\SegmentedCustomersElasticsearchRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class CampaignProviderTest.
 */
class CampaignProviderTest extends TestCase
{
    /**
     * @test
     * @dataProvider getUsageLeftForCustomerDataProvider
     *
     * @param int  $availableCouponsCount
     * @param bool $isSingleCoupon
     * @param int  $limitPerUser
     * @param bool $isUnlimited
     * @param int  $usageForCustomer
     * @param int  $expected
     */
    public function it_returns_usage_left_for_a_customer(
        int $availableCouponsCount,
        bool $isSingleCoupon,
        int $limitPerUser,
        bool $isUnlimited,
        int $usageForCustomer,
        int $expected
    ): void {
        $campaign = $this->getCampaign(
            $isSingleCoupon,
            $limitPerUser,
            $isUnlimited
        );

        /** @var SegmentedCustomersElasticsearchRepository|MockObject $segmentedCustomersRepository */
        $segmentedCustomersRepository = $this
            ->getMockBuilder(SegmentedCustomersElasticsearchRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CustomersBelongingToOneLevelElasticsearchRepository|MockObject $customerBelongingToOneLevelRepository */
        $customerBelongingToOneLevelRepository = $this
            ->getMockBuilder(CustomersBelongingToOneLevelElasticsearchRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CouponUsageRepository|MockObject $couponUsageRepository */
        $couponUsageRepository = $this
            ->getMockBuilder(CouponUsageRepository::class)
            ->getMock();
        $couponUsageRepository->method('countUsageForCampaignAndCustomer')->willReturn($usageForCustomer);
        $couponUsageRepository->method('countUsageForCampaignAndCustomerAndCode')->willReturn($usageForCustomer);

        /** @var CampaignValidator|MockObject $campaignValidator */
        $campaignValidator = $this
            ->getMockBuilder(CampaignValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CampaignUsageRepository|MockObject $campaignUsageRepository */
        $campaignUsageRepository = $this->getMockBuilder(CampaignUsageRepository::class)->getMock();

        /** @var DoctrineCampaignRepository|MockObject $campaignRepository */
        $campaignRepository = $this
            ->getMockBuilder(DoctrineCampaignRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CampaignProvider|MockObject $campaignProvider */
        $campaignProvider = $this->getMockBuilder(CampaignProvider::class)
            ->setConstructorArgs([
                $segmentedCustomersRepository,
                $customerBelongingToOneLevelRepository,
                $couponUsageRepository,
                $campaignValidator,
                $campaignUsageRepository,
                $campaignRepository,
            ])
            ->setMethods(['getCouponsUsageLeftCount'])
            ->getMock();

        $campaignProvider->method('getCouponsUsageLeftCount')->willReturn($availableCouponsCount);

        $result = $campaignProvider->getUsageLeftForCustomer(
            $campaign,
            new CustomerId('00000000-0000-0000-0000-000000000000')
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getUsageLeftForCustomerDataProvider(): array
    {
        return [
            [1, true, 1, false, 1, 0],
            [1, true, 10, false, 1, 1],
            [1, true, 1, false, 0, 1],
            [1, true, 1, false, 0, 1],
            [5, true, 5, false, 2, 3],
            [5, false, 5, false, 2, 3],
            [10, false, 1, false, 1, 0],
            [0, false, 10, false, 2, 0],
            [2, false, 10, false, 1, 2],
            [10, false, 10, true, 2, 10],
            [0, false, 10, true, -2, 0],
        ];
    }

    /**
     * @param bool $isSingleCoupon
     * @param int  $limitPerUser
     * @param bool $isUnlimited
     *
     * @return MockObject|Campaign
     */
    private function getCampaign(
        bool $isSingleCoupon,
        int $limitPerUser,
        bool $isUnlimited
    ): MockObject {
        $campaign = $this->getMockBuilder(Campaign::class)->getMock();
        $campaign->method('getCampaignId')->willReturn(
            new CampaignId('00000000-0000-0000-0000-000000000000')
        );
        $campaign->method('isSingleCoupon')->willReturn($isSingleCoupon);
        $campaign->method('getLimitPerUser')->willReturn($limitPerUser);
        $campaign->method('isUnlimited')->willReturn($isUnlimited);
        $campaign->method('getCoupons')->willReturn([]);

        return $campaign;
    }
}
