<?php

namespace OpenLoyalty\Bundle\EarningRuleBundle\Tests\Integration\Controller\Api;

use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseApiTest;
use OpenLoyalty\Bundle\EarningRuleBundle\DataFixtures\ORM\LoadEarningRuleData;
use OpenLoyalty\Bundle\EarningRuleBundle\Model\EarningRule;
use OpenLoyalty\Component\EarningRule\Domain\EarningRuleId;
use OpenLoyalty\Component\EarningRule\Domain\EarningRuleRepository;
use OpenLoyalty\Component\EarningRule\Domain\EventEarningRule;
use OpenLoyalty\Component\EarningRule\Domain\PointsEarningRule;
use OpenLoyalty\Component\EarningRule\Domain\ProductPurchaseEarningRule;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class EarningRuleControllerTest.
 */
class EarningRuleControllerTest extends BaseApiTest
{
    const LEVEL_ID = 'f99748f2-bf86-11e6-a4a6-cec0c932ce01';

    /**
     * @var EarningRuleRepository
     */
    protected $repository;

    protected function setUp()
    {
        parent::setUp();

        static::bootKernel();
        $this->repository = static::$kernel->getContainer()->get('oloy.earning_rule.repository');
    }

    /**
     * @test
     */
    public function it_creates_event_rule()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/earningRule',
            [
                'earningRule' => array_merge($this->getMainData(), [
                    'type' => EarningRule::TYPE_EVENT,
                    'eventName' => 'test event',
                    'pointsAmount' => 100,
                    'levels' => [
                        self::LEVEL_ID,
                    ],
                ]),
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('earningRuleId', $data);
        $rule = $this->repository->byId(new EarningRuleId($data['earningRuleId']));
        $this->assertInstanceOf(EventEarningRule::class, $rule);
    }

    /**
     * @test
     */
    public function it_creates_purchase_product_rule()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/earningRule',
            [
                'earningRule' => array_merge($this->getMainData(), [
                    'type' => EarningRule::TYPE_PRODUCT_PURCHASE,
                    'skuIds' => ['test sku'],
                    'pointsAmount' => 100,
                    'levels' => [
                        self::LEVEL_ID,
                    ],
                ]),
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('earningRuleId', $data);
        $rule = $this->repository->byId(new EarningRuleId($data['earningRuleId']));
        $this->assertInstanceOf(ProductPurchaseEarningRule::class, $rule);
    }

    /**
     * @test
     */
    public function it_creates_points_rule()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/earningRule',
            [
                'earningRule' => array_merge($this->getMainData(), [
                    'type' => EarningRule::TYPE_POINTS,
                    'pointValue' => 1.1,
                    'excludedSKUs' => '123;222;111',
                    'excludedLabels' => 'asas:aaa;ccc:eee',
                    'excludeDeliveryCost' => true,
                    'minOrderValue' => 111.11,
                    'levels' => [
                        self::LEVEL_ID,
                    ],
                ]),
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('earningRuleId', $data);
        /** @var PointsEarningRule $rule */
        $rule = $this->repository->byId(new EarningRuleId($data['earningRuleId']));
        $this->assertInstanceOf(PointsEarningRule::class, $rule);
        $this->assertCount(2, $rule->getExcludedLabels());
        $this->assertCount(3, $rule->getExcludedSKUs());
        $this->assertEquals(111.11, $rule->getMinOrderValue());
    }

    /**
     * @test
     */
    public function it_returns_earning_rule_with_proper_type()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/earningRule/'.LoadEarningRuleData::EVENT_RULE_ID
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('hasPhoto', $data);
        $this->assertInternalType('bool', $data['hasPhoto']);
        $this->assertEquals(EarningRule::TYPE_EVENT, $data['type']);
    }

    /**
     * @test
     */
    public function it_returns_earning_rules()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/earningRule'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('earningRules', $data);
        $this->assertTrue(count($data['earningRules']) > 0, 'There should be at least one earning rule');
    }

    /**
     * @test
     */
    public function it_allows_to_edit_rule()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/earningRule/'.LoadEarningRuleData::EVENT_RULE_ID,
            [
                'earningRule' => array_merge($this->getMainData(), [
                    'eventName' => 'test event - edited',
                    'pointsAmount' => 100,
                    'levels' => [
                        self::LEVEL_ID,
                    ],
                ]),
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('earningRuleId', $data);
        $rule = $this->repository->byId(new EarningRuleId($data['earningRuleId']));
        $this->assertInstanceOf(EventEarningRule::class, $rule);
        $this->assertEquals('test event - edited', $rule->getEventName());
    }

    /**
     * @test
     */
    public function it_adds_new_photo_to_earning_rule()
    {
        $rules = $this->repository->findAllActive();
        /** @var EarningRule $first */
        $first = reset($rules);
        $client = $this->createAuthenticatedClient();
        $filesystem = static::$kernel->getContainer()->get('filesystem');
        $filesystem->copy(__DIR__.'/../../../data/sample.png', __DIR__.'/../../../data/sample_test.png');
        $uploadedFile = new UploadedFile(__DIR__.'/../../../data/sample_test.png', 'sample_test.png');

        $client->request(
            'POST',
            '/api/earningRule/'.$first->getEarningRuleId().'/photo',
            [],
            [
                'photo' => ['file' => $uploadedFile],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('200', $response->getStatusCode());

        $getClient = $this->createAuthenticatedClient();
        $getClient->request(
            'GET',
            '/api/earningRule/'.$first->getEarningRuleId().'/photo'
        );
        $getResponse = $getClient->getResponse();
        $this->assertEquals(200, $getResponse->getStatusCode());
    }

    public function it_removes_photo_from_earning_rule()
    {
        $rules = $this->repository->findAllActive();

        if (!count($rules)) {
            return;
        }

        /** @var EarningRule $first */
        $first = reset($rules);

        $client = $this->createAuthenticatedClient();
        $filesystem = static::$kernel->getContainer()->get('filesystem');
        $filesystem->copy(__DIR__.'/../../../data/sample.png', __DIR__.'/../../../data/sample_test.png');
        $uploadedFile = new UploadedFile(__DIR__.'/../../../data/sample_test.png', 'sample_test.png');

        $client->request(
            'POST',
            '/api/earningRule/'.$first->getEarningRuleId().'/photo',
            [],
            [
                'photo' => ['file' => $uploadedFile],
            ]
        );

        $client->request(
            'GET',
            '/api/earningRule/'.$first->getEarningRuleId().'/photo'
        );
        $getResponse = $client->getResponse();
        $this->assertEquals(200, $getResponse->getStatusCode());

        $client->request(
            'DELETE',
            '/api/earningRule/'.$first->getEarningRuleId().'/photo'
        );

        $deleteResponse = $client->getResponse();

        $this->assertEquals(200, $deleteResponse->getStatusCode());

        $client->request(
            'GET',
            '/api/earningRule/'.$first->getEarningRuleId().'/photo'
        );
        $checkResponse = $client->getResponse();
        $this->assertEquals(404, $checkResponse->getStatusCode());
    }

    protected function getMainData($name = 'test')
    {
        return [
            'name' => $name,
            'description' => 'sth',
            'startAt' => '2016-08-01',
            'endAt' => '2016-10-10',
            'active' => false,
            'allTimeActive' => false,
        ];
    }
}
