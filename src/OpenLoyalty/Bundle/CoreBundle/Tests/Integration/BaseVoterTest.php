<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CoreBundle\Tests\Integration;

use OpenLoyalty\Bundle\UserBundle\DataFixtures\ORM\LoadUserData;
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
    const USER_ID = '00000000-0000-474c-b092-b0dd880c07e1';

    protected function getAdminToken()
    {
        $admin = $this->getMockBuilder(Admin::class)->disableOriginalConstructor()->getMock();
        $admin->method('hasRole')->with($this->isType('string'))->will($this->returnCallback(function ($role) {
            return $role == 'ROLE_ADMIN';
        }));
        $admin->method('getId')->willReturn(LoadUserData::ADMIN_ID);

        return new UsernamePasswordToken($admin, '', 'some_empty_string');
    }

    protected function getCustomerToken()
    {
        $customer = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();
        $customer->method('hasRole')->with($this->isType('string'))->will($this->returnCallback(function ($role) {
            return $role == 'ROLE_PARTICIPANT';
        }));
        $customer->method('getId')->willReturn(self::USER_ID);

        return new UsernamePasswordToken($customer, '', 'some_empty_string');
    }

    /**
     * @param bool $isAllowedPointTransfer
     *
     * @return UsernamePasswordToken
     */
    protected function getSellerToken(bool $isAllowedPointTransfer = false): UsernamePasswordToken
    {
        $seller = $this->getMockBuilder(Seller::class)->disableOriginalConstructor()->getMock();
        $seller->method('hasRole')->with($this->isType('string'))->will($this->returnCallback(function ($role) {
            return $role == 'ROLE_SELLER';
        }));

        $seller->method('getId')->willReturn(self::USER_ID);
        $seller->method('isAllowPointTransfer')->willReturn($isAllowedPointTransfer);

        return new UsernamePasswordToken($seller, '', 'some_empty_string');
    }

    /**
     * @param $attributes
     * @param $voter
     */
    protected function makeAssertions($attributes, Voter $voter)
    {
        foreach ($attributes as $attr => $params) {
            $subject = isset($params['id']) ? $this->getSubjectById($params['id']) : null;
            $this->assertEquals(
                $params['customer'] ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
                $voter->vote($this->getCustomerToken(), $subject, [$attr]),
                $attr.' - customer'
            );
            $this->assertEquals(
                $params['admin'] ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
                $voter->vote($this->getAdminToken(), $subject, [$attr]),
                $attr.' - admin'
            );
            $this->assertEquals(
                $params['seller'] ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
                $voter->vote($this->getSellerToken(), $subject, [$attr]),
                $attr.' - seller'
            );
        }
    }

    abstract protected function getSubjectById($id);
}
