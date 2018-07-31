<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\Service;

use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;

/**
 * Class EarningRuleCampaignProvider.
 */
class EarningRuleCampaignProvider implements EarningRuleCampaignProviderInterface
{
    /**
     * @var CampaignProvider
     */
    private $campaignProvider;

    /**
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * EarningRuleCampaignProvider constructor.
     *
     * @param CampaignProvider   $campaignProvider
     * @param CampaignValidator  $campaignValidator
     * @param CampaignRepository $campaignRepository
     */
    public function __construct(
        CampaignProvider $campaignProvider,
        CampaignValidator $campaignValidator,
        CampaignRepository $campaignRepository
    ) {
        $this->campaignProvider = $campaignProvider;
        $this->campaignValidator = $campaignValidator;
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * @param string $campaignId
     *
     * @return Campaign
     */
    private function findCampaign(string $campaignId): Campaign
    {
        return $this->campaignRepository->byId(new CampaignId($campaignId));
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(string $campaignId): bool
    {
        $campaign = $this->findCampaign($campaignId);
        if (!$campaign) {
            return false;
        }

        return $this->campaignValidator->isCampaignActive($campaign);
    }

    /**
     * {@inheritdoc}
     */
    public function isValidForCustomer(string $campaignId, string $customerId): bool
    {
        $campaign = $this->findCampaign($campaignId);
        if (!$campaign) {
            return false;
        }

        $customers = $this->campaignProvider->validForCustomers($campaign);

        return in_array($customerId, $customers, true);
    }
}
