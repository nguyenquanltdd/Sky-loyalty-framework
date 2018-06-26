<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Tests\Unit\ReadModel;

use Broadway\ReadModel\SerializableReadModel;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignBought;

/**
 * Class CampaignBoughtTest.
 */
class CampaignBoughtTest extends \PHPUnit_Framework_TestCase
{
    const CAMPAIGN_ID = '3a40b784-913f-45ee-8646-a78b2b4f5cef';
    const CUSTOMER_ID = '16d23cb7-e27a-47f7-a010-84f53b66cde1';
    const PURCHASED_AT = '2018-01-23 15:01';
    const COUPON_CODE = '1234-4321';
    const CAMPAIGN_NAME = 'some-campaign';
    const CUSTOMER_EMAIL = 'user@oloy.com';
    const CUSTOMER_PHONE = '5551234554321';
    const CUSTOMER_NAME = 'Joe';
    const CUSTOMER_SURNAME = 'Doe';
    const COST_IN_POINTS = 100;
    const ACTIVE_POINTS = 940;
    const TAX_PRICE_VALUE = 23;

    /**
     * @var Campaign
     */
    private $campaignObject;

    /**
     * @var CampaignBought
     */
    private $campaignBoughtObject;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $campaignId = new CampaignId(self::CAMPAIGN_ID);
        $customerId = new CustomerId(self::CUSTOMER_ID);
        $this->campaignObject = new Campaign($campaignId);

