<?php

namespace OpenLoyalty\Bundle\UserBundle\Tests\Integration\Controller\Api;

use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseApiTest;
use OpenLoyalty\Bundle\LevelBundle\DataFixtures\ORM\LoadLevelData;
use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadUserData;
use OpenLoyalty\Bundle\UtilityBundle\Tests\Integration\Traits\UploadedFileTrait;
use OpenLoyalty\Component\Customer\Tests\Domain\Command\CustomerCommandHandlerTest;
use OpenLoyalty\Component\Customer\Domain\PosId;
use OpenLoyalty\Component\Import\Infrastructure\ImportResultItem;

/**
 * Class CustomerControllerTest.
 */
class CustomerControllerTest extends BaseApiTest
{
    use UploadedFileTrait;

    /**
     * @test
     */
    public function it_allows_to_set_customer_level_manually()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            sprintf('/api/customer/%s/level', LoadUserData::USER_USER_ID),
            [
                'levelId' => LoadLevelData::LEVEL3_ID,
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(
            200,
            $response->getStatusCode(),
            'Response should have status 200'
        );
    }

    /**
     * @test
     */
    public function it_allows_to_remove_customer_manually_level()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            sprintf('/api/customer/%s/remove-manually-level', LoadUserData::USER_USER_ID)
        );

        $response = $client->getResponse();
        $this->assertEquals(
            204,
            $response->getStatusCode(),
            'Response should have status 204'
        );
    }

    /**
     * @test
     * @depends it_allows_to_remove_customer_manually_level
     */
    public function it_does_not_allow_to_remove_manually_level_when_is_not_manually_assigned()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            sprintf('/api/customer/%s/remove-manually-level', LoadUserData::USER_USER_ID)
        );

        $response = $client->getResponse();
        $this->assertEquals(
            400,
            $response->getStatusCode(),
            'Response should have status 400'
        );
    }

    /**
     * @test
     */
    public function it_allows_to_register_new_customer()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john@doe.com',
                    'gender' => 'male',
                    'birthDate' => '1990-01-01',
                    'address' => [
                        'street' => 'Bagno',
                        'address1' => '12',
                        'postal' => '00-800',
                        'city' => 'Warszawa',
                        'country' => 'PL',
                        'province' => 'mazowieckie',
                    ],
                    'agreement1' => true,
                    'agreement2' => true,
                    'loyaltyCardNumber' => '0000000011',
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('customerId', $data);
        $this->assertArrayHasKey('email', $data);
    }

    /**
     * @test
     */
    public function it_allows_to_register_new_customer_with_labels()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.john@doe.doe.com',
                    'gender' => 'male',
                    'birthDate' => '1990-01-01',
                    'address' => [
                        'street' => 'Bagno',
                        'address1' => '12',
                        'postal' => '00-800',
                        'city' => 'Warszawa',
                        'country' => 'PL',
                        'province' => 'mazowieckie',
                    ],
                    'labels' => 'l1:v1;l2:v2',
                    'agreement1' => true,
                    'agreement2' => true,
                    'loyaltyCardNumber' => '1000000011',
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('customerId', $data);
        $this->assertArrayHasKey('email', $data);

        self::$kernel->boot();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer/'.$data['customerId']
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('labels', $data);

        $labels = $data['labels'];
        $this->assertCount(2, $labels);
        $this->assertEquals('v1', $labels[0]['value']);
        $this->assertEquals('l1', $labels[0]['key']);
        $this->assertEquals('v2', $labels[1]['value']);
        $this->assertEquals('l2', $labels[1]['key']);
    }

    /**
     * @test
     */
    public function it_allows_to_register_new_customer_by_seller()
    {
        $client = $this->createAuthenticatedClient('john@doe.com', 'open', 'seller');
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john33@doe.com',
                    'gender' => 'male',
                    'birthDate' => '1990-01-01',
                    'levelId' => LoadLevelData::LEVEL3_ID,
                    'address' => [
                        'street' => 'Bagno',
                        'address1' => '12',
                        'postal' => '00-800',
                        'city' => 'Warszawa',
                        'country' => 'PL',
                        'province' => 'mazowieckie',
                    ],
                    'agreement1' => true,
                    'agreement2' => true,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('customerId', $data);
        $this->assertArrayHasKey('email', $data);
    }

    /**
     * @test
     */
    public function it_allows_to_register_new_customer_by_himself()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/customer/self_register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john22@doe.com',
                    'gender' => 'male',
                    'birthDate' => '1990-01-01',
                    'address' => [
                        'street' => 'Bagno',
                        'address1' => '12',
                        'postal' => '00-800',
                        'city' => 'Warszawa',
                        'country' => 'PL',
                        'province' => 'mazowieckie',
                    ],
                    'agreement1' => true,
                    'agreement2' => true,
                    'plainPassword' => 'OpenLoyalty123!',
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('customerId', $data);
        $this->assertArrayHasKey('email', $data);
    }

    /**
     * @test
     */
    public function it_allows_to_register_new_customer_with_only_required_data_and_some_data_in_address()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john@loyalty.com',
                    'agreement1' => true,
                    'address' => [
                        'street' => 'Bagno',
                        'postal' => '00-800',
                        'city' => 'Warszawa',
                    ],
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('customerId', $data);
        $this->assertArrayHasKey('email', $data);
    }

    /**
     * @test
     */
    public function it_properly_validates_address()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john3@doe.com',
                    'gender' => 'male',
                    'birthDate' => '1990-01-01',
                    'agreement1' => true,
                    'address' => [
                        'street' => 'Bagno',
                        'address1' => '12',
                        'postal' => '00-800',
                        'country' => 'PL',
                        'province' => 'mazowieckie',
                    ],
                    'loyaltyCardNumber' => '0000000011',
                ],
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 400');
    }

    /**
     * @test
     */
    public function it_allows_to_register_new_customer_without_certain_fields()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john2@doe.com',
                    'agreement1' => true,
                ],
            ]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('customerId', $data);
        $this->assertArrayHasKey('email', $data);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_register_new_customer_with_the_same_email()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john@doe.com',
                    'agreement1' => true,
                    'gender' => 'male',
                    'birthDate' => '1990-01-01',
                    'address' => [
                        'street' => 'Bagno',
                        'address1' => '12',
                        'postal' => '00-800',
                        'city' => 'Warszawa',
                        'country' => 'PL',
                        'province' => 'mazowieckie',
                    ],
                    'loyaltyCardNumber' => '000000000',
                ],
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 400');
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_register_new_customer_with_the_same_loyalty_card_number()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john2@doe.com',
                    'agreement1' => true,
                    'gender' => 'male',
                    'birthDate' => '1990-01-01',
                    'address' => [
                        'street' => 'Bagno',
                        'address1' => '12',
                        'postal' => '00-800',
                        'city' => 'Warszawa',
                        'country' => 'PL',
                        'province' => 'mazowieckie',
                    ],
                    'loyaltyCardNumber' => '0000000011',
                ],
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 400');
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_register_new_customer_with_the_same_phone()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john3@doe.com',
                    'agreement1' => true,
                    'gender' => 'male',
                    'birthDate' => '1990-01-01',
                    'address' => [
                        'street' => 'Bagno',
                        'address1' => '12',
                        'postal' => '00-800',
                        'city' => 'Warszawa',
                        'country' => 'PL',
                        'province' => 'mazowieckie',
                    ],
                    'phone' => '+48123123123',
                    'loyaltyCardNumber' => '0000000011',
                ],
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode(), 'Response should have status 400');
    }

    /**
     * @test
     */
    public function it_allows_to_edit_customer_details()
    {
        $client = $this->createAuthenticatedClient();
        $customerData = CustomerCommandHandlerTest::getCustomerData();
        $tmp = new \DateTime();
        $tmp->setTimestamp($customerData['birthDate']);
        $customerData['birthDate'] = $tmp->format('Y-m-d');
        unset($customerData['createdAt']);
        unset($customerData['updatedAt']);
        unset($customerData['status']);
        $customerData['firstName'] = 'Jane';
        $customerData['address']['street'] = 'Prosta';
        $client->request(
            'PUT',
            '/api/customer/'.LoadUserData::TEST_USER_ID,
            [
                'customer' => $customerData,
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($customerData['firstName'], $data['firstName']);
        $this->assertEquals($customerData['address']['street'], $data['address']['street']);

        self::$kernel->boot();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer/'.LoadUserData::TEST_USER_ID
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertEquals($customerData['firstName'], $data['firstName']);
        $this->assertEquals($customerData['address']['street'], $data['address']['street']);
    }

    /**
     * @test
     */
    public function it_allows_to_edit_customer_level()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer/'.LoadUserData::USER2_USER_ID
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $customerData['firstName'] = $data['firstName'];
        $customerData['lastName'] = $data['lastName'];
        $customerData['agreement1'] = true;
        $customerData['email'] = $data['email'];
        $customerData['phone'] = $data['phone'];
        $customerData['levelId'] = LoadLevelData::LEVEL4_ID;
        $client->request(
            'PUT',
            '/api/customer/'.LoadUserData::USER2_USER_ID,
            [
                'customer' => $customerData,
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $data = json_decode($response->getContent(), true);
        $this->assertEquals($customerData['levelId'], $data['level']['levelId']['id']);
        $this->assertEquals($customerData['levelId'], $data['levelId']);
        $this->assertEquals($customerData['levelId'], $data['manuallyAssignedLevelId']['levelId']);

        $client->request(
            'GET',
            '/api/customer/'.LoadUserData::USER2_USER_ID
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertEquals($customerData['levelId'], $data['levelId']);
    }

    /**
     * @test
     */
    public function it_allows_to_edit_customer_details_with_seller_assignment()
    {
        $client = $this->createAuthenticatedClient();
        $customerData = CustomerCommandHandlerTest::getCustomerData();
        $tmp = new \DateTime();
        $tmp->setTimestamp($customerData['birthDate']);
        $customerData['birthDate'] = $tmp->format('Y-m-d');
        unset($customerData['createdAt']);
        unset($customerData['updatedAt']);
        unset($customerData['status']);
        $customerData['firstName'] = 'Jane';
        $customerData['sellerId'] = LoadUserData::TEST_SELLER_ID;
        $customerData['address']['street'] = 'Prosta';
        $client->request(
            'PUT',
            '/api/customer/'.LoadUserData::TEST_USER_ID,
            [
                'customer' => $customerData,
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(LoadUserData::TEST_SELLER_ID, $data['sellerId']);

        self::$kernel->boot();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer/'.LoadUserData::TEST_USER_ID
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertEquals('Jane', $data['firstName']);
        $this->assertEquals(LoadUserData::TEST_SELLER_ID, $data['sellerId']);
    }

    /**
     * @test
     */
    public function it_allows_to_remove_customer_company()
    {
        $customerData = CustomerCommandHandlerTest::getCustomerData();
        $tmp = new \DateTime();
        $tmp->setTimestamp($customerData['birthDate']);
        $customerData['birthDate'] = $tmp->format('Y-m-d');
        $customerData['phone'] = '+48123123123';
        unset($customerData['createdAt']);
        unset($customerData['updatedAt']);
        unset($customerData['status']);
        $customerData['company'] = null;
        $client = $this->createAuthenticatedClient();
        $client->request(
            'PUT',
            '/api/customer/'.LoadUserData::TEST_USER_ID,
            [
                'customer' => $customerData,
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        self::$kernel->boot();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer/'.LoadUserData::TEST_USER_ID
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertTrue(empty($data['company']));
    }

    /**
     * @test
     */
    public function it_allows_to_get_customer_details()
    {
        self::$kernel->boot();
        $user = self::$kernel->getContainer()->get('doctrine.orm.entity_manager')
            ->getRepository('OpenLoyaltyUserBundle:User')->findOneBy(['email' => 'user@oloy.com']);
        $id = $user->getId();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer/'.$id
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertEquals('John', $data['firstName']);
        $this->assertEquals('male', $data['gender']);
    }

    /**
     * @test
     */
    public function it_allows_to_get_customers_list()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer'
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
    }

    /**
     * @test
     */
    public function it_allows_to_assign_pos_to_customer()
    {
        $client = $this->createAuthenticatedClient();
        $posId = new PosId('00000000-0000-0000-0000-000000000011');

        $client->request(
            'POST',
            '/api/customer/'.LoadUserData::TEST_USER_ID.'/pos',
            [
                'posId' => $posId->__toString(),
            ]
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        self::$kernel->boot();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer/'.LoadUserData::TEST_USER_ID
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertArrayHasKey('posId', $data);
        $this->assertEquals($posId->__toString(), $data['posId'], json_encode($data));
    }

    /**
     * @test
     */
    public function it_add_points_to_customer_after_register_on_agreement2_checked()
    {
        $client = $this->createAuthenticatedClient();

        /* Create new customer with marketing agreement */
        $client->request(
            'POST',
            '/api/customer/register',
            [
                'customer' => [
                    'firstName' => 'John Marks',
                    'lastName' => 'Doe Smith',
                    'email' => 'marketing@doe.com',
                    'agreement1' => true,
                    'agreement2' => true,
                ],
            ]
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        $responseBody = json_decode($response->getContent());
        $points = $this->getCustomerPoints($client, $responseBody->customerId);
        $this->assertEquals(85, $points);

        //Test newsletter subscribe flag
        $customer = $this->getCustomerEntity($responseBody->customerId);
        $this->assertTrue($customer->getNewsletterUsedFlag());
    }

    /**
     * @test
     */
    public function it_add_points_to_customer_after_self_register_customer_activation_on_agreement2_checked()
    {
        $client = $this->createAuthenticatedClient();
        $customerEmail = 'marketing_self@doe.com';

        /* Create new customer with marketing agreement */
        $client->request(
            'POST',
            '/api/customer/self_register',
            [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => $customerEmail,
                    'agreement1' => true,
                    'agreement2' => true,
                    'plainPassword' => 'OpenLoyalty123!',
                ],
            ]
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        $customerId = json_decode($response->getContent())->customerId;
        $points = $this->getCustomerPoints($client, $customerId);
        $this->assertEquals(0, $points);

        //Test newsletter subscribe flag
        $customer = $this->getCustomerEntity($customerId);
        $this->assertFalse($customer->getNewsletterUsedFlag());

        //Activate customer
        $activateToken = $this->getActivateTokenForCustomer($customerEmail);
        $client->request(
            'POST',
            '/api/customer/activate/'.$activateToken
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        //Check points from newsletter subscription event
        $points = $this->getCustomerPoints($client, $customerId);
        $this->assertEquals(85, $points);

        //Test newsletter subscribe flag
        $customer = $this->getCustomerEntity($customerId);
        $this->assertTrue($customer->getNewsletterUsedFlag());
    }

    /**
     * @test
     */
    public function if_add_points_to_customer_after_account_edit_and_agreement2_check()
    {
        $client = $this->createAuthenticatedClient();
        $customerId = LoadUserData::TEST_USER_ID;
        $points = $this->getCustomerPoints($client, $customerId);

        //Update customer data with checked agreement2
        $client->request(
            'GET',
            '/api/customer/'.$customerId
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $customerData = json_decode($response->getContent(), true);
        $this->assertEquals(false, $customerData['agreement2'], 'Agreement2 should be false');

        //Test newsletter subscribe flag
        $customer = $this->getCustomerEntity(LoadUserData::TEST_USER_ID);
        $this->assertFalse($customer->getNewsletterUsedFlag());

        $client->request(
            'PUT',
            '/api/customer/'.$customerId,
            [
                'customer' => [
                    'firstName' => $customerData['firstName'],
                    'lastName' => $customerData['lastName'],
                    'email' => $customerData['email'],
                    'agreement1' => true,
                    'agreement2' => true,
                ],
            ]
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        //Check customer points
        $expectedPoints = $points + 85;
        $this->assertEquals($expectedPoints, $this->getCustomerPoints($client, $customerId));

        //Test newsletter subscribe flag
        $customer = $this->getCustomerEntity(LoadUserData::TEST_USER_ID);
        $this->assertTrue($customer->getNewsletterUsedFlag());
    }

    /**
     * @test
     * @depends if_add_points_to_customer_after_account_edit_and_agreement2_check
     */
    public function it_dont_add_points_after_customer_send_false_agreement2()
    {
        $client = $this->createAuthenticatedClient();
        $customerId = LoadUserData::TEST_USER_ID;
        $points = $this->getCustomerPoints($client, $customerId);

        //Update customer data with checked agreement2
        $client->request(
            'GET',
            '/api/customer/'.$customerId
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $customerData = json_decode($response->getContent(), true);
        $this->assertEquals(true, $customerData['agreement2'], 'Agreement2 should be true');

        $client->request(
            'PUT',
            '/api/customer/'.$customerId,
            [
                'customer' => [
                    'firstName' => $customerData['firstName'],
                    'lastName' => $customerData['lastName'],
                    'email' => $customerData['email'],
                    'agreement1' => true,
                    'agreement2' => false,
                ],
            ]
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        //Check customer points
        $this->assertEquals($points, $this->getCustomerPoints($client, $customerId));

        //Test newsletter subscribe flag
        $customer = $this->getCustomerEntity($customerId);
        $this->assertTrue($customer->getNewsletterUsedFlag());
    }

    /**
     * @test
     */
    public function it_do_not_add_points_after_2nd_attempt_to_newsletter_subscription()
    {
        $client = $this->createAuthenticatedClient();
        $customerId = LoadUserData::TEST_USER_ID;
        $points = $this->getCustomerPoints($client, $customerId);

        //Test newsletter subscribe flag
        $customer = $this->getCustomerEntity($customerId);
        $this->assertTrue($customer->getNewsletterUsedFlag());

        //Update customer data with checked agreement2
        $client->request(
            'GET',
            '/api/customer/'.$customerId
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $customerData = json_decode($response->getContent(), true);
        $this->assertEquals(false, $customerData['agreement2'], 'Agreement2 should be false');

        $client->request(
            'PUT',
            '/api/customer/'.$customerId,
            [
                'customer' => [
                    'firstName' => $customerData['firstName'],
                    'lastName' => $customerData['lastName'],
                    'email' => $customerData['email'],
                    'agreement1' => true,
                    'agreement2' => true,
                ],
            ]
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        //Check customer points
        $this->assertEquals($points, $this->getCustomerPoints($client, $customerId));

        //Test newsletter subscribe flag
        $customer = $this->getCustomerEntity($customerId);
        $this->assertTrue($customer->getNewsletterUsedFlag());
    }

    /**
     * @test
     *
     * @dataProvider getPartialPhrases
     *
     * @param string $field
     * @param string $phrase
     * @param int    $counter
     */
    public function it_allows_to_get_customers_list_filtered_by_partial_phrase($field, $phrase, $counter)
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer',
            [$field => $phrase, 'perPage' => 1000]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertTrue(
            count($data['customers']) == $counter,
            'Expected records '.$counter.' but found '.count($data['customers'])
        );
        $this->assertEquals($counter, $data['total'], 'Expected total = '.$counter.' but found '.$data['total']);

        foreach ($data['customers'] as $customer) {
            $this->assertTrue(
                array_key_exists($field, $customer),
                'Field '.$field.' does not exists'
            );
            $this->assertTrue(
                (strpos($customer[$field], $phrase) !== false),
                'Searching phrase '.$phrase.' but found '.$customer[$field]
            );
        }
    }

    /**
     * @return array
     */
    public function getPartialPhrases()
    {
        return [
            ['firstName', 'Jo', 12],
            ['firstName', 'Marks', 1],
            ['firstName', '1', 1],
            ['firstName', 'John1', 1],
            ['lastName', 'Doe', 11],
            ['lastName', 'Doe1', 1],
            ['lastName', 'Smith', 2],
            ['phone', '48', 6],
            ['phone', '645', 2],
            ['email', '@', 17],
            ['email', 'user-1', 1],
            ['loyaltyCardNumber', '000000', 3],
            ['transactionsAmount', '3', 0],
            ['transactionsAmount', '60', 0],
            ['transactionsAmount', '15', 0],
            ['averageTransactionAmount', '3', 0],
            ['averageTransactionAmount', '15', 0],
            ['averageTransactionAmount', '7.5', 0],
            ['transactionsCount', '4', 0],
            ['transactionsCount', '2', 0],
            ['transactionsCount', '0', 12],
        ];
    }

    /**
     * @test
     *
     * @dataProvider getStrictPhrases
     *
     * @param string $field
     * @param string $phrase
     * @param int    $counter
     */
    public function it_allows_to_get_customers_list_filtered_by_strict_phrase($field, $phrase, $counter)
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer',
            [$field => $phrase, 'strict' => true, 'perPage' => 1000]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertTrue(
            count($data['customers']) == $counter,
            'Expected records '.$counter.' but found '.count($data['customers'])
        );
        $this->assertEquals($counter, $data['total'], 'Expected total = '.$counter.' but found '.$data['total']);

        foreach ($data['customers'] as $customer) {
            $this->assertTrue(
                array_key_exists($field, $customer),
                'Field '.$field.' does not exists'
            );
            $this->assertTrue(
                (strpos($customer[$field], $phrase) !== false),
                'Searching phrase '.$phrase.' but found '.$customer[$field]
            );
        }
    }

    /**
     * @return array
     */
    public function getStrictPhrases()
    {
        return [
            ['firstName', 'John', 9],
            ['firstName', 'John Marks', 1],
            ['firstName', '1', 0],
            ['firstName', 'John1', 1],
            ['lastName', 'Doe', 9],
            ['lastName', 'Doe Smith', 1],
            ['lastName', 'Doe1', 1],
            ['lastName', '1', 0],
            ['phone', '48', 0],
            ['phone', '+48123123123', 1],
            ['email', '@', 0],
            ['email', 'user-1', 0],
            ['loyaltyCardNumber', '000000', 1],
            ['transactionsAmount', '3', 0],
            ['transactionsAmount', '60', 0],
            ['transactionsAmount', '15', 0],
            ['averageTransactionAmount', '3', 0],
            ['averageTransactionAmount', '15', 0],
            ['averageTransactionAmount', '7.5', 0],
            ['transactionsCount', '4', 0],
            ['transactionsCount', '2', 0],
            ['transactionsCount', '0', 12],
        ];
    }

    /**
     * @test
     *
     * @dataProvider getEmailOrPhone
     *
     * @param string     $field
     * @param string     $phrase
     * @param int        $counter
     * @param array|null $columns
     * @param bool       $strict
     */
    public function it_allows_to_get_customers_list_filtered_by_email_or_phone($field, $phrase, $counter, array $columns = null, $strict = false)
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/customer',
            [$field => $phrase, 'strict' => $strict]
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
        $this->assertTrue(
            count($data['customers']) == $counter,
            'Expected records '.$counter.' but found '.count($data['customers'])
        );
        $this->assertEquals($counter, $data['total'], 'Expected total = '.$counter.' but found '.$data['total']);

        foreach ($data['customers'] as $customer) {
            $result = false;
            foreach ($columns as $column) {
                $this->assertTrue(
                    array_key_exists($column, $customer),
                    'Field '.$column.' does not exists'
                );

                $result = $result || (strpos($customer[$column], $phrase) !== false);
            }

            $this->assertTrue(
                $result,
                'Searching phrase '.$phrase.' not found '
            );
        }
    }

    /**
     * @test
     */
    public function it_imports_customers()
    {
        $xmlContent = file_get_contents(__DIR__.'/import.xml');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/admin/customer/import',
            [],
            [
                'file' => [
                    'file' => $this->createUploadedFile($xmlContent, 'import.xml', 'application/xml', UPLOAD_ERR_OK),
                ],
            ]
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        $this->assertArrayHasKey('items', $data);
        $this->assertCount(1, $data['items']);
        $this->assertArrayHasKey('status', $data['items'][0]);
        $this->assertTrue($data['items'][0]['status'] == ImportResultItem::SUCCESS);
    }

    /**
     * @test
     */
    public function it_gets_customer_status_as_an_administrator(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/admin/customer/'.LoadUserData::USER_USER_ID.'/status'
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');

        $this->assertArrayHasKey('firstName', $data);
        $this->assertArrayHasKey('lastName', $data);
        $this->assertArrayHasKey('customerId', $data);
        $this->assertArrayHasKey('points', $data);
        $this->assertArrayHasKey('totalEarnedPoints', $data);
        $this->assertArrayHasKey('usedPoints', $data);
        $this->assertArrayHasKey('expiredPoints', $data);
        $this->assertArrayHasKey('lockedPoints', $data);
        $this->assertArrayHasKey('level', $data);
        $this->assertArrayHasKey('levelName', $data);
        $this->assertArrayHasKey('levelConditionValue', $data);
        $this->assertArrayHasKey('nextLevel', $data);
        $this->assertArrayHasKey('nextLevelName', $data);
        $this->assertArrayHasKey('nextLevelConditionValue', $data);
        $this->assertArrayHasKey('transactionsAmountWithoutDeliveryCosts', $data);
        $this->assertArrayHasKey('averageTransactionsAmount', $data);
        $this->assertArrayHasKey('transactionsCount', $data);
        $this->assertArrayHasKey('transactionsAmount', $data);
        $this->assertArrayHasKey('currency', $data);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_get_customer_status_as_a_different_customer(): void
    {
        $client = $this->createAuthenticatedClient(LoadUserData::USER_USERNAME, LoadUserData::USER_PASSWORD, 'customer');
        $client->request(
            'GET',
            '/api/customer/'.LoadUserData::USER1_USER_ID.'/status'
        );
        $response = $client->getResponse();
        $this->assertEquals(403, $response->getStatusCode(), 'Response should have status 403');
    }

    /**
     * @test
     */
    public function it_gets_customer_status_as_a_client(): void
    {
        $client = $this->createAuthenticatedClient(LoadUserData::USER_USERNAME, LoadUserData::USER_PASSWORD, 'customer');
        $client->request(
            'GET',
            '/api/customer/'.LoadUserData::USER_USER_ID.'/status'
        );
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200');
    }

    /**
     * @test
     */
    public function it_receives_a_customer_status()
    {
        $client = $this->createAuthenticatedClient(LoadUserData::USER_USERNAME, LoadUserData::USER_PASSWORD, 'customer');
        $client->request(
            'GET',
            '/api/customer/level'
        );
        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode(), 'Response should have status 200'.$response->getContent());

        $this->assertArrayHasKey('levels', $data);
        $first = reset($data['levels']);

        $this->assertArrayHasKey('levelId', $first);
        $this->assertInternalType('string', $first['levelId']);
        $this->assertArrayHasKey('description', $first);
        $this->assertInternalType('string', $first['description']);
        $this->assertArrayHasKey('name', $first);
        $this->assertInternalType('string', $first['name']);
        $this->assertArrayHasKey('hasPhoto', $first);
        $this->assertInternalType('bool', $first['hasPhoto']);
        $this->assertArrayHasKey('conditionValue', $first);
        $this->assertInternalType('int', $first['conditionValue']);
    }

    /**
     * @return array
     */
    public function getEmailOrPhone()
    {
        return [
            ['emailOrPhone', 'user-1', 1, ['email', 'phone']],
            ['emailOrPhone', 'user-1@oloy.com', 1, ['email', 'phone']],
            ['emailOrPhone', '+48', 6, ['email', 'phone']],
            ['emailOrPhone', '645', 2, ['email', 'phone']],
        ];
    }
}
