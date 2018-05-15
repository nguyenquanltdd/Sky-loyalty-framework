<?php

namespace OpenLoyalty\Component\Seller\Tests\Domain\ReadModel;

use Broadway\ReadModel\InMemory\InMemoryRepository;
use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Testing\ProjectorScenarioTestCase;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignBoughtProjector;
use OpenLoyalty\Component\Customer\Domain\Customer;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignUsageWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;

class CampaignBoughtProjectorTest extends ProjectorScenarioTestCase
{
    private $repository;

    private $customer;

    /**
     * {@inheritdoc}
     */
    protected function createProjector(InMemoryRepository $repository): Projector
    {
        $this->repository = $repository;
        $campaignRepository = $this->getMockBuilder(CampaignRepository::class)->getMock();
        $campaignRepository->method('byId')->willReturn(
            new Campaign(new CampaignId('11111111-0000-0000-0000-000000000000'), ['reward' => 'Reward'])
        );
        $this->customer = $this->getMockBuilder(Customer::class)->getMock();
        $customerRepository = $this->getMockBuilder(CustomerDetailsRepository::class)->getMock();
        $customerRepository->method('find')->willReturn($this->customer);

        return new CampaignBoughtProjector($repository, $campaignRepository, $customerRepository);
    }

    /**
     * @test
     *
     * @dataProvider getDataProvider
     *
     * @param string|null $customerEmail
     * @param string|null $customerPhone
     */
    public function it_creates_a_read_model_when_campaign_was_bought_by_customer($customerEmail, $customerPhone)
    {
        $customerId = new \OpenLoyalty\Component\Customer\Domain\CustomerId('00000000-0000-0000-0000-000000000000');
        $campaignId = new \OpenLoyalty\Component\Customer\Domain\CampaignId('11111111-0000-0000-0000-000000000000');
        $coupon = new \OpenLoyalty\Component\Customer\Domain\Model\Coupon('testCoupon');

        $this->customer->method('getEmail')->willReturn($customerEmail);
        $this->customer->method('getPhone')->willReturn($customerPhone);

        $expectedData = [
            'customerId' => $customerId->__toString(),
            'campaignId' => $campaignId->__toString(),
            'coupon' => $coupon->getCode(),
            'campaignType' => 'Reward',
            'campaignName' => 'campaignName',
            'customerEmail' => $customerEmail,
            'customerPhone' => $customerPhone,
            'used' => false,
        ];
        $this->scenario->given(array())
            ->when(
                new CampaignWasBoughtByCustomer(
                    $customerId,
                    $campaignId,
                    'campaignName',
                    '1',
                    $coupon,
                    Campaign::REWARD_TYPE_DISCOUNT_CODE
                )
            );

        $result = $this->repository->findAll();
        $result = array_pop($result)->serialize();
        unset($result['purchasedAt']);

        $this->assertEquals($expectedData, $result);
    }

    /**
     * @test
     */
    public function it_update_a_read_model_when_campaign_usage_was_changed()
    {
        $customerId = new \OpenLoyalty\Component\Customer\Domain\CustomerId('00000000-0000-0000-0000-000000000000');
        $campaignId = new \OpenLoyalty\Component\Customer\Domain\CampaignId('11111111-0000-0000-0000-000000000000');
        $coupon = new \OpenLoyalty\Component\Customer\Domain\Model\Coupon('testCoupon');

        $this->customer->method('getEmail')->willReturn('customerEmail');
        $this->customer->method('getPhone')->willReturn('customerPhone');

        $expectedData = [
            'customerId' => $customerId->__toString(),
            'campaignId' => $campaignId->__toString(),
            'coupon' => $coupon->getCode(),
            'campaignType' => 'Reward',
            'campaignName' => 'campaignName',
            'customerEmail' => 'customerEmail',
            'customerPhone' => 'customerPhone',
            'used' => true,
        ];
        $this->scenario->given(
                array(
                    new CampaignWasBoughtByCustomer(
                        $customerId,
                        $campaignId,
                        'campaignName',
                        '1',
                        $coupon,
                        Campaign::REWARD_TYPE_DISCOUNT_CODE
                    ),
                )
            )
            ->when(new CampaignUsageWasChanged($customerId, $campaignId, $coupon, true));

        $result = $this->repository->findAll();
        $result = array_pop($result)->serialize();
        unset($result['purchasedAt']);

        $this->assertEquals($expectedData, $result);
    }

    /**
     * Data provider for a customer.
     *
     * @return array
     */
    public function getDataProvider()
    {
        return [
            ['customerEmail', 'customerPhone'],
            [null, 'customerPhone'],
            ['customerEmail', null],
            ['customerEmail', ''],
            ['', 'customerPhone'],
        ];
    }
}
