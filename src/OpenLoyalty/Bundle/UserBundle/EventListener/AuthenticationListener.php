<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\EventListener;

use Broadway\EventDispatcher\EventDispatcher;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use OpenLoyalty\Bundle\UserBundle\Entity\Customer;
use OpenLoyalty\Bundle\UserBundle\Entity\User;
use OpenLoyalty\Bundle\UserBundle\Exception\SellerIsNotActiveException;
use OpenLoyalty\Bundle\UserBundle\Service\UserManager;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerLoggedInSystemEvent;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerSystemEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AuthenticationListener.
 */
class AuthenticationListener
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * AuthenticationListener constructor.
     *
     * @param UserManager         $userManager
     * @param EventDispatcher     $dispatcher
     * @param TranslatorInterface $translator
     */
    public function __construct(UserManager $userManager, EventDispatcher $dispatcher, TranslatorInterface $translator)
    {
        $this->userManager = $userManager;
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
    }

    /**
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $user = $event->getUser();

        $payload = $event->getData();
        $roles = $user->getRoles();
        $roleNames = array_map(function (RoleInterface $role) {
            return $role->getRole();
        }, $roles);
        $payload['roles'] = $roleNames;
        if ($user instanceof User) {
            $payload['id'] = $user->getId();
        }
        $payload['lastLoginAt'] = $user->getLastLoginAt() ? $user->getLastLoginAt()->format(\DateTime::ISO8601) : null;

        $event->setData($payload);
    }

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $user = $event->getUser();
        $data = $event->getData();

        if ($user instanceof User) {
            $user->setLastLoginAt(new \DateTime());
            $this->userManager->updateUser($user);
            $this->dispatcher->dispatch(
                CustomerSystemEvents::CUSTOMER_LOGGED_IN,
                [new CustomerLoggedInSystemEvent(new CustomerId($user->getId()))]
            );
        }

        if ($user instanceof Customer && $user->getTemporaryPasswordSetAt()) {
            $data['error'] = 'password change needed';
        }

        $event->setData($data);
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event)
    {
        $exception = $event->getException();
        $previous = $exception->getPrevious();

        if ($exception instanceof BadCredentialsException) {
            $event->setResponse(
                new JsonResponse(
                    [
                        'message' => $this->translator->trans($exception->getMessage()),
                    ],
                    Response::HTTP_UNAUTHORIZED
                )
            );
        }

        if ($previous instanceof SellerIsNotActiveException) {
            $event->setResponse(new JsonResponse([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST));
        }
    }
}
