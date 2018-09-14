<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Tests\Unit\Service;

use Broadway\CommandHandling\CommandBus;
use Doctrine\ORM\EntityManager;
use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadUserData;
use OpenLoyalty\Bundle\UserBundle\Entity\Customer;
use OpenLoyalty\Bundle\UserBundle\Service\RegisterCustomerManager;
use OpenLoyalty\Bundle\UserBundle\Service\UserManager;
use OpenLoyalty\Component\Customer\Domain\Command\MoveCustomerToLevel;
use OpenLoyalty\Component\Customer\Domain\Command\RegisterCustomer;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerAddress;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerCompanyDetails;
use OpenLoyalty\Component\Customer\Domain\Command\UpdateCustomerLoyaltyCardNumber;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Exception\LoyaltyCardNumberAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\Exception\PhoneAlreadyExistsException;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Customer\Domain\Validator\CustomerUniqueValidator;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class RegisterCustomerManagerTest.
 */
class RegisterCustomerManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param UserManager|null             $userManagerMock
     * @param CustomerUniqueValidator|null $customerUniqueValidator
     * @param CommandBus|null              $commandBus
     *
     * @return RegisterCustomerManager
     */
    protected function getRegisterCustomerManagerInstance(
        ?UserManager $userManagerMock = null,
        ?CustomerUniqueValidator $customerUniqueValidator = null,
        ?CommandBus $commandBus = null
    ): RegisterCustomerManager {
        $userManagerMock = $userManagerMock
            ?? $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();

        $userManagerMock->method('createNewCustomer')
                ->willReturn(new Customer(new CustomerId('00000000-0000-474c-b092-b0dd880c07e2')));

        $customerUniqueValidator = $customerUniqueValidator
            ?? $this->getMockBuilder(CustomerUniqueValidator::class)->disableOriginalConstructor()->getMock();
        $commandBus = $commandBus
            ?? $this->getMockBuilder(CommandBus::class)->disableOriginalConstructor()->getMock();

        /** @var CustomerDetailsRepository|PHPUnit_Framework_MockObject_MockObject $customerRepository */
        $customerRepository = $this->getMockBuilder(CustomerDetailsRepository::class)
            ->disableOriginalConstructor()->getMock();

        /** @var EntityManager|PHPUnit_Framework_MockObject_MockObject $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        /** @var TranslatorInterface|PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->getMockBuilder(TranslatorInterface::class)->disableOriginalConstructor()->getMock();

        return new RegisterCustomerManager(
            $userManagerMock,
            $customerUniqueValidator,
            $commandBus,
            $customerRepository,
            $entityManager,
            $translator
        );
    }

    /**
     * @test
     * @expectedException \OpenLoyalty\Component\Customer\Domain\Exception\EmailAlreadyExistsException
     */
    public function it_throws_exception_when_customer_with_the_same_email_exist()
    {
        $userManagerMock = $this->getMockBuilder(UserManager::class)->disableOriginalConstructor()->getMock();
        $userManagerMock->expects($this->once())->method('isCustomerExist')->willReturn(true);

        $customerManager = $this->getRegisterCustomerManagerInstance($userManagerMock);
        $customerManager->register(
            new CustomerId(LoadUserData::TEST_USER_ID),
            [
                'email' => 'mock@example.com',
            ]
        );
    }

    /**
     * @test
     * @expectedException \OpenLoyalty\Component\Customer\Domain\Exception\LoyaltyCardNumberAlreadyExistsException
     */
    public function it_throws_exception_when_customer_with_the_same_loyalty_card_exist()
    {
        $customerUniqueValidator = $this->getMockBuilder(CustomerUniqueValidator::class)->disableOriginalConstructor()->getMock();
        $customerUniqueValidator->expects($this->once())->method('validateLoyaltyCardNumberUnique')
            ->willThrowException(new LoyaltyCardNumberAlreadyExistsException());

        $customerManager = $this->getRegisterCustomerManagerInstance(null, $customerUniqueValidator);
        $customerManager->register(
            new CustomerId(LoadUserData::TEST_USER_ID),
            [
                'email' => 'mock@example.com',
                'loyaltyCardNumber' => '123456',
            ]
        );
    }

    /**
     * @test
     * @expectedException \OpenLoyalty\Component\Customer\Domain\Exception\PhoneAlreadyExistsException
     */
    public function it_throws_exception_when_customer_with_the_same_phone_exist()
    {
        $customerUniqueValidator = $this->getMockBuilder(CustomerUniqueValidator::class)->disableOriginalConstructor()->getMock();
        $customerUniqueValidator->expects($this->once())->method('validatePhoneUnique')
            ->willThrowException(new PhoneAlreadyExistsException());

        $customerManager = $this->getRegisterCustomerManagerInstance(null, $customerUniqueValidator);
        $customerManager->register(
            new CustomerId(LoadUserData::TEST_USER_ID),
            [
                'email' => 'mock@example.com',
                'loyaltyCardNumber' => '123456',
                'phone' => '+48123123',
            ]
        );
    }

    /**
     * @test
     */
    public function it_dispatch_only_register_command()
    {
        $commandBus = $this->getMockBuilder(CommandBus::class)->disableOriginalConstructor()->getMock();
        $commandBus->expects($this->once())->method('dispatch')
            ->with($this->isInstanceOf(RegisterCustomer::class));

        $customerManager = $this->getRegisterCustomerManagerInstance(null, null, $commandBus);
        $customerManager->register(
            new CustomerId(LoadUserData::TEST_USER_ID),
            [
                'email' => 'mock@example.com',
                'phone' => '+48123123',
            ]
        );
    }

    /**
     * @test
     */
    public function it_dispatch_register_command_and_related_commands()
    {
        $commandBus = $this->getMockBuilder(CommandBus::class)->disableOriginalConstructor()->getMock();
        $commandBus->expects($this->exactly(5))->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(RegisterCustomer::class)],
                [$this->isInstanceOf(UpdateCustomerAddress::class)],
                [$this->isInstanceOf(UpdateCustomerCompanyDetails::class)],
                [$this->isInstanceOf(UpdateCustomerLoyaltyCardNumber::class)],
                [$this->isInstanceOf(MoveCustomerToLevel::class)]
            );

        $customerManager = $this->getRegisterCustomerManagerInstance(null, null, $commandBus);
        $customerManager->register(
            new CustomerId(LoadUserData::TEST_USER_ID),
            [
                'email' => 'mock@example.com',
                'phone' => '+48123123',
                'address' => '',
                'company' => [
                    'name' => 'company_name',
                    'nip' => '889-11-22-981',
                ],
                'loyaltyCardNumber' => '123456',
                'level' => 'f99748f2-bf86-11e6-a4a6-cec0c932ce01',
            ]
        );
    }
}
