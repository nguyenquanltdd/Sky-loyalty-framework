<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain;

use Broadway\EventSourcing\EventSourcedAggregateRoot;
use OpenLoyalty\Component\Core\Domain\Model\Identifier;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignCouponWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignStatusWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignUsageWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerDetailsWereUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerLevelWasRecalculated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasActivated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasDeactivated;
use OpenLoyalty\Component\Customer\Domain\Event\PosWasAssignedToCustomer;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasMovedToLevel;
use OpenLoyalty\Component\Customer\Domain\Event\SellerWasAssignedToCustomer;
use OpenLoyalty\Component\Customer\Domain\Model\Address;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\Model\Gender;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerAddressWasUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerCompanyDetailsWereUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerLoyaltyCardNumberWasUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasRegistered;
use OpenLoyalty\Component\Customer\Domain\Model\Company;
use Assert\Assertion as Assert;
use OpenLoyalty\Component\Customer\Domain\Model\Status;

/**
 * Class Customer.
 */
class Customer extends EventSourcedAggregateRoot
{
    /**
     * @var CustomerId
     */
    protected $id;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var Gender
     */
    protected $gender;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $phone;

    /**
     * @var \DateTime
     */
    protected $birthDate;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var Status
     */
    protected $status;

    /**
     * @var string
     */
    protected $loyaltyCardNumber;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $firstPurchaseAt;

    /**
     * @var bool
     */
    protected $agreement1 = false;

    /**
     * @var bool
     */
    protected $agreement2 = false;

    /**
     * @var bool
     */
    protected $agreement3 = false;

    /**
     * @var Company
     */
    protected $company = null;

    /**
     * @return string
     */
    public function getAggregateRootId(): string
    {
        return $this->id;
    }

    /**
     * @param CustomerId $customerId
     * @param array      $customerData
     *
     * @return Customer
     */
    public static function registerCustomer(CustomerId $customerId, array $customerData): Customer
    {
        $customer = new self();
        $customer->register($customerId, $customerData);

        return $customer;
    }

    /**
     * @param array $addressData
     */
    public function updateAddress(array $addressData): void
    {
        $this->apply(
            new CustomerAddressWasUpdated($this->id, $addressData)
        );
    }

    /**
     * @param array $companyData
     */
    public function updateCompanyDetails(array $companyData): void
    {
        $this->apply(
            new CustomerCompanyDetailsWereUpdated($this->id, $companyData)
        );
    }

    /**
     * @param $cardNumber
     */
    public function updateLoyaltyCardNumber($cardNumber): void
    {
        $this->apply(
            new CustomerLoyaltyCardNumberWasUpdated($this->id, $cardNumber)
        );
    }

    /**
     * @param LevelId|null $levelId
     * @param bool         $manually
     * @param bool         $removeLevelManually
     */
    public function addToLevel(LevelId $levelId = null, $manually = false, $removeLevelManually = false): void
    {
        $this->apply(
            new CustomerWasMovedToLevel($this->getId(), $levelId, $manually, $removeLevelManually)
        );
    }

    /**
     * @param CustomerId $userId
     * @param array      $customerData
     */
    private function register(CustomerId $userId, array $customerData): void
    {
        $this->apply(
            new CustomerWasRegistered($userId, $customerData)
        );
    }

    /**
     * @param array $customerData
     */
    public function updateCustomerDetails(array $customerData): void
    {
        $this->apply(
            new CustomerDetailsWereUpdated($this->getId(), $customerData)
        );
    }

    /**
     * @param PosId $posId
     */
    public function assignPosToCustomer(PosId $posId): void
    {
        $this->apply(
            new PosWasAssignedToCustomer($this->getId(), $posId)
        );
    }

    /**
     * @param SellerId $sellerId
     */
    public function assignSellerToCustomer(SellerId $sellerId): void
    {
        $this->apply(
            new SellerWasAssignedToCustomer($this->getId(), $sellerId)
        );
    }

    /**
     * @param CampaignId $campaignId
     * @param $campaignName
     * @param $costInPoints
     * @param Coupon $coupon
     * @param $reward
     * @param string          $status
     * @param \DateTime|null  $activeSince
     * @param \DateTime|null  $activeTo
     * @param null|Identifier $transactionId
     */
    public function buyCampaign(CampaignId $campaignId, $campaignName, $costInPoints, Coupon $coupon, $reward, string $status, ?\DateTime $activeSince, ?\DateTime $activeTo, ?Identifier $transactionId): void
    {
        $this->apply(
            new CampaignWasBoughtByCustomer($this->getId(), $campaignId, $campaignName, $costInPoints, $coupon, $reward, $status, $activeSince, $activeTo, $transactionId)
        );
    }

