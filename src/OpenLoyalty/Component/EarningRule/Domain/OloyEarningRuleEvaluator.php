<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\EarningRule\Domain;

use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Component\Account\Domain\TransactionId;
use OpenLoyalty\Component\Customer\Domain\Model\Status;
use OpenLoyalty\Component\Customer\Domain\ReadModel\InvitationDetailsRepository;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\EarningRuleAlgorithmFactoryInterface;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\EarningRuleAlgorithmInterface;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\RuleEvaluationContext;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\RuleNameContext;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\RuleNameContextInterface;
use OpenLoyalty\Component\EarningRule\Domain\Stoppable\StoppableProvider;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Account\Infrastructure\EarningRuleApplier;
use OpenLoyalty\Component\Account\Infrastructure\Model\EvaluationResult;
use OpenLoyalty\Component\Account\Infrastructure\Model\ReferralEvaluationResult;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Core\Domain\Model\Identifier;
use OpenLoyalty\Component\Segment\Domain\ReadModel\SegmentedCustomersRepository;

/**
 * Class OloyEarningRuleEvaluator.
 */
class OloyEarningRuleEvaluator implements EarningRuleApplier
{
    /**
     * @var EarningRuleGeoRepository
     */
    protected $earningRuleGeoRepository;

    /**
     * @var EarningRuleRepository
     */
    protected $earningRuleRepository;

    /**
     * @var TransactionDetailsRepository
     */
    protected $transactionDetailsRepository;

    /**
     * @var InvitationDetailsRepository
     */
    protected $invitationDetailsRepository;

    /**
     * @var EarningRuleAlgorithmFactoryInterface
     */
    protected $algorithmFactory;

    /**
     * @var SegmentedCustomersRepository
     */
    protected $segmentedCustomerElasticSearchRepository;

    /**
     * @var CustomerDetailsRepository
     */
    protected $customerDetailsRepository;

    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * @var StoppableProvider
     */
    private $stoppableProvider;

    /**
     * OloyEarningRuleEvaluator constructor.
     *
     * @param EarningRuleRepository                $earningRuleRepository
     * @param TransactionDetailsRepository         $transactionDetailsRepository
     * @param EarningRuleAlgorithmFactoryInterface $algorithmFactory
     * @param InvitationDetailsRepository          $invitationDetailsRepository
     * @param SegmentedCustomersRepository         $segmentedCustomerElasticSearchRepository
     * @param CustomerDetailsRepository            $customerDetailsRepository
     * @param SettingsManager                      $settingsManager
     * @param StoppableProvider                    $stoppableProvider
     * @param EarningRuleGeoRepository             $earningRuleGeoRepository
     */
    public function __construct(
        EarningRuleRepository $earningRuleRepository,
        TransactionDetailsRepository $transactionDetailsRepository,
        EarningRuleAlgorithmFactoryInterface $algorithmFactory,
        InvitationDetailsRepository $invitationDetailsRepository,
        SegmentedCustomersRepository $segmentedCustomerElasticSearchRepository,
        CustomerDetailsRepository $customerDetailsRepository,
        SettingsManager $settingsManager,
        StoppableProvider $stoppableProvider,
        EarningRuleGeoRepository $earningRuleGeoRepository
    ) {
        $this->earningRuleRepository = $earningRuleRepository;
        $this->transactionDetailsRepository = $transactionDetailsRepository;
        $this->algorithmFactory = $algorithmFactory;
        $this->segmentedCustomerElasticSearchRepository = $segmentedCustomerElasticSearchRepository;
        $this->customerDetailsRepository = $customerDetailsRepository;
        $this->invitationDetailsRepository = $invitationDetailsRepository;
        $this->settingsManager = $settingsManager;
        $this->stoppableProvider = $stoppableProvider;
        $this->earningRuleGeoRepository = $earningRuleGeoRepository;
    }

    /**
     * @param TransactionDetails|TransactionId $transaction
     *
     * @return TransactionDetails
     */
    protected function getTransactionObject($transaction)
    {
        if ($transaction instanceof TransactionId) {
            $transaction = $this->transactionDetailsRepository->find($transaction->__toString());
        }

        if ($transaction instanceof TransactionDetails) {
            return $transaction;
        }

        return;
    }

