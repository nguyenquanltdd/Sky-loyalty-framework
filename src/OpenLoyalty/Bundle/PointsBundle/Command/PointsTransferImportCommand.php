<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\PointsBundle\Command;

use OpenLoyalty\Bundle\ImportBundle\Command\AbstractFileImportCommand;

/**
 * Class PointsTransferImportCommand.
 */
class PointsTransferImportCommand extends AbstractFileImportCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('oloy:points:import')
            ->setDescription('Import points transfers from XML file');
    }

    /**
     * {@inheritdoc}
     */
    protected function getImporter()
    {
        return $this->container->get('oloy.account.points_transfers.import.points_transfer_importer');
    }
}
