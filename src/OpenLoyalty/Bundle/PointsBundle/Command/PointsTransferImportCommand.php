<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\PointsBundle\Command;

use OpenLoyalty\Bundle\ImportBundle\Command\AbstractFileImportCommand;
use OpenLoyalty\Bundle\PointsBundle\Import\PointsTransferXmlImporter;
use OpenLoyalty\Component\Import\Infrastructure\FileImporter;

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
    protected function getImporter(): FileImporter
    {
        return $this->container->get(PointsTransferXmlImporter::class);
    }
}
