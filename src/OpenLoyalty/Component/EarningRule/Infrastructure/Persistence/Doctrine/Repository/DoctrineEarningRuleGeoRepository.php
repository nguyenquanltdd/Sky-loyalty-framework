<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\EarningRule\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use OpenLoyalty\Component\EarningRule\Domain\EarningRuleGeoRepository;

/**
 * Class DoctrineEarningRuleGeoRepository.
 */
class DoctrineEarningRuleGeoRepository extends EntityRepository implements EarningRuleGeoRepository
{
    /**
     * {@inheritdoc}
     */
    public function findGeoRules(): array
    {
        $qb = $this->createQueryBuilder('e');

        return $qb->getQuery()->getResult();
    }
}
