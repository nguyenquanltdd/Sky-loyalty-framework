<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\ActivationCode\Domain\Command;

use Broadway\CommandHandling\CommandHandler;
use Broadway\EventDispatcher\EventDispatcherInterface;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCode;
use OpenLoyalty\Component\ActivationCode\Domain\ActivationCodeRepositoryInterface;
use OpenLoyalty\Component\ActivationCode\Domain\SystemEvent\ActivationCodeCreatedSystemEvent;
use OpenLoyalty\Component\ActivationCode\Domain\SystemEvent\ActivationCodeSystemEvents;

/**
 * Class ActivationCodeCommandHandler.
 */
class ActivationCodeCommandHandler extends CommandHandler
{
    /**
     * Email settings repository.
     *
     * @var ActivationCodeRepositoryInterface
     */
    private $repository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $params;

    /**
     * EmailCommandHandler constructor.
     *
     * @param ActivationCodeRepositoryInterface $repository
     * @param EventDispatcherInterface          $eventDispatcher
     * @param array                             $params
     */
    public function __construct(ActivationCodeRepositoryInterface $repository, EventDispatcherInterface $eventDispatcher, array $params)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
        $this->params = $params;
    }

    /**
     * @param CreateActivationCode $command
     *
     * @throws \Assert\AssertionFailedException
     */
    public function handleCreateActivationCode(CreateActivationCode $command)
    {
        $data = $command->getActivationCodeData();
        $domain = ActivationCode::create($command->getActivationCodeId(), $data);
        $this->repository->save($domain);

        $this->eventDispatcher->dispatch(
            ActivationCodeSystemEvents::ACTIVATION_CODE_CREATED,
            [new ActivationCodeCreatedSystemEvent($command->getActivationCodeId(), $data)]
        );
    }

    /**
     * @return string
     */
    protected function getCode(): string
    {
        return $this->params['code'];
    }

    /**
     * @return string
     */
    protected function getObjectType(): string
    {
        return $this->params['object_type'];
    }

    /**
     * @return string
     */
    protected function getObjectId(): string
    {
        return $this->params['object_id'];
    }

    /**
     * Get data.
     *
     * @param      $data
     * @param      $key
     * @param null $default
     *
     * @return null|mixed
     */
    protected function getData($data, $key, $default = null)
    {
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }
}
