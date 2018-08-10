<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Service;

/**
 * Class GeneralSettingsManager.
 */
class GeneralSettingsManager extends DoctrineSettingsManager implements GeneralSettingsManagerInterface
{
    const DEFAULT_POINTS_DURATION_VALIDITY_DAYS = 90;
    const DEFAULT_CURRENCY = 'PLN';

    /**
     * @return int
     */
    public function getPointsDaysActive(): ?int
    {
        $allTimeActive = $this->getSettingByKey('allTimeActive');
        if ($allTimeActive && $allTimeActive->getValue()) {
            return null;
        }

        return $this->getSettingByKey('pointsDaysActive')->getValue() ?? self::DEFAULT_POINTS_DURATION_VALIDITY_DAYS;
    }

    /**
     * {@inheritdoc}
     */
    public function getPointsDaysLocked(): ?int
    {
        $allTimeNotLocked = $this->getSettingByKey('allTimeNotLocked');
        if ($allTimeNotLocked && $allTimeNotLocked->getValue()) {
            return null;
        }
        $pointsDaysLocked = $this->getSettingByKey('pointsDaysLocked');

        return $pointsDaysLocked ? $pointsDaysLocked->getValue() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency(): string
    {
        return $this->getSettingByKey('currency')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getTimezone(): string
    {
        return $this->getSettingByKey('timezone')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguage(): string
    {
        return $this->getSettingByKey('defaultFrontendTranslations')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getProgramName(): string
    {
        return $this->getSettingByKey('programName')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getProgramUrl(): ?string
    {
        return $this->getSettingByKey('programUrl')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionsUrl(): ?string
    {
        return $this->getSettingByKey('programUrl')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function FAQUrl(): ?string
    {
        return $this->getSettingByKey('programFaqUrl')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getPointsSingular(): string
    {
        return $this->getSettingByKey('programPointsSingular')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getPointsPlural(): string
    {
        return $this->getSettingByKey('programPointsPlural')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getHelpEmail(): ?string
    {
        return $this->getSettingByKey('helpEmailAddress')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function isAllTimeActive(): ?bool
    {
        return $this->getSettingByKey('allTimeActive')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function isReturnAvailable(): bool
    {
        return (bool) $this->getSettingByKey('returns')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function isDeliveryCostExcluded(): bool
    {
        return (bool) $this->getSettingByKey('excludeDeliveryCostsFromTierAssignment')->getValue();
    }
}
