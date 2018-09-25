<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Infrastructure\EventListener;

use Gaufrette\Filesystem;

/**
 * Class DeleteFileListenerTest.
 */
class DeleteFileListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fileSystem;

    /**
     * @test
     */
    public function it_remove_file_on_invoke_event(): void
    {
        $this->fileSystem->expects($this->once())->method('delete');
        $listener = new DeleteFileListener($this->fileSystem);
        $listener->__invoke('path/to/file.jpg');
    }

    protected function setUp(): void
    {
        /* @var Filesystem|\PHPUnit_Framework_MockObject_MockObject $filesystem */
        $this->fileSystem = $this->createMock(Filesystem::class);
    }
}
