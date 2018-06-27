<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use OpenLoyalty\Bundle\CampaignBundle\Model\Campaign;
use OpenLoyalty\Bundle\CampaignBundle\Model\CampaignActivity;
use OpenLoyalty\Bundle\CampaignBundle\Model\CampaignVisibility;
use OpenLoyalty\Bundle\LevelBundle\DataFixtures\ORM\LoadLevelData;
use OpenLoyalty\Bundle\SegmentBundle\DataFixtures\ORM\LoadSegmentData;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\Command\CreateCampaign;
use OpenLoyalty\Component\Campaign\Domain\LevelId;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon;
use OpenLoyalty\Component\Campaign\Domain\SegmentId;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use Symfony\Bridge\Doctrine\Tests\Fixtures\ContainerAwareFixture;

/**
 * Class LoadCampaignData.
 */
class LoadCampaignData extends ContainerAwareFixture
{
    const CAMPAIGN_ID = '000096cf-32a3-43bd-9034-4df343e5fd93';
    const CAMPAIGN2_ID = '000096cf-32a3-43bd-9034-4df343e5fd92';
    const CAMPAIGN3_ID = '000096cf-32a3-43bd-9034-4df343e5fd91';

    public function load(ObjectManager $manager)
    {
        $campaign = new Campaign();
        $campaign->setActive(true);
        $campaign->setCostInPoints(10);
        $campaign->setLimit(10);
        $campaign->setUnlimited(false);
        $campaign->setLimitPerUser(2);
        $campaign->setLevels([new LevelId(LoadLevelData::LEVEL2_ID)]);
        $campaign->setSegments([new SegmentId(LoadSegmentData::SEGMENT2_ID)]);
        $campaign->setCoupons([new Coupon('123')]);
        $campaign->setReward(Campaign::REWARD_TYPE_DISCOUNT_CODE);
        $campaign->setName('tests');
        $campaign->setLabels([new Label('type', 'promotion')]);
        $campaignActivity = new CampaignActivity();
        $campaignActivity->setAllTimeActive(false);
        $campaignActivity->setActiveFrom(new \DateTime('2016-01-01'));
        $campaignActivity->setActiveTo(new \DateTime('2037-01-01'));
        $campaign->setCampaignActivity($campaignActivity);
        $campaignVisibility = new CampaignVisibility();
        $campaignVisibility->setAllTimeVisible(false);
        $campaignVisibility->setVisibleFrom(new \DateTime('2016-01-01'));
        $campaignVisibility->setVisibleTo(new \DateTime('2037-01-01'));
        $campaign->setCampaignVisibility($campaignVisibility);

        $this->container->get('broadway.command_handling.command_bus')
            ->dispatch(
                new CreateCampaign(new CampaignId(self::CAMPAIGN_ID), $campaign->toArray())
            );

        $campaign = new Campaign();
        $campaign->setActive(false);
        $campaign->setCostInPoints(10);
        $campaign->setLimit(10);
        $campaign->setUnlimited(false);
        $campaign->setLimitPerUser(2);
        $campaign->setLevels([new LevelId(LoadLevelData::LEVEL2_ID)]);
        $campaign->setSegments([new SegmentId(LoadSegmentData::SEGMENT2_ID)]);
        $campaign->setCoupons([new Coupon('123')]);
        $campaign->setReward(Campaign::REWARD_TYPE_DISCOUNT_CODE);
        $campaign->setName('for test');
        $campaign->setLabels([new Label('type', 'test')]);
        $campaignActivity = new CampaignActivity();
        $campaignActivity->setAllTimeActive(false);
        $campaignActivity->setActiveFrom(new \DateTime('2016-01-01'));
        $campaignActivity->setActiveTo(new \DateTime('2037-01-01'));
        $campaign->setCampaignActivity($campaignActivity);
        $campaignVisibility = new CampaignVisibility();
        $campaignVisibility->setAllTimeVisible(false);
        $campaignVisibility->setVisibleFrom(new \DateTime('2016-01-01'));
        $campaignVisibility->setVisibleTo(new \DateTime('2037-01-01'));
        $campaign->setCampaignVisibility($campaignVisibility);

        $this->container->get('broadway.command_handling.command_bus')
            ->dispatch(
                new CreateCampaign(new CampaignId(self::CAMPAIGN2_ID), $campaign->toArray())
            );

        $campaign = new Campaign();
        $campaign->setReward(Campaign::REWARD_TYPE_CASHBACK);
        $campaign->setName('cashback');
        $campaign->setActive(true);
        $campaign->setPointValue(10);
        $campaign->setLabels([new Label('type', 'cashback'), new Label('type', 'promotion')]);
        $campaign->setLevels([new LevelId(LoadLevelData::LEVEL2_ID)]);
        $campaign->setSegments([new SegmentId(LoadSegmentData::SEGMENT2_ID)]);
        $campaignActivity = new CampaignActivity();
        $campaignActivity->setAllTimeActive(true);
        $campaign->setCampaignActivity($campaignActivity);
        $campaignVisibility = new CampaignVisibility();
        $campaignVisibility->setAllTimeVisible(true);
        $campaign->setCampaignVisibility($campaignVisibility);

        $this->container->get('broadway.command_handling.command_bus')
                        ->dispatch(
                            new CreateCampaign(new CampaignId(self::CAMPAIGN3_ID), $campaign->toArray())
                        );
    }
}
