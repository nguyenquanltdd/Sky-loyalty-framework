<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain;

/**
 * Interface CampaignRepository.
 */
interface CampaignRepository
{
    /**
     * @param CampaignId $campaignId
     *
     * @return null|Campaign
     */
    public function byId(CampaignId $campaignId);

    /**
     * @param bool $returnQueryBuilder
     *
     * @return array|\Doctrine\ORM\QueryBuilder
     */
    public function findAll($returnQueryBuilder = false);

    /**
     * @param int    $page
     * @param int    $perPage
     * @param null   $sortField
     * @param string $direction
     *
     * @return array
     */
    public function findAllPaginated($page = 1, $perPage = 10, $sortField = null, $direction = 'DESC');

    /**
     * @param int    $page
     * @param int    $perPage
     * @param null   $sortField
     * @param string $direction
     *
     * @return array
     */
    public function findAllVisiblePaginated($page = 1, $perPage = 10, $sortField = null, $direction = 'DESC');

    /**
     * @param SegmentId[] $segmentIds
     * @param LevelId     $levelId
     * @param array       $categoryIds
     * @param int         $page
     * @param int         $perPage
     * @param null        $sortField
     * @param string      $direction
     *
     * @return Campaign[]
     */
    public function getActiveCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null, array $categoryIds = [], $page = 1, $perPage = 10, $sortField = null, $direction = 'ASC'): array;

    public function getActiveCashbackCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null);

    /**
     * @param SegmentId[] $segmentIds
     * @param LevelId     $levelId
     * @param array       $categoryIds
     * @param int         $page
     * @param int         $perPage
     * @param null        $sortField
     * @param string      $direction
     *
     * @return Campaign[]
     */
    public function getVisibleCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null, array $categoryIds = [], $page = 1, $perPage = 10, $sortField = null, $direction = 'ASC'): array;

    /**
     * @param bool $onlyVisible
     *
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countTotal($onlyVisible = false);

    /**
     * @param Campaign $campaign
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Campaign $campaign);

    /**
     * @param Campaign $campaign
     */
    public function remove(Campaign $campaign);

    /**
     * @return array
     */
    public function getActiveCampaigns(): array;
}
