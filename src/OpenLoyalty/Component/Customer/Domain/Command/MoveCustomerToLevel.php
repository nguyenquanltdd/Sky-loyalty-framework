<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\Command;

use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\LevelId;

/**
 * Class MoveCustomerToLevel.
 */
class MoveCustomerToLevel extends CustomerCommand
{
    /**
     * @var LevelId
     */
    protected $levelId;

    /**
     * @var bool
     */
    protected $manually = false;

    /**
     * @var bool
     */
    protected $removeLevelManually = false;

    /**
     * MoveCustomerToLevel constructor.
     *
     * @param CustomerId   $customerId
     * @param null|LevelId $levelId
     * @param bool         $manually
     * @param bool         $removeLevelManually
     */
    public function __construct(
        CustomerId $customerId,
        ? LevelId $levelId = null,
        bool $manually = false,
        bool $removeLevelManually = false
    ) {
        parent::__construct($customerId);
        $this->levelId = $levelId;
        $this->manually = $manually;
        $this->removeLevelManually = $removeLevelManually;
    }

    /**
     * @return null|LevelId
     */
    public function getLevelId() : ? LevelId
    {
        return $this->levelId;
    }

    /**
     * @return bool
     */
    public function isManually() : bool
    {
        return $this->manually;
    }

    /**
     * @return bool
     */
    public function isRemoveLevelManually(): bool
    {
        return $this->removeLevelManually;
    }
}
