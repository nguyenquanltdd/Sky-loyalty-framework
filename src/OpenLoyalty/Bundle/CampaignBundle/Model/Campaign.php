<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\Model;

use OpenLoyalty\Component\Campaign\Domain\Campaign as BaseCampaign;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Campaign.
 */
class Campaign extends BaseCampaign
{
    /**
     * Campaign constructor.
     */
    public function __construct()
    {
    }

    public function toArray()
    {
        return [
            'reward' => $this->reward,
            'name' => $this->name,
            'shortDescription' => $this->shortDescription,
            'conditionsDescription' => $this->conditionsDescription,
            'moreInformationLink' => $this->moreInformationLink,
            'active' => $this->active,
            'costInPoints' => $this->costInPoints,
            'pointValue' => $this->pointValue,
            'levels' => $this->levels,
            'segments' => $this->segments,
            'unlimited' => $this->unlimited,
            'singleCoupon' => $this->singleCoupon,
            'limit' => $this->limit,
            'limitPerUser' => $this->limitPerUser,
            'coupons' => $this->coupons,
            'campaignActivity' => $this->campaignActivity ? $this->campaignActivity->toArray() : null,
            'campaignVisibility' => $this->campaignVisibility ? $this->campaignVisibility->toArray() : null,
            'usageInstruction' => $this->usageInstruction,
            'rewardValue' => $this->rewardValue,
            'tax' => $this->tax,
            'taxPriceValue' => $this->taxPriceValue,
        ];
    }

    /**
     * @param ExecutionContextInterface $context
     * @Assert\Callback()
     */
    public function validateLimit(ExecutionContextInterface $context)
    {
        if ($this->unlimited) {
            return;
        }

        if ($this->reward == self::REWARD_TYPE_CASHBACK) {
            return;
        }

        if (!$this->limit) {
            $context->buildViolation((new NotBlank())->message)->atPath('limit')->addViolation();
        }
        if (!$this->limitPerUser) {
            $context->buildViolation((new NotBlank())->message)->atPath('limitPerUser')->addViolation();
        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @Assert\Callback()
     */
    public function validateSegmentsAndLevels(ExecutionContextInterface $context)
    {
        if (count($this->levels) == 0 && count($this->segments) == 0) {
            $message = 'This collection should contain 1 element or more.';
            $context->buildViolation($message)->atPath('levels')->addViolation();
            $context->buildViolation($message)->atPath('segments')->addViolation();
        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @Assert\Callback()
     */
    public function validateCoupons(ExecutionContextInterface $context)
    {
        if ($this->reward == self::REWARD_TYPE_CASHBACK) {
            return;
        }

        if (count($this->coupons) == 0) {
            $message = 'This collection should contain 1 element or more.';
            $context->buildViolation($message)->atPath('coupons')->addViolation();
        }
    }

    /**
     * @param ExecutionContextInterface $context
     * @Assert\Callback()
     */
    public function validateTax(ExecutionContextInterface $context)
    {
        if (!empty($this->tax)) {
            if (!filter_var($this->tax, FILTER_VALIDATE_INT)) {
                $context->buildViolation('This value should be of type integer.')->atPath('tax')->addViolation();
            }
        }
    }
}
