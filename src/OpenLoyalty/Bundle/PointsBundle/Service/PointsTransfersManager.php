<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\PointsBundle\Service;

use Broadway\CommandHandling\CommandBus;
use OpenLoyalty\Bundle\SettingsBundle\Service\GeneralSettingsManager;
use OpenLoyalty\Bundle\SettingsBundle\Service\GeneralSettingsManagerInterface;
use OpenLoyalty\Component\Account\Domain\Command\ExpirePointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\AddPointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\PointsTransfer;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetailsRepository;
use OpenLoyalty\Component\Account\Domain\TransactionId;
use OpenLoyalty\Component\Account\Infrastructure\PointsTransferManagerInterface;

/**
 * Class PointsTransfersManager.
 */
class PointsTransfersManager implements PointsTransferManagerInterface
{
    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var PointsTransferDetailsRepository
     */
    protected $pointsTransferDetailsRepository;

    /**
     * @var GeneralSettingsManager
     */
    protected $settingsManager;

    /**
     * PointsTransfersManager constructor.
     *
     * @param CommandBus                      $commandBus
     * @param PointsTransferDetailsRepository $pointsTransferDetailsRepository
     * @param GeneralSettingsManagerInterface $settingsManager
     */
    public function __construct(
        CommandBus $commandBus,
        PointsTransferDetailsRepository $pointsTransferDetailsRepository,
        GeneralSettingsManagerInterface $settingsManager
    ) {
        $this->commandBus = $commandBus;
        $this->pointsTransferDetailsRepository = $pointsTransferDetailsRepository;
        $this->settingsManager = $settingsManager;
    }

    /**
     * @return array
     */
    public function expireTransfers()
    {
        $allTime = $this->settingsManager->getSettingByKey('allTimeActive');
        if (null !== $allTime && $allTime->getValue()) {
            return [];
        }

        $transfers = $this->pointsTransferDetailsRepository->findAllActiveAddingTransfersExpiredAfter(time());

        /** @var PointsTransferDetails $transfer */
        foreach ($transfers as $transfer) {
            $this->commandBus->dispatch(new ExpirePointsTransfer(
                $transfer->getAccountId(),
                $transfer->getPointsTransferId()
            ));
        }

        return $transfers;
    }

    /**
     * @param PointsTransferId   $id
     * @param int                $value
     * @param \DateTime|null     $createdAt
     * @param bool               $canceled
     * @param TransactionId|null $transactionId
     * @param string|null        $comment
     * @param string             $issuer
     *
     * @return AddPointsTransfer
     */
    public function createAddPointsTransferInstance(
        PointsTransferId $id,
        $value,
        \DateTime $createdAt = null,
        $canceled = false,
        TransactionId $transactionId = null,
        ? string $comment = null,
        $issuer = PointsTransfer::ISSUER_SYSTEM
    ): AddPointsTransfer {
        $validtyDaysDuration = $this->settingsManager->getPointsDaysActive();

        return new AddPointsTransfer(
            $id,
            $value,
            $validtyDaysDuration,
            $createdAt,
            $canceled,
            $transactionId,
            $comment,
            $issuer
        );
    }
}
