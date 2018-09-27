<?php
/**
 * Copyright ÂŠ 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\EarningRule\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;
use OpenLoyalty\Component\EarningRule\Domain\EarningRuleQrcodeRepository;

/**
 * Class DoctrineEarningRuleQrcodeRepository.
 */
class DoctrineEarningRuleQrcodeRepository extends EntityRepository implements EarningRuleQrcodeRepository
{
    /**
     * {@inheritdoc}
     */
    public function findQrcodeRules(): array
    {
        $qb = $this->createQueryBuilder('e');

        return $qb->getQuery()->getResult();
    }
}
