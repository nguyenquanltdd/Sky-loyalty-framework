<?php

namespace OpenLoyalty\Bundle\CoreBundle\Tests\Integration;

use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadAdminData;
use OpenLoyalty\Bundle\UserBundle\Entity\Admin;
use OpenLoyalty\Bundle\UserBundle\Entity\Customer;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class BaseApiTest.
 */
abstract class BaseApiTest extends WebTestCase
{
    protected function createAuthenticatedClient($username = LoadAdminData::ADMIN_USERNAME, $password = LoadAdminData::ADMIN_PASSWORD, $type = 'admin')
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/'.$type.'/login_check',
            [
                '_username' => $username,
                '_password' => $password,
            ]
        );

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertTrue(
            isset($data['token']),
            sprintf(
                'Response should have field "token". %s%s',
                $client->getResponse()->getContent(),
                json_encode(
                    [
                        '/api/'.$type.'/login_check',
                        [
                            '_username' => $username,
                            '_password' => $password,
                        ],
                    ]
                )
            )
        );
        $this->assertTrue(
            isset($data['refresh_token']),
            sprintf(
                'Response should have field "refresh_token". %s',
                $client->getResponse()->getContent()
            )
        );

        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $data['token']));

        return $client;
    }

    /**
     * @param Client $client
     * @param string $customerId
     *
     * @return float
     */
    protected function getCustomerPoints(Client $client, $customerId)
    {
        $client->request(
            'GET',
            '/api/customer/'.$customerId.'/status'
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('points', $data);

        return (float) $data['points'];
    }

    /**
     * @param $customerEmail
     *
     * @return string
     */
    protected function getActivateTokenForCustomer($customerEmail)
    {
        $em = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');
        $activateToken = $em
            ->getRepository('OpenLoyaltyUserBundle:Customer')
            ->findOneBy(['email' => $customerEmail])
            ->getActionToken();

        return $activateToken;
    }

    /**
     * @param string $customerId
     *
     * @return null|Customer
     */
    protected function getCustomerEntity($customerId)
    {
        $customer = static::$kernel->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OpenLoyaltyUserBundle:Customer')->findOneBy(['id' => $customerId]);

        return $customer;
    }

    /**
     * @param string $adminId
     *
     * @return null|Admin
     */
    protected function getAdminEntity(string $adminId)
    {
        $admin = static::$kernel->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OpenLoyaltyUserBundle:Admin')->findOneBy(['id' => $adminId]);

        return $admin;
    }
}
