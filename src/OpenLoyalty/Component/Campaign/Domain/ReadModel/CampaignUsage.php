<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Domain\ReadModel;

use Broadway\ReadModel\SerializableReadModel;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;

/**
 * Class CampaignUsage.
 */
class CampaignUsage implements SerializableReadModel
{
    /**
     * @var CampaignId
     */
    protected $campaignId;

    /**
     * @var int
     */
    protected $campaignUsage;

    /**
     * CampaignUsage constructor.
     *
     * @param CampaignId $campaignId
     */
    public function __construct(CampaignId $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->campaignId->__toString();
    }

    /**
     * @param array $data
     *
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        $tmp = new self(new CampaignId($data['campaignId']));
        if (isset($data['usage'])) {
            $tmp->setCampaignUsage($data['usage']);
        }

        return $tmp;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        return [
            'campaignId' => $this->campaignId->__toString(),
            'usage' => $this->campaignUsage,
        ];
    }

    /**
     * @return CampaignId
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @return int
     */
    public function getCampaignUsage()
    {
        return $this->campaignUsage;
    }

    /**
     * @param int $campaignUsage
     */
    public function setCampaignUsage($campaignUsage)
    {
        $this->campaignUsage = $campaignUsage;
    }
}
