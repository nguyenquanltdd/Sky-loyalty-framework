<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Tests\Integration\Security;

use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseApiTest;
use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadUserData;
use OpenLoyalty\Bundle\UserBundle\Service\MasterAdminProvider;

/**
 * Class LoginTest.
 */
class LoginTest extends BaseApiTest
{
    /**
     * @dataProvider userProvider
     *
     * @param $username
     * @param $password
     * @param $type
     *
     * @test
     */
    public function it_accepts_correct_credentials($username, $password, $type)
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/'.$type.'/login_check',
            array(
                '_username' => $username,
                '_password' => $password,
            )
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue(isset($data['token']), 'Response should have field "token". '.$client->getResponse()->getContent());
        $this->assertTrue(isset($data['refresh_token']), 'Response should have field "refresh_token". '.$client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function it_returns_proper_response_code_after_login_failed()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/admin/login_check',
            array(
                '_username' => 'admin',
                '_password' => 'wrong_password',
            )
        );

        $this->assertTrue($client->getResponse()->getStatusCode() == 401, 'Status code should be 401');
    }

    /**
     * @test
     */
    public function it_returns_token_with_roles_included()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/admin/login_check',
            array(
                '_username' => LoadUserData::ADMIN_USERNAME,
                '_password' => LoadUserData::ADMIN_PASSWORD,
            )
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        $container = static::$kernel->getContainer();
        $token = $container->get('lexik_jwt_authentication.jwt_encoder')->decode($data['token']);
        $this->assertTrue(isset($token['roles']), 'Token should have field "roles"');
        $this->assertTrue(in_array('ROLE_ADMIN', $token['roles']), 'ROLE_ADMIN should be present in token');
    }

    /**
     * @test
     */
    public function it_accepts_correct_master_api_credentials()
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
            'GET',
            '/api/admin',
            [],
            [],
            ['HTTP_X-AUTH-TOKEN' => '1234']
        );

        $this->assertTrue($client->getResponse()->getStatusCode() == 200, 'Status code should be 200');
    }

    /**
     * @test
     */
    public function it_deny_access_invalid_master_api_credentials()
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
            'GET',
            '/api/admin',
            [],
            [],
            ['HTTP_X-AUTH-TOKEN' => 'invalid_token']
        );

        $this->assertTrue($client->getResponse()->getStatusCode() == 403, 'Status code should be 403');
    }

    /**
     * @return array
     */
    public function userProvider()
    {
        return [
            [LoadUserData::ADMIN_USERNAME, LoadUserData::ADMIN_PASSWORD, 'admin'],
            [LoadUserData::USER_USERNAME, LoadUserData::USER_PASSWORD, 'customer'],
            ['+48234234000', LoadUserData::USER_PASSWORD, 'customer'],
            [LoadUserData::TEST_SELLER_ID, 'open', 'seller'],
        ];
    }
}
