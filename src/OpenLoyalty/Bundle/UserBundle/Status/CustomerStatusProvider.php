<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Status;

use Broadway\ReadModel\Repository;
use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Bundle\UserBundle\Model\CustomerStatus;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Customer\Infrastructure\Exception\LevelDowngradeModeNotSupportedException;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Level\Domain\Level;
use OpenLoyalty\Component\Level\Domain\LevelRepository;
use OpenLoyalty\Component\Customer\Infrastructure\ExcludeDeliveryCostsProvider;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use OpenLoyalty\Component\Level\Domain\LevelId;

/**
 * Class CustomerStatusProvider.
 */
class CustomerStatusProvider
{
    /**
     * @var Repository
     */
    protected $accountDetailsRepository;

    /**
     * @var LevelRepository
     */
    protected $levelRepository;

    /**
     * @var CustomerDetailsRepository
     */
    protected $customerDetailsRepository;

    /**
     * @var TierAssignTypeProvider
     */
    protected $tierAssignTypeProvider;

    /**
     * @var ExcludeDeliveryCostsProvider
     */
    protected $excludeDeliveryCostProvider;

    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * @var LevelDowngradeModeProvider
     */
    protected $levelDowngradeModeProvider;

    /**
     * CustomerStatusProvider constructor.
     *
     * @param Repository                   $accountDetailsRepository
     * @param LevelRepository              $levelRepository
     * @param CustomerDetailsRepository    $customerDetailsRepository
     * @param TierAssignTypeProvider       $tierAssignTypeProvider
     * @param ExcludeDeliveryCostsProvider $excludeDeliveryCostProvider
     * @param SettingsManager              $settingsManager
     * @param LevelDowngradeModeProvider   $downgradeModeProvider
     */
    public function __construct(
        Repository $accountDetailsRepository,
        LevelRepository $levelRepository,
        CustomerDetailsRepository $customerDetailsRepository,
        TierAssignTypeProvider $tierAssignTypeProvider,
        ExcludeDeliveryCostsProvider $excludeDeliveryCostProvider,
        SettingsManager $settingsManager,
        LevelDowngradeModeProvider $downgradeModeProvider
    ) {
        $this->accountDetailsRepository = $accountDetailsRepository;
        $this->levelRepository = $levelRepository;
        $this->customerDetailsRepository = $customerDetailsRepository;
        $this->tierAssignTypeProvider = $tierAssignTypeProvider;
        $this->excludeDeliveryCostProvider = $excludeDeliveryCostProvider;
        $this->settingsManager = $settingsManager;
        $this->levelDowngradeModeProvider = $downgradeModeProvider;
    }

    /**
     * @param CustomerId $customerId
     *
     * @return CustomerStatus
     */
    public function getStatus(CustomerId $customerId)
    {
        $status = new CustomerStatus($customerId);
        $status->setCurrency($this->getCurrency());

        $customer = $this->getCustomerDetails($customerId);
        if (!$customer) {
            return $status;
        }

        $status->setFirstName($customer->getFirstName());
        $status->setLastName($customer->getLastName());

        $accountDetails = $this->getAccountDetails($customerId);
        /** @var Level $level */
        $level = $customer->getLevelId() ?
            $this->levelRepository->byId(new LevelId($customer->getLevelId()->__toString()))
            : null;

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
    protected function applyNextLevelRequirements(
        CustomerDetails $customer,
        CustomerStatus $status,
        Level $nextLevel,
        $currentPoints
    ) {
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
     * @param CustomerId $customerId
     *
     * @return null|AccountDetails
     */
    protected function getAccountDetails(CustomerId $customerId)
    {
        $accounts = $this->accountDetailsRepository->findBy(['customerId' => $customerId->__toString()]);
        if (count($accounts) == 0) {
            return;
        }
        /** @var AccountDetails $account */
        $account = reset($accounts);

        if (!$account instanceof AccountDetails) {
            return;
        }

        return $account;
    }

    /**
     * @param CustomerId $customerId
     *
     * @return CustomerDetails
     */
    protected function getCustomerDetails(CustomerId $customerId)
    {
        return $this->customerDetailsRepository->find($customerId->__toString());
    }

    protected function getCurrency()
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
            return $this->tierAssignTypeProvider->getType() == TierAssignTypeProvider::TYPE_POINTS && $this->levelDowngradeModeProvider->getMode() === LevelDowngradeModeProvider::MODE_X_DAYS;
        } catch (LevelDowngradeModeNotSupportedException $e) {
            return false;
        }
    }
}
