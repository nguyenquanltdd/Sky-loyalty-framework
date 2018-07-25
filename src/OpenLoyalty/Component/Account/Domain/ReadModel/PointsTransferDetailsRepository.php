<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Domain\ReadModel;

use Broadway\ReadModel\Repository;

interface PointsTransferDetailsRepository extends Repository
{
    /**
     * @param int $timestamp
     *
     * @return PointsTransferDetails[]
     */
    public function findAllActiveAddingTransfersExpiredAfter(int $timestamp): array;

    /**
     * @param \DateTime $dateTime
     *
     * @return PointsTransferDetails[]
     */
    public function findAllPendingAddingTransfersToUnlock(\DateTime $dateTime): array;

    /**
     * @param $timestamp
     *
     * @return PointsTransferDetails[]
     */
    public function findAllActiveAddingTransfersCreatedAfter($timestamp): array;

    /**
     * @param int    $page
     * @param int    $perPage
     * @param string $sortField
     * @param string $direction
     *
     * @return PointsTransferDetails[]
     */
    public function findAllPaginated($page = 1, $perPage = 10, $sortField = 'earningRuleId', $direction = 'DESC'): array;

    /**
     * @param array  $params
     * @param bool   $exact
     * @param int    $page
     * @param int    $perPage
     * @param null   $sortField
     * @param string $direction
     *
     * @return PointsTransferDetails[]
     */
    public function findByParametersPaginated(array $params, $exact = true, $page = 1, $perPage = 10, $sortField = null, $direction = 'DESC'): array;

    /**
     * @param array $params
     * @param bool  $exact
     *
     * @return int
     */
    public function countTotal(array $params = [], $exact = true): int;

    /**
     * @return int
     */
    public function countTotalSpendingTransfers(): int;

    /**
     * @return float
     */
    public function getTotalValueOfSpendingTransfers(): float;
}
