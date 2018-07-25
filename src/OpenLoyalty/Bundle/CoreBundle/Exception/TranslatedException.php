<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CoreBundle\Exception;

use Throwable;

/**
 * Class TranslatedException.
 */
class TranslatedException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct($message, $previous ? $previous->getCode() : 0, $previous);
    }
}
