<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\Exception;

use OpenLoyalty\Component\Core\Domain\Exception\Translatable;

/**
 * Class PointsTransferCannotBeCanceled.
 */
class PointsTransferCannotBeCanceledException extends \Exception implements Translatable
{
    /**
     * @var string
     */
    protected $id;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->message = sprintf('Points transfer #%s cannot be cancelled', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'account.points_transfer.cannot_be_cancelled';
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageParams()
    {
        return [
            '%id%' => $this->id,
        ];
    }
}
