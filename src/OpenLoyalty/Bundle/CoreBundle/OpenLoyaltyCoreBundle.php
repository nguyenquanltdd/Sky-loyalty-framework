<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CoreBundle;

use OpenLoyalty\Bundle\CoreBundle\Command\SchemaEventStoreCreateCommand;
use OpenLoyalty\Bundle\CoreBundle\Command\SchemaEventStoreDropCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class OpenLoyaltyCoreBundle.
 */
class OpenLoyaltyCoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function registerCommands(Application $application)
    {
        $application->add(new SchemaEventStoreCreateCommand());
        $application->add(new SchemaEventStoreDropCommand());
    }
}
