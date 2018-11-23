<?php

namespace OpenLoyalty\Bundle\ActivationCodeBundle\Tests\Unit\Service;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use Doctrine\ORM\EntityManager;
use OpenLoyalty\Bundle\ActivationCodeBundle\Generator\CodeGenerator;
use OpenLoyalty\Bundle\ActivationCodeBundle\Service\ActivationCodeManager;
use OpenLoyalty\Bundle\ActivationCodeBundle\Service\SmsSender;
use OpenLoyalty\Bundle\UserBundle\Entity\Customer;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCode;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCodeId;
use OpenLoyalty\Component\ActivationCode\Infrastructure\Persistence\Doctrine\Repository\DoctrineActivationCodeRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ActivationCodeManagerTest.
 */
class ActivationCodeManagerTest extends TestCase
{
    /**
     * @var EntityManager|MockObject
     */
    protected $em;

    /**
     * @var DoctrineActivationCodeRepository|MockObject
     */
    protected $repository;

    /**
     * @var UuidGeneratorInterface|MockObject
     */
    protected $uuidGenerator;

    /**
     * @var SmsSender|MockObject
     */
    protected $smsSender;

    /**
     * @var TranslatorInterface|MockObject
     */
    protected $translator;

    /**
     * @var CodeGenerator|MockObject
     */
    protected $codeGenerator;

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

        $this->codeGenerator = $this->getMockBuilder(CodeGenerator::class)->getMock();
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

        $this->codeGenerator->method('generate')->willReturn('1234567');

        $activationCode = $this->getActivationCodeManager(
            $this->uuidGenerator,
            $this->em,
            $this->codeGenerator,
            $this->smsSender
        )->newCode($objectType, $objectId);

        $this->assertInstanceOf(ActivationCode::class, $activationCode);
        $this->assertEquals($id, $activationCode->getactivationCodeId()->__toString());
        $this->assertEquals($objectId, $activationCode->getObjectId());
        $this->assertEquals($objectId, $activationCode->getObjectId());
        $this->assertNotEmpty($activationCode->getCode());
        $this->assertEquals('1234567', $activationCode->getCode());
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
        $this->codeGenerator->method('generate')->willReturn($code);

        $activationCodeManager = $this->getActivationCodeManager(
            $this->uuidGenerator,
            $this->em,
            $this->codeGenerator,
            $this->smsSender
        );

        $activationCode = $this->getActivationCodeMock(
            $activationCodeId,
            $objectType,
            $objectId,
            $code
        );

        $this->repository
            ->method('countByObjectTypeAndObjectId')
            ->willReturn(1);

        $this->smsSender->expects($this->once())->method('send');

        $activationCodeManager->sendCode($activationCode, $phone);
    }

    /**
     * @test
     */
    public function it_throws_exception_if_no_sms_sender()
    {
        $this->codeGenerator->method('generate')->willReturn('1234');

        $activationCodeManager = $this->getActivationCodeManager(
            $this->uuidGenerator,
            $this->em,
            $this->codeGenerator
        );

        $activationCode = $this->getActivationCodeMock(
            new ActivationCodeId('542ecfc0-1543-11e8-b642-0ed5f89f718b'),
            'test',
            'test',
            '1234'
        );

        $this->repository
            ->method('countByObjectTypeAndObjectId')
            ->willReturn(1);

        $this->expectException(\RuntimeException::class);
        $activationCodeManager->sendCode($activationCode, '123456000');
    }

    /**
     * @test
     */
    public function it_returns_false_if_no_sms_sender_set_when_checking_settings()
    {
        $activationCodeManager = $this->getActivationCodeManager(
            $this->uuidGenerator,
            $this->em,
            $this->codeGenerator
        );

        $result = $activationCodeManager->hasNeededSettings();
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_returns_true_if_sms_sender_is_set_while_checking_settings()
    {
        $this->smsSender->expects($this->once())->method('hasNeededSettings')->willReturn(true);

        $activationCodeManager = $this->getActivationCodeManager(
            $this->uuidGenerator,
            $this->em,
            $this->codeGenerator,
            $this->smsSender
        );

        $result = $activationCodeManager->hasNeededSettings();
        $this->assertTrue($result);
    }

    /**
     * @param ActivationCodeId $activationCodeId
     * @param string           $objectType
     * @param string           $objectId
     * @param string           $code
     *
     * @return MockObject|ActivationCode
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
     * @return MockObject|SmsSender
     */
    protected function getSmsSenderMock()
    {
        return $this->getMockBuilder(SmsSender::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|UuidGeneratorInterface
     */
    protected function getUuidGeneratorMock()
    {
        return $this->getMockBuilder(UuidGeneratorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param UuidGeneratorInterface $uuidGenerator
     * @param EntityManager          $em
     * @param CodeGenerator          $codeGenerator
     * @param SmsSender|null         $smsSender
     *
     * @return ActivationCodeManager
     */
    protected function getActivationCodeManager(UuidGeneratorInterface $uuidGenerator, EntityManager $em, CodeGenerator $codeGenerator, SmsSender $smsSender = null)
    {
        $manager = new ActivationCodeManager($uuidGenerator, $em, $this->translator, $codeGenerator, 'OpenLoyalty', 6);

        if (null !== $smsSender) {
            $manager->setSmsSender($smsSender);
        }

        return $manager;
    }

    /**
     * @return MockObject|DoctrineActivationCodeRepository
     */
    protected function getActivationCodeRepositoryMock()
    {
        return $this->getMockBuilder(DoctrineActivationCodeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject|EntityManager
     */
    protected function getEntityManagerMock()
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
