<?php
/*
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CoreBundle\Tests\Integration;

use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadAdminData;
use OpenLoyalty\Bundle\UserBundle\Entity\Admin;
use OpenLoyalty\Bundle\UserBundle\Entity\Customer;
use OpenLoyalty\Bundle\UserBundle\Entity\Seller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Class BaseVoterTest.
 */
abstract class BaseVoterTest extends \PHPUnit_Framework_TestCase
{
    protected const USER_ID = '00000000-0000-474c-b092-b0dd880c07e1';

    /**
     * @return UsernamePasswordToken
     */
    protected function getAdminToken(): UsernamePasswordToken
    {
        $adminMock = $this->createMock(Admin::class);
        $adminMock
            ->method('hasRole')
            ->with($this->isType('string'))
            ->will($this->returnCallback(
                function (string $role): bool {
                    return $role === 'ROLE_ADMIN';
                }
            ))
        ;
        $adminMock
            ->method('getId')
            ->willReturn(LoadAdminData::ADMIN_ID)
        ;

        return new UsernamePasswordToken($adminMock, '', 'some_empty_string');
    }

    /**
     * @return UsernamePasswordToken
     */
    protected function getCustomerToken(): UsernamePasswordToken
    {
        $customerMock = $this->createMock(Customer::class);
        $customerMock
            ->method('hasRole')
            ->with($this->isType('string'))
            ->will($this->returnCallback(
                function (string $role): bool {
                    return $role === 'ROLE_PARTICIPANT';
                }
            ))
        ;
        $customerMock
            ->method('getId')
            ->willReturn(self::USER_ID)
        ;

        return new UsernamePasswordToken($customerMock, '', 'some_empty_string');
    }

    /**
     * @param bool $isAllowedPointTransfer
     *
     * @return UsernamePasswordToken
     */
    protected function getSellerToken(bool $isAllowedPointTransfer = false): UsernamePasswordToken
    {
        $sellerMock = $this->createMock(Seller::class);
        $sellerMock
            ->method('hasRole')
            ->with($this->isType('string'))
            ->will($this->returnCallback(
                function (string $role): string {
                    return $role === 'ROLE_SELLER';
                }
            ))
        ;
        $sellerMock
            ->method('getId')
            ->willReturn(self::USER_ID)
        ;
        $sellerMock
            ->method('isAllowPointTransfer')
            ->willReturn($isAllowedPointTransfer)
        ;

        return new UsernamePasswordToken($sellerMock, '', 'some_empty_string');
    }

    /**
     * @param Voter $voter
     * @param array $attributes
     */
    protected function assertVoterAttributes(Voter $voter, array $attributes): void
    {
        foreach ($attributes as $attribute => $params) {
            $subject = isset($params['id']) ? $this->getSubjectById($params['id']) : null;

            // override with custom subject
            if (null === $subject && array_key_exists('subject', $params)) {
                $subject = $params['subject'];
            }
            $this->assertEquals(
                $params['customer'] ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
                $voter->vote($this->getCustomerToken(), $subject, [$attribute]),
                $attribute.' - customer'
            );
            $this->assertEquals(
                $params['admin'] ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
                $voter->vote($this->getAdminToken(), $subject, [$attribute]),
                $attribute.' - admin'
            );
            $this->assertEquals(
                $params['seller'] ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
                $voter->vote($this->getSellerToken(), $subject, [$attribute]),
                $attribute.' - seller'
            );
        }
    }

    abstract protected function getSubjectById($id);
}
