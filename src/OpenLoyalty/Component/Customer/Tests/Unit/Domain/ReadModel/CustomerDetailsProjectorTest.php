<?php
/*
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace OpenLoyalty\Component\Customer\Tests\Unit\Domain\ReadModel;

use Broadway\ReadModel\Projector;
use Broadway\ReadModel\InMemory\InMemoryRepository;
use Broadway\ReadModel\Testing\ProjectorScenarioTestCase;
use Broadway\Repository\Repository;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerLevelWasRecalculated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasMovedToLevel;
use OpenLoyalty\Component\Customer\Domain\LevelId;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsProjector;
use OpenLoyalty\Component\Customer\Tests\Unit\Domain\Command\CustomerCommandHandlerTest;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerAddressWasUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerCompanyDetailsWereUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerDetailsWereUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerLoyaltyCardNumberWasUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasDeactivated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasRegistered;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Level\Domain\LevelRepository;
use OpenLoyalty\Component\Level\Domain\ReadModel\LevelDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Level\Domain\LevelId as LevelLevelId;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class CustomerDetailsProjectorTest.
 */
final class CustomerDetailsProjectorTest extends ProjectorScenarioTestCase
{
    const TEST_LEVEL_ID = '00000000-2222-0000-0000-000000000111';
    const TEST_LEVEL_NAME = 'Level name 1';

    /**
     * @return LevelDetails
     */
    protected function createTestLevelDetails()
    {
        $levelDetails = new LevelDetails(new LevelLevelId(self::TEST_LEVEL_ID));
        $levelDetails->setName(self::TEST_LEVEL_NAME);

        return $levelDetails;
    }