    /**
     * @param CampaignId $campaignId
     * @param Coupon     $coupon
     * @param $used
     */
    public function changeCampaignUsage(CampaignId $campaignId, Coupon $coupon, $used): void
    {
        $this->apply(
            new CampaignUsageWasChanged($this->getId(), $campaignId, $coupon, $used)
        );
    }

    /**
     * @param CampaignId         $campaignId
     * @param Coupon             $coupon
     * @param null|TransactionId $transactionId
     */
    public function expireCampaignBought(CampaignId $campaignId, Coupon $coupon, ?TransactionId $transactionId): void
    {
        $this->apply(
            new CampaignStatusWasChanged($this->getId(), $campaignId, $coupon, CampaignPurchase::STATUS_EXPIRED, $transactionId)
        );
    }

    /**
     * @param CampaignId         $campaignId
     * @param Coupon             $coupon
     * @param null|TransactionId $transactionId
     */
    public function activateCampaignBought(CampaignId $campaignId, Coupon $coupon, ?TransactionId $transactionId): void
    {
        $this->apply(
            new CampaignStatusWasChanged($this->getId(), $campaignId, $coupon, CampaignPurchase::STATUS_ACTIVE, $transactionId)
        );
    }

    /**
     * @param CampaignId    $campaignId
     * @param TransactionId $transactionId
     * @param \DateTime     $createdAt
     * @param Coupon        $newCoupon
     */
    public function changeCampaignCoupon(CampaignId $campaignId, TransactionId $transactionId, \DateTime $createdAt, Coupon $newCoupon): void
    {
        $this->apply(
            new CampaignCouponWasChanged($this->getId(), $campaignId, $transactionId, $createdAt, $newCoupon)
        );
    }

    /**
     * @param CampaignId         $campaignId
     * @param Coupon             $coupon
     * @param null|TransactionId $transactionId
     */
    public function cancelCampaignBought(CampaignId $campaignId, Coupon $coupon, ?TransactionId $transactionId): void
    {
        $this->apply(
            new CampaignStatusWasChanged($this->getId(), $campaignId, $coupon, CampaignPurchase::STATUS_CANCELLED, $transactionId)
        );
    }

    public function deactivate(): void
    {
        $this->apply(
            new CustomerWasDeactivated($this->getId())
        );
    }

    public function activate(): void
    {
        $this->apply(
            new CustomerWasActivated($this->getId())
        );
    }

    /**
     * @param \DateTime $date
     */
    public function recalculateLevel(\DateTime $date): void
    {
        $this->apply(
            new CustomerLevelWasRecalculated($this->getId(), $date)
        );
    }

    /**
     * @param CustomerWasRegistered $event
     */
    protected function applyCustomerWasRegistered(CustomerWasRegistered $event): void
    {
        $data = $event->getCustomerData();
        $data = $this->resolveOptions($data);

        $this->id = $event->getCustomerId();
        $this->setFirstName($data['firstName']);
        $this->setLastName($data['lastName']);
        if (isset($data['phone'])) {
            $this->setPhone($data['phone']);
        }
        if (isset($data['gender'])) {
            $this->setGender(new Gender($data['gender']));
        }
        if (isset($data['email'])) {
            $this->setEmail($data['email']);
        }
        if (isset($data['birthDate'])) {
            $this->setBirthDate($data['birthDate']);
        }

        if (isset($data['agreement1'])) {
            $this->setAgreement1($data['agreement1']);
        }

        if (isset($data['agreement2'])) {
            $this->setAgreement2($data['agreement2']);
        }

        if (isset($data['agreement3'])) {
            $this->setAgreement3($data['agreement3']);
        }

        if (isset($data['status'])) {
            $this->setStatus(Status::fromData($data['status']));
        }

        $this->setCreatedAt($data['createdAt']);
    }

    /**
     * @param CustomerDetailsWereUpdated $event
     */
    protected function applyCustomerDetailsWereUpdated(CustomerDetailsWereUpdated $event): void
    {
        $data = $event->getCustomerData();

        if (!empty($data['firstName'])) {
            $this->setFirstName($data['firstName']);
        }
        if (!empty($data['lastName'])) {
            $this->setLastName($data['lastName']);
        }
        if (isset($data['phone'])) {
            $this->setPhone($data['phone']);
        }
        if (!empty($data['gender'])) {
            $this->setGender(new Gender($data['gender']));
        }
        if (!empty($data['status'])) {
            $this->setStatus(Status::fromData($data['status']));
        }
        if (array_key_exists('email', $data)) {
            $this->setEmail($data['email']);
        }
        if (!empty($data['birthDate'])) {
            $this->setBirthDate($data['birthDate']);
        }

        if (isset($data['agreement1'])) {
            $this->setAgreement1($data['agreement1']);
        }

        if (isset($data['agreement2'])) {
            $this->setAgreement2($data['agreement2']);
        }

        if (isset($data['agreement3'])) {
            $this->setAgreement3($data['agreement3']);
        }
    }

