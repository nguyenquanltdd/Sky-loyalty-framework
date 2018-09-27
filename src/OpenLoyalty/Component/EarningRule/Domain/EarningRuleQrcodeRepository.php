<?php
/**
 * Copyright ÂŠ 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\EarningRule\Domain;

/**
 * Interface EarningRuleQrcodeRepository.
 */
interface EarningRuleQrcodeRepository
{
    /**
     * @return array
     */
    public function findQrcodeRules(): array;
}
