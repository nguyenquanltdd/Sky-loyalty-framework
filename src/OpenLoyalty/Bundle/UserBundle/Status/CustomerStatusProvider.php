<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Status;

use Broadway\ReadModel\Repository;
use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Bundle\UserBundle\Model\CustomerStatus;
use OpenLoyalty\Component\Account\Infrastructure\Provider\AccountDetailsProviderInterface;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Infrastructure\Exception\LevelDowngradeModeNotSupportedException;
use OpenLoyalty\Component\Customer\Infrastructure\ExcludeDeliveryCostsProvider;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Customer\Infrastructure\Provider\CustomerDetailsProviderInterface;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use OpenLoyalty\Component\Level\Domain\Level;
use OpenLoyalty\Component\Level\Domain\LevelId;
use OpenLoyalty\Component\Level\Domain\LevelRepository;

/**
 * Class CustomerStatusProvider.
 */
class CustomerStatusProvider
{
    /**
     * @var Repository
     */
    private $accountDetailsRepository;

    /**
     * @var LevelRepository
     */
    private $levelRepository;

    /**
     * @var CustomerDetailsProviderInterface
     */
    private $customerDetailsProvider;

    /**
     * @var TierAssignTypeProvider
     */
    private $tierAssignTypeProvider;

    /**
     * @var ExcludeDeliveryCostsProvider
     */
    private $excludeDeliveryCostProvider;

    /**
     * @var SettingsManager
     */
    private $settingsManager;

    /**
     * @var LevelDowngradeModeProvider
     */
    private $levelDowngradeModeProvider;

    /**
     * @var AccountDetailsProviderInterface
     */
    private $accountDetailsProvider;

    /**
     * CustomerStatusProvider constructor.
     *
     * @param Repository                       $accountDetailsRepository
     * @param LevelRepository                  $levelRepository
     * @param CustomerDetailsProviderInterface $customerDetailsProvider
     * @param TierAssignTypeProvider           $tierAssignTypeProvider
     * @param ExcludeDeliveryCostsProvider     $excludeDeliveryCostProvider
     * @param SettingsManager                  $settingsManager
     * @param LevelDowngradeModeProvider       $downgradeModeProvider
     * @param AccountDetailsProviderInterface  $accountDetailsProvider
     */
    public function __construct(
        Repository $accountDetailsRepository,
        LevelRepository $levelRepository,
        CustomerDetailsProviderInterface $customerDetailsProvider,
        TierAssignTypeProvider $tierAssignTypeProvider,
        ExcludeDeliveryCostsProvider $excludeDeliveryCostProvider,
        SettingsManager $settingsManager,
        LevelDowngradeModeProvider $downgradeModeProvider,
        AccountDetailsProviderInterface $accountDetailsProvider
    ) {
        $this->accountDetailsRepository = $accountDetailsRepository;
        $this->levelRepository = $levelRepository;
        $this->customerDetailsProvider = $customerDetailsProvider;
        $this->tierAssignTypeProvider = $tierAssignTypeProvider;
        $this->excludeDeliveryCostProvider = $excludeDeliveryCostProvider;
        $this->settingsManager = $settingsManager;
        $this->levelDowngradeModeProvider = $downgradeModeProvider;
        $this->accountDetailsProvider = $accountDetailsProvider;
    }

