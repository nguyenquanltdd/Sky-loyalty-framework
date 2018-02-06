<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\ActivationCode\Domain\SystemEvent;

use OpenLoyalty\Component\ActivationCode\Domain\ActivationCodeId;

/**
 * Class ActivationCodeCreatedSystemEvent.
 */
class ActivationCodeCreatedSystemEvent extends ActivationCodeSystemEvent
{
    /**
     * @var array
     */
    protected $data;

    /**
     * {@inheritdoc}
     *
     * @param array|null $data
     */
    public function __construct(ActivationCodeId $activationCodeId, array $data = null)
    {
        parent::__construct($activationCodeId);

        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getActivationCodeData()
    {
        return $this->data;
    }
}
