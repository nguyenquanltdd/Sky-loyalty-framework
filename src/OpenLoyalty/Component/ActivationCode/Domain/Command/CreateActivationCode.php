<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\ActivationCode\Domain\Command;

use OpenLoyalty\Component\ActivationCode\Domain\ActivationCodeId;

/**
 * Class CreateActivationCode.
 */
final class CreateActivationCode extends ActivationCodeCommand
{
    /**
     * Activation code data.
     *
     * @var array
     */
    private $data;

    /**
     * {@inheritdoc}
     *
     * @param array $data
     */
    public function __construct(ActivationCodeId $activationCodeId, array $data)
    {
        parent::__construct($activationCodeId);

        $this->validateCommand($data);

        $this->data = $data;
    }

    /**
     * Get activation code data.
     *
     * @return array
     */
    public function getActivationCodeData()
    {
        return $this->data;
    }
}
