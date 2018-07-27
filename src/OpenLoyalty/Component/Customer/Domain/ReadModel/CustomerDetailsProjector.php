<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\ReadModel;

use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Repository;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignStatusWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignUsageWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerDetailsWereUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerLevelWasRecalculated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasActivated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasDeactivated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasMovedToLevel;
use OpenLoyalty\Component\Customer\Domain\Event\PosWasAssignedToCustomer;
use OpenLoyalty\Component\Customer\Domain\Event\SellerWasAssignedToCustomer;
use OpenLoyalty\Component\Customer\Domain\Model\Address;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Gender;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerAddressWasUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerCompanyDetailsWereUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerLoyaltyCardNumberWasUpdated;
use OpenLoyalty\Component\Customer\Domain\Event\CustomerWasRegistered;
use OpenLoyalty\Component\Customer\Domain\Model\Company;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\Status;
use OpenLoyalty\Component\Customer\Domain\TransactionId;
use OpenLoyalty\Component\Level\Domain\Level;
use OpenLoyalty\Component\Level\Domain\LevelId;
use OpenLoyalty\Component\Level\Domain\LevelRepository;
use OpenLoyalty\Component\Level\Domain\ReadModel\LevelDetails;
use OpenLoyalty\Component\Transaction\Domain\Event\CustomerWasAssignedToTransaction;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Transaction\Domain\Transaction;

/**
 * Class CustomerDetailsProjector.
 */
