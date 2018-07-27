<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Gaufrette\Filesystem;
use OpenLoyalty\Bundle\SettingsBundle\Entity\BooleanSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Entity\FileSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Entity\IntegerSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Entity\JsonSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Entity\StringSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Model\Logo;
use OpenLoyalty\Bundle\SettingsBundle\Model\Settings;
use OpenLoyalty\Bundle\SettingsBundle\Provider\AvailableMarketingVendors;
use OpenLoyalty\Component\Customer\Domain\Model\AccountActivationMethod;
use OpenLoyalty\Component\Customer\Domain\Model\Status;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use Symfony\Bridge\Doctrine\Tests\Fixtures\ContainerAwareFixture;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class LoadSettingsData.
 */
class LoadSettingsData extends ContainerAwareFixture implements OrderedFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->loadDefaultTranslations();

        $settings = new Settings();

        $currency = new StringSettingEntry('currency', 'eur');
        $settings->addEntry($currency);

        $timezone = new StringSettingEntry('timezone', 'Europe/Warsaw');
        $settings->addEntry($timezone);

        $programName = new StringSettingEntry('programName', 'Loyalty Program');
        $settings->addEntry($programName);

        $pointsSingular = new StringSettingEntry('programPointsSingular', 'Point');
        $settings->addEntry($pointsSingular);

        $pointsPlural = new StringSettingEntry('programPointsPlural', 'Points');
        $settings->addEntry($pointsPlural);

        $pointsDaysActive = new IntegerSettingEntry('pointsDaysActive', 30);
        $settings->addEntry($pointsDaysActive);

        $allTimeNotLocked = new BooleanSettingEntry('allTimeNotLocked', true);
        $settings->addEntry($allTimeNotLocked);

        $returns = new BooleanSettingEntry('returns', true);
        $settings->addEntry($returns);

        $entry = new StringSettingEntry('tierAssignType');
        $entry->setValue(TierAssignTypeProvider::TYPE_TRANSACTIONS);
        $settings->addEntry($entry);

        $entry3 = new JsonSettingEntry('excludedLevelCategories');
        $entry3->setValue(['category_excluded_from_level']);
        $settings->addEntry($entry3);

        $downgradeMode = new StringSettingEntry('levelDowngradeMode');
        $downgradeMode->setValue(LevelDowngradeModeProvider::MODE_NONE);
        $settings->addEntry($downgradeMode);

        // copy logo
        $rootDirectory = $this->getContainer()->getParameter('kernel.root_dir');
        $destinationDirectory = $rootDirectory.'/uploads/logo';
        $filesystem = $this->getContainer()->get('filesystem');
        if (!$filesystem->exists($destinationDirectory)) {
            $filesystem->mkdir($destinationDirectory);
        }
        $kernel = $this->getContainer()->get('kernel');
        $filesystem->copy(
            $kernel->locateResource('@OpenLoyaltySettingsBundle/Resources/images/logo/logo.png'),
            $destinationDirectory.'/logo.png'
        );

        $logo = new Logo();
        $logo->setMime('image/png');
        $logo->setPath('logo/logo.png');
        $entry4 = new FileSettingEntry('logo', $logo);
        $settings->addEntry($entry4);

        $smallLogo = new Logo();
        $smallLogo->setMime('image/png');
        $smallLogo->setPath('logo/logo.png');
        $entry5 = new FileSettingEntry('small-logo', $smallLogo);
        $settings->addEntry($entry5);

        $heroImage = new Logo();
        $heroImage->setMime('image/png');
        $heroImage->setPath('logo/logo.png');
        $entry6 = new FileSettingEntry('hero-image', $heroImage);
        $settings->addEntry($entry6);

        $earningStatuses = new JsonSettingEntry('customerStatusesEarning');
        $earningStatuses->setValue([Status::TYPE_ACTIVE]);
        $settings->addEntry($earningStatuses);

        $spendingStatuses = new JsonSettingEntry('customerStatusesSpending');
        $spendingStatuses->setValue([Status::TYPE_ACTIVE]);
        $settings->addEntry($spendingStatuses);

        $priority = new JsonSettingEntry('customersIdentificationPriority');
        $priorities = [
            [
                'priority' => 3,
                'field' => 'phone',
            ],
            [
                'priority' => 2,
                'field' => 'loyaltyCardNumber',
            ],
            [
                'priority' => 1,
                'field' => 'email',
            ],
        ];
        $priority->setValue($priorities);
        $settings->addEntry($priority);

        $defaultFrontendTranslations = new StringSettingEntry('defaultFrontendTranslations');
        $defaultFrontendTranslations->setValue('english.json');
        $settings->addEntry($defaultFrontendTranslations);

        $accountActivationMethod = new StringSettingEntry('accountActivationMethod');
        $accountActivationMethod->setValue(AccountActivationMethod::METHOD_EMAIL);
        $settings->addEntry($accountActivationMethod);

        $marketingVendor = new StringSettingEntry('marketingVendorsValue');
        $marketingVendor->setValue(AvailableMarketingVendors::NONE);
        $settings->addEntry($marketingVendor);

        $this->getContainer()->get('ol.settings.manager')->save($settings);
    }

    /**
     * Copy default translations to translations directory.
     */
    protected function loadDefaultTranslations(): void
    {
        /** @var Filesystem $fileSystem */
        $fileSystem = $this->getContainer()->get('ol.settings.frontend_translations_filesystem');

        /** @var Kernel $kernel */
        $kernel = $this->getContainer()->get('kernel');

        $transDir = $kernel->locateResource('@OpenLoyaltySettingsBundle/DataFixtures/ORM/translations/');
        $finder = Finder::create();
        $finder->files()->in($transDir);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $fileSystem->write($file->getFilename(), $file->getContents(), true);
        }
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the order of this fixture.
     *
     * @return int
     */
    public function getOrder()
    {
        return 0;
    }
}
