<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\EarningRule\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use OpenLoyalty\Component\Core\Infrastructure\Persistence\Doctrine\SortByFilter;
use OpenLoyalty\Component\Core\Infrastructure\Persistence\Doctrine\SortFilter;
use OpenLoyalty\Component\EarningRule\Domain\CustomEventEarningRule;
use OpenLoyalty\Component\EarningRule\Domain\EarningRule;
use OpenLoyalty\Component\EarningRule\Domain\EarningRuleId;
use OpenLoyalty\Component\EarningRule\Domain\EarningRuleRepository;
use OpenLoyalty\Component\EarningRule\Domain\EventEarningRule;
use OpenLoyalty\Component\EarningRule\Domain\ReferralEarningRule;
use OpenLoyalty\Component\Core\Domain\Model\Identifier;
use OpenLoyalty\Component\Core\Infrastructure\Persistence\Doctrine\Functions\Cast;

/**
 * Class DoctrineEarningRuleRepository.
 */
class DoctrineEarningRuleRepository extends EntityRepository implements EarningRuleRepository
{
    use SortFilter, SortByFilter;

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
    public function byId(EarningRuleId $earningRuleId)
    {
        return parent::find($earningRuleId);
    }

    /**
     * {@inheritdoc}
     */
    public function save(EarningRule $earningRule)
    {
        $this->getEntityManager()->persist($earningRule);
        $this->getEntityManager()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(EarningRule $earningRule)
    {
        $this->getEntityManager()->remove($earningRule);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllPaginated($page = 1, $perPage = 10, $sortField = null, $direction = 'ASC', $returnQb = false)
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

        return $returnQb ? $qb : $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function countTotal($returnQb = false)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('count(e.earningRuleId)');

        return $returnQb ? $qb : $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findAllActive(\DateTime $date = null)
    {
        if (!$date) {
            $date = new \DateTime();
        }

        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.active = :true')->setParameter('true', true);
        $qb->andWhere($qb->expr()->orX(
            'e.allTimeActive = :true',
            'e.startAt <= :date AND e.endAt >= :date'
        ))->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findAllActiveEventRules(
        $eventName = null,
        array $segmentIds = [],
        $levelId = null,
        \DateTime $date = null,
        $posId = null
    ) {
        $qb = $this->getEarningRulesForLevelAndSegmentQueryBuilder($segmentIds, $levelId, $date, $posId);

        $qb->add('from', EventEarningRule::class.' e');
        if ($eventName) {
            $qb->andWhere('e.eventName = :eventName')->setParameter('eventName', $eventName);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findByCustomEventName(
        $eventName,
        array $segmentIds = [],
        $levelId = null,
        \DateTime $date = null,
        $posId = null
    ) {
        $qb = $this->getEarningRulesForLevelAndSegmentQueryBuilder($segmentIds, $levelId, $date, $posId);

        $qb->add('from', CustomEventEarningRule::class.' e');
        $qb->andWhere('e.eventName = :eventName')->setParameter('eventName', $eventName);

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findReferralByEventName(
        $eventName,
        array $segmentIds = [],
        $levelId = null,
        \DateTime $date = null,
        $posId = null
    ) {
        $qb = $this->getEarningRulesForLevelAndSegmentQueryBuilder($segmentIds, $levelId, $date, $posId);

        $qb->add('from', ReferralEarningRule::class.'e');
        $qb->andWhere('e.eventName = :eventName')->setParameter('eventName', $eventName);

        return $qb->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function isCustomEventEarningRuleExist($eventName, $currentEarningRuleId = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()->select('count(e)')
            ->from(CustomEventEarningRule::class, 'e');
        if ($currentEarningRuleId) {
            $qb->andWhere('e.earningRuleId != :earning_rule_id')
                ->setParameter('earning_rule_id', $currentEarningRuleId);
        }
        $qb->andWhere('e.eventName = :event_name')->setParameter('event_name', $eventName);

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function findAllActiveEventRulesBySegmentsAndLevels(
        \DateTime $date = null,
        array $segmentIds = [],
        $levelId = null,
        $posId = null
    ) {
        $qb = $this->getEarningRulesForLevelAndSegmentQueryBuilder($segmentIds, $levelId, $date, $posId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array          $segmentIds
     * @param null           $levelId
     * @param \DateTime|null $date
     * @param null           $posId
     *
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getEarningRulesForLevelAndSegmentQueryBuilder(
        array $segmentIds = [],
        $levelId = null,
        \DateTime $date = null,
        $posId = null
    ) {
        $this->getEntityManager()->getConfiguration()->addCustomStringFunction('cast', Cast::class);

        if (!$date) {
            $date = new \DateTime();
        }

        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.active = :true')->setParameter('true', true);
        $qb->andWhere($qb->expr()->orX(
            'e.allTimeActive = :true',
            'e.startAt <= :date AND e.endAt >= :date'
        ))->setParameter('date', $date);

        $levelOrSegment = $qb->expr()->orX();
        if ($levelId) {
            $levelId = ($levelId instanceof Identifier) ? $levelId->__toString() : $levelId;
            $levelOrSegment->add($qb->expr()->like('cast(e.levels as text)', ':levelId'));
            $qb->setParameter('levelId', '%'.$levelId.'%');
        }

        $i = 0;
        foreach ($segmentIds as $segmentId) {
            $segmentId = ($segmentId instanceof Identifier) ? $segmentId->__toString() : $segmentId;
            $levelOrSegment->add($qb->expr()->like('cast(e.segments as text)', ':segmentId'.$i));
            $qb->setParameter('segmentId'.$i, '%'.$segmentId.'%');
            ++$i;
        }

        $qb->andWhere($levelOrSegment);

        if ($posId) {
            // if posId is defined, find all ER that has this posId or has empty posId setting
            $pos = $qb->expr()->orX();
            $posId = ($posId instanceof Identifier) ? $posId->__toString() : $posId;
            $pos->add($qb->expr()->like('cast(e.pos as text)', ':posId'));
            $pos->add($qb->expr()->eq('cast(e.pos as text)', ':pos'));
            $qb->setParameter('posId', '%'.$posId.'%');
            $qb->setParameter('pos', '[]');
            $qb->andWhere($pos);
        } else {
            // if posId is not defined, find all ER that hs empty posId setting
            $qb->andWhere($qb->expr()->eq('cast(e.pos as text)', ':pos'))->setParameter('pos', '[]');
        }

        return $qb;
    }
}
