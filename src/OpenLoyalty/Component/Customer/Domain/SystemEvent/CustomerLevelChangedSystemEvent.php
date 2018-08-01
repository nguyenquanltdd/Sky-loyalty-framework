<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\SystemEvent;

use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\LevelId;

/**
 * Class CustomerLevelChangedSystemEvent.
 */
class CustomerLevelChangedSystemEvent extends CustomerSystemEvent
{
    /**
     * @var LevelId
     */
    private $levelId;

    /**
     * @var null|string
     */
    private $levelName;

    public function __construct(CustomerId $customerId, LevelId $levelId, ?string $levelName = null)
    {
        parent::__construct($customerId);

        $this->levelId = $levelId;
        $this->levelName = $levelName;
    }

    /**
     * @return LevelId
     */
    public function getLevelId(): LevelId
    {
        return $this->levelId;
    }

    /**
     * @return null|string
     */
    public function getLevelName(): ?string
    {
        return $this->levelName;
    }
}