    /**
     * @param TransactionDetails $transaction
     * @param $customerId
     *
     * @return array
     */
    protected function getEarningRulesAlgorithms(TransactionDetails $transaction, $customerId)
    {
        $customerData = $this->getCustomerDetails($customerId);

        $earningRules = $this->earningRuleRepository->findAllActiveEventRulesBySegmentsAndLevels(
            $transaction->getPurchaseDate(),
            $customerData['segments'],
            $customerData['level'],
            $transaction->getPosId()
        );

        $result = [];

        foreach ($earningRules as $earningRule) {
            // ignore event rules (supported by call method)
            if ($earningRule instanceof EventEarningRule
                || $earningRule instanceof CustomEventEarningRule
                || $earningRule instanceof EarningRuleGeo
                || $earningRule instanceof ReferralEarningRule
            ) {
                continue;
            }

            /** @var EarningRuleAlgorithmInterface $algorithm */
            $algorithm = $this->algorithmFactory->getAlgorithm($earningRule);
            $result[] = [
                $earningRule,
                $algorithm,
            ];
        }

        usort(
            $result,
            function ($x, $y) {
                return $x[1]->getPriority() - $y[1]->getPriority();
            }
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateTransaction($transaction, $customerId, RuleEvaluationContext $context = null)
    {
        $transaction = $this->getTransactionObject($transaction);

        if (!$transaction) {
            return 0;
        }

        $customerData = $this->getCustomerDetails($customerId);
        if (null !== $customerData['status'] && !in_array($customerData['status'], $this->getCustomerEarningStatuses())) {
            return 0;
        }

        $earningRulesItems = $this->getEarningRulesAlgorithms($transaction, $customerId);

        if (null === $context) {
            $context = new RuleEvaluationContext($transaction, $customerId);
        }

        foreach ($earningRulesItems as $earningRuleItem) {
            /** @var EarningRule $earningRule */
            $earningRule = $earningRuleItem[0];
            /** @var EarningRuleAlgorithmInterface $algorithm */
            $algorithm = $earningRuleItem[1];

            $executed = $algorithm->evaluate($context, $earningRule);

            if ($executed && $this->stoppableProvider->isStoppable($earningRule) && $earningRule->isLastExecutedRule()) {
                break;
            }
        }

        return round((float) array_sum($context->getProducts()), 2);
    }

    /**
     * @param $transaction
     * @param $customerId
     *
     * @return array
     */
    public function evaluateTransactionWithComment($transaction, $customerId)
    {
        $transaction = $this->getTransactionObject($transaction);

        if (!$transaction) {
            return [
                'points' => 0,
                'comment' => null,
            ];
        }

        $context = new RuleEvaluationContext($transaction, $customerId);
        $points = $this->evaluateTransaction($transaction, $customerId, $context);

        return [
            'points' => $points,
            'comment' => $context->getEarningRuleNames(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateEvent($eventName, $customerId, RuleNameContextInterface $context = null)
    {
        $points = 0;

        $customerData = $this->getCustomerDetails($customerId);
        if (null !== $customerData['status'] && !in_array($customerData['status'], $this->getCustomerEarningStatuses())) {
            return 0;
        }

        $earningRules = $this->earningRuleRepository->findAllActiveEventRules(
            $eventName,
            $customerData['segments'],
            $customerData['level'],
            null,
            $customerData['pos']
        );

        /** @var EventEarningRule $earningRule */
        foreach ($earningRules as $earningRule) {
            if ($earningRule->getPointsAmount() > $points) {
                $points = $earningRule->getPointsAmount();
                if (null !== $context) {
                    $context->addEarningRuleName($earningRule->getEarningRuleId(), $earningRule->getName());
                }
            }
        }

        return round((float) $points, 2);
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateEventWithContext(string $eventName, ?string $customerId): array
    {
        $context = new RuleNameContext();
        $points = $this->evaluateEvent($eventName, $customerId, $context);

        return [
            'points' => $points,
            'comment' => $context->getEarningRuleNames(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateGeoEvent(float $latitude, float $longitude, string $customerId): array
    {
        /** @var EvaluationResult[] $result */
        $result = [];

        /** @var array $customerData */
        $customerData = $this->getCustomerDetails($customerId);
        if (null !== $customerData['status'] && !in_array($customerData['status'], $this->getCustomerEarningStatuses())) {
            return $result;
        }

        $earningGeoRules = $this->earningRuleGeoRepository->findGeoRules(
            $customerData['segments'],
            $customerData['level'],
            null,
            $customerData['pos']
        );

        foreach ($earningGeoRules as $earningGeoRule) {
            /** @var EarningRuleGeo $earningGeoRule */
            if ($earningGeoRule->isActive()) {
                $distance = $earningGeoRule->getDistance($latitude, $longitude);
                if ($earningGeoRule->getRadius() >= $distance) {
                    $result[] = new EvaluationResult(
                        (string) $earningGeoRule->getEarningRuleId(),
                        $earningGeoRule->getPointsAmount(),
                        $earningGeoRule->getName()
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Return number of points for this custom event.
     *
     * @param string $eventName
     * @param string $customerId
     *
     * @return int|EvaluationResult
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function evaluateCustomEvent($eventName, $customerId)
    {
        /** @var EvaluationResult $result */
        $result = null;

        /** @var array $customerData */
        $customerData = $this->getCustomerDetails($customerId);
        if (null !== $customerData['status'] && !in_array($customerData['status'], $this->getCustomerEarningStatuses())) {
            return 0;
        }

        $earningRules = $this->earningRuleRepository->findByCustomEventName(
            $eventName,
            $customerData['segments'],
            $customerData['level'],
            null,
            $customerData['pos']
        );

        if (!$earningRules) {
            return 0;
        }

        if (null !== $customerData['status'] && !in_array($customerData['status'], $this->getCustomerEarningStatuses())) {
            return 0;
        }

        /** @var EarningRule $earningRule */
        foreach ($earningRules as $earningRule) {
            if (null == $result || $earningRule->getPointsAmount() > $result->getPoints()) {
                $result = new EvaluationResult(
                    $earningRule->getEarningRuleId()->__toString(),
                    $earningRule->getPointsAmount(),
                    $earningRule->getName()
                );
            }
        }

        return $result;
    }

    /**
     * @param string $eventName
     * @param string $customerId
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return null|EvaluationResult|ReferralEvaluationResult[]
     */
    public function evaluateReferralEvent($eventName, $customerId)
    {
        /** @var ReferralEvaluationResult[] $results */
        $results = [];

        /** @var array $customerData */
        $customerData = $this->getCustomerDetails($customerId);

        $invitation = $this->invitationDetailsRepository->findOneByRecipientId(new \OpenLoyalty\Component\Customer\Domain\CustomerId($customerId));

        if (!$invitation) {
            return $results;
        }

        $earningRules = $this->earningRuleRepository->findReferralByEventName(
            $eventName,
            $customerData['segments'],
            $customerData['level'],
            null,
            $customerData['pos']
        );
        if (!$earningRules) {
            return $results;
        }

        /** @var ReferralEarningRule $earningRule */
        foreach ($earningRules as $earningRule) {
            if (!isset($results[$earningRule->getRewardType()]) || $earningRule->getPointsAmount() > $results[$earningRule->getRewardType()]->getPoints()) {
                $results[$earningRule->getRewardType()] = new ReferralEvaluationResult(
                    $earningRule->getEarningRuleId()->__toString(),
                    $earningRule->getPointsAmount(),
                    $earningRule->getRewardType(),
                    $invitation,
                    $earningRule->getName()
                );
            }
        }

        return $results;
    }

    /**
     * Get customer level and segments data from transaction.
     *
     * @param string customerId
     *
     * @return array
     */
    protected function getCustomerDetails($customerId)
    {
        $result = [
            'level' => null,
            'status' => null,
            'segments' => [],
            'pos' => null,
        ];

        if ($customerId) {
            $customerId = $customerId instanceof Identifier ? $customerId->__toString() : $customerId;

            $customerDetails = $this->customerDetailsRepository->findOneByCriteria(['id' => $customerId], 1);
            $levelId = $this->getCustomerLevelById($customerDetails);
            $status = $this->getCustomerStatusById($customerDetails);
            $pos = $this->getCustomerPos($customerDetails);

            $arrayOfSegments = $this->getCustomerSegmentsById($customerId);

            $result = [
                'level' => $levelId,
                'status' => $status,
                'segments' => $arrayOfSegments,
                'pos' => $pos,
            ];
        }

        return $result;
    }

    /**
     * @param $customerDetails
     *
     * @return null|PosId
     */
    public function getCustomerPos($customerDetails)
    {
        if (!$customerDetails) {
            return null;
        }

        $pos = array_map(
            function ($element) {
                return $element->getPosId();
            },
            $customerDetails
        );

        return isset($pos[0]) ? $pos[0] : null;
    }

    /**
     * Get customers segments.
     *
     * @param $customerId
     *
     * @return array
     */
    protected function getCustomerSegmentsById($customerId)
    {
        $segments = [];

        $customerDetails = $this->segmentedCustomerElasticSearchRepository
            ->findByParameters(
                ['customerId' => $customerId],
                true
            );

        if ($customerDetails) {
            $segments = array_map(
                function ($element) {
                    return $element->getSegmentId();
                },
                $customerDetails
            );
        }

        return $segments;
    }

    /**
     * Get customers level.
     *
     * @param $customerDetails
     *
     * @return LevelId
     */
    protected function getCustomerLevelById($customerDetails)
    {
        if (!$customerDetails) {
            return null;
        }

        $levels = array_map(
            function ($element) {
                return $element->getLevelId();
            },
            $customerDetails
        );

        return isset($levels[0]) ? $levels[0] : null;
    }

    /**
     * Get customers status.
     *
     * @param $customerDetails
     *
     * @return Status
     */
    protected function getCustomerStatusById($customerDetails)
    {
        if (!$customerDetails) {
            return null;
        }

        $statuses = array_map(
            function ($element) {
                return (null !== $element->getStatus()) ? $element->getStatus()->getType() : null;
            },
            $customerDetails
        );

        return isset($statuses[0]) ? $statuses[0] : null;
    }

    /**
     * @return array
     */
    protected function getCustomerEarningStatuses()
    {
        $customerStatusesEarning = $this->settingsManager->getSettingByKey('customerStatusesEarning');
        if ($customerStatusesEarning) {
            return $customerStatusesEarning->getValue();
        }

        return [];
    }
}
