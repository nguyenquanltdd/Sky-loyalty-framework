<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Infrastructure\Repository;

use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignBoughtRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CampaignBoughtElasticsearchRepositoryTest.
 */
class CampaignBoughtElasticsearchRepositoryTest extends KernelTestCase
{
    /**
     * @var CampaignBoughtElasticsearchRepository
     */
    private $campaignBoughtRepository;

    public function setUp()
    {
        parent::setUp();
        static::bootKernel();
        $this->campaignBoughtRepository = self::$kernel->getContainer()->get('oloy.campaign.read_model.repository.campaign_bought');
    }

    /**
     * @test
     */
    public function it_returns_campaign_items_without_date_range_filter()
    {
        $items = $this->campaignBoughtRepository->findByParametersPaginated([]);
        $this->assertGreaterThan(0, count($items));
    }

    /**
     * @test
     */
    public function it_returns_campaign_items_with_date_range_filter_from_future()
    {
        $items = $this->campaignBoughtRepository->findByParametersPaginated(['purchasedAtFrom' => date('Y-m-d H:i:s', strtotime('+ 2 years'))]);
        $this->assertLessThanOrEqual(0, count($items));
    }

    /**
     * @test
     */
    public function it_returns_campaign_items_with_date_range_filter()
    {
        $items = $this->campaignBoughtRepository->findByParametersPaginated(['purchasedAtTo' => date('Y-m-d H:i:s', strtotime('+ 2 years'))]);
        $this->assertLessThanOrEqual(2, count($items));
    }
}
