<?php

namespace OpenLoyalty\Bundle\PointsBundle\Tests\Integration\Controller\Api;

use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseApiTest;
use OpenLoyalty\Bundle\PointsBundle\DataFixtures\ORM\LoadAccountsWithTransfersData;
use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadUserData;
use OpenLoyalty\Bundle\UserBundle\Service\MasterAdminProvider;
use OpenLoyalty\Bundle\UtilityBundle\Tests\Integration\Traits\UploadedFileTrait;
use OpenLoyalty\Component\Account\Domain\Model\PointsTransfer;
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
        $xmlContent = file_get_contents(__DIR__.'/import.xml');

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
    public function it_fetches_transfer()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/points/transfer'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertTrue(count($data['transfers']) >= 4);
    }

    /**
     * @test
     */
    public function it_adds_points()
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
    public function it_adds_points_as_api_when_logged_in_using_master_key()
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
}
