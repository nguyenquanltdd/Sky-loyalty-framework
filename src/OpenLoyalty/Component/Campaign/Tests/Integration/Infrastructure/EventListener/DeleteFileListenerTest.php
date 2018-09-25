<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Tests\Integration\Infrastructure\EventListener;

use OpenLoyalty\Component\Campaign\Infrastructure\EventListener\DeleteFileListener;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Gaufrette\Filesystem;

/**
 * Class DeleteFileListenerTest.
 */
class DeleteFileListenerTest extends KernelTestCase
{
    private const CAMPAIGN_PHOTO_DIR = __DIR__.'/../../../../../../../../app/uploads/tests/campaign_photos/';
    private const IMAGE_FILE_NAME = 'test_delete_listener.png';

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        static::bootKernel();
        $this->fileSystem = self::$kernel->getContainer()->get('campaign_photos_filesystem');
        $this->saveFile();
    }

    /**
     * @test
     */
    public function it_remove_file_from_local_disk(): void
    {
        $file = self::CAMPAIGN_PHOTO_DIR.self::IMAGE_FILE_NAME;
        $this->assertFileExists($file);

        $listener = new DeleteFileListener($this->fileSystem);
        $listener->__invoke('campaign_photos/'.self::IMAGE_FILE_NAME);

        $this->assertFileNotExists($file);
    }

    private function saveFile(): void
    {
        $file = 'campaign_photos/'.self::IMAGE_FILE_NAME;
        $this->fileSystem->write($file, file_get_contents(__DIR__.'/../fixture/'.self::IMAGE_FILE_NAME), true);
    }
}
