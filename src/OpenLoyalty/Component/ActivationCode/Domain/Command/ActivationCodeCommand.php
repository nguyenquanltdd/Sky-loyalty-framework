<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\ActivationCode\Domain\Command;

use Assert\Assertion as Assert;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCodeId;

/**
 * Class ActivationCodeCommand.
 */
class ActivationCodeCommand
{
    /**
     * @var ActivationCodeId
     */
    protected $activationCodeId;

    /**
     * ActivationCodeCommand constructor.
     *
     * @param ActivationCodeId $activationCodeId
     */
    public function __construct(ActivationCodeId $activationCodeId)
    {
        $this->activationCodeId = $activationCodeId;
    }

    /**
     * Get activation code id.
     *
     * @return ActivationCodeId
     */
    public function getActivationCodeId()
    {
        return $this->activationCodeId;
    }

    /**
     * Validate command.
     *
     * @param array $data
     */
    protected function validateCommand(array $data)
    {
        Assert::uuid($this->activationCodeId->__toString());
        Assert::notEmpty($data['code']);
        Assert::notEmpty($data['object_type']);
        Assert::notEmpty($data['object_id']);
    }
}