        $this->campaignBoughtObject = new CampaignBought(
            $campaignId,
            $customerId,
            new \DateTime(self::PURCHASED_AT),
            new Coupon(self::COUPON_CODE),
            'regular',
            self::CAMPAIGN_NAME,
            self::CUSTOMER_EMAIL,
            self::CUSTOMER_PHONE,
            null,
            self::CUSTOMER_NAME,
            self::CUSTOMER_SURNAME,
            self::COST_IN_POINTS,
            self::ACTIVE_POINTS,
            self::TAX_PRICE_VALUE
        );
    }

    /**
     * @test
     */
    public function it_returns_right_interface()
    {
        $this->assertInstanceOf(SerializableReadModel::class, $this->campaignBoughtObject);
    }

    /**
     * @test
     */
    public function it_returns_generated_id_from_campaign_customer_and_level()
    {
        $this->assertEquals(
            CampaignBought::createId(
                new CampaignId(self::CAMPAIGN_ID),
                new CustomerId(self::CUSTOMER_ID),
                new Coupon(self::COUPON_CODE)
            ),
            $this->campaignBoughtObject->getId()
        );
    }

    /**
     * @test
     */
    public function it_returns_same_data_from_serialization()
    {
        $serializedData = $this->campaignBoughtObject->serialize();

        $this->assertArrayHasKey('campaignId', $serializedData);
        $this->assertEquals(self::CAMPAIGN_ID, $serializedData['campaignId']);

        $this->assertArrayHasKey('customerId', $serializedData);
        $this->assertEquals(self::CUSTOMER_ID, $serializedData['customerId']);

        $this->assertArrayHasKey('purchasedAt', $serializedData);
        $this->assertEquals((new \DateTime(self::PURCHASED_AT))->getTimestamp(), $serializedData['purchasedAt']);

        $this->assertArrayHasKey('coupon', $serializedData);
        $this->assertEquals(self::COUPON_CODE, $serializedData['coupon']);

        $this->assertArrayHasKey('campaignType', $serializedData);
        $this->assertEquals('regular', $serializedData['campaignType']);

        $this->assertArrayHasKey('campaignName', $serializedData);
        $this->assertEquals(self::CAMPAIGN_NAME, $serializedData['campaignName']);

        $this->assertArrayHasKey('customerEmail', $serializedData);
        $this->assertEquals(self::CUSTOMER_EMAIL, $serializedData['customerEmail']);

        $this->assertArrayHasKey('customerPhone', $serializedData);
        $this->assertEquals(self::CUSTOMER_PHONE, $serializedData['customerPhone']);

        $this->assertArrayHasKey('used', $serializedData);
        $this->assertNull($serializedData['used']);

        $this->assertArrayHasKey('customerName', $serializedData);
        $this->assertEquals(self::CUSTOMER_NAME, $serializedData['customerName']);

        $this->assertArrayHasKey('customerLastname', $serializedData);
        $this->assertEquals(self::CUSTOMER_SURNAME, $serializedData['customerLastname']);

        $this->assertArrayHasKey('costInPoints', $serializedData);
        $this->assertEquals(self::COST_IN_POINTS, $serializedData['costInPoints']);

        $this->assertArrayHasKey('currentPointsAmount', $serializedData);
        $this->assertEquals(self::ACTIVE_POINTS, $serializedData['currentPointsAmount']);

        $this->assertArrayHasKey('taxPriceValue', $serializedData);
        $this->assertEquals(self::TAX_PRICE_VALUE, $serializedData['taxPriceValue']);
    }

    /**
     * @test
     */
    public function it_returns_same_data_after_deserialization()
    {
        $deserializedObject = CampaignBought::deserialize(
            [
                'campaignId' => self::CAMPAIGN_ID,
                'customerId' => self::CUSTOMER_ID,
                'purchasedAt' => (new \DateTime(self::PURCHASED_AT))->getTimestamp(),
                'coupon' => self::COUPON_CODE,
                'campaignType' => 'regular',
                'campaignName' => self::CAMPAIGN_NAME,
                'customerEmail' => self::CUSTOMER_EMAIL,
                'customerPhone' => self::CUSTOMER_PHONE,
                'used' => null,
                'customerName' => self::CUSTOMER_NAME,
                'customerLastname' => self::CUSTOMER_SURNAME,
                'costInPoints' => self::COST_IN_POINTS,
                'currentPointsAmount' => self::ACTIVE_POINTS,
                'taxPriceValue' => self::TAX_PRICE_VALUE,
            ]
        );

        $serializedData = $deserializedObject->serialize();

        $this->assertArrayHasKey('campaignId', $serializedData);
        $this->assertEquals(self::CAMPAIGN_ID, $serializedData['campaignId']);

        $this->assertArrayHasKey('customerId', $serializedData);
        $this->assertEquals(self::CUSTOMER_ID, $serializedData['customerId']);

        $this->assertArrayHasKey('purchasedAt', $serializedData);
        $this->assertEquals((new \DateTime(self::PURCHASED_AT))->getTimestamp(), $serializedData['purchasedAt']);

        $this->assertArrayHasKey('coupon', $serializedData);
        $this->assertEquals(self::COUPON_CODE, $serializedData['coupon']);

        $this->assertArrayHasKey('campaignType', $serializedData);
        $this->assertEquals('regular', $serializedData['campaignType']);

        $this->assertArrayHasKey('campaignName', $serializedData);
        $this->assertEquals(self::CAMPAIGN_NAME, $serializedData['campaignName']);

        $this->assertArrayHasKey('customerEmail', $serializedData);
        $this->assertEquals(self::CUSTOMER_EMAIL, $serializedData['customerEmail']);

        $this->assertArrayHasKey('customerPhone', $serializedData);
        $this->assertEquals(self::CUSTOMER_PHONE, $serializedData['customerPhone']);

        $this->assertArrayHasKey('used', $serializedData);
        $this->assertNull($serializedData['used']);

        $this->assertArrayHasKey('customerName', $serializedData);
        $this->assertEquals(self::CUSTOMER_NAME, $serializedData['customerName']);

        $this->assertArrayHasKey('customerLastname', $serializedData);
        $this->assertEquals(self::CUSTOMER_SURNAME, $serializedData['customerLastname']);

        $this->assertArrayHasKey('costInPoints', $serializedData);
        $this->assertEquals(self::COST_IN_POINTS, $serializedData['costInPoints']);

        $this->assertArrayHasKey('currentPointsAmount', $serializedData);
        $this->assertEquals(self::ACTIVE_POINTS, $serializedData['currentPointsAmount']);

        $this->assertArrayHasKey('taxPriceValue', $serializedData);
        $this->assertEquals(self::TAX_PRICE_VALUE, $serializedData['taxPriceValue']);
    }
}
