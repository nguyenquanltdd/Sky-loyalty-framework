<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Infrastructure;

use OpenLoyalty\Component\Account\Infrastructure\Model\EvaluationResult;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\RuleNameContextInterface;

interface EarningRuleApplier
{
    /**
     * Return number of points for this transaction.
     *
     * @param $transaction
     * @param $customerId
     *
     * @return int
     */
    public function evaluateTransaction($transaction, $customerId);

    /**
     * @param $transaction
     * @param $customerId
     *
     * @return array
     */
    public function evaluateTransactionWithComment($transaction, $customerId);

    /**
     * Return number of points for this event.
     *
     * @param string                        $eventName
     * @param string                        $customerId
     * @param RuleNameContextInterface|null $context
     *
     * @return int
     */
    public function evaluateEvent($eventName, $customerId, RuleNameContextInterface $context = null);

    /**
     * Return number of points for this event.
     *
     * @param string      $eventName
     * @param string|null $customerId
     *
     * @return array
     */
    public function evaluateEventWithContext(string $eventName, ?string $customerId): array;

    /**
     * Return number of points for this custom event.
     *
     * @param string $eventName
     * @param string $customerId
     *
     * @return EvaluationResult
     */
    public function evaluateCustomEvent($eventName, $customerId);

    /**
     * @param string $eventName
     * @param string $customerId
     *
     * @return null|EvaluationResult
     */
    public function evaluateReferralEvent($eventName, $customerId);

    /**
     * @param float  $latitude
     * @param float  $longitude
     * @param string $customerId
     *
     * @return EvaluationResult[]
     */
    public function evaluateGeoEvent(float $latitude, float $longitude, string $customerId): array;

    /**
     * @param string      $code
     * @param string|null $earningRuleId
     *
     * @return EvaluationResult[]
     */
    public function evaluateQrcodeEvent(string $code, ?string $earningRuleId): array;
}
