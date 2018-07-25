<?php

namespace OpenLoyalty\Component\Account\Domain\Exception;

use OpenLoyalty\Component\Core\Domain\Exception\Translatable;

/**
 * Class PointsTransferNotExistException.
 */
class PointsTransferNotExistException extends \InvalidArgumentException implements Translatable
{
    /**
     * @var string
     */
    protected $id;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->message = sprintf('Points transfer #%s does not exist', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'account.points_transfer.not_exist';
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
