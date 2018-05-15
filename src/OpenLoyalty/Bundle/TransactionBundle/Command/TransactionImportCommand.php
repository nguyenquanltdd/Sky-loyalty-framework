<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\TransactionBundle\Command;

use OpenLoyalty\Bundle\ImportBundle\Command\AbstractFileImportCommand;

/**
 * Class TransactionImportCommand.
 */
class TransactionImportCommand extends AbstractFileImportCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('oloy:transaction:import')
            ->setDescription('Import transaction from XML file');
    }

    /**
     * {@inheritdoc}
     */
    protected function getImporter()
    {
        return $this->container->get('oloy.transaction.import.transaction_importer');
    }
}
