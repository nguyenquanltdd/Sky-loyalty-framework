<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Tests\Unit\Domain;

use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\Model\CampaignPhoto;

/**
 * Class CampaignTest.
 */
class CampaignTest extends \PHPUnit_Framework_TestCase
{
    const CAMPAIGN_ID = '3a40b784-913f-45ee-8646-a78b2b4f5cef';

    /**
     * @var Campaign
     */
    private $campaignObject;

    public function setUp()
    {
        parent::setUp();
        $this->campaignObject = new Campaign(new CampaignId(self::CAMPAIGN_ID));

        $campaignPhoto = new CampaignPhoto();
        $campaignPhoto->setPath('some/path/to/photo.png');
        $campaignPhoto->setMime('some/mime');
        $campaignPhoto->setOriginalName('photo.png');
        $this->campaignObject->setCampaignPhoto($campaignPhoto);
    }

    /**
     * @test
     */
    public function it_returns_true_if_photo_exists()
    {
        $this->assertTrue($this->campaignObject->hasCampaignPhoto());
    }

    /**
     * @test
     */
    public function it_returns_false_if_photo_does_not_exist()
    {
        $this->campaignObject->setCampaignPhoto(null);
        $this->assertFalse($this->campaignObject->hasCampaignPhoto());
    }

    /**
     * @test
     */
    public function it_returns_false_if_photo_object_is_empty()
    {
        $this->campaignObject->setCampaignPhoto(null);
        $this->campaignObject->setCampaignPhoto(new CampaignPhoto());
        $this->assertFalse($this->campaignObject->hasCampaignPhoto());
    }
}