    /**
     * @param CustomerId $customerId
     *
     * @return CustomerStatus
     */
    public function getStatus(CustomerId $customerId): CustomerStatus
    {
        $status = new CustomerStatus($customerId);
        $status->setCurrency($this->getCurrency());

        $customer = $this->customerDetailsProvider->getCustomerDetailsByCustomerId($customerId);
        if (!$customer) {
            return $status;
        }

        $status->setFirstName($customer->getFirstName());
        $status->setLastName($customer->getLastName());

        $accountDetails = $this->accountDetailsProvider->getAccountDetailsByCustomerId($customerId);

        /** @var Level $level */
        $level = $customer->getLevelId() ? $this->levelRepository->byId(new LevelId((string) $customer->getLevelId())) : null;

        $nextLevel = null;
        $conditionValue = 0;

        $tierAssignType = $this->tierAssignTypeProvider->getType();
        if ($tierAssignType == TierAssignTypeProvider::TYPE_POINTS) {
            if ($accountDetails) {
                $conditionValue = $accountDetails->getAvailableAmount();
            }
        } elseif ($tierAssignType == TierAssignTypeProvider::TYPE_TRANSACTIONS) {
            if ($this->excludeDeliveryCostProvider->areExcluded()) {
                $conditionValue = $customer->getTransactionsAmountWithoutDeliveryCosts() - $customer->getAmountExcludedForLevel();
            } else {
                $conditionValue = $customer->getTransactionsAmount() - $customer->getAmountExcludedForLevel();
            }
        }

        /** @var Level $nextLevel */
        $nextLevel = $level ?
            $this->levelRepository->findNextLevelByConditionValueWithTheBiggestReward(
                $conditionValue,
                $level->getConditionValue()
            )
            : null;

        if ($accountDetails) {
            $status->setPoints($accountDetails->getAvailableAmount());
            $status->setP2pPoints($accountDetails->getP2PAvailableAmount());
            $status->setTotalEarnedPoints($accountDetails->getEarnedAmount());
            $status->setUsedPoints($accountDetails->getUsedAmount());
            $status->setExpiredPoints($accountDetails->getExpiredAmount());
            $status->setLockedPoints($accountDetails->getLockedAmount());

            $status->setTransactionsAmount($customer->getTransactionsAmount());
            $status->setTransactionsAmountWithoutDeliveryCosts($customer->getTransactionsAmountWithoutDeliveryCosts());
            $status->setAverageTransactionsAmount(number_format($customer->getAverageTransactionAmount(), 2, '.', ''));
            $status->setTransactionsCount($customer->getTransactionsCount());
            if ($this->displayDowngradeModeXDaysStats()) {
                $startDate = $customer->getLastLevelRecalculation() ?: $customer->getCreatedAt();
                $status->setPointsSinceLastLevelRecalculation($accountDetails->getEarnedAmountSince($startDate));
            }
        }

        if ($level) {
            $status->setLevelName($level->getName());
            $status->setLevelPercent(number_format($level->getReward()->getValue() * 100, 2).'%');
            $status->setLevelConditionValue($level->getConditionValue());
        }

        if ($nextLevel) {
            $status->setNextLevelName($nextLevel->getName());
            $status->setNextLevelPercent(number_format($nextLevel->getReward()->getValue() * 100, 2).'%');
            $status->setNextLevelConditionValue($nextLevel->getConditionValue());
        }

        if ($level && $nextLevel && $this->displayDowngradeModeXDaysStats()) {
            $pointsRequiredToRetainLevel = $status->getLevelConditionValue() - $status->getPointsSinceLastLevelRecalculation();
            if ($pointsRequiredToRetainLevel < 0) {
                $pointsRequiredToRetainLevel = 0.00;
            }
            $status->setPointsRequiredToRetainLevel($pointsRequiredToRetainLevel);
        }

        if ($nextLevel && $accountDetails) {
            $this->applyNextLevelRequirements($customer, $status, $nextLevel, $accountDetails->getAvailableAmount());
        }

        if ($this->displayDowngradeModeXDaysStats()) {
            $date = $customer->getLastLevelRecalculation() ?: $customer->getCreatedAt();
            $nextDate = (clone $date)->modify(sprintf('+%u days', $this->levelDowngradeModeProvider->getDays()));
            $currentDate = new \DateTime();
            if ($nextDate < $currentDate) {
                $days = 0;
            } else {
                $diff = abs($nextDate->getTimestamp() - $currentDate->getTimestamp());
                $days = ceil($diff / 86400);
            }

            $status->setLevelWillExpireInDays($days);
        }

        return $status;
    }

    /**
     * @param CustomerDetails $customer
     * @param CustomerStatus  $status
     * @param Level           $nextLevel
     * @param                 $currentPoints
     */
    private function applyNextLevelRequirements(
        CustomerDetails $customer,
        CustomerStatus $status,
        Level $nextLevel,
        $currentPoints
    ): void {
        $tierAssignType = $this->tierAssignTypeProvider->getType();

        if ($tierAssignType == TierAssignTypeProvider::TYPE_POINTS) {
            $status->setPointsToNextLevel($nextLevel->getConditionValue() - $currentPoints);
        } elseif ($tierAssignType == TierAssignTypeProvider::TYPE_TRANSACTIONS) {
            if ($this->excludeDeliveryCostProvider->areExcluded()) {
                $currentAmount = $customer->getTransactionsAmountWithoutDeliveryCosts() - $customer->getAmountExcludedForLevel();
                $status->setTransactionsAmountToNextLevelWithoutDeliveryCosts(($nextLevel->getConditionValue() - $currentAmount));
            } else {
                $currentAmount = $customer->getTransactionsAmount() - $customer->getAmountExcludedForLevel();
                $status->setTransactionsAmountToNextLevel(($nextLevel->getConditionValue() - $currentAmount));
            }
        }
    }

    /**
     * @return string
     */
    private function getCurrency(): string
    {
        $currency = $this->settingsManager->getSettingByKey('currency');
        if ($currency) {
            return $currency->getValue();
        }

        return 'PLN';
    }

    /**
     * @return bool
     */
    private function displayDowngradeModeXDaysStats(): bool
    {
        try {
            return
                $this->tierAssignTypeProvider->getType() == TierAssignTypeProvider::TYPE_POINTS &&
                $this->levelDowngradeModeProvider->getMode() === LevelDowngradeModeProvider::MODE_X_DAYS
            ;
        } catch (LevelDowngradeModeNotSupportedException $e) {
            return false;
        }
    }
}
