<?php

namespace OpenLoyalty\Bundle\ActivationCodeBundle\Test\Service;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use Doctrine\ORM\EntityManager;
use OpenLoyalty\Bundle\ActivationCodeBundle\Service\ActivationCodeManager;
use OpenLoyalty\Bundle\ActivationCodeBundle\Service\SmsSender;
use OpenLoyalty\Bundle\UserBundle\Entity\Customer;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCode;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCodeId;
use OpenLoyalty\Component\ActivationCode\Infrastructure\Persistence\Doctrine\Repository\DoctrineActivationCodeRepository;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ActivationCodeManagerTest.
 */
class ActivationCodeManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var DoctrineActivationCodeRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var UuidGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uuidGenerator;

    /**
     * @var SmsSender|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $smsSender;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->repository = $this->getActivationCodeRepositoryMock();

        $this->em = $this->getEntityManagerMock();
        $this->em
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->repository
            ->method('countByObjectTypeAndObjectId')
            ->willReturn(1);

        $this->smsSender = $this->getSmsSenderMock();
        $this->smsSender
            ->method('send')
            ->willReturn(true);

        $this->uuidGenerator = $this->getUuidGeneratorMock();

        $this->translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $this->translator->method('trans')->willReturn('content');
    }

    /**
     * @return array
     */
    public function objectTypeObjectIdProvider()
    {
        return [
            ['c85bef3a-1549-11e8-b642-0ed5f89f718b', Customer::class, '542ecfc0-1543-11e8-b642-0ed5f89f718b'],
            ['c85bef3a-1549-11e8-b642-0ed5f89f718b', Customer::class, '542ed61e-1543-11e8-b642-0ed5f89f718b'],
        ];
    }

    /**
     * @return array
     *
     * @throws \Assert\AssertionFailedException
     */
    public function activationCodePhoneProvider()
    {
        return [
            [
                new ActivationCodeId('542ecfc0-1543-11e8-b642-0ed5f89f718b'),
                Customer::class,
                '1b62631e-1548-11e8-b642-0ed5f89f718b',
                'ABC123',
                '123456789',
            ],
            [
                new ActivationCodeId('1b6266a2-1548-11e8-b642-0ed5f89f718b'),
                Customer::class,
                '70cba220-1548-11e8-b642-0ed5f89f718b',
                'ABC123',
                '123456789',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider objectTypeObjectIdProvider
     *
     * @param string $objectType
     * @param string $objectId
     *
     * @throws \Assert\AssertionFailedException
     * @throws \Doctrine\DBAL\Exception\UniqueConstraintViolationException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function it_creates_code($id, string $objectType, string $objectId)
    {
        $this->uuidGenerator->method('generate')->willReturn($id);
        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $activationCode = $this->getActivationCodeManager(
            $this->uuidGenerator,
            $this->smsSender,
            $this->em
        )->newCode($objectType, $objectId);

        $this->assertInstanceOf(ActivationCode::class, $activationCode);
        $this->assertEquals($id, $activationCode->getactivationCodeId()->__toString());
        $this->assertEquals($objectId, $activationCode->getObjectId());
        $this->assertEquals($objectId, $activationCode->getObjectId());
        $this->assertNotEmpty($activationCode->getCode());
    }

    /**
     * @test
     * @dataProvider activationCodePhoneProvider
     *
     * @param ActivationCodeId $activationCodeId
     * @param string           $objectType
     * @param string           $objectId
     * @param string           $code
     * @param string           $phone
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function it_sends_code(
        ActivationCodeId $activationCodeId,
        string $objectType,
        string $objectId,
        string $code,
        string $phone
    ) {
        $activationCodeManager = $this->getActivationCodeManager(
            $this->uuidGenerator,
            $this->smsSender,
            $this->em
        );

        $activationCode = $this->getActivationCodeMock(
            $activationCodeId, $objectType, $objectId, $code
        );

        $this->repository
            ->method('countByObjectTypeAndObjectId')
            ->willReturn(1);

        $this->smsSender->expects($this->once())->method('send');

        $activationCodeManager->sendCode($activationCode, $phone);
    }

    /**
     * @param ActivationCodeId $activationCodeId
     * @param string           $objectType
     * @param string           $objectId
     * @param string           $code
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ActivationCode
     */
    protected function getActivationCodeMock(
        ActivationCodeId $activationCodeId,
        string $objectType,
        string $objectId,
        string $code
    ) {
        return $this->getMockBuilder(ActivationCode::class)
            ->setConstructorArgs([$activationCodeId, $objectType, $objectId, $code])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SmsSender
     */
    protected function getSmsSenderMock()
    {
        return $this->getMockBuilder(SmsSender::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UuidGeneratorInterface
     */
    protected function getUuidGeneratorMock()
    {
        return $this->getMockBuilder(UuidGeneratorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param UuidGeneratorInterface $uuidGenerator
     * @param SmsSender              $smsSender
     * @param EntityManager          $em
     *
     * @return ActivationCodeManager
     */
    protected function getActivationCodeManager(UuidGeneratorInterface $uuidGenerator, SmsSender $smsSender, EntityManager $em)
    {
        $manager = new ActivationCodeManager($uuidGenerator, $em, $this->translator, 'OpenLoyalty');
        $manager->setSmsSender($smsSender);

        return $manager;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineActivationCodeRepository
     */
    protected function getActivationCodeRepositoryMock()
    {
        return $this->getMockBuilder(DoctrineActivationCodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEntityManagerMock()
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
