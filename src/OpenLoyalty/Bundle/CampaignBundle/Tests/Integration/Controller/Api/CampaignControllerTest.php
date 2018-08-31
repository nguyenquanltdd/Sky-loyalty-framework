<?php

namespace OpenLoyalty\Bundle\CampaignBundle\Tests\Integration\Controller\Api;

use OpenLoyalty\Bundle\CampaignBundle\DataFixtures\ORM\LoadCampaignData;
use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseApiTest;
use OpenLoyalty\Bundle\LevelBundle\DataFixtures\ORM\LoadLevelData;
use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadUserData;
use OpenLoyalty\Bundle\UtilityBundle\Tests\Integration\Traits\UploadedFileTrait;
use OpenLoyalty\Component\Account\Domain\CustomerId;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Customer\Domain\CampaignId as CustomerCampaignId;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CampaignControllerTest.
 */
class CampaignControllerTest extends BaseApiTest
{
    use UploadedFileTrait;

    /**
     * @var CampaignRepository
     */
    protected $campaignRepository;

    /**
     * @var CustomerDetailsRepository
     */
    private $customerDetailsRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        static::bootKernel();
        $this->campaignRepository = static::$kernel->getContainer()->get('oloy.campaign.repository');
        $this->customerDetailsRepository = static::$kernel->getContainer()->get('oloy.user.read_model.repository.customer_details');
    }

    /**
     * @test
     */
    public function it_updates_campaign_photo()
    {
        $imgContent = file_get_contents(__DIR__.'/../../../Resources/test.jpg');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN2_ID.'/photo',
            [],
            [
                'photo' => [
                    'file' => $this->createUploadedFile($imgContent, 'test.jpg', 'image/jpeg', UPLOAD_ERR_OK),
                ],
            ]
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
    }

    /**
     * @test
     * @depends it_updates_campaign_photo
     */
    public function it_gets_campaign_photo()
    {
        $fileHash = md5_file(__DIR__.'/../../../Resources/test.jpg');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN2_ID.'/photo'
        );
        $response = $client->getResponse();
        $this->assertEquals($fileHash, md5($response->getContent()), 'File has not been uploaded correctly.');
    }

    /**
     * @test
     * @depends it_gets_campaign_photo
     */
    public function it_removes_campaign_photo()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN2_ID.'/photo'
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
    }

    /**
     * @test
     */
    public function it_updates_campaign_brand()
    {
        $imgContent = file_get_contents(__DIR__.'/../../../Resources/test.jpg');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN2_ID.'/brand_icon',
            [],
            [
                'brand_icon' => [
                    'file' => $this->createUploadedFile($imgContent, 'test.jpg', 'image/jpeg', UPLOAD_ERR_OK),
                ],
            ]
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
    }

    /**
     * @test
     * @depends it_updates_campaign_brand
     */
    public function it_gets_campaign_brand()
    {
        $fileHash = md5_file(__DIR__.'/../../../Resources/test.jpg');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN2_ID.'/brand_icon'
        );
        $response = $client->getResponse();
        $this->assertEquals($fileHash, md5($response->getContent()), 'File has not been uploaded correctly.');
    }

    /**
     * @test
     * @depends it_gets_campaign_photo
     */
    public function it_removes_campaign_brand()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'DELETE',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN2_ID.'/brand_icon'
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
    }

    /**
     * @test
     */
    public function it_creates_campaign()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/campaign',
            [
                'campaign' => [
                    'name' => 'test',
                    'reward' => Campaign::REWARD_TYPE_GIFT_CODE,
                    'levels' => [LoadLevelData::LEVEL2_ID],
                    'segments' => [],
                    'unlimited' => false,
                    'limit' => 10,
                    'limitPerUser' => 2,
                    'coupons' => ['123'],
                    'costInPoints' => 12,
                    'brandDescription' => '_test_brand_desc_',
                    'campaignActivity' => [
                        'allTimeActive' => false,
                        'activeFrom' => (new \DateTime('2016-01-01'))->format('Y-m-d H:i'),
                        'activeTo' => (new \DateTime('2037-01-11'))->format('Y-m-d H:i'),
                    ],
                    'campaignVisibility' => [
                        'allTimeVisible' => false,
                        'visibleFrom' => (new \DateTime('2016-02-01'))->format('Y-m-d H:i'),
                        'visibleTo' => (new \DateTime('2037-02-11'))->format('Y-m-d H:i'),
                    ],
                    'labels' => 'key0:value0;key1:value1',
                    'taxPriceValue' => 99.95,
                    'tax' => 23,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('campaignId', $data);
        $campaign = $this->campaignRepository->byId(new CampaignId($data['campaignId']));
        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals(99.95, $campaign->getTaxPriceValue());
        $this->assertEquals(23, $campaign->getTax());
        $this->assertEquals('_test_brand_desc_', $campaign->getBrandDescription());
        $this->assertInternalType('array', $campaign->getLabels());
        $this->assertCount(2, $campaign->getLabels());
        foreach ($campaign->getLabels() as $key => $label) {
            $this->assertInstanceOf(Label::class, $label);
            $this->assertEquals('key'.$key, $label->getKey());
            $this->assertEquals('value'.$key, $label->getValue());
        }
    }

    /**
     * @test
     */
    public function it_creates_single_coupon_campaign()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/campaign',
            [
                'campaign' => [
                    'name' => 'test_single_coupon',
                    'reward' => Campaign::REWARD_TYPE_GIFT_CODE,
                    'levels' => [LoadLevelData::LEVEL2_ID],
                    'segments' => [],
                    'unlimited' => false,
                    'limit' => 10,
                    'limitPerUser' => 2,
                    'singleCoupon' => true,
                    'coupons' => ['123'],
                    'costInPoints' => 12,
                    'campaignActivity' => [
                        'allTimeActive' => false,
                        'activeFrom' => (new \DateTime('2016-01-01'))->format('Y-m-d H:i'),
                        'activeTo' => (new \DateTime('2037-01-11'))->format('Y-m-d H:i'),
                    ],
                    'campaignVisibility' => [
                        'allTimeVisible' => false,
                        'visibleFrom' => (new \DateTime('2016-02-01'))->format('Y-m-d H:i'),
                        'visibleTo' => (new \DateTime('2037-02-11'))->format('Y-m-d H:i'),
                    ],
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $campaign = $this->campaignRepository->byId(new CampaignId($data['campaignId']));
        $this->objectHasAttribute('singleCoupon', $campaign);
        $this->assertEquals(true, $campaign->isSingleCoupon());
    }

    /**
     * @test
     */
    public function it_updates_campaign()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN2_ID,
            [
                'campaign' => [
                    'name' => 'test',
                    'reward' => Campaign::REWARD_TYPE_GIFT_CODE,
                    'levels' => [LoadLevelData::LEVEL2_ID],
                    'segments' => [],
                    'active' => true,
                    'costInPoints' => 10,
                    'unlimited' => false,
                    'limit' => 10,
                    'limitPerUser' => 2,
                    'coupons' => ['123'],
                    'labels' => 'type:promotion',
                    'campaignActivity' => [
                        'allTimeActive' => false,
                        'activeFrom' => (new \DateTime('2016-01-01'))->format('Y-m-d H:i'),
                        'activeTo' => (new \DateTime('2037-01-11'))->format('Y-m-d H:i'),
                    ],
                    'campaignVisibility' => [
                        'allTimeVisible' => false,
                        'visibleFrom' => (new \DateTime('2016-02-01'))->format('Y-m-d H:i'),
                        'visibleTo' => (new \DateTime('2037-02-11'))->format('Y-m-d H:i'),
                    ],
                    'taxPriceValue' => 300.95,
                    'tax' => 23,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('campaignId', $data);
        $campaign = $this->campaignRepository->byId(new CampaignId($data['campaignId']));
        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('test', $campaign->getName());
        $this->assertEquals(300.95, $campaign->getTaxPriceValue());
        $this->assertEquals(23, $campaign->getTax());
        $this->assertInternalType('array', $campaign->getLabels());
        $this->assertCount(1, $campaign->getLabels());
        $label = $campaign->getLabels()[0];
        $this->assertInstanceOf(Label::class, $label);
        $this->assertEquals('type', $label->getKey());
        $this->assertEquals('promotion', $label->getValue());
    }

    /**
     * @test
     */
    public function it_validates_from()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/campaign',
            [
                'campaign' => [
                    'levels' => [LoadLevelData::LEVEL2_ID],
                    'segments' => [],
                    'unlimited' => false,
                    'limit' => 10,
                    'limitPerUser' => 2,
                    'coupons' => ['123'],
                    'singleCoupon' => false,
                    'campaignActivity' => [
                        'allTimeActive' => false,
                        'activeFrom' => (new \DateTime('2016-01-01'))->format('Y-m-d H:i'),
                        'activeTo' => (new \DateTime('2037-01-11'))->format('Y-m-d H:i'),
                    ],
                    'campaignVisibility' => [
                        'allTimeVisible' => false,
                        'visibleFrom' => (new \DateTime('2016-02-01'))->format('Y-m-d H:i'),
                        'visibleTo' => (new \DateTime('2037-02-11'))->format('Y-m-d H:i'),
                    ],
                ],
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 200');
    }

    /**
     * @test
     */
    public function it_returns_campaigns_list()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign'
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('campaigns', $data);
        $this->assertTrue(count($data['campaigns']) > 0, 'Contains at least one element');
    }

    /**
     * @test
     *
     * @dataProvider getCampaignsFilters
     *
     * @param array $filters
     * @param int   $expectedCount
     */
    public function it_filters_campaigns_list(array $filters, int $expectedCount)
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign',
            $filters
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('campaigns', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertCount($expectedCount, $data['campaigns']);
        $this->assertEquals($expectedCount, $data['total']);
    }

    /**
     * @return array
     */
    public function getCampaignsFilters()
    {
        return [
            [['labels' => [['key' => 'key0'], ['value' => 'value0']]], 1],
            [['labels' => [['key' => 'type']]], 4],
            [['labels' => [['key' => 'test']]], 0],
            [['active' => 1], 10],
            [['active' => 0], 8],
            [['campaignType' => 'discount_code'], 7],
        ];
    }

    /**
     * @test
     * @dataProvider sortParamsProvider
     */
    public function it_returns_campaigns_list_sorted($field, $direction, $oppositeDirection)
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            sprintf('/api/campaign?sort=%s&direction=%s', $field, $direction)
        );
        $sortedResponse = $client->getResponse();
        $sortedData = json_decode($sortedResponse->getContent(), true);
        $this->assertEquals(200, $sortedResponse->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('campaigns', $sortedData);
        $firstElementSorted = reset($sortedData['campaigns']);
        $sortedSize = count($sortedData['campaigns']);

        if ($sortedData['total'] < 2) {
            return;
        }

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            sprintf('/api/campaign?sort=%s&direction=%s', $direction, $oppositeDirection)
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $firstElement = reset($data['campaigns']);
        $size = count($data['campaigns']);

        $this->assertNotEquals($firstElement['campaignId'], $firstElementSorted['campaignId']);
        $this->assertEquals($size, $sortedSize);
    }

    /**
     * @test
     */
    public function it_returns_bought_campaigns_list()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign/bought'
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('boughtCampaigns', $data);
    }

    /**
     * @test
     */
    public function it_returns_bought_campaigns_list_filtered_by_future_date_from()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign/bought?purchasedAtFrom='.date('Y-m-d H:i:s')
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('boughtCampaigns', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(0, $data['total']);
    }

    /**
     * @test
     */
    public function it_returns_campaign()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN_ID
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('campaignId', $data);
        $this->assertArrayHasKey('hasPhoto', $data);
        $this->assertInternalType('bool', $data['hasPhoto']);
        $this->assertArrayHasKey('levels', $data);
        $this->assertInternalType('array', $data['levels']);
        $this->assertArrayHasKey('segments', $data);
        $this->assertInternalType('array', $data['segments']);
        $this->assertArrayHasKey('coupons', $data);
        $this->assertInternalType('array', $data['coupons']);
        $this->assertArrayHasKey('reward', $data);
        $this->assertInternalType('string', $data['reward']);
        $this->assertArrayHasKey('name', $data);
        $this->assertInternalType('string', $data['name']);
        $this->assertArrayHasKey('active', $data);
        $this->assertInternalType('bool', $data['active']);
        $this->assertArrayHasKey('costInPoints', $data);
        $this->assertInternalType('int', $data['costInPoints']);
        $this->assertArrayHasKey('singleCoupon', $data);
        $this->assertInternalType('bool', $data['singleCoupon']);
        $this->assertArrayHasKey('unlimited', $data);
        $this->assertInternalType('bool', $data['unlimited']);
        $this->assertArrayHasKey('limit', $data);
        $this->assertInternalType('int', $data['limit']);
        $this->assertArrayHasKey('limitPerUser', $data);
        $this->assertInternalType('int', $data['limitPerUser']);
        $this->assertArrayHasKey('campaignActivity', $data);
        $this->assertInternalType('array', $data['campaignActivity']);
        $this->assertArrayHasKey('campaignVisibility', $data);
        $this->assertInternalType('array', $data['campaignVisibility']);
        $this->assertArrayHasKey('segmentNames', $data);
        $this->assertInternalType('array', $data['segmentNames']);
        $this->assertArrayHasKey('levelNames', $data);
        $this->assertInternalType('array', $data['levelNames']);
        $this->assertArrayHasKey('usageLeft', $data);
        $this->assertInternalType('int', $data['usageLeft']);
        $this->assertArrayHasKey('visibleForCustomersCount', $data);
        $this->assertInternalType('int', $data['visibleForCustomersCount']);
        $this->assertArrayHasKey('usersWhoUsedThisCampaignCount', $data);
        $this->assertInternalType('int', $data['usersWhoUsedThisCampaignCount']);
        $this->assertEquals(LoadCampaignData::CAMPAIGN_ID, $data['campaignId']);
        $this->assertInternalType('array', $data['labels']);
        $this->assertCount(1, $data['labels']);
        $this->assertArrayHasKey('key', $data['labels'][0]);
        $this->assertArrayHasKey('value', $data['labels'][0]);

        $this->assertArrayHasKey('brandDescription', $data);
        $this->assertArrayHasKey('shortDescription', $data);
        $this->assertArrayHasKey('conditionsDescription', $data);
        $this->assertArrayHasKey('usageInstruction', $data);

        $this->assertEquals('_branddescription_', $data['brandDescription']);
        $this->assertEquals('_shortdescription_', $data['shortDescription']);
        $this->assertEquals('_conditionsdescription_', $data['conditionsDescription']);
        $this->assertEquals('_usageinstruction_', $data['usageInstruction']);
    }

    /**
     * @test
     */
    public function it_returns_campaign_using_html_format()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign/'.LoadCampaignData::CAMPAIGN_ID,
            [
                'format' => 'html',
            ]
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        $this->assertArrayHasKey('brandDescription', $data);
        $this->assertArrayHasKey('shortDescription', $data);
        $this->assertArrayHasKey('conditionsDescription', $data);
        $this->assertArrayHasKey('usageInstruction', $data);

        $this->assertEquals('<em>branddescription</em>', $data['brandDescription']);
        $this->assertEquals('<em>shortdescription</em>', $data['shortDescription']);
        $this->assertEquals('<em>conditionsdescription</em>', $data['conditionsDescription']);
        $this->assertEquals('<em>usageinstruction</em>', $data['usageInstruction']);
    }

    /**
     * @test
     */
    public function it_allows_to_buy_a_campaign_for_customer()
    {
        static::$kernel->boot();
        $customerDetailsBefore = $this->getCustomerDetails(LoadUserData::USER_USERNAME);
        $accountBefore = $this->getCustomerAccount(new CustomerId($customerDetailsBefore->getCustomerId()->__toString()));

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/admin/customer/'.$customerDetailsBefore->getCustomerId()->__toString().'/campaign/'.LoadCampaignData::CAMPAIGN2_ID.'/buy'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('coupon', $data);
        $customerDetails = $this->getCustomerDetails(LoadUserData::USER_USERNAME);
        $this->assertInstanceOf(CustomerDetails::class, $customerDetails);
        $campaigns = $customerDetails->getCampaignPurchases();
        $found = false;
        foreach ($campaigns as $campaignPurchase) {
            if ($campaignPurchase->getCampaignId()->__toString() == LoadCampaignData::CAMPAIGN2_ID) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Customer should have campaign purchase with campaign id = '.LoadCampaignData::CAMPAIGN2_ID);

        $accountAfter = $this->getCustomerAccount(new CustomerId($customerDetails->getCustomerId()->__toString()));
        $this->assertTrue(
            ($accountBefore ? $accountBefore->getAvailableAmount() : 0) - 10 == ($accountAfter ? $accountAfter->getAvailableAmount() : 0),
            'Available points after campaign is bought should be '.(($accountBefore ? $accountBefore->getAvailableAmount() : 0) - 10)
            .', but it is '.($accountAfter ? $accountAfter->getAvailableAmount() : 0)
        );
    }

    /**
     * @test
     */
    public function it_returns_active_campaigns_list()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign/active'
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('campaigns', $data);
    }

    /**
     * @return array
     */
    public function sortParamsProvider()
    {
        return [
            ['campaignId', 'asc', 'desc'],
        ];
    }

    /**
     * @test
     */
    public function it_returns_csv_response_when_exports_bought_data()
    {
        $filenamePrefix = static::$kernel->getContainer()->getParameter('oloy.campaign.bought.export.filename_prefix');
        $expectedHeaderData = sprintf('attachment; filename="%s', $filenamePrefix);
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/campaign/bought/export/csv'
        );
        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), 'Response should have status 200');
        $this->assertEquals(0, strpos($expectedHeaderData, $response->headers->get('content-disposition')));
    }

    /**
     * @param CustomerId $customerId
     *
     * @return AccountDetails|null
     */
    protected function getCustomerAccount(CustomerId $customerId)
    {
        $accountDetailsRepository = static::$kernel->getContainer()->get('oloy.points.account.repository.account_details');
        $accounts = $accountDetailsRepository->findBy(['customerId' => $customerId->__toString()]);
        if (count($accounts) == 0) {
            return;
        }

        return reset($accounts);
    }

    /**
     * @param $email
     *
     * @return CustomerDetails
     */
    protected function getCustomerDetails($email)
    {
        $customerDetails = $this->customerDetailsRepository->findBy(['email' => $email]);
        /** @var CustomerDetails $customerDetails */
        $customerDetails = reset($customerDetails);

        return $customerDetails;
    }

    /**
     * @test
     */
    public function it_change_coupon_to_used()
    {
        $customerDetails = $this->getCustomerDetails(LoadUserData::USER2_USERNAME);
        $couponCode = Uuid::uuid4()->toString();
        $customerDetails->addCampaignPurchase(
            new CampaignPurchase(
                new \DateTime(),
                0,
                new CustomerCampaignId(LoadCampaignData::CAMPAIGN_ID),
                new Coupon($couponCode),
                Campaign::REWARD_TYPE_DISCOUNT_CODE
            )
        );

        $this->customerDetailsRepository->save($customerDetails);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            sprintf(
                '/api/admin/customer/%s/campaign/%s/coupon/%s',
                LoadUserData::USER2_USER_ID,
                LoadCampaignData::CAMPAIGN_ID,
                $couponCode
            ),
            [
                'used' => true,
            ]
        );

        $response = $client->getResponse();

        $customerDetails = $this->getCustomerDetails(LoadUserData::USER2_USERNAME);
        $campaigns = $customerDetails->getCampaignPurchases();
        $campaignPurchase = null;

        /** @var CampaignPurchase $campaign */
        foreach ($campaigns as $campaign) {
            if ($campaign->getCoupon()->getCode() === $couponCode) {
                $campaignPurchase = $campaign;
            }
        }

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), 'Response should have status 200');
        $this->assertNotNull($campaignPurchase);
        $this->assertInstanceOf(CampaignPurchase::class, $campaignPurchase);
        $this->assertTrue($campaignPurchase->isUsed());
    }

    /**
     * @test
     */
    public function it_change_multiple_coupons_to_used()
    {
        $customerDetails = $this->getCustomerDetails(LoadUserData::USER2_USERNAME);
        $couponCode = Uuid::uuid4()->toString();
        $customerDetails->addCampaignPurchase(
            new CampaignPurchase(
                new \DateTime(),
                0,
                new CustomerCampaignId(LoadCampaignData::CAMPAIGN_ID),
                new Coupon($couponCode),
                Campaign::REWARD_TYPE_DISCOUNT_CODE
            )
        );

        $this->customerDetailsRepository->save($customerDetails);

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/admin/campaign/coupons/mark_as_used',
            [
                'coupons' => [
                        [
                            'customerId' => LoadUserData::USER2_USER_ID,
                            'campaignId' => LoadCampaignData::CAMPAIGN_ID,
                            'code' => $couponCode,
                            'used' => true,
                        ],
                    ],
            ]
        );

        $response = $client->getResponse();

        $customerDetails = $this->getCustomerDetails(LoadUserData::USER2_USERNAME);
        $campaigns = $customerDetails->getCampaignPurchases();
        $campaignPurchase = null;

        /** @var CampaignPurchase $campaign */
        foreach ($campaigns as $campaign) {
            if ($campaign->getCoupon()->getCode() === $couponCode) {
                $campaignPurchase = $campaign;
            }
        }

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode(), 'Response should have status 200');
        $this->assertNotNull($campaignPurchase);
        $this->assertInstanceOf(CampaignPurchase::class, $campaignPurchase);
        $this->assertTrue($campaignPurchase->isUsed());
    }
}
