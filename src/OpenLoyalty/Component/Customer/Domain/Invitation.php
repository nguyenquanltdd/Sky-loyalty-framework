<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasAttachedToInvitation;
use OpenLoyalty\Component\Customer\Domain\Event\InvitationWasCreated;
use OpenLoyalty\Component\Customer\Domain\Event\PurchaseWasMadeForThisInvitation;

/**
 * Class Invitation.
 */
class Invitation extends EventSourcedAggregateRoot
{
    const STATUS_INVITED = 'invited';
    const STATUS_REGISTERED = 'registered';
    const STATUS_MADE_PURCHASE = 'made_purchase';

    /**
     * @var InvitationId
     */
    private $id;

    /**
     * @var CustomerId
     */
    private $referrerId;

    /**
     * @var string
     */
    private $recipientEmail;

    /**
     * @var CustomerId
     */
    private $recipientId;

    /**
     * @var string
     */
    private $status = self::STATUS_INVITED;

    /**
     * @return string
     */
    public function getAggregateRootId(): string
    {
        return $this->id;
    }

    /**
     * @param InvitationId $invitationId
     * @param CustomerId   $referrerId
     * @param              $recipientEmail
     * @param              $token
     *
     * @return Invitation
     */
    public static function createInvitation(InvitationId $invitationId, CustomerId $referrerId, $recipientEmail, $token): Invitation
    {
        $invitation = new self();
        $invitation->create($invitationId, $referrerId, $recipientEmail, $token);

        return $invitation;
    }

    /**
     * @param CustomerId $customerId
     */
    public function attachCustomer(CustomerId $customerId): void
    {
        $this->apply(
            new CustomerWasAttachedToInvitation($this->id, $customerId)
        );
    }

    /**
     * Made purchase.
     */
    public function purchaseMade(): void
    {
        $this->apply(
            new PurchaseWasMadeForThisInvitation($this->id)
        );
    }

    /**
     * @param PurchaseWasMadeForThisInvitation $event
     */
    protected function applyPurchaseWasMadeForThisInvitation(PurchaseWasMadeForThisInvitation $event): void
    {
        $this->status = Invitation::STATUS_MADE_PURCHASE;
    }

    /**
     * @param InvitationId $invitationId
     * @param CustomerId   $referrerId
     * @param              $recipientEmail
     * @param              $token
     */
    private function create(InvitationId $invitationId, CustomerId $referrerId, $recipientEmail, $token): void
    {
        $this->apply(
            new InvitationWasCreated($invitationId, $referrerId, $recipientEmail, $token)
        );
    }

    /**
     * @param InvitationWasCreated $event
     */
    protected function applyInvitationWasCreated(InvitationWasCreated $event): void
    {
        $this->setId($event->getInvitationId());
        $this->setRecipientEmail($event->getRecipientEmail());
        $this->setReferrerId($event->getReferrerId());
    }

    /**
     * @param CustomerWasAttachedToInvitation $event
     */
    protected function applyCustomerWasAttachedToInvitation(CustomerWasAttachedToInvitation $event): void
    {
        $this->recipientId = $event->getCustomerId();
    }

    /**
     * @param InvitationId $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return CustomerId|null
     */
    public function getReferrerId(): ?CustomerId
    {
        return $this->referrerId;
    }

    /**
     * @param CustomerId $referrerId
     */
    public function setReferrerId(CustomerId $referrerId): void
    {
        $this->referrerId = $referrerId;
    }

    /**
     * @return string|null
     */
    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    /**
     * @param string $recipientEmail
     */
    public function setRecipientEmail(string $recipientEmail): void
    {
        $this->recipientEmail = $recipientEmail;
    }

    /**
     * @param string $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }
}
