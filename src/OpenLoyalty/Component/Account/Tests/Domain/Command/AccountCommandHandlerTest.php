<?php

namespace OpenLoyalty\Component\Account\Tests\Domain\Command;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use OpenLoyalty\Component\Account\Domain\AccountRepository;
use OpenLoyalty\Component\Account\Domain\Command\AccountCommandHandler;

/**
 * Class AccountCommandHandlerTest.
 */
abstract class AccountCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * Create a command handler for the given scenario test case.
     *
     * @param EventStore $eventStore
     * @param EventBus   $eventBus
     *
     * @return CommandHandler
     */
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        return new AccountCommandHandler(
            new AccountRepository($eventStore, $eventBus)
        );
    }
}
