<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain;

use Assert\Assertion as Assert;

/**
 * Class CampaignCategory.
 */
class CampaignCategory
{
    /**
     * @var CampaignCategoryId
     */
    protected $campaignCategoryId;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var bool
     */
    protected $active = true;

    /**
     * @var int|null
     */
    protected $sortOrder = 0;

    /**
     * CampaignCategory constructor.
     *
     * @param CampaignCategoryId $campaignCategoryId
     * @param array              $data
     */
    public function __construct(CampaignCategoryId $campaignCategoryId, array $data = [])
    {
        $this->campaignCategoryId = $campaignCategoryId;
        $this->setFromArray($data);
    }

    /**
     * @param array $data
     */
    public function setFromArray(array $data): void
    {
        if (isset($data['name'])) {
            $this->name = $data['name'];
        }

        if (isset($data['sortOrder'])) {
            $this->sortOrder = $data['sortOrder'];
        }

        if (isset($data['active'])) {
            $this->active = $data['active'];
        }
    }

    /**
     * @param array $data
     *
     * @throws \Assert\AssertionFailedException
     */
    public static function validateRequiredData(array $data): void
    {
        Assert::keyIsset($data, 'name');
        Assert::string($data['name']);
        Assert::keyIsset($data, 'sortOrder');
        Assert::integer($data['sortOrder']);
    }

    /**
     * @return CampaignCategoryId
     */
    public function getCampaignCategoryId(): CampaignCategoryId
    {
        return $this->campaignCategoryId;
    }

    /**
     * @param CampaignCategoryId $campaignCategoryId
     */
    public function setCampaignCategoryId(CampaignCategoryId $campaignCategoryId): void
    {
        $this->campaignCategoryId = $campaignCategoryId;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return int|null
     */
    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    /**
     * @param int|null $sortOrder
     */
    public function setSortOrder(?int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }
}
