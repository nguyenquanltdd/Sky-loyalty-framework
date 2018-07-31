<?php

namespace OpenLoyalty\Component\Seller\Tests\Domain\ReadModel;

use Broadway\ReadModel\InMemory\InMemoryRepository;
use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Testing\ProjectorScenarioTestCase;
use OpenLoyalty\Bundle\UserBundle\Service\AccountDetailsProvider;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignBoughtProjector;
use OpenLoyalty\Component\Customer\Domain\Customer;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignUsageWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;

class CampaignBoughtProjectorTest extends ProjectorScenarioTestCase
{
    const CUSTOMER_ID = '00000000-0000-0000-0000-000000000000';

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
            new Campaign(new CampaignId('11111111-0000-0000-0000-000000000000'), ['reward' => 'Reward', 'name' => 'campaignName'])
        );
        $customerId = new \OpenLoyalty\Component\Customer\Domain\CustomerId('00000000-0000-0000-0000-000000000000');
        $this->customer = Customer::registerCustomer($customerId, $this->getCustomerData());
        $accountDetailsRepository = $this->getMockBuilder(AccountDetailsProvider::class)->disableOriginalConstructor()->getMock();
        $accountDetailsRepository->method('getCustomerById')->willReturn($this->customer);

        return new CampaignBoughtProjector($repository, $campaignRepository, $accountDetailsRepository);
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_when_campaign_was_bought_by_customer()
    {
        $customerId = new \OpenLoyalty\Component\Customer\Domain\CustomerId('00000000-0000-0000-0000-000000000000');
        $campaignId = new \OpenLoyalty\Component\Customer\Domain\CampaignId('11111111-0000-0000-0000-000000000000');
        $coupon = new \OpenLoyalty\Component\Customer\Domain\Model\Coupon('testCoupon');

        $expectedData = [
            'customerId' => $customerId->__toString(),
            'campaignId' => $campaignId->__toString(),
            'coupon' => $coupon->getCode(),
            'campaignType' => 'Reward',
            'campaignName' => 'campaignName',
            'customerEmail' => 'customerEmail',
            'customerPhone' => 'customerPhone',
            'customerName' => 'Joe',
            'customerLastname' => 'Doe',
            'costInPoints' => 0,
            'currentPointsAmount' => 0,
            'taxPriceValue' => null,
            'used' => null,
            'status' => CampaignPurchase::STATUS_ACTIVE,
            'activeSince' => null,
            'activeTo' => null,
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

        $expectedData = [
            'customerId' => $customerId->__toString(),
            'campaignId' => $campaignId->__toString(),
            'coupon' => $coupon->getCode(),
            'campaignType' => 'Reward',
            'campaignName' => 'campaignName',
            'customerEmail' => 'customerEmail',
            'customerPhone' => 'customerPhone',
            'customerName' => 'Joe',
            'customerLastname' => 'Doe',
            'costInPoints' => 0,
            'currentPointsAmount' => 0,
            'taxPriceValue' => null,
            'used' => true,
            'status' => CampaignPurchase::STATUS_ACTIVE,
            'activeSince' => null,
            'activeTo' => null,
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
     * helper data.
     *
     * @return array
     */
    private function getCustomerData(): array
    {
        return [
            'id' => self::CUSTOMER_ID,
            'firstName' => 'Joe',
            'lastName' => 'Doe',
            'birthDate' => new \DateTime('1999-02-22'),
            'createdAt' => new \DateTime('2018-01-01'),
            'email' => 'customerEmail',
            'phone' => 'customerPhone',
        ];
    }
}
