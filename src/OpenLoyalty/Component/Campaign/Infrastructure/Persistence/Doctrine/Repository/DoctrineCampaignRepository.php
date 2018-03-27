<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
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

            $qb->andWhere('e.active = :true')->setParameter('true', true);
            $qb->setParameter('now', new \DateTime());
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null, $page = 1, $perPage = 10, $sortField = null, $direction = 'ASC')
    {
        $qb = $this->getCampaignsForLevelAndSegmentQueryBuilder($segmentIds, $levelId, $page, $perPage, $sortField, $direction);
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
    public function getVisibleCampaignsForLevelAndSegment(array $segmentIds = [], LevelId $levelId = null, $page = 1, $perPage = 10, $sortField = null, $direction = 'ASC')
    {
        $qb = $this->getCampaignsForLevelAndSegmentQueryBuilder($segmentIds, $levelId, $page, $perPage, $sortField, $direction);
        $qb->andWhere(
            $qb->expr()->orX(
                'c.campaignVisibility.allTimeVisible = :true',
                $qb->expr()->andX(
                    'c.campaignVisibility.visibleFrom <= :now',
                    'c.campaignVisibility.visibleTo >= :now'
                )
            )
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array        $segmentIds
     * @param LevelId|null $levelId
     * @param int          $page
     * @param int          $perPage
     * @param null         $sortField
     * @param string       $direction
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getCampaignsForLevelAndSegmentQueryBuilder(array $segmentIds = [], LevelId $levelId = null, $page = 1, $perPage = 10, $sortField = null, $direction = 'ASC')
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

        if ($sortField) {
            $qb->orderBy(
                'e.'.$this->validateSort($sortField),
                $this->validateSortBy($direction)
            );
        }
        if ($perPage) {
            $qb->setMaxResults($perPage);
            $qb->setFirstResult(($page - 1) * $perPage);
        }

        return $qb;
    }
}
