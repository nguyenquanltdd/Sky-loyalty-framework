<?php

namespace OpenLoyalty\Bundle\UserBundle\Tests\Integration\Controller;

use OpenLoyalty\Component\Customer\Domain\Customer;
use PHPUnit\Framework\TestCase;

/**
 * Class CustomerTest.
 */
class CustomerTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_throws_exception_on_empty_first_name()
    {
        $customer = new Customer();
        $customer->setFirstName(null);
    }
}
