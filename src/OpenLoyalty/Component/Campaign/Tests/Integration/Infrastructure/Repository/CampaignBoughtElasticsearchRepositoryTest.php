<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace OpenLoyalty\Component\Campaign\Tests\Integration\Infrastructure\Repository;

use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignBoughtRepository;
use OpenLoyalty\Component\Campaign\Infrastructure\Repository\CampaignBoughtElasticsearchRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class CampaignBoughtElasticsearchRepositoryTest.
 */
final class CampaignBoughtElasticsearchRepositoryTest extends KernelTestCase
{
    /**
     * @var CampaignBoughtElasticsearchRepository
     */
    private $campaignBoughtRepository;

    public function setUp()
    {
        parent::setUp();
        static::bootKernel();
        $this->campaignBoughtRepository = self::$kernel->getContainer()->get(CampaignBoughtRepository::class);
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
