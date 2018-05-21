<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Level\Domain\ReadModel;

use Broadway\ReadModel\SerializableReadModel;
use OpenLoyalty\Component\Level\Domain\LevelId;

/**
 * Class LevelDetails.
 */
class LevelDetails implements SerializableReadModel
{
    /**
     * @var LevelId
     */
    protected $levelId;

    /**
     * @var string
     */
    protected $name;

    /**
     * LevelDetails constructor.
     *
     * @param LevelId $id
     */
    public function __construct(LevelId $id)
    {
        $this->levelId = $id;
    }

    /**
     * @param array $data
     *
     * @return LevelDetails The object instance
     */
    public static function deserialize(array $data)
    {
        $level = new self(new LevelId($data['id']));
        if (!empty($data['name'])) {
            $level->setName($data['name']);
        }

        return $level;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'id' => $this->getLevelId()->__toString(),
            'name' => $this->getName(),
        ];
    }

    /**
     * @return LevelId
     */
    public function getLevelId(): LevelId
    {
        return $this->levelId;
    }

    /**
     * @param LevelId $levelId
     */
    public function setLevelId(LevelId $levelId)
    {
        $this->levelId = $levelId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->getLevelId()->__toString();
    }
}
