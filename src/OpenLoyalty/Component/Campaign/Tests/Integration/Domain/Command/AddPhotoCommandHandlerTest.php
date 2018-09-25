<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Tests\Integration\Domain\Command;

use Doctrine\ORM\EntityManagerInterface;
use OpenLoyalty\Component\Campaign\Domain\Command\AddPhotoCommand;
use OpenLoyalty\Component\Campaign\Domain\Command\AddPhotoCommandHandler;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\Entity\CampaignPhoto;
use OpenLoyalty\Component\Campaign\Domain\Factory\PhotoEntityFactory;
use OpenLoyalty\Component\Campaign\Domain\Repository\CampaignPhotoRepositoryInterface;
use OpenLoyalty\Component\Campaign\Infrastructure\Doctrine\ORM\Repository\CampaignPhotoRepository;
use OpenLoyalty\Component\Campaign\Infrastructure\Doctrine\ORM\Repository\CampaignRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class AddPhotoCommandHandlerIntegrationTest.
 */
class AddPhotoCommandHandlerTest extends KernelTestCase
{
    private const CAMPAIGN_ID = '000096cf-32a3-43bd-9034-4df343e5fd93';

    private const CAMPAIGN_PHOTO_DIR = __DIR__.'/../../../../../../../../app/uploads/tests/campaign_photos/';

    /**
     * @var CampaignPhotoRepositoryInterface
     */
    private $photoRepository;

    /**
     * @var AddPhotoCommandHandler
     */
    private $handler;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        static::bootKernel();
        $this->clearPhotoDir();
        $this->entityManager = self::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');
        $uuidGenerator = self::$kernel->getContainer()->get('broadway.uuid.generator');
        $eventDispatcher = self::$kernel->getContainer()->get('broadway.event_dispatcher');

        $campaignRepository = new CampaignRepository($this->entityManager);
        $this->photoRepository = new CampaignPhotoRepository($this->entityManager);
        $photoEntityFactory = new PhotoEntityFactory();

        $this->handler = new AddPhotoCommandHandler(
            $campaignRepository,
            $eventDispatcher,
            $this->photoRepository,
            $photoEntityFactory,
            $uuidGenerator
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPhotoDir();
    }

    /**
     * @test
     */
    public function it_save_file_data_to_database(): void
    {
        $uploadedFile = new UploadedFile(
            __DIR__.'/../fixture/add_photo_handler_sample.png',
            'add_photo_handler_sample.png',
            'image/png'
        );
        $command = AddPhotoCommand::withData($uploadedFile, new CampaignId(self::CAMPAIGN_ID));
        $this->handler->handleAddPhotoCommand($command);

        $actual = $this->entityManager
            ->getRepository(CampaignPhoto::class)
            ->findOneBy(['campaign' => new CampaignId(self::CAMPAIGN_ID)]);

        $this->assertNotNull($actual);
        $this->assertSame(1, $this->countFilesInDir());
    }

    /**
     * @return int
     */
    private function countFilesInDir(): int
    {
        return count(
            array_diff(
                scandir(self::CAMPAIGN_PHOTO_DIR),
                ['..', '.']
            )
        );
    }

    private function clearPhotoDir(): void
    {
        $files = glob(self::CAMPAIGN_PHOTO_DIR.'*');
        array_map(
            function (string $file): void {
                if (is_file($file)) {
                    unlink($file);
                }
            },
            $files
        );
    }
}
