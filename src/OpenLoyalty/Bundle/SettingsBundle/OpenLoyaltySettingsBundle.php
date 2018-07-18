<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle;

use OpenLoyalty\Bundle\SettingsBundle\DependencyInjection\Compiler\ConfigureChoicesProviderCompilerPass;
use OpenLoyalty\Bundle\SettingsBundle\DependencyInjection\Compiler\MarketingConfigCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OpenLoyaltySettingsBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigureChoicesProviderCompilerPass());
        $container->addCompilerPass(new MarketingConfigCompilerPass());
    }
}
