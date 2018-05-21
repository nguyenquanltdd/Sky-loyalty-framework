<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\CQRS\Handler;

use Broadway\CommandHandling\CommandHandler;
use OpenLoyalty\Bundle\UserBundle\CQRS\Command\CreateAdmin;
use OpenLoyalty\Bundle\UserBundle\CQRS\Command\EditAdmin;
use OpenLoyalty\Bundle\UserBundle\CQRS\Command\SelfEditAdmin;
use OpenLoyalty\Bundle\UserBundle\Entity\Repository\AdminRepository;
use OpenLoyalty\Bundle\UserBundle\Exception\AdminNotFoundException;
use OpenLoyalty\Bundle\UserBundle\Exception\EmailAlreadyExistException;
use OpenLoyalty\Bundle\UserBundle\Service\UserManager;

/**
 * Class AdminHandler.
 */
class AdminHandler implements CommandHandler
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var AdminRepository
     */
    protected $adminRepository;

    /**
     * AdminHandler constructor.
     *
     * @param UserManager     $userManager
     * @param AdminRepository $adminRepository
     */
    public function __construct(
        UserManager $userManager,
        AdminRepository $adminRepository
    ) {
        $this->userManager = $userManager;
        $this->adminRepository = $adminRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($command)
    {
        switch (true) {
            case $command instanceof CreateAdmin:
                return $this->handleCreateAdmin($command);
            case $command instanceof EditAdmin:
                return $this->handleEditAdmin($command);
            case $command instanceof SelfEditAdmin:
                return $this->handleSelfEditAdmin($command);
        }
    }

    /**
     * @param CreateAdmin $command
     */
    protected function handleCreateAdmin(CreateAdmin $command)
    {
        if ($this->adminRepository->isEmailExist($command->getEmail())) {
            throw new EmailAlreadyExistException();
        }

        $admin = $this->userManager->createNewAdmin($command->getAdminId());
        $admin->setApiKey($command->getApiKey());
        $admin->setEmail($command->getEmail());
        $admin->setFirstName($command->getFirstName());
        $admin->setLastName($command->getLastName());
        $admin->setPhone($command->getPhone());
        $admin->setPlainPassword($command->getPlainPassword());
        $admin->setExternal($command->isExternal());
        $admin->setIsActive($command->isActive());
        $this->userManager->updateUser($admin);
    }

    /**
     * @param EditAdmin $command
     *
     * @throws EmailAlreadyExistException
     * @throws AdminNotFoundException
     */
    protected function handleEditAdmin(EditAdmin $command)
    {
        if ($this->adminRepository->isEmailExist($command->getEmail(), $command->getAdminId()->__toString())) {
            throw new EmailAlreadyExistException();
        }

        $admin = $this->adminRepository->findById($command->getAdminId()->__toString());

        $admin->setApiKey($command->getApiKey());
        $admin->setEmail($command->getEmail());
        $admin->setFirstName($command->getFirstName());
        $admin->setLastName($command->getLastName());
        $admin->setPhone($command->getPhone());
        $admin->setPlainPassword($command->getPlainPassword());
        $admin->setExternal($command->isExternal());
        $admin->setIsActive($command->isActive());
        $this->userManager->updateUser($admin);
    }

    /**
     * @param SelfEditAdmin $command
     *
     * @throws EmailAlreadyExistException
     * @throws AdminNotFoundException
     */
    protected function handleSelfEditAdmin(SelfEditAdmin $command)
    {
        if ($this->adminRepository->isEmailExist($command->getEmail(), $command->getAdminId()->__toString())) {
            throw new EmailAlreadyExistException();
        }

        $admin = $this->adminRepository->findById($command->getAdminId()->__toString());

        $admin->setEmail($command->getEmail());
        $admin->setFirstName($command->getFirstName());
        $admin->setLastName($command->getLastName());
        $admin->setPhone($command->getPhone());
        $this->userManager->updateUser($admin);
    }
}
