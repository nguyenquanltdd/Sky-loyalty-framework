<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\Controller\Api;

use Broadway\CommandHandling\CommandBus;
use Doctrine\ORM\Query\QueryException;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View as FosView;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use OpenLoyalty\Bundle\CampaignBundle\Exception\CampaignLimitException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\CampaignUsageChange\CampaignUsageChangeException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\NoCouponsLeftException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\NotAllowedException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\NotEnoughPointsException;
use OpenLoyalty\Bundle\CampaignBundle\ResponseModel\CouponUsageResponse;
use OpenLoyalty\Bundle\CampaignBundle\Service\CampaignProvider;
use OpenLoyalty\Bundle\CampaignBundle\Service\CampaignValidator;
use OpenLoyalty\Bundle\CampaignBundle\Service\MultipleCampaignCouponUsageProvider;
use OpenLoyalty\Bundle\UserBundle\Entity\User;
use OpenLoyalty\Component\Campaign\Domain\Campaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\Command\BuyCampaign;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\LevelId;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon as CampaignCoupon;
use OpenLoyalty\Component\Campaign\Domain\SegmentId;
use OpenLoyalty\Component\Customer\Domain\CampaignId as CustomerCampaignId;
use OpenLoyalty\Component\Customer\Domain\Command\ChangeCampaignUsage;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Segment\Domain\ReadModel\SegmentedCustomers;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class CustomerCampaignsController.
 *
 * @Security("is_granted('ROLE_PARTICIPANT')")
 */
