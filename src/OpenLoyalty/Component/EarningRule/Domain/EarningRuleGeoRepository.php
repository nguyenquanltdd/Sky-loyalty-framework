<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\EarningRule\Domain;

/**
 * Interface EarningRuleGeoRepository.
 */
interface EarningRuleGeoRepository
{
    /**
     * @return array
     */
    public function findGeoRules(): array;
}
