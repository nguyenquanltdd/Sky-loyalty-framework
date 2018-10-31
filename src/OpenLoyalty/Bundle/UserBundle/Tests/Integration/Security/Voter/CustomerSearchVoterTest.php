<?php
/*
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Tests\Integration\Security\Voter;

use OpenLoyalty\Bundle\CoreBundle\Tests\Integration\BaseVoterTest;
use OpenLoyalty\Bundle\UserBundle\Security\Voter\CustomerSearchVoter;

/**
 * Class CustomerSearchVoterTest.
 */
class CustomerSearchVoterTest extends BaseVoterTest
{
    /**
     * @test
     */
    public function it_works(): void
    {
        $attributes = [
            CustomerSearchVoter::SEARCH_CUSTOMER => ['seller' => true, 'customer' => false, 'admin' => true],
        ];

        $voter = new CustomerSearchVoter();

        $this->assertVoterAttributes($voter, $attributes);
    }

    protected function getSubjectById($id)
    {
        return;
    }
}
