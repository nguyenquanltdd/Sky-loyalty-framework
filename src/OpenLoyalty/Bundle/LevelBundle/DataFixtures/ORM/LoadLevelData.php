<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\LevelBundle\DataFixtures\ORM;

use Broadway\CommandHandling\SimpleCommandBus;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use OpenLoyalty\Component\Level\Domain\Command\ActivateLevel;
use OpenLoyalty\Component\Level\Domain\Command\CreateLevel;
use OpenLoyalty\Component\Level\Domain\LevelId;
use Symfony\Bridge\Doctrine\Tests\Fixtures\ContainerAwareFixture;

/**
 * Class LoadLevelData.
 */
class LoadLevelData extends ContainerAwareFixture implements OrderedFixtureInterface
{
    const LEVEL_ID = 'e82c96cf-32a3-43bd-9034-4df343e5fd94';
    const LEVEL_NAME = 'level0';
    const LEVEL2_ID = '000096cf-32a3-43bd-9034-4df343e5fd94';
    const LEVEL2_NAME = 'level1';
    const LEVEL3_ID = '000096cf-32a3-43bd-9034-4df343e5fd93';
    const LEVEL3_NAME = 'level2';
    const LEVEL4_ID = '000096cf-32a3-43bd-9034-4df343e5fd95';
    const LEVEL4_NAME = 'level3';

    public function load(ObjectManager $manager)
    {
        $level0 = [
            'name' => self::LEVEL_NAME,
            'description' => 'example level',
            'conditionValue' => 0,
            'reward' => [
                'name' => 'test reward',
                'value' => 0.14,
                'code' => 'abc',
            ],
        ];

        $level1 = [
            'name' => self::LEVEL2_NAME,
            'description' => 'example level',
            'conditionValue' => 20,
            'reward' => [
                'name' => 'test reward',
                'value' => 0.15,
                'code' => 'abc',
            ],
        ];
        $level2 = [
            'name' => self::LEVEL3_NAME,
            'description' => 'example level',
            'conditionValue' => 200,
            'reward' => [
                'name' => 'test reward',
                'value' => 0.20,
                'code' => 'abc',
            ],
            'specialRewards' => [
                0 => [
                    'name' => 'special reward',
                    'value' => 0.22,
                    'code' => 'spec',
                    'startAt' => new \DateTime('2016-10-10'),
                    'endAt' => new \DateTime('2016-11-10'),
                    'active' => true,
                    'id' => 'e82c96cf-32a3-43bd-9034-4df343e5fd00',
                ],
                1 => [
                    'name' => 'special reward 2',
                    'value' => 0.11,
                    'code' => 'spec2',
                    'startAt' => new \DateTime('2016-09-10'),
                    'endAt' => new \DateTime('2016-11-10'),
                    'active' => false,
                    'id' => 'e82c96cf-32a3-43bd-9034-4df343e50094',
                ],
            ],
        ];
        $level4 = [
            'name' => self::LEVEL4_NAME,
            'description' => 'Level4',
            'conditionValue' => 999,
            'reward' => [
                'name' => 'Level 4 reward',
                'value' => 0.00,
                'code' => 'level4',
            ],
        ];

        /** @var SimpleCommandBus $commandBus */
        $commandBus = $this->container->get('broadway.command_handling.command_bus');
        $commandBus->dispatch(
            new CreateLevel(new LevelId(self::LEVEL_ID), $level1)
        );
        $commandBus->dispatch(
            new ActivateLevel(new LevelId(self::LEVEL_ID))
        );
        $commandBus->dispatch(
            new CreateLevel(new LevelId(self::LEVEL2_ID), $level2)
        );
        $commandBus->dispatch(
            new ActivateLevel(new LevelId(self::LEVEL2_ID))
        );
        $commandBus->dispatch(
            new CreateLevel(new LevelId(self::LEVEL3_ID), $level0)
        );
        $commandBus->dispatch(
            new ActivateLevel(new LevelId(self::LEVEL3_ID))
        );
        $commandBus->dispatch(
            new CreateLevel(new LevelId(self::LEVEL4_ID), $level4)
        );
        $commandBus->dispatch(
            new ActivateLevel(new LevelId(self::LEVEL4_ID))
        );
    }

    /**
     * Get the order of this fixture.
     *
     * @return int
     */
    public function getOrder()
    {
        return 0;
    }
}
