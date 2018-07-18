<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UtilityBundle\Command;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\SimpleEventBus;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class RecreateReadModelsCommand.
 */
class RecreateReadModelsCommand extends ContainerAwareCommand
{
    protected $projectors = [
        'oloy.user.customer.read_model.projector.customer_details',
        'oloy.user.customer.read_model.projector.invitation_details',
        'oloy.user.customer.read_model.projector.seller_details',
        'oloy.user.customer.read_model.projector.customers_belonging_to_one_level',
        'oloy.points.account.read_model.projector.account_details',
        'oloy.points.account.read_model.projector.point_transfer_details',
        'oloy.transaction.read_model.projector.transaction_details',
        'oloy.campaign.read_model.projector.coupon_usage',
        'oloy.campaign.read_model.projector.campaign_usage',
        'oloy.campaign.read_model.projector.campaign_bought',
    ];

    protected function configure()
    {
        $this->setName('oloy:utility:read-models:recreate');
        $this->addOption('force', 'force', InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'This method will create read models. Make sure that you dropped them earlier using command '
            .'oloy:user:projections:purge (backup of es data is recommended).'.PHP_EOL.'Do you want to continue?',
            true
        );
        if (!$input->getOption('force')) {
            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $metadataSerializer = $this->getContainer()->get('broadway.serializer.metadata');
        $payloadSerializer = $this->getContainer()->get('broadway.serializer.payload');
        $events = [];
        foreach ($connection->fetchAll('SELECT * FROM events ORDER BY id ASC') as $event) {
            $events[] = new DomainMessage(
                $event['uuid'],
                $event['playhead'],
                $metadataSerializer->deserialize(json_decode($event['metadata'], true)),
                $payloadSerializer->deserialize(json_decode($event['payload'], true)),
                DateTime::fromString($event['recorded_on'])
            );
        }

        $eventBus = new SimpleEventBus();

        foreach ($this->projectors as $projector) {
            $eventBus->subscribe($this->getContainer()->get($projector));
        }

        $eventStream = new DomainEventStream($events);
        $eventBus->publish($eventStream);
    }
}
