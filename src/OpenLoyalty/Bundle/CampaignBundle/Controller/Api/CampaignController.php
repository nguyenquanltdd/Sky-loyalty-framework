<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\Controller\Api;

use Broadway\CommandHandling\CommandBus;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\View\View as FosView;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use OpenLoyalty\Bundle\CampaignBundle\Exception\CampaignLimitException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\CampaignUsageChange\CampaignUsageChangeException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\InvalidTransactionException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\NotAllowedException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\NotEnoughPointsException;
use OpenLoyalty\Bundle\CampaignBundle\Exception\TransactionRequiredException;
use OpenLoyalty\Bundle\CampaignBundle\Form\Type\CampaignFormType;
use OpenLoyalty\Bundle\CampaignBundle\Form\Type\CampaignPhotoFormType;
use OpenLoyalty\Bundle\CampaignBundle\Form\Type\EditCampaignFormType;
use OpenLoyalty\Bundle\CampaignBundle\Model\Campaign;
use OpenLoyalty\Bundle\CampaignBundle\ResponseModel\CouponUsageResponse;
use OpenLoyalty\Bundle\CampaignBundle\Service\CampaignProvider;
use OpenLoyalty\Bundle\CampaignBundle\Service\CampaignValidator;
use OpenLoyalty\Bundle\CampaignBundle\Service\MultipleCampaignCouponUsageProvider;
use OpenLoyalty\Bundle\CoreBundle\Service\CSVGenerator;
use OpenLoyalty\Bundle\MarkDownBundle\Service\FOSContextProvider;
use OpenLoyalty\Component\Campaign\Domain\Campaign as DomainCampaign;
use OpenLoyalty\Component\Campaign\Domain\CampaignId;
use OpenLoyalty\Component\Campaign\Domain\CampaignRepository;
use OpenLoyalty\Component\Campaign\Domain\Command\BuyCampaign;
use OpenLoyalty\Component\Campaign\Domain\Command\ChangeCampaignState;
use OpenLoyalty\Component\Campaign\Domain\Command\CreateCampaign;
use OpenLoyalty\Component\Campaign\Domain\Command\RemoveCampaignPhoto;
use OpenLoyalty\Component\Campaign\Domain\Command\SetCampaignPhoto;
use OpenLoyalty\Component\Campaign\Domain\Command\UpdateCampaign;
use OpenLoyalty\Component\Campaign\Domain\Coupon\CouponCodeProvider;
use OpenLoyalty\Component\Campaign\Domain\CustomerId;
use OpenLoyalty\Component\Campaign\Domain\LevelId;
use OpenLoyalty\Component\Campaign\Domain\Model\Coupon as CampaignCoupon;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\ActiveCampaigns;
use OpenLoyalty\Component\Campaign\Domain\ReadModel\CampaignShortInfo;
use OpenLoyalty\Component\Campaign\Domain\SegmentId;
use OpenLoyalty\Component\Customer\Domain\CampaignId as CustomerCampaignId;
use OpenLoyalty\Component\Customer\Domain\Command\ChangeCampaignUsage;
use OpenLoyalty\Component\Customer\Domain\Model\CampaignPurchase;
use OpenLoyalty\Component\Customer\Domain\Model\Coupon;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetails;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Segment\Domain\ReadModel\SegmentedCustomers;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CampaignController.
 */
