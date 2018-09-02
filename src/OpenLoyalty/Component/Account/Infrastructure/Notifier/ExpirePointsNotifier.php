<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Infrastructure\Notifier;

use Broadway\CommandHandling\CommandBus;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetailsRepository;
use OpenLoyalty\Component\Webhook\Domain\Command\DispatchWebhook;

/**
 * Class ExpirePointsNotifier.
 */
class ExpirePointsNotifier implements ExpirePointsNotifierInterface
{
    private const REQUEST_PACKAGE_SIZE = 1000;

    private const ACCOUNT_EXPIRING_POINTS_REMINDER_GENERATED = 'account.expiring_points_reminder_generated';

    /**
     * @var int
     */
    private $sentNotifications = 0;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var PointsTransferDetailsRepository
     */
    private $pointsTransferDetailsRepository;

    /**
     * @param CommandBus                      $commandBus
     * @param PointsTransferDetailsRepository $pointsTransferDetailsRepository
     */
    public function __construct(
        CommandBus $commandBus,
        PointsTransferDetailsRepository $pointsTransferDetailsRepository
    ) {
        $this->commandBus = $commandBus;
        $this->pointsTransferDetailsRepository = $pointsTransferDetailsRepository;
    }

    /**
     * @param \DateTimeInterface $dateTime
     */
    public function sendNotificationsForPointsExpiringBefore(\DateTimeInterface $dateTime): void
    {
        $pointTransfers = $this->pointsTransferDetailsRepository->findAllActiveAddingTransfersBeforeExpired($dateTime);

        $notifications = [];

        /** @var PointsTransferDetails $pointTransfer */
        foreach ($pointTransfers as $pointTransfer) {
            if (null === $pointTransfer->getExpiresAt()) {
                continue;
            }

            $notifications[] = [
                'customerId' => (string) $pointTransfer->getCustomerId(),
                'customerEmail' => $pointTransfer->getCustomerEmail(),
                'customerPhone' => $pointTransfer->getCustomerPhone(),
                'customerLoyaltyCardNumber' => $pointTransfer->getCustomerLoyaltyCardNumber(),
                'customerFirstName' => $pointTransfer->getCustomerFirstName(),
                'customerLastName' => $pointTransfer->getCustomerLastName(),
                'points' => $pointTransfer->getValue(),
                'pointsWillExpire' => $pointTransfer->getExpiresAt()->format(\DateTime::ATOM),
            ];

            ++$this->sentNotifications;
        }

        $notificationPackages = array_chunk($notifications, self::REQUEST_PACKAGE_SIZE);

        foreach ($notificationPackages as $package) {
            $this->commandBus->dispatch(new DispatchWebhook(
                self::ACCOUNT_EXPIRING_POINTS_REMINDER_GENERATED,
                $package
            ));
        }
    }

    /**
     * @return int
     */
    public function sentNotificationsCount(): int
    {
        return $this->sentNotifications;
    }
}
