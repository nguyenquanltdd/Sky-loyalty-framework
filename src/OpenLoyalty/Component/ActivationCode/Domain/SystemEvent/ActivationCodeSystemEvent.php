<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\ActivationCode\Domain\SystemEvent;

use OpenLoyalty\Component\ActivationCode\Domain\ActivationCodeId;

/**
 * Class ActivationCodeSystemEvent.
 */
class ActivationCodeSystemEvent
{
    /**
     * @var ActivationCodeId
     */
    protected $activationCodeId;

    /**
     * ActivationCodeSystemEvent constructor.
     *
     * @param ActivationCodeId $activationCodeId
     */
    public function __construct(ActivationCodeId $activationCodeId)
    {
        $this->activationCodeId = $activationCodeId;
    }

    /**
     * @return ActivationCodeId
     */
    public function getActivationCodeId()
    {
        return $this->activationCodeId;
    }
}
