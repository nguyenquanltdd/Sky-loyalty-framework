<?php

namespace OpenLoyalty\Bundle\SettingsBundle\Tests\Unit\Form\EventListener;

use OpenLoyalty\Bundle\SettingsBundle\Entity\StringSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Form\EventListener\DowngradeModeSubscriber;
use OpenLoyalty\Bundle\SettingsBundle\Model\Settings;
use OpenLoyalty\Component\Customer\Infrastructure\LevelDowngradeModeProvider;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

/**
 * Class DowngradeModeSubscriberTest.
 */
class DowngradeModeSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function it_not_validates_when_tier_assign_type_is_transaction()
    {
        $form = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $settings = new Settings();
        $settings->addEntry(new StringSettingEntry('tierAssignType', TierAssignTypeProvider::TYPE_TRANSACTIONS));
        $form->expects($this->never())->method('get');

        $event = new FormEvent($form, $settings);

        $listener = new DowngradeModeSubscriber();
        $listener->submit($event);
    }

    /**
     * @test
     */
    public function it_not_validates_when_level_downgrade_mode_is_auto()
    {
        $form = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $settings = new Settings();
        $settings->addEntry(new StringSettingEntry('tierAssignType', TierAssignTypeProvider::TYPE_POINTS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeMode', LevelDowngradeModeProvider::MODE_AUTO));
        $form->expects($this->never())->method('get');

        $event = new FormEvent($form, $settings);

        $listener = new DowngradeModeSubscriber();
        $listener->submit($event);
    }

    /**
     * @test
     */
    public function it_not_validates_when_level_downgrade_mode_is_none()
    {
        $form = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $settings = new Settings();
        $settings->addEntry(new StringSettingEntry('tierAssignType', TierAssignTypeProvider::TYPE_POINTS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeMode', LevelDowngradeModeProvider::MODE_NONE));
        $form->expects($this->never())->method('get');

        $event = new FormEvent($form, $settings);

        $listener = new DowngradeModeSubscriber();
        $listener->submit($event);
    }

    /**
     * @test
     */
    public function it_add_error_when_days_not_provided()
    {
        $form = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $settings = new Settings();
        $settings->addEntry(new StringSettingEntry('tierAssignType', TierAssignTypeProvider::TYPE_POINTS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeMode', LevelDowngradeModeProvider::MODE_X_DAYS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeBase', LevelDowngradeModeProvider::BASE_ACTIVE_POINTS));
        $levelDowngradeDaysForm = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $levelDowngradeDaysForm->expects($this->once())->method('addError');
        $form->expects($this->atLeast(1))->method('get')->with($this->equalTo('levelDowngradeDays'))->willReturn(
            $levelDowngradeDaysForm
        );

        $event = new FormEvent($form, $settings);

        $listener = new DowngradeModeSubscriber();
        $listener->submit($event);
    }

    /**
     * @test
     */
    public function it_add_error_when_days_less_than_one()
    {
        $form = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $settings = new Settings();
        $settings->addEntry(new StringSettingEntry('tierAssignType', TierAssignTypeProvider::TYPE_POINTS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeMode', LevelDowngradeModeProvider::MODE_X_DAYS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeBase', LevelDowngradeModeProvider::BASE_ACTIVE_POINTS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeDays', 0));
        $levelDowngradeDaysForm = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $levelDowngradeDaysForm->expects($this->once())->method('addError');
        $form->expects($this->atLeast(1))->method('get')->with($this->equalTo('levelDowngradeDays'))->willReturn(
            $levelDowngradeDaysForm
        );

        $event = new FormEvent($form, $settings);

        $listener = new DowngradeModeSubscriber();
        $listener->submit($event);
    }

    /**
     * @test
     */
    public function it_add_error_when_base_not_provided()
    {
        $form = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $settings = new Settings();
        $settings->addEntry(new StringSettingEntry('tierAssignType', TierAssignTypeProvider::TYPE_POINTS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeMode', LevelDowngradeModeProvider::MODE_X_DAYS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeDays', 1));
        $levelBaseForm = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $levelBaseForm->expects($this->once())->method('addError');
        $form->expects($this->atLeast(1))->method('get')->with($this->equalTo('levelDowngradeBase'))->willReturn(
            $levelBaseForm
        );

        $event = new FormEvent($form, $settings);

        $listener = new DowngradeModeSubscriber();
        $listener->submit($event);
    }

    /**
     * @test
     */
    public function it_not_add_errors_when_valid()
    {
        $form = $this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock();
        $settings = new Settings();
        $settings->addEntry(new StringSettingEntry('tierAssignType', TierAssignTypeProvider::TYPE_POINTS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeMode', LevelDowngradeModeProvider::MODE_X_DAYS));
        $settings->addEntry(new StringSettingEntry('levelDowngradeDays', 1));
        $settings->addEntry(new StringSettingEntry('levelDowngradeBase', LevelDowngradeModeProvider::BASE_ACTIVE_POINTS));
        $form->expects($this->never())->method('get');

        $event = new FormEvent($form, $settings);

        $listener = new DowngradeModeSubscriber();
        $listener->submit($event);
    }
}
