<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\ActivationCodeBundle\Service;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use OpenLoyalty\Bundle\SmsApiBundle\Service\MessageFactoryInterface;
use OpenLoyalty\Bundle\SmsApiBundle\SmsApi\OloySmsApiInterface;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCode;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCodeId;

/**
 * Class ActivationCodeManager.
 */
class ActivationCodeManager
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var UuidGeneratorInterface
     */
    protected $uuidGenerator;

    /**
     * @var OloySmsApiInterface
     */
    protected $smsApi;

    /**
     * @var MessageFactoryInterface
     */
    protected $messageFactory;

    /**
     * @var int
     */
    protected $codeLength = 8;

    /**
     * ActivationCodeManager constructor.
     *
     * @param UuidGeneratorInterface  $uuidGenerator
     * @param OloySmsApiInterface     $smsApi
     * @param MessageFactoryInterface $messageFactory
     * @param EntityManager           $em
     */
    public function __construct(UuidGeneratorInterface $uuidGenerator, OloySmsApiInterface $smsApi, MessageFactoryInterface $messageFactory, EntityManager $em)
    {
        $this->em = $em;
        $this->uuidGenerator = $uuidGenerator;
        $this->smsApi = $smsApi;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @return int
     */
    public function getCodeLength(): int
    {
        return $this->codeLength;
    }

    /**
     * @param int $codeLength
     */
    public function setCodeLength(int $codeLength)
    {
        $this->codeLength = $codeLength;
    }

    /**
     * @param string $code
     * @param string $objectType
     *
     * @return null|object|ActivationCode
     */
    public function findCode(string $code, string $objectType)
    {
        return $this->em->getRepository(ActivationCode::class)->findOneBy([
            'code' => $code,
            'objectType' => $objectType,
        ]);
    }

    /**
     * @param string $code
     * @param string $objectType
     *
     * @return null|object|ActivationCode|string
     */
    public function findValidCode(string $code, string $objectType)
    {
        $entity = $this->findCode($code, $objectType);
        if (null !== $entity) {
            $lastCode = $this->em->getRepository(ActivationCode::class)->getLastByObjectTypeAndObjectId(
                $objectType,
                $entity->getObjectId()
            );

            if (0 === strcasecmp($lastCode->getObjectId(), $entity->getObjectId())) {
                return $code;
            }
        }
    }

    /**
     * @param string $objectType
     * @param string $objectId
     *
     * @return ActivationCode
     *
     * @throws UniqueConstraintViolationException
     * @throws \Assert\AssertionFailedException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function newCode(string $objectType, string $objectId)
    {
        $entity = new ActivationCode(
            new ActivationCodeId($this->uuidGenerator->generate()),
            $objectType,
            $objectId,
            $this->generateUniqueCode($objectType, $objectId)
        );

        $this->persistUniqueActivationCode($entity);

        return $entity;
    }

    /**
     * @param ActivationCode $code
     * @param string         $phone
     * @param string         $senderName
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function sendCode(ActivationCode $code, string $phone, string $senderName = 'Info')
    {
        $msg = $this->messageFactory->create();

        $codeNo = $this->em->getRepository(ActivationCode::class)->countByObjectTypeAndObjectId(
            $code->getObjectType(),
            $code->getObjectId()
        );

        $content = sprintf('OpenLoyalty activation code (no. %d): %s', $codeNo, $code->getCode());

        $msg->setContent($content);
        $msg->setRecipient($phone);
        $msg->setSenderName($senderName);

        return $this->smsApi->send($msg);
    }

    /**
     * @param ActivationCode $entity
     * @param int            $tryLimit
     *
     * @throws UniqueConstraintViolationException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function persistUniqueActivationCode(ActivationCode $entity, $tryLimit = 7)
    {
        try {
            $this->em->persist($entity);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            if ($tryLimit-- == 0) {
                throw $e;
            }
            $entity->setCode($this->generateUniqueCode($entity->getObjectType(), $entity->getObjectId()));
            $this->persistUniqueActivationCode($entity, $tryLimit);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                if ($tryLimit-- == 0) {
                    throw $e;
                }

                $entity->setCode($this->generateUniqueCode($entity->getObjectType(), $entity->getObjectId()));
                $this->persistUniqueActivationCode($entity, $tryLimit);
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param string $objectType
     * @param string $objectId
     *
     * @return string
     */
    protected function generateUniqueCode(string $objectType, string $objectId)
    {
        while (true) {
            $code = $this->generateCode($objectType, $objectId);
            $entity = $this->em->getRepository(ActivationCode::class)->findOneBy([
                'code' => $code,
            ]);

            if (null === $entity) {
                return $code;
            }
        }
    }

    /**
     * @param string $objectType
     * @param string $objectId
     *
     * @return string
     */
    protected function generateCode(string $objectType, string $objectId)
    {
        $hash = hash('sha512', implode('', [
            uniqid(mt_rand(), true),
            microtime(true),
            bin2hex(openssl_random_pseudo_bytes(100)),
            $objectType,
            $objectId,
        ]));

        $length = $this->getCodeLength();

        return strtoupper(substr($hash,  mt_rand(0, strlen($hash) - $length - 1), $length));
    }
}
