<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * Class LoyaltyProgramParticipant.
 *
 * @ORM\Entity()
 */
class Customer extends User
{
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", name="temporary_password_set_at", nullable=true)
     */
    protected $temporaryPasswordSetAt;

    /**
     * @ORM\Column(name="action_token", type="string", length = 20, nullable = true)
     */
    private $actionToken;

    /**
     * @ORM\Column(name="referral_customer_email", type="string", length = 128, nullable= true)
     */
    private $referralCustomerEmail;

    /**
     * @ORM\Column(name="newsletter_used_flag", type="boolean")
     */
    private $newsletterUsedFlag;

    /**
     * @var Status
     * @ORM\Embedded(class = "Status", columnPrefix = "status_" )
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"registration"})
     * @JMS\Expose()
     */
    protected $phone;

    /**
     * Customer constructor.
     *
     * @param CustomerId $id
     */
    public function __construct(CustomerId $id)
    {
        parent::__construct($id->__toString());
    }

    /**
     * @return mixed
     */
    public function getActionToken()
    {
        return $this->actionToken;
    }

    /**
     * @param mixed $actionToken
     */
    public function setActionToken($actionToken)
    {
        $this->actionToken = $actionToken;
    }

    /**
     * @return \DateTime
     */
    public function getTemporaryPasswordSetAt()
    {
        return $this->temporaryPasswordSetAt;
    }

    /**
     * @param \DateTime $temporaryPasswordSetAt
     */
    public function setTemporaryPasswordSetAt($temporaryPasswordSetAt)
    {
        $this->temporaryPasswordSetAt = $temporaryPasswordSetAt;
    }

    /**
     * @return string
     */
    public function getReferralCustomerEmail()
    {
        return $this->referralCustomerEmail;
    }

    /**
     * @param string $referralCustomerEmail
     */
    public function setReferralCustomerEmail($referralCustomerEmail)
    {
        $this->referralCustomerEmail = $referralCustomerEmail;
    }

    /**
     * @return bool
     */
    public function getNewsletterUsedFlag()
    {
        return boolval($this->newsletterUsedFlag);
    }

    /**
     * @param bool $newsletterUsedFlag
     */
    public function setNewsletterUsedFlag($newsletterUsedFlag)
    {
        $this->newsletterUsedFlag = $newsletterUsedFlag;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        if ($this->getStatus() && $this->getStatus()->getType() === Status::TYPE_NEW) {
            return true;
        }

        return false;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone = null)
    {
        $this->phone = $phone;
    }
}
