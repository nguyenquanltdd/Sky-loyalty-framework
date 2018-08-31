<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\LevelId;
use OpenLoyalty\Component\Campaign\Domain\SegmentId;
use OpenLoyalty\Component\Core\Infrastructure\Persistence\Doctrine\Functions\Cast;
use OpenLoyalty\Component\Core\Infrastructure\Persistence\Doctrine\SortByFilter;
use OpenLoyalty\Component\Core\Infrastructure\Persistence\Doctrine\SortFilter;

/**
 * Class DoctrineCampaignRepository.
 */
class DoctrineCampaignRepository extends EntityRepository implements CampaignRepository
{
    use SortFilter;
    use SortByFilter;

    /**
     * {@inheritdoc}
     */
    public function findAll($returnQueryBuilder = false)
    {
        if ($returnQueryBuilder) {
            return $this->createQueryBuilder('e');
        }

        return parent::findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function byId(CampaignId $campaignId)
    {
        return parent::find($campaignId);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Campaign $campaign)
    {
        $this->getEntityManager()->persist($campaign);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Campaign $campaign)
    {
        $this->getEntityManager()->remove($campaign);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllPaginated($page = 1, $perPage = 10, $sortField = null, $direction = 'ASC')
    {
        $qb = $this->createQueryBuilder('e');

        if ($sortField) {
            $qb->orderBy(
                'e.'.$this->validateSort($sortField),
                $this->validateSortBy($direction)
            );
        }

        $qb->setMaxResults($perPage);
        $qb->setFirstResult(($page - 1) * $perPage);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array  $params
     * @param int    $page
     * @param int    $perPage
     * @param null   $sortField
     * @param string $direction
     *
     * @return array
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function findByParametersPaginated(
        array $params,
        $page = 1,
        $perPage = 10,
        $sortField = null,
        $direction = 'ASC'
    ) {
        $qb = $this->getCampaignsByParamsQueryBuilder($params);

        if ($sortField) {
            $qb->orderBy(
                'c.'.$this->validateSort($sortField),
                $this->validateSortBy($direction)
            );
        }

        $qb->setMaxResults($perPage);
        $qb->setFirstResult(($page - 1) * $perPage);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $params
     *
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    public function countFindByParameters(array $params)
    {
        $qb = $this->getCampaignsByParamsQueryBuilder($params);
        $qb->select('count(c.campaignId)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findAllVisiblePaginated($page = 1, $perPage = 10, $sortField = null, $direction = 'ASC')
    {
        $qb = $this->createQueryBuilder('c');

        if ($sortField) {
            $qb->orderBy(
                'e.'.$this->validateSort($sortField),
                $this->validateSortBy($direction)
            );
        }

        $qb->andWhere(
            $qb->expr()->orX(
                'c.campaignVisibility.allTimeVisible = :true',
                $qb->expr()->andX(
                    'c.campaignVisibility.visibleFrom <= :now',
                    'c.campaignVisibility.visibleTo >= :now'
                )
            )
        );
        $qb->andWhere('c.reward != :cashback')->setParameter('cashback', Campaign::REWARD_TYPE_CASHBACK);

        $qb->andWhere('c.active = :true')->setParameter('true', true);
        $qb->setParameter('now', new \DateTime());

        $qb->setMaxResults($perPage);
        $qb->setFirstResult(($page - 1) * $perPage);

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function countTotal($onlyVisible = false)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('count(e.campaignId)');

        if ($onlyVisible) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'e.campaignVisibility.allTimeVisible = :true',
                    $qb->expr()->andX(
                        'e.campaignVisibility.visibleFrom <= :now',
                        'e.campaignVisibility.visibleTo >= :now'
                    )
                )
            );

            $qb->andWhere('e.reward != :cashback')->setParameter('cashback', Campaign::REWARD_TYPE_CASHBACK);

            $qb->andWhere('e.active = :true')->setParameter('true', true);
            $qb->setParameter('now', new \DateTime());
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null, array $categoryIds = [], $page = 1, $perPage = 10, $sortField = null, $direction = 'ASC'): array
    {
        $qb = $this->getCampaignsForLevelAndSegmentQueryBuilder($segmentIds, $levelId, $categoryIds, $page, $perPage, $sortField, $direction);
        $qb->andWhere(
            $qb->expr()->orX(
                'c.campaignActivity.allTimeActive = :true',
                $qb->expr()->andX(
                    'c.campaignActivity.activeFrom <= :now',
                    'c.campaignActivity.activeTo >= :now'
                )
            )
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param SegmentId[]  $segmentIds
     * @param LevelId|null $levelId
     *
     * @return Campaign[]
     */
    public function getActiveCashbackCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null)
    {
        $qb = $this->getCampaignsForLevelAndSegmentQueryBuilder($segmentIds, $levelId);
        $qb->andWhere('c.reward = :reward')->setParameter('reward', Campaign::REWARD_TYPE_CASHBACK);
        $qb->andWhere(
            $qb->expr()->orX(
                'c.campaignActivity.allTimeActive = :true',
                $qb->expr()->andX(
                    'c.campaignActivity.activeFrom <= :now',
                    'c.campaignActivity.activeTo >= :now'
                )
            )
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibleCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null, array $categoryIds = [], $page = 1, $perPage = 10, $sortField = null, $direction = 'ASC'): array
    {
        $qb = $this->getCampaignsForLevelAndSegmentQueryBuilder($segmentIds, $levelId, $categoryIds, $page, $perPage, $sortField, $direction);
        $qb->andWhere(
            $qb->expr()->orX(
                'c.campaignVisibility.allTimeVisible = :true',
                $qb->expr()->andX(
                    'c.campaignVisibility.visibleFrom <= :now',
                    'c.campaignVisibility.visibleTo >= :now'
                )
            )
        );
        $qb->andWhere('c.reward != :cashback')->setParameter('cashback', Campaign::REWARD_TYPE_CASHBACK);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array        $segmentIds
     * @param LevelId|null $levelId
     * @param array        $categoryIds
     * @param int          $page
     * @param int          $perPage
     * @param null         $sortField
     * @param string       $direction
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getCampaignsForLevelAndSegmentQueryBuilder(array $segmentIds = [], LevelId $levelId = null,
        array $categoryIds = [], $page = 1, $perPage = 10, $sortField = null, $direction = 'ASC'): QueryBuilder
    {
        $this->getEntityManager()->getConfiguration()->addCustomStringFunction('cast', Cast::class);
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere('c.active = :true')->setParameter('true', true);

        $qb->setParameter('now', new \DateTime());
        $levelOrSegment = $qb->expr()->orX();
        if ($levelId) {
            $levelOrSegment->add($qb->expr()->like('cast(c.levels as text)', ':levelId'));
            $qb->setParameter('levelId', '%'.$levelId->__toString().'%');
        }

        $i = 0;
        foreach ($segmentIds as $segmentId) {
            $levelOrSegment->add($qb->expr()->like('cast(c.segments as text)', ':segmentId'.$i));
            $qb->setParameter('segmentId'.$i, '%'.$segmentId->__toString().'%');
            ++$i;
        }

        $qb->andWhere($levelOrSegment);

        $this->updateQueryByCategoriesIds($qb, $categoryIds);

        if ($sortField) {
            $qb->orderBy(
                'c.'.$this->validateSort($sortField),
                $this->validateSortBy($direction)
            );
        }
        if ($perPage) {
            $qb->setMaxResults($perPage);
            $qb->setFirstResult(($page - 1) * $perPage);
        }

        return $qb;
    }

    /**
     * @param array $params
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getCampaignsByParamsQueryBuilder(array $params): QueryBuilder
    {
        $this->getEntityManager()->getConfiguration()->addCustomStringFunction('cast', Cast::class);
        $qb = $this->createQueryBuilder('c');

        if (array_key_exists('labels', $params) && is_array($params['labels'])) {
            foreach ($params['labels'] as $label) {
                $searchLabel = '';
                if (array_key_exists('key', $label)) {
                    $searchLabel .= '"key":"'.$label['key'].'"';
                }
                if (array_key_exists('value', $label)) {
                    if (!empty($searchLabel)) {
                        $searchLabel .= ',';
                    }
                    $searchLabel .= '"value":"'.$label['value'].'"';
                }

                if (!empty($searchLabel)) {
                    $qb->andWhere($qb->expr()->like('cast(c.labels as text)', ':label'));
                    $qb->setParameter('label', '%'.$searchLabel.'%');
                }
            }
        }

        if (array_key_exists('active', $params) && !is_null($params['active'])) {
            if ($params['active']) {
                $qb->andWhere('c.active = :true')->setParameter('true', true);
            } else {
                $qb->andWhere('c.active = :false')->setParameter('false', false);
            }
        }

        if (array_key_exists('campaignType', $params) && !is_null($params['campaignType'])) {
            if ($params['campaignType']) {
                $qb->andWhere('c.reward = :campaignType')->setParameter('campaignType', $params['campaignType']);
            }
        }

        if (array_key_exists('name', $params) && !is_null($params['name'])) {
            if ($params['name']) {
                $qb->andWhere($qb->expr()->like('c.name', ':name'))
                    ->setParameter('name', '%'.urldecode($params['name']).'%');
            }
        }

        if (array_key_exists('categoryId', $params) && is_array($params['categoryId'])) {
            $this->updateQueryByCategoriesIds($qb, $params['categoryId']);
        }

        return $qb;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $categoryIds
     */
    protected function updateQueryByCategoriesIds(QueryBuilder $queryBuilder, array $categoryIds): void
    {
        if (count($categoryIds) == 0) {
            return;
        }

        $categoriesOrX = $queryBuilder->expr()->orX();
        $i = 0;
        foreach ($categoryIds as $categoryId) {
            $categoriesOrX->add($queryBuilder->expr()->like('cast(c.categories as text)', ':categories'.$i));
            $queryBuilder->setParameter('categories'.$i, '%'.$categoryId.'%');
            ++$i;
        }
        $queryBuilder->andWhere($categoriesOrX);
    }

    /**
     * @return array
     */
    public function getActiveCampaigns(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.active = :active')
            ->setParameter('active', true);

        return $qb->getQuery()->getResult();
    }
}
