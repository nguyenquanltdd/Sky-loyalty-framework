<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Customer\Domain\ReadModel;

use Broadway\ReadModel\Repository;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;

interface CustomerDetailsRepository extends Repository
{
    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @param bool      $onlyActive
     *
     * @return CustomerDetails[]
     */
    public function findByBirthdayAnniversary(\DateTime $from, \DateTime $to, $onlyActive = true);

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @param bool      $onlyActive
     *
     * @return CustomerDetails[]
     */
    public function findByCreationAnniversary(\DateTime $from, \DateTime $to, $onlyActive = true);

    /**
     * @param array $params
     * @param bool  $exact
     *
     * @return CustomerDetails[]
     */
    public function findByParameters(array $params, $exact = true);

    /**
     * @param array  $params
     * @param bool   $exact
     * @param int    $page
     * @param int    $perPage
     * @param null   $sortField
     * @param string $direction
     *
     * @return CustomerDetails[]
     */
    public function findByParametersPaginated(array $params, $exact = true, $page = 1, $perPage = 10, $sortField = null, $direction = 'DESC');

    /**
     * @param array $params
     * @param bool  $exact
     *
     * @return int
     */
    public function countTotal(array $params = [], $exact = true);

    /**
     * @param CustomerId $customerId
     * @param int        $page
     * @param int        $perPage
     * @param null       $sortField
     * @param string     $direction
     * @param bool       $showCashback
     *
     * @return CampaignPurchase[]
     */
    public function findPurchasesByCustomerIdPaginated(CustomerId $customerId, $page = 1, $perPage = 10, $sortField = null, $direction = 'DESC', $showCashback = false);

    /**
     * @return CustomerDetails[]
     */
    public function findCustomersWithPurchasesToActivate(): array;

    /**
     * @return CustomerDetails[]
     */
    public function findCustomersWithPurchasesToExpire(): array;

    /**
     * @param CustomerId $customerId
     * @param bool       $showCashback
     *
     * @return int
     */
    public function countPurchasesByCustomerId(CustomerId $customerId, $showCashback = false);

    /**
     * @param $criteria
     * @param $limit
     *
     * @return CustomerDetails[]
     */
    public function findOneByCriteria($criteria, $limit);

    /**
     * @param $from
     * @param $to
     * @param bool $onlyActive
     *
     * @return CustomerDetails[]
     */
    public function findAllWithAverageTransactionAmountBetween($from, $to, $onlyActive = true);

    /**
     * @param $from
     * @param $to
     * @param bool $onlyActive
     *
     * @return CustomerDetails[]
     */
    public function findAllWithTransactionAmountBetween($from, $to, $onlyActive = true);

    /**
     * @param $from
     * @param $to
     * @param bool $onlyActive
     *
     * @return CustomerDetails[]
     */
    public function findAllWithTransactionCountBetween($from, $to, $onlyActive = true);

    /**
     * @param $fieldName
     *
     * @return int
     */
    public function sumAllByField($fieldName);

    /**
     * @param array $labels
     * @param null  $active
     *
     * @return CustomerDetails[]
     */
    public function findByLabels(array $labels, $active = null);

    /**
     * @param array $labels
     * @param null  $active
     *
     * @return CustomerDetails[]
     */
    public function findWithLabels(array $labels, $active = null);
}
