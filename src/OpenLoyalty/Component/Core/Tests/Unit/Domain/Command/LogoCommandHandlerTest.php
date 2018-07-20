<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Core\Tests\Unit\Domain\Command;

use Broadway\CommandHandling\SimpleCommandHandler;
use Broadway\EventDispatcher\EventDispatcher;
use OpenLoyalty\Bundle\SettingsBundle\Service\ImageResizer;
use OpenLoyalty\Component\Core\Domain\Command\LogoCommandHandler;
use OpenLoyalty\Component\Core\Domain\Command\ResizeLogo;
use OpenLoyalty\Component\Core\Domain\SystemEvent\LogoSystemEvents;
use OpenLoyalty\Component\Core\Infrastructure\FileInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class LogoCommandHandlerTest.
 */
class LogoCommandHandlerTest extends TestCase
{
    /**
     * @var LogoCommandHandler
     */
    private $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resizer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $systemEvents;

    /**
     * @test
     */
    public function setUp()
    {
        parent::setUp();

        $this->dispatcher = $this->getMockBuilder(EventDispatcher::class)->disableOriginalConstructor()->getMock();
        $this->resizer = $this->getMockBuilder(ImageResizer::class)->disableOriginalConstructor()->getMock();
        $this->handler = new LogoCommandHandler($this->dispatcher, new LogoSystemEvents(), $this->resizer);
    }

    /**
     * @test
     */
    public function it_has_right_interface_implemented()
    {
        $this->assertInstanceOf(SimpleCommandHandler::class, $this->handler);
    }

    /**
     * @test
     */
    public function it_returns_sizes_on_logo_resize_command()
    {
        $this->resizer->expects($this->once())->method('resize');
        $this->dispatcher->expects($this->once())->method('dispatch');
        $logoMock = $this->getMockForAbstractClass(FileInterface::class);
        $resizeCommand = new ResizeLogo($logoMock, 'small-logo');
        $this->handler->handleResizeLogo($resizeCommand);
    }
}