    /**
     * {@inheritdoc}
     */
    protected function createProjector(InMemoryRepository $repository): Projector
    {
        /** @var TransactionDetailsRepository|PHPUnit_Framework_MockObject_MockObject $transactionDetailsRepo */
        $transactionDetailsRepo = $this->getMockBuilder(TransactionDetailsRepository::class)->getMock();

        /** @var Levelrepository|PHPUnit_Framework_MockObject_MockObject $levelRepository */
        $levelRepository = $this->getMockBuilder(LevelRepository::class)->getMock();
        $levelRepository->method('byId')->willReturn($this->createTestLevelDetails());

        /** @var Repository|PHPUnit_Framework_MockObject_MockObject $transactionRepository */
        $transactionRepository = $this->getMockBuilder(Repository::class)->getMock();

        return new CustomerDetailsProjector($repository, $transactionDetailsRepo, $levelRepository, $transactionRepository);
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_from_empty_level_to_given_level()
    {
        $levelId = new LevelId(self::TEST_LEVEL_ID);
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();
        $data['levelId'] = $levelId->__toString();
        $data['level'] = [
            'id' => self::TEST_LEVEL_ID,
            'name' => self::TEST_LEVEL_NAME,
        ];

        $this->scenario
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
            ])
            ->when(new CustomerWasMovedToLevel($customerId, $levelId))
            ->then([
                $this->createBaseReadModel($customerId, $data),
            ]);
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_when_level_is_assigned_manually()
    {
        $levelId = new LevelId(self::TEST_LEVEL_ID);
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();
        $data['levelId'] = $levelId->__toString();
        $data['level'] = [
            'id' => self::TEST_LEVEL_ID,
            'name' => self::TEST_LEVEL_NAME,
        ];
        $data['manuallyAssignedLevelId'] = self::TEST_LEVEL_ID;

        $this->scenario
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
            ])
            ->when(new CustomerWasMovedToLevel($customerId, $levelId, true))
            ->then([
                $this->createBaseReadModel($customerId, $data),
            ]);
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_when_level_is_empty()
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();

        $this->scenario
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
            ])
            ->when(new CustomerWasMovedToLevel($customerId, null))
            ->then([
                $this->createBaseReadModel($customerId, $data),
            ]);
    }

    /**
     * @test
     */
    public function it_updates_last_level_recalculation_date()
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();
        $date = new \DateTime();
        /** @var CustomerDetails $baseReadModel */
        $baseReadModel = $this->createBaseReadModel($customerId, $data);
        $baseReadModel->setLastLevelRecalculation($date);
        $this->scenario
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
            ])
            ->when(new CustomerLevelWasRecalculated($customerId, $date))
            ->then([
                $baseReadModel,
            ]);
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_when_manually_assigned_level_is_removed()
    {
        $levelId = new LevelId(self::TEST_LEVEL_ID);
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();
        $data['levelId'] = $levelId->__toString();
        $data['level'] = [
            'id' => self::TEST_LEVEL_ID,
            'name' => self::TEST_LEVEL_NAME,
        ];
        $data['manuallyAssignedLevelId'] = null;

        $this->scenario
            ->given([
                new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()),
                new CustomerWasMovedToLevel($customerId, $levelId, true),
            ])
            ->when(new CustomerWasMovedToLevel($customerId, $levelId, false, true))
            ->then([
                $this->createBaseReadModel($customerId, $data),
            ]);
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_on_register(): void
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $this->scenario->given([])
            ->when(new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()))
            ->then(
                [
                    $this->createBaseReadModel($customerId, CustomerCommandHandlerTest::getCustomerData()),
                ]
            );
    }

    /**
     * @test
     */
    public function it_create_read_model_on_customer_registered_event_with_empty_phone_number(): void
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $customerData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'phone' => '',
            'birthDate' => 653011200,
            'createdAt' => 1470646394,
            'updatedAt' => 1470646394,
            'email' => 'customer@example.com',
            'status' => [
                'type' => 'new',
            ],
        ];
        $this->scenario
            ->given([])
            ->when(new CustomerWasRegistered($customerId, $customerData))
            ->then(
                [
                    $this->createBaseReadModel($customerId, $customerData),
                ]
            );
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_on_register_and_properly_sets_agreements()
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();
        $data['agreement1'] = true;
        $data['agreement2'] = false;
        $data['agreement3'] = true;

        $this->scenario->given(array())
            ->when(new CustomerWasRegistered($customerId, $data))
            ->then(array(
                $this->createBaseReadModel($customerId, $data),
            ));
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_on_register_and_properly_sets_labels()
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();
        $data['agreement1'] = true;
        $data['agreement2'] = false;
        $data['agreement3'] = true;
        $data['labels'] = [
            ['key' => 'l1', 'value' => 'v1'],
        ];

        $this->scenario->given(array())
            ->when(new CustomerWasRegistered($customerId, $data))
            ->then(array(
                $this->createBaseReadModel($customerId, $data),
            ));
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_on_register_and_address_update()
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $customerLoyaltyCardNumberWasUpdated = new CustomerLoyaltyCardNumberWasUpdated(
            $customerId,
            CustomerCommandHandlerTest::getCustomerData()['loyaltyCardNumber']
        );
        $data = CustomerCommandHandlerTest::getCustomerData();
        $data['updatedAt'] = $customerLoyaltyCardNumberWasUpdated->getUpdateAt()->getTimestamp();

        $this->scenario->given(array())
            ->when(new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()))
            ->when(new CustomerAddressWasUpdated($customerId, CustomerCommandHandlerTest::getCustomerData()['address']))
            ->when(new CustomerCompanyDetailsWereUpdated($customerId, CustomerCommandHandlerTest::getCustomerData()['company']))
            ->when($customerLoyaltyCardNumberWasUpdated)
            ->then(array(
                $this->createReadModel($customerId, $data),
            ));
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_on_register_and_deactivate()
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();
        $data['active'] = false;
        $data['address'] = null;
        $data['loyaltyCardNumber'] = null;
        $data['company'] = null;
        $data['status']['type'] = 'blocked';

        $this->scenario->given(array())
            ->when(new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()))
            ->when(new CustomerWasDeactivated($customerId))
            ->then(array(
                $this->createReadModel($customerId, $data),
            ));
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_on_register_and_name_update()
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');

        $data = CustomerCommandHandlerTest::getCustomerData();
        $data['firstName'] = 'Jane';
        unset($data['company']);
        unset($data['loyaltyCardNumber']);
        $customerDetailsWereUpdated = new CustomerDetailsWereUpdated($customerId, ['firstName' => 'Jane']);
        $data['updatedAt'] = $customerDetailsWereUpdated->getUpdateAt()->getTimestamp();

        $this->scenario->given(array())
            ->when(new CustomerWasRegistered($customerId, CustomerCommandHandlerTest::getCustomerData()))
            ->when(new CustomerAddressWasUpdated($customerId, CustomerCommandHandlerTest::getCustomerData()['address']))
            ->when($customerDetailsWereUpdated)
            ->then(array(
                $this->createReadModel($customerId, $data),
            ));
    }

    /**
     * @param CustomerId $customerId
     * @param array      $data
     *
     * @return CustomerDetails
     */
    private function createBaseReadModel(CustomerId $customerId, array $data)
    {
        $data['id'] = (string) $customerId;
        unset($data['loyaltyCardNumber']);
        unset($data['company']);
        unset($data['address']);

        return CustomerDetails::deserialize($data);
    }

    /**
     * @param CustomerId $customerId
     * @param array      $data
     *
     * @return CustomerDetails
     */
    private function createReadModel(CustomerId $customerId, array $data): CustomerDetails
    {
        $data['id'] = (string) $customerId;

        return CustomerDetails::deserialize($data);
    }
}
