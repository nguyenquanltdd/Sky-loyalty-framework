<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Account\Infrastructure\Model;

/**
 * Class EvaluationResult.
 */
class EvaluationResult
{
    /**
     * @var string
     */
    protected $earningRuleId = null;

    /**
     * @var int
     */
    protected $points = null;

    /**
     * @var string
     */
    protected $name;

    /**
     * EvaluationResult constructor.
     *
     * @param string $earningRuleId
     * @param float  $points
     * @param string $name
     */
    public function __construct($earningRuleId, $points, string $name = '')
    {
        $this->earningRuleId = $earningRuleId;
        $this->points = $points;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getEarningRuleId()
    {
        return $this->earningRuleId;
    }

    /**
     * @return int
     */
    public function getPoints()
    {
        return $this->points;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
