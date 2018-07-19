<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Infrastructure\SystemEvent\Listener;

use Broadway\CommandHandling\CommandBus;
use Broadway\ReadModel\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use OpenLoyalty\Bundle\PointsBundle\Service\PointsTransfersManager;
use OpenLoyalty\Component\Account\Domain\Command\AddPoints;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountCreatedSystemEvent;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountSystemEvents;
use OpenLoyalty\Component\Account\Domain\SystemEvent\CustomEventOccurredSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerAttachedToInvitationSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerLoggedInSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerSystemEvents;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\NewsletterSubscriptionSystemEvent;
use OpenLoyalty\Component\EarningRule\Domain\ReferralEarningRule;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\CustomerFirstTransactionSystemEvent;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\TransactionSystemEvents;
use OpenLoyalty\Component\Account\Infrastructure\EarningRuleApplier;
use OpenLoyalty\Component\Account\Infrastructure\EarningRuleLimitValidator;

/**
 * Class ApplyEarningRuleToEventListener.
 */
class ApplyEarningRuleToEventListener extends BaseApplyEarningRuleListener
{
    /**
     * @var EarningRuleLimitValidator
     */
    protected $earningRuleLimitValidator;

    /**
     * ApplyEarningRuleToEventListener constructor.
     *
     * @param CommandBus             $commandBus
     * @param Repository             $accountDetailsRepository
     * @param UuidGeneratorInterface $uuidGenerator
     * @param EarningRuleApplier     $earningRuleApplier
     * @param $pointsTransfersManager        $pointsTransfersManager
     * @param EarningRuleLimitValidator|null $earningRuleLimitValidator
     */
    public function __construct(
        CommandBus $commandBus,
        Repository $accountDetailsRepository,
        UuidGeneratorInterface $uuidGenerator,
        EarningRuleApplier $earningRuleApplier,
        PointsTransfersManager $pointsTransfersManager,
        EarningRuleLimitValidator $earningRuleLimitValidator = null
    ) {
        parent::__construct($commandBus, $accountDetailsRepository, $uuidGenerator, $earningRuleApplier, $pointsTransfersManager);
        $this->earningRuleLimitValidator = $earningRuleLimitValidator;
    }

    /**
     * @param CustomEventOccurredSystemEvent $event
     *
     * @throws \OpenLoyalty\Component\Account\Infrastructure\Exception\EarningRuleLimitExceededException
     */
    public function onCustomEvent(CustomEventOccurredSystemEvent $event)
    {
        $result = $this->earningRuleApplier->evaluateCustomEvent($event->getEventName(), $event->getCustomerId());
        if (null == $result || $result->getPoints() <= 0) {
            return;
        }
        $account = $this->getAccountDetails($event->getCustomerId()->__toString());
        if (!$account) {
            return;
        }
        if ($this->earningRuleLimitValidator) {
            $this->earningRuleLimitValidator->validate($result->getEarningRuleId(), $event->getCustomerId());
        }

        $this->commandBus->dispatch(
            new AddPoints(
                $account->getAccountId(),
                $this->pointsTransferManager->createAddPointsTransferInstance(
                    new PointsTransferId($this->uuidGenerator->generate()),
                    $result->getPoints(),
                    null,
                    false,
                    null,
                    $result->getName()
                )
            )
        );
        $event->setEvaluationResult($result);
    }

    /**
     * @param AccountCreatedSystemEvent $event
     */
    public function onCustomerRegistered(AccountCreatedSystemEvent $event)
    {
        $result = $this->earningRuleApplier->evaluateEventWithContext(
            AccountSystemEvents::ACCOUNT_CREATED,
            $event->getCustomerId()
        );

        if (array_key_exists('points', $result) && $result['points'] > 0) {
            $this->commandBus->dispatch(
                new AddPoints(
                    $event->getAccountId(),
                    $this->pointsTransferManager->createAddPointsTransferInstance(
                        new PointsTransferId($this->uuidGenerator->generate()),
                        $result['points'],
                        null,
                        null,
                        null,
                        $result['comment']
                    )
                )
            );
        }
    }

    /**
     * @param CustomerAttachedToInvitationSystemEvent $event
     */
    public function onCustomerAttachedToInvitation(CustomerAttachedToInvitationSystemEvent $event)
    {
        $this->evaluateReferral(ReferralEarningRule::EVENT_REGISTER, $event->getCustomerId()->__toString());
    }

    /**
     * @param CustomerFirstTransactionSystemEvent $event
     */
    public function onFirstTransaction(CustomerFirstTransactionSystemEvent $event)
    {
        $result = $this->earningRuleApplier->evaluateEventWithContext(
            TransactionSystemEvents::CUSTOMER_FIRST_TRANSACTION,
            $event->getCustomerId()
        );
        $account = $this->getAccountDetails($event->getCustomerId()->__toString());

        if (!$account) {
            return;
        }

        if (array_key_exists('points', $result) && $result['points'] > 0) {
            $this->commandBus->dispatch(
                new AddPoints(
                    $account->getAccountId(),
                    $this->pointsTransferManager->createAddPointsTransferInstance(
                        new PointsTransferId($this->uuidGenerator->generate()),
                        $result['points'],
                        null,
                        false,
                        null,
                        $result['comment']
                    )
                )
            );
        }

        $this->evaluateReferral(ReferralEarningRule::EVENT_FIRST_PURCHASE, $event->getCustomerId()->__toString());
    }

    /**
     * @param CustomerLoggedInSystemEvent $event
     */
    public function onCustomerLogin(CustomerLoggedInSystemEvent $event)
    {
        $result = $this->earningRuleApplier->evaluateEventWithContext(
            CustomerSystemEvents::CUSTOMER_LOGGED_IN,
            $event->getCustomerId()
        );

        if (!array_key_exists('points', $result) || $result['points'] <= 0) {
            return;
        }
        $account = $this->getAccountDetails($event->getCustomerId()->__toString());

        if (!$account) {
            return;
        }

        $this->commandBus->dispatch(
            new AddPoints(
                $account->getAccountId(),
                $this->pointsTransferManager->createAddPointsTransferInstance(
                    new PointsTransferId($this->uuidGenerator->generate()),
                    $result['points'],
                    null,
                    false,
                    null,
                    $result['comment']
                )
            )
        );
    }

    /**
     * @param NewsletterSubscriptionSystemEvent $event
     */
    public function onNewsletterSubscription(NewsletterSubscriptionSystemEvent $event)
    {
        $result = $this->earningRuleApplier->evaluateEventWithContext(
            CustomerSystemEvents::NEWSLETTER_SUBSCRIPTION,
            $event->getCustomerId()
        );

        if (!array_key_exists('points', $result) || $result['points'] <= 0) {
            return;
        }
        $account = $this->getAccountDetails($event->getCustomerId()->__toString());

        if (!$account) {
            return;
        }

        $this->commandBus->dispatch(
            new AddPoints(
                $account->getAccountId(),
                $this->pointsTransferManager->createAddPointsTransferInstance(
                    new PointsTransferId($this->uuidGenerator->generate()),
                    $result['points'],
                    null,
                    false,
                    null,
                    $result['comment']
                )
            )
        );
    }
}
