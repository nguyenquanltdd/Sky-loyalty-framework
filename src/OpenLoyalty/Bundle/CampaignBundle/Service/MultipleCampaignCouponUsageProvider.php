<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\Service;

use Assert\AssertionFailedException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\CampaignUsageChange\CampaignUsageChangeException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\CampaignUsageChange\InvalidDataProvidedException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\CampaignUsageChange\MissingDataInRowsException;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Customer\Domain\CampaignId as CustomerCampaignId;
use OpenLoyalty\Component\Customer\Domain\Command\ChangeCampaignUsage;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class MultipleCampaignCouponUsageProvider.
 */
class MultipleCampaignCouponUsageProvider
{
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;

    /**
     * @var CustomerDetailsRepository
     */
    private $customerDetailsRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * MultipleCampaignCouponUsageProvider constructor.
     *
     * @param CampaignRepository        $campaignRepository
     * @param CustomerDetailsRepository $customerDetailsRepository
     * @param TranslatorInterface       $translator
     */
    public function __construct(
        CampaignRepository $campaignRepository,
        CustomerDetailsRepository $customerDetailsRepository,
        TranslatorInterface $translator
    ) {
        $this->campaignRepository = $campaignRepository;
        $this->customerDetailsRepository = $customerDetailsRepository;
        $this->translator = $translator;
    }

    /**
     * @param array $coupons
     *
     * @return ChangeCampaignUsage[]
     *
     * @throws CampaignUsageChangeException
     */
    public function validateRequest(array $coupons): array
    {
        $result = [];

        foreach ($coupons as $key => $coupon) {
            if (!isset($coupon['used'], $coupon['code'], $coupon['customerId'], $coupon['campaignId'])) {
                throw new MissingDataInRowsException($this->translator->trans('campaign.missing_data_in_rows'));
            }
            try {
                $used = boolval($coupon['used']);
                $campaign = $this->campaignRepository->byId(new CampaignId($coupon['campaignId']));
                $customer = $this->customerDetailsRepository->find(new CustomerId($coupon['customerId']));

                $this->checkFields($used, $customer, $campaign, (string) $key);
            } catch (AssertionFailedException $exception) {
                throw new InvalidDataProvidedException(
                    $this->translator->trans(
                        'campaign.invalid_value_campaign_id_in_row',
                        [
                            '%content%' => $exception->getMessage(),
                            '%row%' => $key,
                        ]
                    )
                );
            }

            $result[] = new ChangeCampaignUsage(
                $customer->getCustomerId(),
                new CustomerCampaignId($campaign->getCampaignId()->__toString()),
                new Coupon($coupon['code']),
                $used
            );
        }

        return $result;
    }

    /**
     * @param array           $coupons
     * @param CustomerDetails $customer
     *
     * @return array
     *
     * @throws CampaignUsageChangeException
     */
    public function validateRequestForCustomer(array $coupons, CustomerDetails $customer): array
    {
        $result = [];

        foreach ($coupons as $key => $coupon) {
            if (!isset($coupon['used'], $coupon['code'], $coupon['campaignId'])) {
                throw new MissingDataInRowsException();
            }
            try {
                $used = boolval($coupon['used']);
                $campaign = $this->campaignRepository->byId(new CampaignId($coupon['campaignId']));

                $this->checkFields($used, $customer, $campaign, $key);
            } catch (AssertionFailedException $exception) {
                throw new InvalidDataProvidedException();
            }

            $result[] = new ChangeCampaignUsage(
                $customer->getCustomerId(),
                new CustomerCampaignId($campaign->getCampaignId()->__toString()),
                new Coupon($coupon['code']),
                $used
            );
        }

        return $result;
    }

    /**
     * @param bool            $used
     * @param CustomerDetails $customer
     * @param Campaign        $campaign
     * @param string          $key
     *
     * @throws CampaignUsageChangeException
     */
    private function checkFields(bool $used, CustomerDetails $customer, Campaign $campaign, string $key)
    {
        if (!$used) {
            throw new InvalidDataProvidedException(
                $this->translator->trans('campaign.invalid_value_field_in_row', ['%name%' => 'used', '%row%' => $key])
            );
        }
        if (!$customer) {
            throw new InvalidDataProvidedException(
                $this->translator->trans('campaign.invalid_value_field_in_row', ['%name%' => 'customerId', '%row%' => $key])
            );
        }
        if (!$campaign) {
            throw new InvalidDataProvidedException(
                $this->translator->trans('campaign.invalid_value_field_in_row', ['%name%' => 'campaignId', '%row%' => $key])
            );
        }
        if (count(array_filter($customer->getCampaignPurchases(), function (CampaignPurchase $campaignPurchase) use ($campaign) {
            return $campaignPurchase->getCampaignId()->__toString() === $campaign->getCampaignId()->__toString();
        })) === 0) {
            throw new InvalidDataProvidedException(
                $this->translator->trans('campaign.invalid_value_field_in_row', ['%name%' => 'code', '%row%' => $key])
            );
        }
    }
}
