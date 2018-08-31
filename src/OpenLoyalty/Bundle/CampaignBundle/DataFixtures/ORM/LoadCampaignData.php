<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Faker\Provider\Uuid;
use OpenLoyalty\Bundle\CampaignBundle\Model\Campaign;
use OpenLoyalty\Bundle\CampaignBundle\Model\CampaignActivity;
use OpenLoyalty\Bundle\CampaignBundle\Model\CampaignCategory;
use OpenLoyalty\Bundle\CampaignBundle\Model\CampaignVisibility;
use OpenLoyalty\Bundle\LevelBundle\DataFixtures\ORM\LoadLevelData;
use OpenLoyalty\Bundle\SegmentBundle\DataFixtures\ORM\LoadSegmentData;
use OpenLoyalty\Component\Campaign\Domain\CampaignCategoryId;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\Command\CreateCampaign;
use OpenLoyalty\Component\Campaign\Domain\Command\CreateCampaignCategory;
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
    const PERCENTAGE_COUPON_CAMPAIGN_ID = '000096cf-32a3-43bd-9034-4df343e5fd94';

    const CAMPAIGN_CATEGORY1_ID = '000096cf-32a3-43bd-9034-4df343e5fd99';
    const CAMPAIGN_CATEGORY2_ID = '000096cf-32a3-43bd-9034-4df343e5fd98';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $campaignCategory = new CampaignCategory();
        $campaignCategory->setName('Category A');
        $campaignCategory->setActive(true);
        $campaignCategory->setSortOrder(0);

        $this->container->get('broadway.command_handling.command_bus')
            ->dispatch(
                new CreateCampaignCategory(new CampaignCategoryId(self::CAMPAIGN_CATEGORY1_ID), $campaignCategory->toArray())
            );

        $campaignCategory = new CampaignCategory();
        $campaignCategory->setName('Category B');
        $campaignCategory->setActive(true);
        $campaignCategory->setSortOrder(0);

        $this->container->get('broadway.command_handling.command_bus')
            ->dispatch(
                new CreateCampaignCategory(new CampaignCategoryId(self::CAMPAIGN_CATEGORY2_ID), $campaignCategory->toArray())
            );

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
        $campaign->setBrandDescription('_branddescription_');
        $campaign->setShortDescription('_shortdescription_');
        $campaign->setConditionsDescription('_conditionsdescription_');
        $campaign->setUsageInstruction('_usageinstruction_');
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
        $campaign->setCategories([
            new CampaignCategoryId(self::CAMPAIGN_CATEGORY1_ID),
            new CampaignCategoryId(self::CAMPAIGN_CATEGORY2_ID),
        ]);

        $this->container->get('broadway.command_handling.command_bus')
            ->dispatch(
                new CreateCampaign(new CampaignId(self::CAMPAIGN_ID), $campaign->toArray())
            );

        $campaign = new Campaign();
        $campaign->setActive(false);
        $campaign->setCostInPoints(5);
        $campaign->setLimit(10);
        $campaign->setUnlimited(false);
        $campaign->setLimitPerUser(2);
        $campaign->setLevels([new LevelId(LoadLevelData::LEVEL2_ID), new LevelId(LoadLevelData::LEVEL_ID)]);
        $campaign->setSegments([new SegmentId(LoadSegmentData::SEGMENT2_ID)]);
        $campaign->setCoupons([new Coupon('123'), new Coupon('1233'), new Coupon('1234')]);
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

        $campaign = new Campaign();
        $campaign->setReward(Campaign::REWARD_TYPE_PERCENTAGE_DISCOUNT_CODE);
        $campaign->setName('Percentage discount code');
        $campaign->setActive(true);
        $campaign->setLabels([new Label('type', 'cashback')]);
        $campaign->setLevels([new LevelId(LoadLevelData::LEVEL2_ID)]);
        $campaign->setSegments([new SegmentId(LoadSegmentData::SEGMENT2_ID)]);
        $campaignActivity = new CampaignActivity();
        $campaignActivity->setAllTimeActive(true);
        $campaign->setCampaignActivity($campaignActivity);
        $campaignVisibility = new CampaignVisibility();
        $campaignVisibility->setAllTimeVisible(true);
        $campaign->setCampaignVisibility($campaignVisibility);
        $campaign->setDaysInactive(28);
        $campaign->setDaysValid(100);
        $campaign->setTransactionPercentageValue(10);
        $campaign->setCategories([
            new CampaignCategoryId(self::CAMPAIGN_CATEGORY2_ID),
        ]);

        $this->container->get('broadway.command_handling.command_bus')
            ->dispatch(
                new CreateCampaign(new CampaignId(self::PERCENTAGE_COUPON_CAMPAIGN_ID), $campaign->toArray())
            );

        $this->loadRandomActiveCampaigns();
    }

    /**
     * add some extra random data.
     */
    protected function loadRandomActiveCampaigns()
    {
        $commandBus = $this->container->get('broadway.command_handling.command_bus');
        for ($i = 0; $i < 12; ++$i) {
            $randomId = Uuid::uuid();
            $campaign = new Campaign();
            $campaign->setActive($i % 2 == 0);
            $campaign->setCostInPoints(1);
            $campaign->setLimit(rand(10, 50));
            $campaign->setUnlimited(false);
            $campaign->setLimitPerUser(10);
            $campaign->setLevels([new LevelId(LoadLevelData::LEVEL_ID), new LevelId(LoadLevelData::LEVEL2_ID)]);
            $campaign->setCoupons([new Coupon(rand(100, 1000))]);
            $campaign->setReward($i % 2 == 0 ? Campaign::REWARD_TYPE_DISCOUNT_CODE : Campaign::REWARD_TYPE_FREE_DELIVERY_CODE);
            $campaign->setName(sprintf('%s', $i));
            $campaignActivity = new CampaignActivity();
            $campaignActivity->setAllTimeActive(false);
            $campaignActivity->setActiveFrom(new \DateTime('now'));
            $campaignActivity->setActiveTo(new \DateTime(sprintf('+%u days', rand(10, 30))));
            $campaign->setCampaignActivity($campaignActivity);
            $campaignVisibility = new CampaignVisibility();
            $campaignVisibility->setAllTimeVisible(true);
            $campaign->setCampaignVisibility($campaignVisibility);

            $commandBus
                ->dispatch(
                    new CreateCampaign(new CampaignId($randomId), $campaign->toArray())
                );
        }
    }
}
