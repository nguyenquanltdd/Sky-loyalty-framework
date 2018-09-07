<?php

namespace OpenLoyalty\Component\Customer\Tests\Domain\Command;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use OpenLoyalty\Bundle\AuditBundle\Service\AuditManagerInterface;
use OpenLoyalty\Component\Customer\Domain\Command\CustomerCommandHandler;
use OpenLoyalty\Component\Customer\Domain\CustomerRepository;
use OpenLoyalty\Component\Customer\Domain\Validator\CustomerUniqueValidator;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;

/**
 * Class CustomerCommandHandlerTest.
 */
abstract class CustomerCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus, AuditManagerInterface $auditManager = null): CommandHandler
    {
        $eventDispatcher = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $eventDispatcher->method('dispatch')->with($this->isType('string'))->willReturn(true);

        if (null === $auditManager) {
            $auditManager = $this->getMockBuilder(AuditManagerInterface::class)->getMock();
        }

        return $this->getCustomerCommandHandler($eventStore, $eventBus, $eventDispatcher, $auditManager, null);
    }

    public static function getCustomerData()
    {
        return [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'gender' => 'male',
            'email' => 'customer@open.com',
            'birthDate' => 653011200,
            'phone' => '+48123123123',
            'createdAt' => 1470646394,
            'loyaltyCardNumber' => '000000',
            'updatedAt' => 1470646394,
            'agreement1' => true,
            'company' => [
                'name' => 'test',
                'nip' => 'nip',
            ],
            'address' => [
                'street' => 'Dmowskiego',
                'address1' => '21',
                'city' => 'Wrocław',
                'country' => 'PL',
                'postal' => '50-300',
                'province' => 'Dolnośląskie',
            ],
            'status' => [
                'type' => 'new',
            ],
        ];
    }

    /**
     * @param EventStore                 $eventStore
     * @param EventBus                   $eventBus
     * @param EventDispatcher            $eventDispatcher
     * @param AuditManagerInterface      $auditManager
     * @param LevelDowngradeModeProvider $levelDowngradeModeProvider
     *
     * @return \OpenLoyalty\Component\Customer\Domain\Command\CustomerCommandHandler
     */
    protected function getCustomerCommandHandler(
        EventStore $eventStore,
        EventBus $eventBus,
        EventDispatcher $eventDispatcher,
        AuditManagerInterface $auditManager = null,
        LevelDowngradeModeProvider $levelDowngradeModeProvider = null)
    {
        $customerDetailsRepository = $this->getMockBuilder('Broadway\ReadModel\Repository')->getMock();
        $customerDetailsRepository->method('findBy')->willReturn([]);
        $validator = new CustomerUniqueValidator($customerDetailsRepository);

        if (null === $auditManager) {
            $auditManager = $this->getMockBuilder(AuditManagerInterface::class)->getMock();
        }

        if (null === $levelDowngradeModeProvider) {
            $levelDowngradeModeProvider = $this->getMockBuilder(LevelDowngradeModeProvider::class)->getMock();
            $levelDowngradeModeProvider->method('getBase')->willReturn(LevelDowngradeModeProvider::BASE_ACTIVE_POINTS);
            $levelDowngradeModeProvider->method('getMode')->willReturn(LevelDowngradeModeProvider::MODE_NONE);
        }

        return new CustomerCommandHandler(
            new CustomerRepository($eventStore, $eventBus),
            $validator,
            $eventDispatcher,
            $auditManager,
            $levelDowngradeModeProvider
        );
    }
}
