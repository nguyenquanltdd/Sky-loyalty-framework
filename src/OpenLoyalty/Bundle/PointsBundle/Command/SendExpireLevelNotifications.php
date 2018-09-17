<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace OpenLoyalty\Bundle\PointsBundle\Command;

use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Component\Account\Infrastructure\Notifier\ExpireLevelNotifierInterface;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class SendExpireLevelNotifications.
 */
class SendExpireLevelNotifications extends Command
{
    private const COMMAND_ID = 'send-expire-level-notifications';

    private const COMMAND_NAME = 'oloy:level:notify:expiration';

    /**
     * @var int
     */
    private $daysToExpire = 0;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var ExpireLevelNotifierInterface
     */
    private $expireLevelNotifier;

    /**
     * @var SettingsManager
     */
    private $settingsManager;

    /**
     * SendExpireLevelNotifications constructor.
     *
     * @param ExpireLevelNotifierInterface $expirePointsNotifier
     * @param SettingsManager              $settingsManager
     */
    public function __construct(ExpireLevelNotifierInterface $expireLevelNotifier, SettingsManager $settingsManager)
    {
        parent::__construct(self::COMMAND_NAME);

        $this->expireLevelNotifier = $expireLevelNotifier;
        $this->settingsManager = $settingsManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Send expire points notification to the users')
            ->addArgument('days-to-expire', InputArgument::OPTIONAL, 'Number of days to expire levels')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);

        $tierAssignTypeSetting = $this->settingsManager->getSettingByKey('tierAssignType');
        if (null !== $tierAssignTypeSetting && TierAssignTypeProvider::TYPE_POINTS !== $tierAssignTypeSetting->getValue()) {
            throw new \RuntimeException(sprintf(
                'Expire level notifications only works with %s tier type.',
                TierAssignTypeProvider::TYPE_POINTS
            ));
        }

        $levelDowngradeModeSetting = $this->settingsManager->getSettingByKey('levelDowngradeMode');
        if (null !== $levelDowngradeModeSetting && LevelDowngradeModeProvider::MODE_X_DAYS !== $levelDowngradeModeSetting->getValue()) {
            throw new \RuntimeException(sprintf(
                'Expire level notifications only works with %s Level Downgrade Mode',
                LevelDowngradeModeProvider::MODE_X_DAYS
            ));
        }

        if (null === $this->settingsManager->getSettingByKey('uriWebhooks')) {
            throw new \RuntimeException('Webhook URI is not configured.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $daysToExpire = $input->getArgument('days-to-expire');

        if (null !== $daysToExpire) {
            $this->daysToExpire = $daysToExpire;

            return;
        }

        $settingDaysToExpire = $this->settingsManager->getSettingByKey('expireLevelsNotificationDays');

        if (null !== $settingDaysToExpire) {
            $this->daysToExpire = $settingDaysToExpire->getValue();

            return;
        }

        throw new \RuntimeException('No expiry date for points specified');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start(self::COMMAND_ID);

        try {
            $expireDate = new \DateTime(sprintf('+%d days', $this->daysToExpire));

            $this->expireLevelNotifier->sendNotificationsForLevelsExpiringAt($expireDate);

            $this->printResultsMessage();
        } catch (\Exception $exception) {
            $this->io->error($exception->getMessage());
        }

        $event = $stopwatch->stop(self::COMMAND_ID);

        if ($output->isVerbose()) {
            $this->io->comment(sprintf(
                'Sent requests with webhooks: %d / Elapsed time: %.2f ms / Consumed memory: %.2f MB',
                $this->expireLevelNotifier->sentNotificationsCount(),
                $event->getDuration(),
                $event->getMemory() / (1024 ** 2)
            ));
        }
    }

    private function printResultsMessage(): void
    {
        if (0 !== $this->expireLevelNotifier->sentNotificationsCount()) {
            $this->io->success(sprintf(
                'Successfully sent %d notifications about expiring levels!',
                $this->expireLevelNotifier->sentNotificationsCount()
            ));

            return;
        }

        $this->io->warning('There were no expiring levels to notify about');
    }
}