class CampaignController extends FOSRestController
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
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * @var CampaignProvider
     */
    private $campaignProvider;

    /**
     * @var CouponCodeProvider
     */
    private $couponCodeProvider;

    /**
     * @var TransactionDetailsRepository
     */
    private $transactionDetailsRepository;

    /**
     * CampaignController constructor.
     *
     * @param CommandBus                   $commandBus
     * @param TranslatorInterface          $translator
     * @param CampaignValidator            $campaignValidator
     * @param CampaignProvider             $campaignProvider
     * @param CouponCodeProvider           $couponCodeProvider
     * @param TransactionDetailsRepository $transactionDetailsRepository
     */
    public function __construct(
        CommandBus $commandBus,
        TranslatorInterface $translator,
        CampaignValidator $campaignValidator,
        CampaignProvider $campaignProvider,
        CouponCodeProvider $couponCodeProvider,
        TransactionDetailsRepository $transactionDetailsRepository
    ) {
        $this->commandBus = $commandBus;
        $this->translator = $translator;
        $this->campaignValidator = $campaignValidator;
        $this->campaignProvider = $campaignProvider;
        $this->couponCodeProvider = $couponCodeProvider;
        $this->transactionDetailsRepository = $transactionDetailsRepository;
    }

    /**
     * Create new campaign.
     *
     * @Route(name="oloy.campaign.create", path="/campaign")
     * @Method("POST")
     * @Security("is_granted('CREATE_CAMPAIGN')")
     * @ApiDoc(
     *     name="Create new Campaign",
     *     section="Campaign",
     *     input={"class" = "OpenLoyalty\Bundle\CampaignBundle\Form\Type\CampaignFormType", "name" = "campaign"},
     *     statusCodes={
     *       200="Returned when successful",
     *       400="Returned when there are errors in form",
     *       404="Returned when campaign not found"
     *     }
     * )
     *
     * @param Request $request
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function createAction(Request $request)
    {
        $form = $this->get('form.factory')->createNamed('campaign', CampaignFormType::class);
        $uuidGenerator = $this->get('broadway.uuid.generator');

        /** @var CommandBus $commandBus */
        $commandBus = $this->get('broadway.command_handling.command_bus');

        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var Campaign $data */
            $data = $form->getData();
            $id = new CampaignId($uuidGenerator->generate());

            $commandBus->dispatch(
                new CreateCampaign($id, $data->toArray())
            );

            return $this->view(['campaignId' => $id->__toString()]);
        }

        return $this->view($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Add photo to campaign.
     *
     * @Route(name="oloy.campaign.add_photo", path="/campaign/{campaign}/photo")
     * @Method("POST")
     * @Security("is_granted('EDIT', campaign)")
     * @ApiDoc(
     *     name="Add photo to Campaign",
     *     section="Campaign",
     *     input={"class" = "OpenLoyalty\Bundle\CampaignBundle\Form\Type\CampaignPhotoFormType", "name" = "photo"}
     * )
     *
     * @param Request        $request
     * @param DomainCampaign $campaign
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function addPhotoAction(Request $request, DomainCampaign $campaign)
    {
        $form = $this->get('form.factory')->createNamed('photo', CampaignPhotoFormType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->getData()->getFile();
            $uploader = $this->get('oloy.campaign.photo_uploader');
            $uploader->remove($campaign->getCampaignPhoto());
            $photo = $uploader->upload($file);
            $command = new SetCampaignPhoto($campaign->getCampaignId(), $photo);
            $this->get('broadway.command_handling.command_bus')->dispatch($command);

            return $this->view([], Response::HTTP_OK);
        }

        return $this->view($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Remove photo from campaign.
     *
     * @Route(name="oloy.campaign.remove_photo", path="/campaign/{campaign}/photo")
     * @Method("DELETE")
     * @Security("is_granted('EDIT', campaign)")
     * @ApiDoc(
     *     name="Delete photo from Campaign",
     *     section="Campaign"
     * )
     *
     * @param DomainCampaign $campaign
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function removePhotoAction(DomainCampaign $campaign)
    {
        $uploader = $this->get('oloy.campaign.photo_uploader');
        $uploader->remove($campaign->getCampaignPhoto());

        $command = new RemoveCampaignPhoto($campaign->getCampaignId());
        $this->get('broadway.command_handling.command_bus')->dispatch($command);

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * Get campaign photo.
     *
     * @Route(name="oloy.campaign.get_photo", path="/campaign/{campaign}/photo")
     * @Method("GET")
     * @ApiDoc(
     *     name="Get campaign photo",
     *     section="Campaign"
     * )
     *
     * @param Request        $request
     * @param DomainCampaign $campaign
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return Response
     */
    public function getPhotoAction(Request $request, DomainCampaign $campaign)
    {
        $photo = $campaign->getCampaignPhoto();
        if (!$photo) {
            throw $this->createNotFoundException();
        }
        $content = $this->get('oloy.campaign.photo_uploader')->get($photo);
        if (!$content) {
            throw $this->createNotFoundException();
        }

        $response = new Response($content);
        $response->headers->set('Content-Disposition', 'inline');
        $response->headers->set('Content-Type', $photo->getMime());
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    /**
     * Edit campaign.
     *
     * @Route(name="oloy.campaign.edit", path="/campaign/{campaign}")
     * @Method("PUT")
     * @Security("is_granted('EDIT', campaign)")
     * @ApiDoc(
     *     name="Create new Campaign",
     *     section="Campaign",
     *     input={"class" = "OpenLoyalty\Bundle\CampaignBundle\Form\Type\EditCampaignFormType", "name" = "campaign"},
     *     statusCodes={
     *       200="Returned when successful",
     *       400="Returned when there are errors in form",
     *       404="Returned when campaign not found"
     *     }
     * )
     *
     * @param Request        $request
     * @param DomainCampaign $campaign
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function editAction(Request $request, DomainCampaign $campaign)
    {
        $form = $this->get('form.factory')->createNamed('campaign', EditCampaignFormType::class, null, [
            'method' => 'PUT',
        ]);

        /** @var CommandBus $commandBus */
        $commandBus = $this->get('broadway.command_handling.command_bus');

        $form->handleRequest($request);
        if ($form->isValid()) {
            /** @var Campaign $data */
            $data = $form->getData();
            $commandBus->dispatch(
                new UpdateCampaign($campaign->getCampaignId(), $data->toArray())
            );

            return $this->view(['campaignId' => $campaign->getCampaignId()->__toString()]);
        }

        return $this->view($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Change campaign state action to active or inactive.
     *
     * @Route(name="oloy.campaign.change_state", path="/campaign/{campaign}/{active}", requirements={"active":"active|inactive"})
     * @Method("POST")
     * @Security("is_granted('EDIT', campaign)")
     * @ApiDoc(
     *     name="Change Campaign state",
     *     section="Campaign"
     * )
     *
     * @param DomainCampaign $campaign
     * @param                $active
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function changeStateAction(DomainCampaign $campaign, $active)
    {
        if ($active == 'active') {
            $campaign->setActive(true);
        } elseif ($active == 'inactive') {
            $campaign->setActive(false);
        }
        /** @var CommandBus $commandBus */
        $commandBus = $this->get('broadway.command_handling.command_bus');
        $commandBus->dispatch(
            new ChangeCampaignState($campaign->getCampaignId(), $campaign->isActive())
        );

        return $this->view(['campaignId' => $campaign->getCampaignId()->__toString()]);
    }

    /**
     * Get all campaigns.
     *
     * @Route(name="oloy.campaign.list", path="/campaign")
     * @Security("is_granted('LIST_ALL_CAMPAIGNS')")
     * @Method("GET")
     *
     * @ApiDoc(
     *     name="get campaigns list",
     *     section="Campaign",
     *     parameters={
     *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
     *      {"name"="perPage", "dataType"="integer", "required"=false, "description"="Number of elements per page"},
     *      {"name"="sort", "dataType"="string", "required"=false, "description"="Field to sort by"},
     *      {"name"="direction", "dataType"="asc|desc", "required"=false, "description"="Sorting direction"},
     *      {"name"="active", "dataType"="boolean", "required"=false, "description"="Filter by activity"},
     *      {"name"="campaignType", "dataType"="string", "required"=false, "description"="Filter by campaign type"},
     *      {"name"="name", "dataType"="string", "required"=false, "description"="Filter by campaign name"},
     *     }
     * )
     *
     * @param Request      $request
     * @param ParamFetcher $paramFetcher
     *
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     *
     * @QueryParam(name="labels", nullable=true, description="filter by labels"))
     * @QueryParam(name="active", nullable=true, description="filter by activity"))
     * @QueryParam(name="campaignType", nullable=true, description="filter by campaign type"))
     * @QueryParam(name="name", nullable=true, description="filter by campaign name"))
     */
    public function getListAction(Request $request, ParamFetcher $paramFetcher)
    {
        $pagination = $this->get('oloy.pagination')->handleFromRequest($request);

        $params = $paramFetcher->all();

        $campaignRepository = $this->get('oloy.campaign.repository');

        $campaigns = $campaignRepository
            ->findByParametersPaginated(
                $params,
                $pagination->getPage(),
                $pagination->getPerPage(),
                $pagination->getSort(),
                $pagination->getSortDirection()
            );
        $total = $campaignRepository->countFindByParameters($params);

        $view = $this->view(
            [
                'campaigns' => $campaigns,
                'total' => $total,
            ],
            Response::HTTP_OK
        );

        $context = new Context();
        $context->setGroups(['Default', 'list']);
        $context->setAttribute(
            FOSContextProvider::OUTPUT_FORMAT_ATTRIBUTE_NAME,
            $request->get('format')
        );
        $view->setContext($context);

        return $view;
    }

    /**
     * Get all bought campaigns.
     *
     * @Route(name="oloy.campaign.bought.list", path="/campaign/bought")
     * @Security("is_granted('LIST_ALL_BOUGHT_CAMPAIGNS')")
     * @Method("GET")
     *
     * @ApiDoc(
     *     name="get bought campaigns list",
     *     section="Campaign",
     *     parameters={
     *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
     *      {"name"="perPage", "dataType"="integer", "required"=false, "description"="Number of elements per page"},
     *      {"name"="sort", "dataType"="string", "required"=false, "description"="Field to sort by"},
     *      {"name"="direction", "dataType"="asc|desc", "required"=false, "description"="Sorting direction"},
     *      {"name"="purchasedAtFrom", "dataType"="string", "required"=false, "description"="Purchased date from filter"},
     *      {"name"="purchasedAtTo", "dataType"="string", "required"=false, "description"="Purchased date to filter"},
     *     }
     * )
     *
     * @QueryParam(name="used", nullable=true, description="Used"))
     * @QueryParam(name="purchasedAtFrom", nullable=true, description="Range date filter"))
     * @QueryParam(name="purchasedAtTo", nullable=true, description="Range date filter"))
     *
     * @param Request $request
     *
     * @return FosView
     */
    public function getBoughtListAction(Request $request, ParamFetcher $paramFetcher)
    {
        $pagination = $this->get('oloy.pagination')->handleFromRequest($request);
        $repo = $this->get('oloy.campaign.read_model.repository.campaign_bought');
        $params = $this->get('oloy.user.param_manager')->stripNulls($paramFetcher->all());

        // extract ES-like params for date range filter
        $this->get('oloy.user.param_manager')
            ->appendDateRangeFilter(
                $params,
                'purchasedAt',
                $params['purchasedAtFrom'] ?? null,
                $params['purchasedAtTo'] ?? null
            );

        unset($params['purchasedAtFrom']);
        unset($params['purchasedAtTo']);

        $boughtCampaigns = $repo->findByParametersPaginated(
            $params,
            true,
            $pagination->getPage(),
            $pagination->getPerPage(),
            $pagination->getSort(),
            $pagination->getSortDirection()
        );

        $total = $repo->countTotal($params);

        return $this->view(
            [
                'boughtCampaigns' => $boughtCampaigns,
                'total' => $total,
            ]
        );
    }

    /**
     * @param ParamFetcher $paramFetcher
     *
     * @Route(name="oloy.campaign.bought.csv", path="/campaign/bought/export/csv")
     * @Security("is_granted('LIST_ALL_BOUGHT_CAMPAIGNS')")
     * @Method("GET")
     * @ApiDoc(
     *     name="generate CSV of bought campaigns",
     *     section="Campaign",
     *     parameters={
     *      {"name"="purchasedAtFrom", "dataType"="string", "required"=false, "description"="Purchased date from filter"},
     *      {"name"="purchasedAtTo", "dataType"="string", "required"=false, "description"="Purchased date to filter"},
     *     }
     * )
     * @QueryParam(name="purchasedAtFrom", nullable=true, description="Range date filter"))
     * @QueryParam(name="purchasedAtTo", nullable=true, description="Range date filter"))
     *
     * @return Response|FosView
     */
    public function exportBoughtAction(ParamFetcher $paramFetcher, TranslatorInterface $translator)
    {
        $params = $this->get('oloy.user.param_manager')->stripNulls($paramFetcher->all());
        $generator = $this->get(CSVGenerator::class);
        $repo = $this->get('oloy.campaign.read_model.repository.campaign_bought');
        $headers = $this->getParameter('oloy.campaign.bought.export.headers');
        $fields = $this->getParameter('oloy.campaign.bought.export.fields');

        try {
            // extract ES-like params for date range filter
            $this->get('oloy.user.param_manager')
                ->appendDateRangeFilter(
                    $params,
                    'purchasedAt',
                    $params['purchasedAtFrom'] ?? null,
                    $params['purchasedAtTo'] ?? null
                );

            unset($params['purchasedAtFrom']);
            unset($params['purchasedAtTo']);
            $content = $generator->generate($repo->findByParameters($params), $headers, $fields);
            $handle = tmpfile();
            fwrite($handle, $content);
            $file = new File(stream_get_meta_data($handle)['uri'], false);
            $file = $file->move($this->container->getParameter('kernel.project_dir').'/app/uploads');
            $response = new BinaryFileResponse($file);
            $response->deleteFileAfterSend(true);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

            return $response;
        } catch (\Exception $exception) {
            return $this->view($translator->trans($exception->getMessage()), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get all visible campaigns.
     *
     * @Route(name="oloy.campaign.seller.list", path="/seller/campaign")
     * @Security("is_granted('LIST_ALL_VISIBLE_CAMPAIGNS')")
     * @Method("GET")
     *
     * @ApiDoc(
     *     name="get campaigns list",
     *     section="Campaign",
     *     parameters={
     *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
     *      {"name"="perPage", "dataType"="integer", "required"=false, "description"="Number of elements per page"},
     *      {"name"="sort", "dataType"="string", "required"=false, "description"="Field to sort by"},
     *      {"name"="direction", "dataType"="asc|desc", "required"=false, "description"="Sorting direction"},
     *     }
     * )
     *
     * @param Request $request
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function getVisibleListAction(Request $request)
    {
        $pagination = $this->get('oloy.pagination')->handleFromRequest($request);

        $campaignRepository = $this->get('oloy.campaign.repository');
        $campaigns = $campaignRepository
            ->findAllVisiblePaginated(
                $pagination->getPage(),
                $pagination->getPerPage(),
                $pagination->getSort(),
                $pagination->getSortDirection()
            );
        $total = $campaignRepository->countTotal(true);

        $view = $this->view(
            [
                'campaigns' => $campaigns,
                'total' => $total,
            ],
            200
        );

        $context = new Context();
        $context->setGroups(['Default', 'list']);
        $context->setAttribute(
            FOSContextProvider::OUTPUT_FORMAT_ATTRIBUTE_NAME,
            $request->get('format')
        );
        $view->setContext($context);

        return $view;
    }

    /**
     * Get active campaigns.
     *
     * @Route(name="oloy.campaign.active.get", path="/campaign/active")
     * @Method("GET")
     * @Security("is_granted('LIST_ALL_ACTIVE_CAMPAIGNS')")
     * @ApiDoc(
     *     name="Get active campaigns",
     *     section="Campaign"
     * )
     *
     * @param Request            $request
     * @param CampaignRepository $campaignRepository
     *
     * @return FosView
     */
    public function getActiveCampaignsAction(Request $request, CampaignRepository $campaignRepository)
    {
        $domainCampaigns = $campaignRepository->getActiveCampaigns();

        $activeCampaigns = new ActiveCampaigns();

        /** @var Campaign $campaign */
        foreach ($domainCampaigns as $campaign) {
            $campaignShortInfo = new CampaignShortInfo($campaign);
            $activeCampaigns->addCampaign($campaignShortInfo);
        }

        $view = $this->view(
            [
                'campaigns' => $activeCampaigns->getCampaigns(),
            ],
            Response::HTTP_OK
        );

        $context = new Context();
        $context->setGroups(['Default', 'list']);
        $context->setAttribute(
            FOSContextProvider::OUTPUT_FORMAT_ATTRIBUTE_NAME,
            $request->get('format')
        );
        $view->setContext($context);

        return $view;
    }

    /**
     * Get single campaign details.
     *
     * @Route(name="oloy.campaign.get", path="/campaign/{campaign}")
     * @Route(name="oloy.campaign.seller.get", path="/seller/campaign/{campaign}")
     * @Method("GET")
     * @Security("is_granted('VIEW', campaign)")
     * @ApiDoc(
     *     name="get campaign details",
     *     section="Campaign"
     * )
     *
     * @param Request        $request
     * @param DomainCampaign $campaign
     *
     * @return FosView
     * @View(serializerGroups={"admin", "Default"})
     */
    public function getAction(Request $request, DomainCampaign $campaign)
    {
        $view = $this->view($campaign);
        $view->getContext()->setAttribute(
            FOSContextProvider::OUTPUT_FORMAT_ATTRIBUTE_NAME,
            $request->get('format')
        );

        return $view;
    }

    /**
     * Get customers who for whom this campaign is visible.
     *
     * @Route(name="oloy.campaign.get_customers_visible_for_campaign", path="/campaign/{campaign}/customers/visible")
     * @Method("GET")
     * @Security("is_granted('LIST_ALL_CAMPAIGNS')")
     *
     * @ApiDoc(
     *     name="campaign visible for customers",
     *     section="Campaign",
     *     parameters={
     *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
     *      {"name"="perPage", "dataType"="integer", "required"=false, "description"="Number of elements per page"},
     *      {"name"="sort", "dataType"="string", "required"=false, "description"="Field to sort by"},
     *      {"name"="direction", "dataType"="asc|desc", "required"=false, "description"="Sorting direction"},
     *     }
     * )
     *
     * @param Request        $request
     * @param DomainCampaign $campaign
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function getVisibleForCustomersAction(Request $request, DomainCampaign $campaign)
    {
        $pagination = $this->get('oloy.pagination')->handleFromRequest($request);

        $customers = array_values($this->campaignProvider->visibleForCustomers($campaign));
        /** @var CustomerDetailsRepository $repo */
        $repo = $this->get('oloy.user.read_model.repository.customer_details');
        $res = [];
        foreach ($customers as $id) {
            $tmp = $repo->find($id);
            if ($tmp instanceof CustomerDetails) {
                $res[] = $tmp;
            }
        }
        $total = count($res);
        $res = array_slice($res, ($pagination->getPage() - 1) * $pagination->getPerPage(), $pagination->getPerPage());

        return $this->view([
            'customers' => $res,
            'total' => $total,
        ]);
    }

    /**
     * List all campaigns that can be baught by this customer.
     *
     * @Route(name="oloy.campaign.admin.customer.available", path="/admin/customer/{customer}/campaign/available")
     * @Route(name="oloy.campaign.seller.customer.available", path="/seller/customer/{customer}/campaign/available")
     * @Method("GET")
     * @Security("is_granted('BUY_FOR_CUSTOMER_SELLER') or is_granted('BUY_FOR_CUSTOMER_ADMIN')")
     *
     * @ApiDoc(
     *     name="get available campaigns for customer list",
     *     section="Campaign",
     *     parameters={
     *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
     *      {"name"="perPage", "dataType"="integer", "required"=false, "description"="Number of elements per page"},
     *      {"name"="sort", "dataType"="string", "required"=false, "description"="Field to sort by"},
     *      {"name"="direction", "dataType"="asc|desc", "required"=false, "description"="Sorting direction"},
     *     }
     * )
     *
     * @param Request         $request
     * @param CustomerDetails $customer
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function availableCampaigns(Request $request, CustomerDetails $customer)
    {
        $pagination = $this->get('oloy.pagination')->handleFromRequest($request);

        $customerSegments = $this->get('oloy.segment.read_model.repository.segmented_customers')
            ->findBy(['customerId' => $customer->getCustomerId()->__toString()]);
        $segments = array_map(function (SegmentedCustomers $segmentedCustomers) {
            return new SegmentId($segmentedCustomers->getSegmentId()->__toString());
        }, $customerSegments);

        $campaignRepository = $this->get('oloy.campaign.repository');
        $campaigns = $campaignRepository
            ->getVisibleCampaignsForLevelAndSegment(
                $segments,
                $customer->getLevelId() ? new LevelId($customer->getLevelId()->__toString()) : null,
                null,
                null,
                $pagination->getSort(),
                $pagination->getSortDirection()
            );
        $campaigns = array_filter($campaigns, function (DomainCampaign $campaign) use ($customer) {
            $usageLeft = $this->campaignProvider->getUsageLeft($campaign);
            $usageLeftForCustomer = $this->campaignProvider
                ->getUsageLeftForCustomer($campaign, $customer->getCustomerId()->__toString());

            return $usageLeft > 0 && $usageLeftForCustomer > 0 ? true : false;
        });

        $view = $this->view(
            [
                'campaigns' => array_slice($campaigns, ($pagination->getPage() - 1) * $pagination->getPerPage(), $pagination->getPerPage()),
                'total' => count($campaigns),
            ],
            200
        );

        $context = new Context();
        $context->setGroups(['Default']);
        $context->setAttribute('customerId', $customer->getCustomerId()->__toString());
        $context->setAttribute(
            FOSContextProvider::OUTPUT_FORMAT_ATTRIBUTE_NAME,
            $request->get('format')
        );
        $view->setContext($context);

        return $view;
    }

    /**
     * Buy campaign as seller for customer.
     *
     * @Route(name="oloy.campaign.seller.buy", path="/seller/customer/{customer}/campaign/{campaign}/buy")
     * @Method("POST")
     * @Security("is_granted('BUY_FOR_CUSTOMER_SELLER')")
     *
     * @ApiDoc(
     *     name="buy campaign for customer",
     *     section="Campaign",
     *     statusCodes={
     *       200="Returned when successful",
     *       400="With error 'No coupons left' returned when campaign cannot be bought because of lack of coupons. With error 'Not enough points' returned when campaign cannot be bought because of not enough points on customer account. With empty error returned when campaign limits exceeded."
     *     }
     * )
     *
     * @param DomainCampaign  $campaign
     * @param CustomerDetails $customer
     * @View(serializerGroups={"admin", "Default"})
     *
     * @param TranslatorInterface $translator
     *
     * @return \FOS\RestBundle\View\View
     */
    public function buyCampaign(DomainCampaign $campaign, CustomerDetails $customer, TranslatorInterface $translator)
    {
        if (!$this->campaignValidator->isCampaignActive($campaign) || !$this->campaignValidator->isCampaignVisible($campaign)) {
            throw $this->createNotFoundException();
        }

        try {
            $this->campaignValidator->validateCampaignLimits($campaign, new CustomerId($customer->getCustomerId()->__toString()));
        } catch (CampaignLimitException $e) {
            return $this->view(['error' => $translator->trans($e->getMessage())], 400);
        }

        try {
            $this->campaignValidator->checkIfCustomerStatusIsAllowed($customer->getStatus());
        } catch (NotAllowedException $e) {
            return $this->view(['error' => $translator->trans($e->getMessage())], 400);
        }

        try {
            $this->campaignValidator->checkIfCustomerHasEnoughPoints($campaign, new CustomerId($customer->getCustomerId()->__toString()));
        } catch (NotEnoughPointsException $e) {
            return $this->view(['error' => $translator->trans($e->getMessage())], 400);
        }

        $freeCoupons = $this->campaignProvider->getFreeCoupons($campaign);
        if (!$campaign->isSingleCoupon() && count($freeCoupons) == 0) {
            return $this->view(['error' => 'No coupons left'], 400);
        } elseif ($campaign->isSingleCoupon()) {
            $freeCoupons = $this->campaignProvider->getAllCoupons($campaign);
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
     * Buy campaign for customer as admin.
     *
     * @Route(name="oloy.campaign.buy", path="/admin/customer/{customer}/campaign/{campaign}/buy")
     * @Method("POST")
     * @Security("is_granted('BUY_FOR_CUSTOMER_ADMIN')")
     *
     * @ApiDoc(
     *     name="buy campaign for customer",
     *     section="Campaign",
     *     parameters={
     *      {"name"="withoutPoints", "dataType"="boolean", "required"=false}
     *     },
     *     statusCodes={
     *       200="Returned when successful",
     *       400="With error 'No coupons left' returned when campaign cannot be bought because of lack of coupons. With error 'Not enough points' returned when campaign cannot be bought because of not enough points on customer account. With empty error returned when campaign limits exceeded."
     *     }
     * )
     *
     * @param DomainCampaign  $campaign
     * @param CustomerDetails $customer
     * @param Request         $request
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     *
     * @throws \Exception
     * @throws NotFoundHttpException
     */
    public function buyCampaignAdmin(DomainCampaign $campaign, CustomerDetails $customer, Request $request)
    {
        if (!$campaign->canBeBoughtManually()) {
            throw new BadRequestHttpException();
        }

        $withoutPoints = $request->get('withoutPoints', false);
        $transactionId = $request->get('transactionId', null);

        if (!$this->campaignValidator->isCampaignActive($campaign)) {
            throw $this->createNotFoundException();
        }

        try {
            if ($transactionId) {
                $transaction = $this->transactionDetailsRepository->find($transactionId);
                if ($transaction) {
                    $transactionValue = $transaction->getGrossValue();
                    if ($transaction->getCustomerId()->__toString() != $customer->getId()) {
                        throw new InvalidTransactionException();
                    }
                }
            }

            if (!isset($transactionValue) && $campaign->isTransactionRequired()) {
                throw new TransactionRequiredException();
            }

            $this->campaignValidator->validateCampaignLimits($campaign, new CustomerId($customer->getCustomerId()->__toString()));
            $this->campaignValidator->checkIfCustomerStatusIsAllowed($customer->getStatus());
            if (!$withoutPoints) {
                $this->campaignValidator->checkIfCustomerHasEnoughPoints($campaign, new CustomerId($customer->getCustomerId()->__toString()));
            }
            $coupon = $this->couponCodeProvider->getCoupon($campaign, $transactionValue ?? 0);
        } catch (CampaignLimitException | NotAllowedException | NotEnoughPointsException $e) {
            return $this->view(['error' => $this->translator->trans($e->getMessage())], 400);
        }

        $this->commandBus->dispatch(
            new BuyCampaign(
                $campaign->getCampaignId(),
                new CustomerId($customer->getId()),
                $coupon,
                $withoutPoints === true ? 0 : $campaign->getCostInPoints()
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
     * Get all campaigns bought by customer.
     *
     * @Route(name="oloy.campaign.admin.customer.bought", path="/admin/customer/{customer}/campaign/bought")
     * @Route(name="oloy.campaign.seller.customer.bought", path="/seller/customer/{customer}/campaign/bought")
     * @Method("GET")
     * @Security("is_granted('BUY_FOR_CUSTOMER_SELLER') or is_granted('BUY_FOR_CUSTOMER_ADMIN')")
     *
     * @ApiDoc(
     *     name="get customer bough campaigns list",
     *     section="Customer Campaign",
     *     parameters={
     *      {"name"="includeDetails", "dataType"="boolean", "required"=false},
     *      {"name"="page", "dataType"="integer", "required"=false, "description"="Page number"},
     *      {"name"="perPage", "dataType"="integer", "required"=false, "description"="Number of elements per page"},
     *      {"name"="sort", "dataType"="string", "required"=false, "description"="Field to sort by"},
     *      {"name"="direction", "dataType"="asc|desc", "required"=false, "description"="Sorting direction"},
     *     }
     * )
     *
     * @param Request         $request
     * @param CustomerDetails $customer
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function boughtCampaigns(Request $request, CustomerDetails $customer)
    {
        $pagination = $this->get('oloy.pagination')->handleFromRequest($request);

        /** @var CustomerDetailsRepository $repo */
        $repo = $this->get('oloy.user.read_model.repository.customer_details');

        if (count($customer->getCampaignPurchases()) == 0) {
            return $this->view(
                [
                    'campaigns' => [],
                    'total' => 0,
                ],
                200
            );
        }

        $campaigns = $repo
            ->findPurchasesByCustomerIdPaginated(
                $customer->getCustomerId(),
                $pagination->getPage(),
                $pagination->getPerPage(),
                $pagination->getSort(),
                $pagination->getSortDirection(),
                true
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
                'total' => $repo->countPurchasesByCustomerId($customer->getCustomerId(), true),
            ],
            200
        );
    }

    /**
     * Mark specific coupon as used/unused by customer.
     *
     * @Route(name="oloy.campaign.admin.customer.coupon_usage", path="/admin/customer/{customer}/campaign/{campaign}/coupon/{coupon}")
     * @Method("POST")
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
     * @param Request         $request
     * @param CustomerDetails $customer
     * @param DomainCampaign  $campaign
     * @param string          $coupon
     * @View(serializerGroups={"admin", "Default"})
     *
     * @return FosView
     */
    public function campaignCouponUsage(Request $request, CustomerDetails $customer, DomainCampaign $campaign, $coupon)
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
     * @Route(name="oloy.campaign.admin.customer.coupon_multiple_usage", path="/admin/campaign/coupons/mark_as_used")
     * @Method("POST")
     * @Security("is_granted('MARK_MULTIPLE_COUPONS_AS_USED')")
     *
     * @ApiDoc(
     *     name="mark multiple coupons as used",
     *     section="Customer Campaign",
     *     parameters={
     *          {"name"="coupons[]", "dataType"="array", "required"=true, "description"="List of coupons to mark as used"},
     *          {"name"="coupons[][used]", "dataType"="boolean", "required"=true, "description"="If coupon is used or not"},
     *          {"name"="coupons[][campaignId]", "dataType"="string", "required"=true, "description"="CampaignId value"},
     *          {"name"="coupons[][customerId]", "dataType"="string", "required"=true, "description"="CustomerId value"},
     *          {"name"="coupons[][code]", "dataType"="string", "required"=true, "description"="Coupon code"},
     *     },
     *     statusCodes={
     *       200="Returned when successful",
     *       400="Returned when data is invalid",
     *       404="Returned when customer or campaign not found"
     *     }
     * )
     *
     * @param Request                             $request
     * @param MultipleCampaignCouponUsageProvider $multipleCampaignCouponUsageProvider
     *
     * @return FosView
     * @View(serializerGroups={"admin", "Default"})
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function campaignCouponListUsage(Request $request, MultipleCampaignCouponUsageProvider $multipleCampaignCouponUsageProvider)
    {
        $coupons = $request->request->get('coupons', []);

        if (empty($coupons)) {
            throw new BadRequestHttpException($this->translator->trans('campaign.invalid_data'));
        }

        try {
            $commands = $multipleCampaignCouponUsageProvider->validateRequest($coupons);
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
}