class CustomerCampaignsController extends FOSRestController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var MultipleCampaignCouponUsageProvider
     */
    private $multipleCampaignCouponUsageProvider;

    /**
     * CustomerCampaignsController constructor.
     *
     * @param CommandBus                          $commandBus
     * @param TranslatorInterface                 $translator
     * @param MultipleCampaignCouponUsageProvider $multipleCampaignCouponUsageProvider
     */
    public function __construct(CommandBus $commandBus, TranslatorInterface $translator, MultipleCampaignCouponUsageProvider $multipleCampaignCouponUsageProvider)
    {
        $this->commandBus = $commandBus;
        $this->translator = $translator;
        $this->multipleCampaignCouponUsageProvider = $multipleCampaignCouponUsageProvider;
    }

    /**
     * Get all campaigns available for logged in customer.
     *
     * @Route(name="oloy.campaign.customer.available", path="/customer/campaign/available")
     * @Method("GET")
     * @Security("is_granted('LIST_CAMPAIGNS_AVAILABLE_FOR_ME')")
     *
     * @ApiDoc(
     *     name="get customer available campaigns list",
     *     section="Customer Campaign",
     *     parameters={
     *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
     *      {"name"="perPage", "dataType"="integer", "required"=false, "description"="Number of elements per page"},
     *      {"name"="sort", "dataType"="string", "required"=false, "description"="Field to sort by"},
     *      {"name"="direction", "dataType"="asc|desc", "required"=false, "description"="Sorting direction"},
     *      {"name"="categoryId[]", "dataType"="string", "required"=false, "description"="Filter by categories"},
     *     }
     * )
     *
     * @param Request $request
     * @View(serializerGroups={"customer", "Default"})
     *
     * @param TranslatorInterface $translator
     *
     * @return FosView
     */
    public function availableCampaigns(Request $request, TranslatorInterface $translator)
    {
        $pagination = $this->get('oloy.pagination')->handleFromRequest($request);
        $customer = $this->getLoggedCustomer();
        $availablePoints = null;

        $categoryIds = $request->query->get('categoryId', []);
        $customerSegments = $this->get('oloy.segment.read_model.repository.segmented_customers')
            ->findBy(['customerId' => $customer->getCustomerId()->__toString()]);
        $segments = array_map(function (SegmentedCustomers $segmentedCustomers) {
            return new SegmentId($segmentedCustomers->getSegmentId()->__toString());
        }, $customerSegments);

        $campaignRepository = $this->get('oloy.campaign.repository');

        try {
            $campaigns = $campaignRepository
                ->getVisibleCampaignsForLevelAndSegment(
                    $segments,
                    new LevelId($customer->getLevelId()->__toString()),
                    $categoryIds,
                    null,
                    null,
                    $pagination->getSort(),
                    $pagination->getSortDirection()
                );
        } catch (QueryException $exception) {
            return $this->view($translator->trans($exception->getMessage()), Response::HTTP_BAD_REQUEST);
        }

        $campaigns = array_filter($campaigns, function (Campaign $campaign) use ($customer) {
            $usageLeft = $this->get(CampaignProvider::class)->getUsageLeft($campaign);
            $usageLeftForCustomer = $this->get(CampaignProvider::class)
                ->getUsageLeftForCustomer($campaign, $customer->getCustomerId()->__toString());

            return $usageLeft > 0 && $usageLeftForCustomer > 0 ? true : false;
        });

        $view = $this->view(
            [
                'campaigns' => array_slice($campaigns, ($pagination->getPage() - 1) * $pagination->getPerPage(), $pagination->getPerPage()),
                'total' => count($campaigns),
            ],
            Response::HTTP_OK
        );
        $context = new Context();
        $context->setGroups(['Default']);
        $context->setAttribute('customerId', $customer->getCustomerId()->__toString());
        $view->setContext($context);

        return $view;
    }

    /**
     * Get all campaigns bought by logged in customer.
     *
     * @Route(name="oloy.campaign.customer.bought", path="/customer/campaign/bought")
     * @Method("GET")
     * @Security("is_granted('LIST_CAMPAIGNS_BOUGHT_BY_ME')")
     *
     * @ApiDoc(
     *     name="get customer bough campaigns list",
     *     section="Customer Campaign",
     *     parameters={
     *       {"name"="includeDetails", "dataType"="boolean", "required"=false},
     *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
     *      {"name"="perPage", "dataType"="integer", "required"=false, "description"="Number of elements per page"},
     *      {"name"="sort", "dataType"="string", "required"=false, "description"="Field to sort by"},
     *      {"name"="direction", "dataType"="asc|desc", "required"=false, "description"="Sorting direction"},
     *     }
     * )
     *
     * @param Request $request
     * @View(serializerGroups={"customer", "Default"})
     *
     * @return FosView
     */
    public function boughtCampaigns(Request $request)
    {
        $pagination = $this->get('oloy.pagination')->handleFromRequest($request);
        $customer = $this->getLoggedCustomer();
        /** @var CustomerDetailsRepository $repo */
        $repo = $this->get('oloy.user.read_model.repository.customer_details');
        if (count($customer->getCampaignPurchases()) == 0) {
            return $this->view(
                [
                    'campaigns' => [],
                    'total' => 0,
                ],
                Response::HTTP_OK
            );
        }
        $campaigns = $repo
            ->findPurchasesByCustomerIdPaginated(
                $customer->getCustomerId(),
                $pagination->getPage(),
                $pagination->getPerPage(),
                $pagination->getSort(),
                $pagination->getSortDirection()
            );

        if ($request->get('includeDetails', false)) {
            $campaignRepo = $this->get('oloy.campaign.repository');

            $campaigns = array_map(function (CampaignPurchase $campaignPurchase) use ($campaignRepo) {
                $campaignPurchase->setCampaign($campaignRepo->byId(new CampaignId($campaignPurchase->getCampaignId()->__toString())));

                return $campaignPurchase;
            }, $campaigns);
        }

        return $this->view(
            [
                'campaigns' => $campaigns,
                'total' => $repo->countPurchasesByCustomerId($customer->getCustomerId()),
            ],
            Response::HTTP_OK
        );
    }

    /**
     * Buy campaign by logged in customer.
     *
     * @Route(name="oloy.campaign.customer.buy", path="/customer/campaign/{campaign}/buy")
     * @Method("POST")
     * @Security("is_granted('BUY', campaign)")
     *
     * @ApiDoc(
     *     name="buy campaign",
     *     section="Customer Campaign",
     *     statusCodes={
     *       200="Returned when successful",
     *       400="With error 'No coupons left' returned when campaign cannot be bought because of lack of coupons. With error 'Not enough points' returned when campaign cannot be bought because of not enough points on customer account. With empty error returned when campaign limits exceeded."
     *     }
     * )
     *
     * @param TranslatorInterface $translator
     * @param Campaign            $campaign
     * @View(serializerGroups={"customer", "Default"})
     *
     * @return FosView
     */
    public function buyCampaign(Campaign $campaign, TranslatorInterface $translator)
    {
        $provider = $this->get(CampaignProvider::class);
        $campaignValidator = $this->get(CampaignValidator::class);

        if (!$campaignValidator->isCampaignActive($campaign) || !$campaignValidator->isCampaignVisible($campaign)) {
            throw $this->createNotFoundException();
        }
        /** @var CustomerDetails $customer */
        $customer = $this->getLoggedCustomer();

        try {
            $campaignValidator->validateCampaignLimits($campaign, new CustomerId($customer->getCustomerId()->__toString()));
        } catch (CampaignLimitException $e) {
            return $this->view(['error' => $translator->trans($e->getMessage())], Response::HTTP_BAD_REQUEST);
        }

        try {
            $campaignValidator->checkIfCustomerStatusIsAllowed($customer->getStatus());
        } catch (NotAllowedException $e) {
            return $this->view(['error' => $translator->trans($e->getMessage())], Response::HTTP_BAD_REQUEST);
        }

        try {
            $campaignValidator->checkIfCustomerHasEnoughPoints($campaign, new CustomerId($customer->getCustomerId()->__toString()));
        } catch (NotEnoughPointsException $e) {
            return $this->view(['error' => $translator->trans($e->getMessage())], Response::HTTP_BAD_REQUEST);
        }

        $freeCoupons = $provider->getFreeCoupons($campaign);

        if (!$campaign->isSingleCoupon() && count($freeCoupons) == 0) {
            return $this->view(['error' => $this->translator->trans('campaign.no_coupons_left')], Response::HTTP_BAD_REQUEST);
        } elseif ($campaign->isSingleCoupon()) {
            $freeCoupons = $provider->getAllCoupons($campaign);
        }

        $coupon = new CampaignCoupon(reset($freeCoupons));

        /** @var CommandBus $bus */
        $bus = $this->get('broadway.command_handling.command_bus');
        $bus->dispatch(
            new BuyCampaign(
                $campaign->getCampaignId(),
                new CustomerId($customer->getId()),
                $coupon
            )
        );

        $this->get('oloy.user.email_provider')->customerBoughtCampaign(
            $customer,
            $campaign,
            $coupon
        );

        return $this->view(['coupon' => $coupon]);
    }

    /**
     * Mark specific coupon as used/unused by customer.
     *
     * @Route(name="oloy.campaign.customer.coupon_usage", path="/customer/campaign/{campaign}/coupon/{coupon}")
     * @Method("POST")
     * @Security("is_granted('MARK_COUPON_AS_USED', campaign)")
     *
     * @ApiDoc(
     *     name="mark coupon as used",
     *     section="Customer Campaign",
     *     parameters={
     *      {"name"="used", "dataType"="true|false", "required"=true, "description"="True if mark as used, false otherwise"},
     *     },
     *     statusCodes={
     *       200="Returned when successful",
     *       400="Returned when parameter 'used' not provided",
     *       404="Returned when customer or campaign not found"
     *     }
     * )
     *
     * @param Request  $request
     * @param Campaign $campaign
     * @param string   $coupon
     * @View(serializerGroups={"customer", "Default"})
     *
     * @return FosView
     */
    public function campaignCouponUsage(Request $request, Campaign $campaign, $coupon)
    {
        $used = $request->request->get('used', null);
        if ($used === null) {
            return $this->view(['errors' => 'field "used" is required'], 400);
        }

        if (is_string($used)) {
            $used = str_replace('"', '', $used);
            $used = str_replace("'", '', $used);
        }

        if ($used === 'false' || $used === '0' || $used === 0) {
            $used = false;
        }

        /** @var CustomerDetails $customer */
        $customer = $this->getLoggedCustomer();

        try {
            $this->multipleCampaignCouponUsageProvider->validateRequestForCustomer(
                [
                    [
                        'code' => $coupon,
                        'campaignId' => (string) $campaign->getCampaignId(),
                        'used' => $used,
                    ],
                ],
                $customer
            );
        } catch (NoCouponsLeftException $e) {
            return $this->view(['error' => $this->translator->trans($e->getMessage())], Response::HTTP_BAD_REQUEST);
        } catch (CampaignUsageChangeException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        /** @var CommandBus $bus */
        $bus = $this->get('broadway.command_handling.command_bus');
        $bus->dispatch(
            new ChangeCampaignUsage(
                $customer->getCustomerId(),
                new CustomerCampaignId($campaign->getCampaignId()->__toString()),
                new Coupon($coupon),
                $used
            )
        );

        return $this->view(['used' => $used]);
    }

    /**
     * Mark multiple coupons as used/unused by customer.
     *
     * @Route(name="oloy.campaign.customer.coupon_multiple_usage", path="/customer/campaign/coupons/mark_as_used")
     * @Method("POST")
     * @Security("is_granted('MARK_SELF_MULTIPLE_COUPONS_AS_USED')")
     *
     * @ApiDoc(
     *     name="mark multiple coupons as used",
     *     section="Customer Campaign",
     *     parameters={
     *          {"name"="coupons", "dataType"="array", "required"=true, "description"="List of coupons to mark as used"},
     *          {"name"="coupons[][used]", "dataType"="boolean", "required"=true, "description"="If coupon is used or not"},
     *          {"name"="coupons[][campaignId]", "dataType"="string", "required"=true, "description"="CampaignId value"},
     *          {"name"="coupons[][code]", "dataType"="string", "required"=true, "description"="Coupon code"},
     *     },
     *     statusCodes={
     *       200="Returned when successful",
     *       400="Returned when data is invalid",
     *       404="Returned when customer or campaign not found"
     *     }
     * )
     *
     * @param Request $request
     *
     * @return FosView
     * @View(serializerGroups={"admin", "Default"})
     */
    public function campaignCouponListUsage(Request $request): FosView
    {
        $coupons = $request->request->get('coupons', []);

        if (empty($coupons)) {
            throw new BadRequestHttpException($this->translator->trans('campaign.invalid_data'));
        }

        /** @var CustomerDetails $customer */
        $customer = $this->getLoggedCustomer();
        try {
            $commands = $this->multipleCampaignCouponUsageProvider->validateRequestForCustomer($coupons, $customer);
        } catch (NoCouponsLeftException $e) {
            return $this->view(['error' => $this->translator->trans($e->getMessage())], Response::HTTP_BAD_REQUEST);
        } catch (CampaignUsageChangeException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        $result = [];

        /** @var ChangeCampaignUsage $command */
        foreach ($commands as $command) {
            $this->commandBus->dispatch($command);

            $result[] = new CouponUsageResponse(
                $command->getCoupon()->getCode(),
                $command->isUsed(),
                $command->getCampaignId()->__toString(),
                $command->getCustomerId()->__toString()
            );
        }

        return $this->view(['coupons' => $result]);
    }

    /**
     * @return CustomerDetails
     */
    protected function getLoggedCustomer(): CustomerDetails
    {
        /** @var User $user */
        $user = $this->getUser();
        $customer = $this->get('oloy.user.read_model.repository.customer_details')->find($user->getId());
        if (!$customer instanceof CustomerDetails) {
            throw $this->createNotFoundException();
        }

        return $customer;
    }
}
