<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Campaign\Tests\Unit\Domain\ReadModel;

use Broadway\ReadModel\InMemory\InMemoryRepository;
use Broadway\ReadModel\Projector;
use Broadway\ReadModel\Testing\ProjectorScenarioTestCase;
use OpenLoyalty\Bundle\UserBundle\Service\AccountDetailsProvider;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignBought;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignBoughtProjector;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignBoughtRepository;
use OpenLoyalty\Component\Customer\Domain\CampaignId as CustomerCampaignId;
use OpenLoyalty\Component\Customer\Domain\Customer;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignUsageWasChanged;
use OpenLoyalty\Component\Customer\Domain\Event\CampaignWasBoughtByCustomer;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\TransactionId;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CampaignBoughtProjectorTest.
 */
class CampaignBoughtProjectorTest extends ProjectorScenarioTestCase
{
    const CUSTOMER_ID = '00000000-0000-0000-0000-000000000000';

    /**
     * @var InMemoryRepository
     */
    private $repository;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * {@inheritdoc}
     */
    protected function createProjector(InMemoryRepository $repository): Projector
    {
        $this->repository = $repository;

        /** @var CampaignBoughtRepository|MockObject $campaignBoughtRepository */
        $campaignBoughtRepository = $this->getMockBuilder(CampaignBoughtRepository::class)->getMock();
        $campaignBoughtRepository->method('findByCustomerIdAndUsed')->will(
            $this->returnCallback(function (string $customerId, bool $used) use ($repository) {
                $campaigns = $repository->findAll();

                return array_filter($campaigns, function ($campaign) use ($customerId, $used) {
                    /* @var CampaignBought $campaign */
                    return (string) $campaign->getCustomerId() === $customerId && $used !== !$campaign->isUsed();
                });
            })
        );

        /** @var CampaignRepository|MockObject $campaignRepository */
        $campaignRepository = $this->getMockBuilder(CampaignRepository::class)->getMock();
        $campaignRepository->method('byId')->willReturn(
            new Campaign(
                new CampaignId('11111111-0000-0000-0000-000000000000'),
                ['reward' => 'Reward', 'translations' => ['en' => ['name' => 'campaignName']]]
            )
        );
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');
        $this->customer = Customer::registerCustomer($customerId, $this->getCustomerData());

        /** @var AccountDetailsProvider|MockObject $accountDetailsRepository */
        $accountDetailsRepository = $this->getMockBuilder(AccountDetailsProvider::class)->disableOriginalConstructor()->getMock();
        $accountDetailsRepository->method('getCustomerById')->willReturn($this->customer);

        return new CampaignBoughtProjector($repository, $campaignBoughtRepository, $campaignRepository, $accountDetailsRepository);
    }

    /**
     * @test
     */
    public function it_creates_a_read_model_when_campaign_was_bought_by_customer(): void
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');
        $campaignId = new CustomerCampaignId('11111111-0000-0000-0000-000000000000');
        $coupon = new Coupon('testCoupon');

        $expectedData = [
            'customerId' => (string) $customerId,
            'campaignId' => (string) $campaignId,
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
            'transactionId' => null,
            'usedForTransactionId' => null,
            'returnedAmount' => 0,
        ];
        $this->scenario->given([])
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
    public function it_update_a_read_model_when_campaign_usage_was_changed(): void
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');
        $campaignId = new CustomerCampaignId('11111111-0000-0000-0000-000000000000');
        $coupon = new Coupon('testCoupon');

        $expectedData = [
            'customerId' => (string) $customerId,
            'campaignId' => (string) $campaignId,
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
            'transactionId' => null,
            'usedForTransactionId' => null,
            'returnedAmount' => 0,
        ];
        $this->scenario->given(
                [
                    new CampaignWasBoughtByCustomer(
                        $customerId,
                        $campaignId,
                        'campaignName',
                        '1',
                        $coupon,
                        Campaign::REWARD_TYPE_DISCOUNT_CODE
                    ),
                ]
            )
            ->when(new CampaignUsageWasChanged($customerId, $campaignId, $coupon, true));

        $result = $this->repository->findAll();
        $result = array_pop($result)->serialize();
        unset($result['purchasedAt']);

        $this->assertEquals($expectedData, $result);
    }

    /**
     * @test
     */
    public function it_update_a_read_model_when_campaign_usage_was_changed_with_transaction(): void
    {
        $customerId = new CustomerId('00000000-0000-0000-0000-000000000000');
        $campaignId = new CustomerCampaignId('11111111-0000-0000-0000-000000000000');
        $transactionId = new TransactionId('00000000-0000-0000-0000-000000000000');
        $coupon = new Coupon('testCoupon');

        $expectedData = [
            'customerId' => (string) $customerId,
            'campaignId' => (string) $campaignId,
            'usedForTransactionId' => $transactionId,
            'returnedAmount' => 0,
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
            'transactionId' => null,
        ];
        $this->scenario->given(
                [
                    new CampaignWasBoughtByCustomer(
                        $customerId,
                        $campaignId,
                        'campaignName',
                        '1',
                        $coupon,
                        Campaign::REWARD_TYPE_DISCOUNT_CODE
                    ),
                ]
            )
            ->when(new CampaignUsageWasChanged($customerId, $campaignId, $coupon, true, $transactionId));

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
