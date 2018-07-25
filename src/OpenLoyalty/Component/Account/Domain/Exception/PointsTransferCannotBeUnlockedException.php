<?php

namespace OpenLoyalty\Component\Account\Domain\Exception;

use OpenLoyalty\Component\Core\Domain\Exception\Translatable;

/**
 * Class PointsTransferCannotBeUnlockedException.
 */
class PointsTransferCannotBeUnlockedException extends \InvalidArgumentException implements Translatable
{
    /**
     * @var string
     */
    protected $id;

    public function __construct(string $id)
    {
        $this->id = $id;
        $this->message = sprintf('Points transfer #%s cannot be unlocked', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'account.points_transfer.cannot_be_unlocked';
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
