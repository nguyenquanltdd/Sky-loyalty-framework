<?php

namespace OpenLoyalty\Component\Account\Domain\Exception;

use OpenLoyalty\Component\Core\Domain\Exception\Translatable;

/**
 * Class PointsTransferAlreadyExistException.
 */
class PointsTransferAlreadyExistException extends \InvalidArgumentException implements Translatable
{
    /**
     * @var string
     */
    protected $id;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->message = sprintf('Points transfer #%s already exists', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'account.points_transfer.already_exists';
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