class CustomerDetailsProjector extends Projector
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var TransactionDetailsRepository
     */
    private $transactionDetailsRepository;

    /**
     * @var LevelRepository
     */
    private $levelRepository;

    /**
     * CustomerDetailsProjector constructor.
     *
     * @param $repository
     * @param TransactionDetailsRepository $transactionDetailsRepository
     * @param LevelRepository              $levelRepository
     */
    public function __construct($repository, TransactionDetailsRepository $transactionDetailsRepository, LevelRepository $levelRepository)
    {
        $this->repository = $repository;
        $this->transactionDetailsRepository = $transactionDetailsRepository;
        $this->levelRepository = $levelRepository;
    }

    /**
     * @param CustomerWasMovedToLevel $event
     */
    public function applyCustomerWasMovedToLevel(CustomerWasMovedToLevel $event): void
    {
        $customerId = $event->getCustomerId();
        $levelId = $event->getLevelId();

        /** @var CustomerDetails $customer */
        $customer = $this->getReadModel($customerId);

        if ($levelId) {
            $customer->setLevelId($levelId);

            if ($event->isRemoveLevelManually()) {
                $customer->setManuallyAssignedLevelId(null);
            } elseif ($event->isManually()) {
                $customer->setManuallyAssignedLevelId($levelId);
            }

            /** @var Level $level */
            $level = $this->levelRepository->byId(new LevelId($levelId->__toString()));
            if ($level) {
                $levelDetails = new LevelDetails($level->getLevelId());
                $levelDetails->setName($level->getName());
                $customer->setLevel($levelDetails);
            }
        } else {
            $customer->setLevel(null);
            $customer->setLevelId(null);
            if ($event->isManually()) {
                $customer->setManuallyAssignedLevelId(null);
            }
        }

        $this->repository->save($customer);
    }

    /**
     * @param CustomerWasRegistered $event
     */
    protected function applyCustomerWasRegistered(CustomerWasRegistered $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());

        $data = $event->getCustomerData();
        $data = $readModel->resolveOptions($data);
        $readModel->setFirstName($data['firstName']);
        $readModel->setLastName($data['lastName']);
        if (!empty($data['phone'])) {
            $readModel->setPhone($data['phone']);
        }
        $readModel->setEmail($data['email']);
        if (!empty($data['gender'])) {
            $readModel->setGender(new Gender($data['gender']));
        }
        if (!empty($data['birthDate'])) {
            $readModel->setBirthDate($data['birthDate']);
        }
        if (isset($data['agreement1'])) {
            $readModel->setAgreement1($data['agreement1']);
        }
        if (isset($data['agreement2'])) {
            $readModel->setAgreement2($data['agreement2']);
        }
        if (isset($data['agreement3'])) {
            $readModel->setAgreement3($data['agreement3']);
        }
        $labels = [];
        if (isset($data['labels'])) {
            foreach ($data['labels'] as $label) {
                $labels[] = new Label($label['key'], $label['value']);
            }
        }
        $readModel->setLabels($labels);
        $readModel->setStatus(Status::typeNew());
        $readModel->setUpdatedAt($event->getUpdateAt());
        $readModel->setCreatedAt($data['createdAt']);

        $this->repository->save($readModel);
    }

    /**
     * @param CustomerDetailsWereUpdated $event
     */
    protected function applyCustomerDetailsWereUpdated(CustomerDetailsWereUpdated $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());

        $data = $event->getCustomerData();
        if (!empty($data['firstName'])) {
            $readModel->setFirstName($data['firstName']);
        }
        if (!empty($data['lastName'])) {
            $readModel->setLastName($data['lastName']);
        }
        if (isset($data['phone'])) {
            $readModel->setPhone($data['phone']);
        }
        if (array_key_exists('email', $data)) {
            $readModel->setEmail($data['email']);
        }
        if (!empty($data['gender'])) {
            $readModel->setGender(new Gender($data['gender']));
        }
        if (!empty($data['birthDate'])) {
            $readModel->setBirthDate($data['birthDate']);
        }

        if (isset($data['agreement1'])) {
            $readModel->setAgreement1($data['agreement1']);
        }
        if (isset($data['agreement2'])) {
            $readModel->setAgreement2($data['agreement2']);
        }
        if (isset($data['agreement3'])) {
            $readModel->setAgreement3($data['agreement3']);
        }
        if (isset($data['status'])) {
            $readModel->setStatus(Status::fromData($data['status']));
        }
        if (isset($data['labels'])) {
            $labels = [];
            foreach ($data['labels'] as $label) {
                $labels[] = new Label($label['key'], $label['value']);
            }
            $readModel->setLabels($labels);
        }
        $readModel->setUpdatedAt($event->getUpdateAt());

        $this->repository->save($readModel);
    }

    /**
     * @param CustomerAddressWasUpdated $event
     */
    protected function applyCustomerAddressWasUpdated(CustomerAddressWasUpdated $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $readModel->setAddress(Address::fromData($event->getAddressData()));
        $readModel->setUpdatedAt($event->getUpdateAt());

        $this->repository->save($readModel);
    }

    /**
     * @param CustomerCompanyDetailsWereUpdated $event
     */
    protected function applyCustomerCompanyDetailsWereUpdated(CustomerCompanyDetailsWereUpdated $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $companyData = $event->getCompanyData();
        if (!$companyData || count($companyData) == 0) {
            $readModel->setCompany(null);
        } else {
            $readModel->setCompany(new Company($companyData['name'], $event->getCompanyData()['nip']));
        }
        $readModel->setUpdatedAt($event->getUpdateAt());

        $this->repository->save($readModel);
    }

    /**
     * @param CustomerLoyaltyCardNumberWasUpdated $event
     */
    protected function applyCustomerLoyaltyCardNumberWasUpdated(CustomerLoyaltyCardNumberWasUpdated $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $readModel->setLoyaltyCardNumber($event->getCardNumber());
        $readModel->setUpdatedAt($event->getUpdateAt());

        $this->repository->save($readModel);
    }

    /**
     * @param PosWasAssignedToCustomer $event
     */
    protected function applyPosWasAssignedToCustomer(PosWasAssignedToCustomer $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $readModel->setPosId($event->getPosId());
        $readModel->setUpdatedAt($event->getUpdateAt());

        $this->repository->save($readModel);
    }

    /**
     * @param SellerWasAssignedToCustomer $event
     */
    protected function applySellerWasAssignedToCustomer(SellerWasAssignedToCustomer $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $readModel->setSellerId($event->getSellerId());
        $readModel->setUpdatedAt($event->getUpdateAt());

        $this->repository->save($readModel);
    }

    /**
     * @param CampaignWasBoughtByCustomer $event
     */
    protected function applyCampaignWasBoughtByCustomer(CampaignWasBoughtByCustomer $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $readModel->addCampaignPurchase(
            new CampaignPurchase(
                $event->getCreatedAt(),
                $event->getCostInPoints(),
                $event->getCampaignId(),
                $event->getCoupon(),
                $event->getReward(),
                $event->getStatus(),
                $event->getActiveSince(),
                $event->getActiveTo()
            )
        );

        $this->repository->save($readModel);
    }

    /**
     * @param CampaignUsageWasChanged $event
     */
    protected function applyCampaignUsageWasChanged(CampaignUsageWasChanged $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $campaignId = $event->getCampaignId()->__toString();
        $coupon = $event->getCoupon()->getCode();

        foreach ($readModel->getCampaignPurchases() as $purchase) {
            if ($purchase->getCampaignId()->__toString() == $campaignId && $purchase->getCoupon()->getCode() == $coupon) {
                $purchase->setUsed($event->isUsed());
                $this->repository->save($readModel);

                return;
            }
        }
    }

    /**
     * @param CampaignStatusWasChanged $event
     */
    protected function applyCampaignStatusWasChanged(CampaignStatusWasChanged $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $campaignId = $event->getCampaignId()->__toString();
        $coupon = $event->getCoupon()->getCode();

        foreach ($readModel->getCampaignPurchases() as $purchase) {
            if ($purchase->getCampaignId()->__toString() === $campaignId && $purchase->getCoupon()->getCode() == $coupon) {
                $purchase->setStatus($event->getStatus());
                $this->repository->save($readModel);

                return;
            }
        }
    }

    /**
     * @param CustomerWasDeactivated $event
     */
    protected function applyCustomerWasDeactivated(CustomerWasDeactivated $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $readModel->setActive(false);
        $readModel->setStatus(Status::typeBlocked());
        $this->repository->save($readModel);
    }

    /**
     * @param CustomerWasActivated $event
     */
    protected function applyCustomerWasActivated(CustomerWasActivated $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $readModel->setActive(true);
        $readModel->setStatus(Status::typeActiveNoCard());
        $this->repository->save($readModel);
    }

    /**
     * @param CustomerLevelWasRecalculated $event
     */
    protected function applyCustomerLevelWasRecalculated(CustomerLevelWasRecalculated $event): void
    {
        /** @var CustomerDetails $readModel */
        $readModel = $this->getReadModel($event->getCustomerId());
        $readModel->setLastLevelRecalculation($event->getDate());
        $this->repository->save($readModel);
    }

    /**
     * @param CustomerWasAssignedToTransaction $event
     */
    public function applyCustomerWasAssignedToTransaction(CustomerWasAssignedToTransaction $event): void
    {
        $readModel = $this->getReadModel(new CustomerId($event->getCustomerId()->__toString()));
        $transaction = $this->transactionDetailsRepository->find($event->getTransactionId()->__toString());
        if (!$transaction instanceof TransactionDetails) {
            return;
        }
        $revisedTransaction = null;
        if ($transaction->getRevisedDocument() && $transaction->getDocumentType() == Transaction::TYPE_RETURN) {
            $tmp = $this->transactionDetailsRepository->findBy(['documentNumberRaw' => $transaction->getRevisedDocument()]);
            if (count($tmp) > 0) {
                $revisedTransaction = reset($tmp);
            }
        }

        $returnAmount = 0;
        $returnWithoutDeliveryAmount = 0;
        if ($revisedTransaction instanceof TransactionDetails) {
            $grossValue = $transaction->getGrossValue();
            $grossValueWithoutDelivery = $transaction->getGrossValueWithoutDeliveryCosts();
            // make return amount always negative
            $returnAmount = $grossValue > 0 ? ($grossValue * -1) : $grossValue;
            $returnWithoutDeliveryAmount = $grossValueWithoutDelivery > 0 ? ($grossValueWithoutDelivery * -1) : $grossValueWithoutDelivery;

            if ($revisedTransaction->getGrossValue() + $returnAmount <= 0) {
                $readModel->setTransactionsCount($readModel->getTransactionsCount() - 1);
            }
        } else {
            $readModel->setTransactionsCount($readModel->getTransactionsCount() + 1);
        }

        if ($returnAmount < 0) {
            $result = $readModel->getTransactionsAmount() + $returnAmount;
            if ($result < 0) { // prevent a negative transaction's amount
                $readModel->setTransactionsAmount(0);
            } else {
                $readModel->setTransactionsAmount($result);
            }
        } else {
            $readModel->setTransactionsAmount($readModel->getTransactionsAmount() + $transaction->getGrossValue());
        }

        if ($returnWithoutDeliveryAmount < 0) {
            // if return transaction type: add a negative amount
            $readModel->setTransactionsAmountWithoutDeliveryCosts($readModel->getTransactionsAmountWithoutDeliveryCosts() + $returnWithoutDeliveryAmount);
        } else {
            $readModel->setTransactionsAmountWithoutDeliveryCosts($readModel->getTransactionsAmountWithoutDeliveryCosts() + $transaction->getGrossValueWithoutDeliveryCosts());
        }

        $readModel->addTransactionId(new TransactionId($event->getTransactionId()->__toString()));
        $readModel->setAverageTransactionAmount($readModel->getTransactionsCount() == 0 ? 0 : $readModel->getTransactionsAmount() / $readModel->getTransactionsCount());
        $readModel->setAmountExcludedForLevel($readModel->getAmountExcludedForLevel() + $transaction->getAmountExcludedForLevel());
        if ($transaction->getPurchaseDate() > $readModel->getLastTransactionDate()) {
            $readModel->setLastTransactionDate($transaction->getPurchaseDate());
        }

        $this->repository->save($readModel);
    }

    /**
     * @param CustomerId $userId
     *
     * @return null|CustomerDetails
     */
    private function getReadModel(CustomerId $userId): CustomerDetails
    {
        $readModel = $this->repository->find($userId->__toString());

        if (null === $readModel) {
            $readModel = new CustomerDetails($userId);
        }

        return $readModel;
    }
}
