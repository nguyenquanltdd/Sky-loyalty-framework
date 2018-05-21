<?php

namespace OpenLoyalty\Component\Transaction\Tests\Command;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\CommandHandlerScenarioTestCase;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\EventHandling\EventBus;
use Broadway\EventStore\EventStore;
use OpenLoyalty\Component\Transaction\Domain\Command\TransactionCommandHandler;
use OpenLoyalty\Component\Transaction\Domain\TransactionRepository;

/**
 * Class TransactionCommandHandlerTest.
 */
abstract class TransactionCommandHandlerTest extends CommandHandlerScenarioTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createCommandHandler(EventStore $eventStore, EventBus $eventBus): CommandHandler
    {
        $eventDispatcher = $this->getMockBuilder(EventDispatcher::class)->getMock();
        $eventDispatcher->method('dispatch')->with($this->isType('string'))->willReturn(true);

        return new TransactionCommandHandler(
            new TransactionRepository($eventStore, $eventBus),
            $eventDispatcher
        );
    }
}
