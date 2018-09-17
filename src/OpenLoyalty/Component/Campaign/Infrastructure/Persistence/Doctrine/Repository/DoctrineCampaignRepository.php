<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\LevelId;
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
        $campaign->mergeNewTranslations();
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
     * @throws ORMException
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
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
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
    public function findAllVisiblePaginated($page = 1, $perPage = 10, $sortField = null, $direction = 'ASC', array $filters = [])
    {
        $qb = $this->createQueryBuilder('c');

        if ($sortField) {
            $qb->orderBy(
                'e.'.$this->validateSort($sortField),
                $this->validateSortBy($direction)
            );
        }

        if (array_key_exists('isPublic', $filters) && !is_null($filters['isPublic'])) {
            $qb->andWhere('c.public = :public')->setParameter('public', $filters['isPublic']);
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
     * @param int    $page
     * @param int    $perPage
     * @param null   $sortField
     * @param string $direction
     * @param array  $filters
     *
     * @return array
     */
    public function findAllFeaturedPaginated(
        $page = 1,
        $perPage = 10,
        $sortField = null,
        $direction = 'ASC',
        array $filters = []
    ): array {
        $query = $this->getFeaturedCampaignsQueryBuilder($filters);

        if ($sortField) {
            $query->orderBy(sprintf('campaign.%s', $this->validateSort($sortField)), $this->validateSortBy($direction));
        }

        $query
            ->setMaxResults($perPage)
            ->setFirstResult(($page - 1) * $perPage)
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    public function countFeatured(array $filters = []): int
    {
        $query = $this->getFeaturedCampaignsQueryBuilder();
        $query->select('count(campaign.campaignId)');

        if (array_key_exists('isPublic', $filters) && !is_null($filters['isPublic'])) {
            $query->andWhere('campaign.public = :public')->setParameter('public', $filters['isPublic']);
        }

        try {
            return $query->getQuery()->getSingleScalarResult();
        } catch (ORMException $ex) {
            return 0;
        }
    }

    /**
     * @param array $filters
     *
     * @return QueryBuilder
     */
    protected function getFeaturedCampaignsQueryBuilder(array $filters = []): QueryBuilder
    {
        $query = $this->createQueryBuilder('campaign');
        $query
            ->andWhere('campaign.active = :true')
            ->setParameter('true', true)
        ;

        $query
            ->andWhere('campaign.featured = :featured')
            ->setParameter('featured', true)
        ;

        if (array_key_exists('isPublic', $filters) && !is_null($filters['isPublic'])) {
            $query->andWhere('campaign.public = :public')->setParameter('public', $filters['isPublic']);
        }

        $query
            ->andWhere(
                $query->expr()->orX(
                    'campaign.campaignVisibility.allTimeVisible = :visible',
                    $query->expr()->andX(
                        'campaign.campaignVisibility.visibleFrom <= :now',
                        'campaign.campaignVisibility.visibleTo >= :now'
                    )
                )
            )
            ->setParameter('now', new \DateTime())
            ->setParameter('visible', true);

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function countTotal($onlyVisible = false, array $filters = [])
    {
        $query = $this->createQueryBuilder('e');
        $query->select('count(e.campaignId)');

        if (array_key_exists('isPublic', $filters) && !is_null($filters['isPublic'])) {
            $query->andWhere('e.public = :public')->setParameter('public', $filters['isPublic']);
        }

        if ($onlyVisible) {
            $query->andWhere(
                $query->expr()->orX(
                    'e.campaignVisibility.allTimeVisible = :true',
                    $query->expr()->andX(
                        'e.campaignVisibility.visibleFrom <= :now',
                        'e.campaignVisibility.visibleTo >= :now'
                    )
                )
            );

            $query->andWhere('e.reward != :cashback')->setParameter('cashback', Campaign::REWARD_TYPE_CASHBACK);

            $query->andWhere('e.active = :true')->setParameter('true', true);
            $query->setParameter('now', new \DateTime());
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ORMException
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
     * {@inheritdoc}
     *
     * @throws ORMException
     */
    public function getActiveCashbackCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null): array
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
     *
     * @throws ORMException
     */
    public function getVisibleCampaignsForLevelAndSegment(
        array $segmentIds = [],
        LevelId $levelId = null,
        array $categoryIds = [],
        $page = 1,
        $perPage = 10,
        $sortField = null,
        $direction = 'ASC',
        array $filters = []
    ): array {
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

        if (array_key_exists('featured', $filters) && !is_null($filters['featured'])) {
            $qb->andWhere('c.featured = :featured')->setParameter('featured', $filters['featured']);
        }

        if (array_key_exists('isPublic', $filters) && !is_null($filters['isPublic'])) {
            $qb->andWhere('c.public = :public')->setParameter('public', $filters['isPublic']);
        }

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
     * @return QueryBuilder
     *
     * @throws ORMException
     */
    protected function getCampaignsForLevelAndSegmentQueryBuilder(
        array $segmentIds = [],
        LevelId $levelId = null,
        array $categoryIds = [],
        $page = 1,
        $perPage = 10,
        $sortField = null,
        $direction = 'ASC'
    ): QueryBuilder {
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
     * @return QueryBuilder
     *
     * @throws ORMException
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

        if (array_key_exists('isPublic', $params) && !is_null($params['isPublic'])) {
            if ($params['isPublic']) {
                $qb->andWhere('c.public = :true')->setParameter('true', true);
            } else {
                $qb->andWhere('c.public = :false')->setParameter('false', false);
            }
        }

        if (array_key_exists('isFeatured', $params) && !is_null($params['isFeatured'])) {
            if ($params['isFeatured']) {
                $qb->andWhere('c.featured = :true')->setParameter('true', true);
            } else {
                $qb->andWhere('c.featured = :false')->setParameter('false', false);
            }
        }

        if (array_key_exists('campaignType', $params) && !is_null($params['campaignType'])) {
            $qb->andWhere('c.reward = :campaignType')->setParameter('campaignType', $params['campaignType']);
        }

        if (array_key_exists('name', $params) && !is_null($params['name'])) {
            $qb->join('c.translations', 't');
            $qb->andWhere($qb->expr()->like('t.name', ':name'))
                ->setParameter('name', '%'.urldecode($params['name']).'%')
                ->andWhere('t.locale = :locale')
                ->setParameter('locale', $params['_locale']);
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
