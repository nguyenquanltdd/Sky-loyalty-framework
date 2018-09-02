<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\PointsBundle\Tests\Integration\Controller\Api;

use Broadway\ReadModel\Repository;
use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseApiTest;
use OpenLoyalty\Bundle\PointsBundle\DataFixtures\ORM\LoadAccountsWithTransfersData;
use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadUserData;
use OpenLoyalty\Bundle\UserBundle\Service\MasterAdminProvider;
use OpenLoyalty\Bundle\UtilityBundle\Tests\Integration\Traits\UploadedFileTrait;
use OpenLoyalty\Component\Account\Domain\Model\PointsTransfer;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetails;
use OpenLoyalty\Component\Import\Infrastructure\ImportResultItem;

/**
 * Class PointsTransferControllerTest.
 */
class PointsTransferControllerTest extends BaseApiTest
{
    use UploadedFileTrait;

    /**
     * @test
     */
    public function it_imports_points_transfer()
    {
        $xmlContent = file_get_contents(__DIR__.'/../../../Resources/fixtures/import.xml');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/points/transfer/import',
            [],
            [
                'file' => [
                    'file' => $this->createUploadedFile($xmlContent, 'import.xml', 'application/xml', UPLOAD_ERR_OK),
                ],
            ]
        );

        $response = $client->getResponse();

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200'.$response->getContent());
        $this->assertArrayHasKey('items', $data);
        $this->assertCount(2, $data['items']);
        $this->assertArrayHasKey('status', $data['items'][0]);
        $this->assertTrue($data['items'][0]['status'] == ImportResultItem::SUCCESS);
    }

    /**
     * @test
     */
    public function it_fetches_transfer(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/points/transfer'
        );

        $response = $client->getResponse();

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertGreaterThanOrEqual(4, count($data['transfers']));
    }

    /**
     * @test
     */
    public function it_fetches_transfers_by_parameters(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/points/transfer',
            [
                'willExpireTill' => (new \DateTime('+14 days'))->format('Y-m-d'),
                'state' => [
                    'active',
                    'expired',
                ],
            ]
        );

        $response = $client->getResponse();

        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertEquals(8, $data['total']);
        $this->assertCount(8, $data['transfers']);
    }

    /**
     * @test
     */
    public function it_adds_points(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/points/transfer/add',
            [
                'transfer' => [
                    'customer' => LoadUserData::TEST_USER_ID,
                    'points' => 100,
                    'validityDuration' => 50,
                ],
            ]
        );

        $response = $client->getResponse();

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('pointsTransferId', $data);
    }

    /**
     * @test
     */
    public function it_throws_error_where_no_points_on_transfer(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/admin/p2p-points-transfer',
            [
                'transfer' => [
                    'sender' => LoadUserData::USER_TRANSFER_1_USER_ID,
                    'receiver' => LoadUserData::USER_TRANSFER_2_USER_ID,
                    'points' => 1000,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 400');
        $this->assertArrayHasKey('form', $data);
    }

    /**
     * @test
     */
    public function it_transfer_points(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/admin/p2p-points-transfer',
            [
                'transfer' => [
                    'sender' => LoadUserData::USER_TRANSFER_2_USER_ID,
                    'receiver' => LoadUserData::USER_TRANSFER_3_USER_ID,
                    'points' => 100,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('pointsTransferId', $data);
        $senderAccount = $this->getAccountIdByCustomerId(LoadUserData::USER_TRANSFER_2_USER_ID);
        $receiverAccount = $this->getAccountIdByCustomerId(LoadUserData::USER_TRANSFER_3_USER_ID);

        $this->assertEquals(0, $senderAccount->getAvailableAmount());
        $this->assertEquals(100, $receiverAccount->getAvailableAmount());
        $this->assertEquals(100, $senderAccount->getEarnedAmount());
        $this->assertEquals(100, $receiverAccount->getEarnedAmount());
    }

    /**
     * @test
     */
    public function it_not_transfers_points_when_there_is_no_points(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/admin/p2p-points-transfer',
            [
                'transfer' => [
                    'sender' => LoadUserData::USER_TRANSFER_3_USER_ID,
                    'receiver' => LoadUserData::USER_TRANSFER_1_USER_ID,
                    'points' => 100,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('pointsTransferId', $data);
        $senderAccount = $this->getAccountIdByCustomerId(LoadUserData::USER_TRANSFER_3_USER_ID);
        $receiverAccount = $this->getAccountIdByCustomerId(LoadUserData::USER_TRANSFER_1_USER_ID);

        $this->assertEquals(0, $senderAccount->getAvailableAmount());
        $this->assertEquals(200, $receiverAccount->getAvailableAmount());

        $client->request(
            'POST',
            '/api/admin/p2p-points-transfer',
            [
                'transfer' => [
                    'sender' => LoadUserData::USER_TRANSFER_3_USER_ID,
                    'receiver' => LoadUserData::USER_TRANSFER_1_USER_ID,
                    'points' => 10,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 400');
        $this->assertArrayHasKey('form', $data);
    }

    /**
     * @test
     */
    public function it_adds_points_as_api_when_logged_in_using_master_key(): void
    {
        $client = static::createClient();
        $container = static::$kernel->getContainer();
        $container->set(
            'OpenLoyalty\Bundle\UserBundle\Service\MasterAdminProvider',
            new MasterAdminProvider(
                '1234',
                $container->get('oloy.user.user_manager')
            )
        );

        $client->request(
            'POST',
            '/api/points/transfer/add',
            [
                'transfer' => [
                    'customer' => LoadUserData::TEST_USER_ID,
                    'points' => 100,
                ],
            ],
            [],
            ['HTTP_X-AUTH-TOKEN' => '1234']
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('pointsTransferId', $data);
        $transferId = $data['pointsTransferId'];

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/points/transfer',
            [
                'customerId' => LoadUserData::TEST_USER_ID,
                'type' => PointsTransferDetails::TYPE_ADDING,
                'sort' => 'createdAt',
                'direction' => 'DESC',
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('transfers', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertTrue(count($data['transfers']) > 0);
        $transfers = array_filter($data['transfers'], function ($transfer) use ($transferId) {
            return $transferId == $transfer['pointsTransferId'];
        });
        $transfer = reset($transfers);
        $this->assertArrayHasKey('pointsTransferId', $transfer);
        $this->assertArrayHasKey('issuer', $transfer);
        $this->assertEquals($transferId, $transfer['pointsTransferId']);
        $this->assertEquals(PointsTransfer::ISSUER_API, $transfer['issuer']);
    }

    /**
     * @test
     */
    public function it_spend_points()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/points/transfer/spend',
            [
                'transfer' => [
                    'customer' => LoadUserData::TEST_USER_ID,
                    'points' => 100,
                    'validityDuration' => 60,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('pointsTransferId', $data);
    }

    /**
     * @test
     */
    public function it_returns_error_when_there_is_not_enough_points()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/points/transfer/spend',
            [
                'transfer' => [
                    'customer' => LoadUserData::TEST_USER_ID,
                    'points' => 10000,
                ],
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 200');
    }

    /**
     * @test
     */
    public function it_returns_error_when_canceling_spend_transfer()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/points/transfer/'.LoadAccountsWithTransfersData::POINTS3_ID.'/cancel'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('this transfer cannot be canceled', $data['error']);
    }

    /**
     * @test
     */
    public function it_cancels_transfer()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/points/transfer/'.LoadAccountsWithTransfersData::POINTS2_ID.'/cancel'
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        static::$kernel->boot();
        $repo = static::$kernel->getContainer()->get('oloy.points.account.repository.points_transfer_details');
        $transfer = $repo->find(LoadAccountsWithTransfersData::POINTS2_ID);
        $this->assertEquals('canceled', $transfer->getState());
    }

    /**
     * @test
     */
    public function merchant_can_add_points_to_customer_by_pos_cockpit()
    {
        $client = $this->createAuthenticatedClient(LoadUserData::TEST_SELLER_USERNAME, LoadUserData::TEST_SELLER_PASSWORD, 'seller');

        $client->request(
            'POST',
            '/api/pos/points/transfer/add',
            [
                'transfer' => [
                    'customer' => LoadUserData::TEST_USER_ID,
                    'points' => 10,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('pointsTransferId', $data);
    }

    /**
     * @test
     */
    public function it_return_an_error_when_merchant_is_not_allowed_to_add_points_transfer()
    {
        $client = $this->createAuthenticatedClient(
            LoadUserData::TEST_SELLER2_USERNAME,
            LoadUserData::TEST_SELLER2_PASSWORD,
            'seller'
        );

        $client->request(
            'POST',
            '/api/pos/points/transfer/add',
            [
                'transfer' => [
                    'customer' => LoadUserData::TEST_USER_ID,
                    'points' => 10,
                ],
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), 'Response should have status 403');
    }

    /**
     * @param $customerId
     *
     * @return AccountDetails
     */
    protected function getAccountIdByCustomerId($customerId): AccountDetails
    {
        /** @var Repository $repo */
        $repo = self::$kernel->getContainer()->get('oloy.points.account.repository.account_details');
        $accounts = $repo->findBy(['customerId' => $customerId]);
        /** @var AccountDetails $account */
        $account = reset($accounts);

        return $account;
    }
}
