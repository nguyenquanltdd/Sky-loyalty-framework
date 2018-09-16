<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Infrastructure\Notifier;

/**
 * Interface NotificationInterface.
 */
interface ExpirePointsNotifierInterface
{
    /**
     * @param \DateTimeInterface $dateTime
     */
    public function sendNotificationsForPointsExpiringAfter(\DateTimeInterface $dateTime): void;

    /**
     * @return int
     */
    public function sentNotificationsCount(): int;
}