    /**
     * @param CustomerAddressWasUpdated $event
     */
    protected function applyCustomerAddressWasUpdated(CustomerAddressWasUpdated $event): void
    {
        $this->setAddress(Address::fromData($event->getAddressData()));
    }

    /**
     * @param CustomerCompanyDetailsWereUpdated $event
     */
    protected function applyCustomerCompanyDetailsWereUpdated(CustomerCompanyDetailsWereUpdated $event): void
    {
        $companyData = $event->getCompanyData();
        if (!$companyData || count($companyData) == 0) {
            $this->setCompany(null);
        } else {
            $this->setCompany(new Company($companyData['name'], $event->getCompanyData()['nip']));
        }
    }

    /**
     * @param CustomerLoyaltyCardNumberWasUpdated $event
     */
    protected function applyCustomerLoyaltyCardNumberWasUpdated(CustomerLoyaltyCardNumberWasUpdated $event): void
    {
        $this->setLoyaltyCardNumber($event->getCardNumber());
    }

    /**
     * @return null|CustomerId
     */
    public function getId(): ?CustomerId
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName): void
    {
        Assert::notEmpty($firstName);
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName): void
    {
        Assert::notEmpty($lastName);
        $this->lastName = $lastName;
    }

    /**
     * @return Gender
     */
    public function getGender(): Gender
    {
        return $this->gender;
    }

    /**
     * @param Gender $gender
     */
    public function setGender(Gender $gender): void
    {
        Assert::notEmpty($gender);
        $this->gender = $gender;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return \DateTime
     */
    public function getBirthDate(): \DateTime
    {
        return $this->birthDate;
    }

    /**
     * @param \DateTime $birthDate
     */
    public function setBirthDate(\DateTime $birthDate): void
    {
        Assert::notEmpty($birthDate);
        $this->birthDate = $birthDate;
    }

    /**
     * @return Address
     */
    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @param Address $address
     */
    public function setAddress(Address $address): void
    {
        Assert::notEmpty($address);
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getLoyaltyCardNumber(): ?string
    {
        return $this->loyaltyCardNumber;
    }

    /**
     * @param string $loyaltyCardNumber
     */
    public function setLoyaltyCardNumber(string $loyaltyCardNumber): void
    {
        Assert::notEmpty($loyaltyCardNumber);
        $this->loyaltyCardNumber = $loyaltyCardNumber;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        Assert::notEmpty($createdAt);
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getFirstPurchaseAt(): \DateTime
    {
        return $this->firstPurchaseAt;
    }

    /**
     * @param \DateTime $firstPurchaseAt
     */
    public function setFirstPurchaseAt(\DateTime $firstPurchaseAt): void
    {
        $this->firstPurchaseAt = $firstPurchaseAt;
    }

    /**
     * @return bool
     */
    public function isCompany(): bool
    {
        return $this->company != null ? true : false;
    }

    /**
     * @param Company $company
     */
    public function setCompany(Company $company = null): void
    {
        $this->company = $company;
    }

    /**
     * @return bool
     */
    public function isAgreement1(): bool
    {
        return $this->agreement1;
    }

    /**
     * @param bool $agreement1
     */
    public function setAgreement1(bool $agreement1): void
    {
        $this->agreement1 = $agreement1;
    }

    /**
     * @return bool
     */
    public function isAgreement2(): bool
    {
        return $this->agreement2;
    }

    /**
     * @param bool $agreement2
     */
    public function setAgreement2(bool $agreement2): void
    {
        $this->agreement2 = $agreement2;
    }

    /**
     * @return Status
     */
    public function getStatus(): Status
    {
        return $this->status;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isAgreement3(): bool
    {
        return $this->agreement3;
    }

    /**
     * @param bool $agreement3
     */
    public function setAgreement3(bool $agreement3): void
    {
        $this->agreement3 = $agreement3;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public static function resolveOptions($data): array
    {
        $defaults = [
            'firstName' => null,
            'lastName' => null,
            'address' => null,
            'status' => null,
            'gender' => null,
            'birthDate' => null,
            'company' => null,
            'loyaltyCardNumber' => null,
        ];

        return array_merge($defaults, $data);
    }
}
