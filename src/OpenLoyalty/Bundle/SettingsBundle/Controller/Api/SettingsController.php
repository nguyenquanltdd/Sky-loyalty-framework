<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Controller\Api;

use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use OpenLoyalty\Bundle\ActivationCodeBundle\Service\SmsSender;
use OpenLoyalty\Bundle\EarningRuleBundle\Model\EarningRuleLimit;
use OpenLoyalty\Bundle\SettingsBundle\Entity\FileSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Form\Type\LogoFormType;
use OpenLoyalty\Bundle\SettingsBundle\Form\Type\SettingsFormType;
use OpenLoyalty\Bundle\SettingsBundle\Form\Type\TranslationsFormType;
use OpenLoyalty\Bundle\SettingsBundle\Model\TranslationsEntry;
use OpenLoyalty\Bundle\SettingsBundle\Service\LogoUploader;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountSystemEvents;
use OpenLoyalty\Component\Customer\Domain\Model\AccountActivationMethod;
use OpenLoyalty\Component\Customer\Domain\Model\Status;
use OpenLoyalty\Component\Customer\Domain\SystemEvent\CustomerSystemEvents;
use OpenLoyalty\Component\EarningRule\Domain\ReferralEarningRule;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\TransactionSystemEvents;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SettingsController.
 */
class SettingsController extends FOSRestController
{
    /**
     * Add logo.
     *
     * @Route(name="oloy.settings.add_logo", path="/settings/logo")
     * @Method("POST")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Add logo to loyalty program",
     *     section="Settings",
     *     input={"class" = "OpenLoyalty\Bundle\SettingsBundle\Form\Type\LogoFormType", "name" = "photo"}
     * )
     *
     * @param Request $request
     *
     * @return View
     */
    public function addLogoAction(Request $request)
    {
        return $this->addPhoto($request, LogoUploader::LOGO);
    }

    /**
     * Add small logo.
     *
     * @Route(name="oloy.settings.add_small_logo", path="/settings/small-logo")
     * @Method("POST")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Add small logo to loyalty program",
     *     section="Settings",
     *     input={"class" = "OpenLoyalty\Bundle\SettingsBundle\Form\Type\LogoFormType", "name" = "photo"}
     * )
     *
     * @param Request $request
     *
     * @return View
     */
    public function addSmallLogoAction(Request $request)
    {
        return $this->addPhoto($request, LogoUploader::SMALL_LOGO);
    }

    /**
     * Add hero image.
     *
     * @Route(name="oloy.settings.add_hero_image", path="/settings/hero-image")
     * @Method("POST")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Add hero image to loyalty program",
     *     section="Settings",
     *     input={"class" = "OpenLoyalty\Bundle\SettingsBundle\Form\Type\LogoFormType", "name" = "photo"}
     * )
     *
     * @param Request $request
     *
     * @return View
     */
    public function addHeroImageAction(Request $request)
    {
        return $this->addPhoto($request, LogoUploader::HERO_IMAGE);
    }

    /**
     * @param Request $request
     * @param string  $entryName
     *
     * @return View
     */
    private function addPhoto(Request $request, string $entryName)
    {
        $form = $this->get('form.factory')->createNamed('photo', LogoFormType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->getData()->getFile();
            $uploader = $this->get('oloy.settings.logo_uploader');

            $settingsManager = $this->get('ol.settings.manager');
            $settings = $settingsManager->getSettings();
            $logo = $settings->getEntry($entryName);
            if ($logo) {
                $uploader->remove($logo->getValue());
                $settingsManager->removeSettingByKey($entryName);
            }

            $photo = $uploader->upload($file);

            $settings->addEntry(new FileSettingEntry($entryName, $photo));
            $settingsManager->save($settings);

            return $this->view([], Response::HTTP_OK);
        }

        return $this->view($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Remove logo.
     *
     * @Route(name="oloy.settings.remove_logo", path="/settings/logo")
     * @Method("DELETE")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Delete logo",
     *     section="Settings"
     * )
     *
     * @return View
     */
    public function removeLogoAction()
    {
        return $this->removePhoto(LogoUploader::LOGO);
    }

    /**
     * Remove small logo.
     *
     * @Route(name="oloy.settings.remove_small_logo", path="/settings/small-logo")
     * @Method("DELETE")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Delete small logo",
     *     section="Settings"
     * )
     *
     * @return View
     */
    public function removeSmallLogoAction()
    {
        return $this->removePhoto(LogoUploader::SMALL_LOGO);
    }

    /**
     * Remove hero imag.
     *
     * @Route(name="oloy.settings.remove_hero_image", path="/settings/hero-image")
     * @Method("DELETE")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Delete hero image",
     *     section="Settings"
     * )
     *
     * @return View
     */
    public function removeHeroImageAction()
    {
        return $this->removePhoto(LogoUploader::HERO_IMAGE);
    }

    /**
     * @param string $entryName
     *
     * @return View
     */
    private function removePhoto(string $entryName)
    {
        $settingsManager = $this->get('ol.settings.manager');
        $settings = $settingsManager->getSettings();
        $logo = $settings->getEntry($entryName);
        if ($logo) {
            $logo = $logo->getValue();
            $uploader = $this->get('oloy.settings.logo_uploader');
            $uploader->remove($logo);
            $settingsManager->removeSettingByKey($entryName);
        }

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * Get logo.
     *
     * @Route(name="oloy.settings.get_logo", path="/settings/logo")
     * @Method("GET")
     * @ApiDoc(
     *     name="Get logo",
     *     section="Settings"
     * )
     *
     * @return Response
     */
    public function getLogoAction()
    {
        return $this->getPhoto(LogoUploader::LOGO);
    }

    /**
     * Get small logo.
     *
     * @Route(name="oloy.settings.get_small_logo", path="/settings/small-logo")
     * @Method("GET")
     * @ApiDoc(
     *     name="Get small logo",
     *     section="Settings"
     * )
     *
     * @return Response
     */
    public function getSmallLogoAction()
    {
        return $this->getPhoto(LogoUploader::SMALL_LOGO);
    }

    /**
     * Get hero image.
     *
     * @Route(name="oloy.settings.get_hero_image", path="/settings/hero-image")
     * @Method("GET")
     * @ApiDoc(
     *     name="Get hero image",
     *     section="Settings"
     * )
     *
     * @return Response
     */
    public function getHeroImageAction()
    {
        return $this->getPhoto(LogoUploader::HERO_IMAGE);
    }

    /**
     * @param string $entryName
     *
     * @return Response
     */
    private function getPhoto(string $entryName)
    {
        $settingsManager = $this->get('ol.settings.manager');
        $settings = $settingsManager->getSettings();
        $logoEntry = $settings->getEntry($entryName);
        $logo = null;

        if ($logoEntry) {
            $logo = $logoEntry->getValue();
        }
        if (!$logo) {
            throw $this->createNotFoundException();
        }

        $content = $this->get('oloy.settings.logo_uploader')->get($logo);
        if (!$content) {
            throw $this->createNotFoundException();
        }

        $response = new Response($content);
        $response->headers->set('Content-Disposition', 'inline');
        $response->headers->set('Content-Type', $logo->getMime());

        return $response;
    }

    /**
     * Method allow to update system settings.
     *
     * @Route(name="oloy.settings.edit", path="/settings")
     * @Method("POST")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Edit system settings",
     *     section="Settings",
     *     input={"class" = "OpenLoyalty\Bundle\SettingsBundle\Form\Type\SettingsFormType", "name" = "settings"},
     *     statusCodes={
     *       200="Returned when successful",
     *       400="Returned when form contains errors",
     *     }
     * )
     *
     * @param Request $request
     *
     * @return View
     */
    public function editAction(Request $request)
    {
        $settingsManager = $this->get('ol.settings.manager');

        $form = $this->get('form.factory')->createNamed('settings', SettingsFormType::class, $settingsManager->getSettings());
        $form->handleRequest($request);

        if ($form->isValid()) {
            $settingsManager->save($form->getData());

            return $this->view([
                'settings' => $form->getData()->toArray(),
            ]);
        }

        return $this->view($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Method will return all system settings.
     *
     * @Route(name="oloy.settings.get", path="/settings")
     * @Method("GET")
     * @Security("is_granted('VIEW_SETTINGS')")
     * @ApiDoc(
     *     name="Get system settings",
     *     section="Settings"
     * )
     *
     * @return View
     */
    public function getAction()
    {
        $settingsManager = $this->get('ol.settings.manager');

        return $this->view([
            'settings' => $settingsManager->getSettings()->toArray(),
        ], 200);
    }

    /**
     * Method will return current translations.
     *
     * @Route(name="oloy.settings.translations", path="/translations")
     * @Method("GET")
     * @ApiDoc(
     *     name="Get translations",
     *     section="Settings"
     * )
     *
     * @return Response
     */
    public function translationsAction()
    {
        $translationsProvider = $this->get('ol.settings.translations');

        return new Response($translationsProvider->getCurrentTranslations()->getContent(), Response::HTTP_OK, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Method will return list of available translations.
     *
     * @Route(name="oloy.settings.translations_list", path="/admin/translations")
     * @Method("GET")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Get translations list",
     *     section="Settings"
     * )
     *
     * @return View
     */
    public function listTranslationsAction()
    {
        $translations = $this->get('ol.settings.translations')->getAvailableTranslationsList();

        return $this->view(
            [
                'translations' => $translations,
                'total' => count($translations),
            ],
            200
        );
    }

    /**
     * Method will return list of available customer statuses.
     *
     * @Route(name="oloy.settings.customer_statuses_list", path="/admin/customer-statuses")
     * @Method("GET")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Get customer statuses list",
     *     section="Settings"
     * )
     *
     * @return View
     */
    public function listCustomerStatusesAction()
    {
        $statuses = Status::getAvailableStatuses();

        return $this->view(
            [
                'statuses' => $statuses,
                'total' => count($statuses),
            ],
            200
        );
    }

    /**
     * Method will return translations<br/> You must provide translation key, available keys can be obtained by /admin/translations endpoint.
     *
     * @Route(name="oloy.settings.translations_get", path="/admin/translations/{key}")
     * @Method("GET")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Get single translation by key",
     *     section="Settings"
     * )
     *
     * @param $key
     *
     * @return View
     */
    public function getTranslationByKeyAction($key)
    {
        $translationsEntry = $this->get('ol.settings.translations')->getTranslationsByKey($key);

        return $this->view($translationsEntry, 200);
    }

    /**
     * Method allows to update specific translations.
     *
     * @Route(name="oloy.settings.translations_update", path="/admin/translations/{key}")
     * @Method("PUT")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Update single translation by key",
     *     section="Settings"
     * )
     *
     * @param Request $request
     * @param $key
     *
     * @return View
     */
    public function updateTranslationByKeyAction(Request $request, $key)
    {
        $provider = $this->get('ol.settings.translations');
        if (!$provider->hasTranslation($key)) {
            throw $this->createNotFoundException();
        }
        $entry = new TranslationsEntry($key);
        $form = $this->get('form.factory')->createNamed('translation', TranslationsFormType::class, $entry, [
            'method' => 'PUT',
            'validation_groups' => ['edit', 'Default'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $provider->save($entry, $key);

            return $this->view($entry, Response::HTTP_OK);
        }

        return $this->view($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Method allows to create new translations.
     *
     * @Route(name="oloy.settings.translations_create", path="/admin/translations")
     * @Method("POST")
     * @Security("is_granted('EDIT_SETTINGS')")
     * @ApiDoc(
     *     name="Create single translation",
     *     section="Settings",
     *     input={"class"="OpenLoyalty\Bundle\SettingsBundle\Form\Type\TranslationsFormType", "name"="translation"},
     *     statusCodes={
     *       200="Returned when successful",
     *       400="Returned when form contains errors",
     *     }
     * )
     *
     * @param Request $request
     *
     * @return View
     */
    public function createTranslationAction(Request $request)
    {
        $provider = $this->get('ol.settings.translations');
        $form = $this->get('form.factory')->createNamed('translation', TranslationsFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entry = $form->getData();
            $provider->save($entry);

            return $this->view($entry, Response::HTTP_OK);
        }

        return $this->view($form->getErrors(), Response::HTTP_BAD_REQUEST);
    }

    /**
     * Method will return activation method (email|sms).
     *
     * @Route(name="oloy.settings.get_activation_method", path="/settings/activation-method")
     * @Method("GET")
     * @ApiDoc(
     *     name="Get activation method",
     *     section="Settings"
     * )
     *
     * @return View
     */
    public function getActivationMethodAction()
    {
        return $this->view(['method' => $this->get('oloy.action_token_manager')->getCurrentMethod()]);
    }

    /**
     * Method will return some data needed for specific select fields.
     *
     * @Route(name="oloy.settings.get_form_choices", path="/settings/choices/{type}")
     * @Method("GET")
     * @Security("is_granted('VIEW_SETTINGS_CHOICES')")
     * @ApiDoc(
     *     name="Get choices",
     *     section="Settings",
     *     parameters={{"name"="type", "description"="allowed types: timezone, language, country, availableFrontendTranslations, earningRuleLimitPeriod, availableCustomerStatuses, availableAccountActivationMethods", "dataType"="string", "required"=true}}
     * )
     *
     * @param $type
     *
     * @return View
     */
    public function getChoicesAction($type)
    {
        if ($type == 'promotedEvents') {
            return $this->view(['choices' => [
                'Customer logged in' => CustomerSystemEvents::CUSTOMER_LOGGED_IN,
                'First purchase' => TransactionSystemEvents::CUSTOMER_FIRST_TRANSACTION,
                'Account created' => AccountSystemEvents::ACCOUNT_CREATED,
                'Newsletter subscription' => CustomerSystemEvents::NEWSLETTER_SUBSCRIPTION,
            ]], 200);
        }

        if ($type == 'language') {
            $type = new LanguageType();
            $choiceList = $type->loadChoiceList();
            $choices = $choiceList ? $choiceList->getStructuredValues() : [];

            return $this->view(['choices' => $choices], 200);
        } elseif ($type == 'country') {
            $type = new CountryType();
            $choiceList = $type->loadChoiceList();
            $choices = $choiceList ? $choiceList->getStructuredValues() : [];

            return $this->view(['choices' => $choices], 200);
        } elseif ($type == 'timezone') {
            $type = new TimezoneType();
            $choiceList = $type->loadChoiceList();
            $choices = $choiceList ? $choiceList->getStructuredValues() : [];

            return $this->view(['choices' => $choices], 200);
        } elseif ($type == 'availableFrontendTranslations') {
            $availableTranslationsList = $this->get('ol.settings.translations')->getAvailableTranslationsList();

            return $this->view(
                [
                    'choices' => $availableTranslationsList,
                ]
            );
        } elseif ($type == 'availableCustomerStatuses') {
            $availableCustomerStatusesList = Status::getAvailableStatuses();

            return $this->view(
                [
                    'choices' => $availableCustomerStatusesList,
                ]
            );
        } elseif ($type == 'availableAccountActivationMethods') {
            $availableAccountActivationMethodsList = AccountActivationMethod::getAvailableMethods();
            if (!$this->container->has('oloy.activation.sms_gateway')) {
                if (($key = array_search(AccountActivationMethod::METHOD_SMS, $availableAccountActivationMethodsList)) !== false) {
                    unset($availableAccountActivationMethodsList[$key]);
                }
            }

            return $this->view(
                [
                    'choices' => $availableAccountActivationMethodsList,
                ]
            );
        } elseif ($type == 'smsGatewayConfig') {
            $fields = [];

            if ($this->container->has('oloy.activation.sms_gateway')) {
                /** @var SmsSender $gateway */
                $gateway = $this->get('oloy.activation.sms_gateway');
                $fields = $gateway->getNeededSettings();
            }

            return $this->view(
                [
                    'fields' => $fields,
                ]
            );
        } elseif ($type == 'earningRuleLimitPeriod') {
            return $this->view(['choices' => [
                '1 day' => EarningRuleLimit::PERIOD_DAY,
                '1 week' => EarningRuleLimit::PERIOD_WEEK,
                '1 month' => EarningRuleLimit::PERIOD_MONTH,
            ]], 200);
        } elseif ($type == 'referralEvents') {
            return $this->view(['choices' => [
                ReferralEarningRule::EVENT_REGISTER => ReferralEarningRule::EVENT_REGISTER,
                ReferralEarningRule::EVENT_FIRST_PURCHASE => ReferralEarningRule::EVENT_FIRST_PURCHASE,
                ReferralEarningRule::EVENT_EVERY_PURCHASE => ReferralEarningRule::EVENT_EVERY_PURCHASE,
            ]], 200);
        } elseif ($type == 'referralTypes') {
            return $this->view(['choices' => [
                ReferralEarningRule::TYPE_REFERRED => ReferralEarningRule::TYPE_REFERRED,
                ReferralEarningRule::TYPE_REFERRER => ReferralEarningRule::TYPE_REFERRER,
                ReferralEarningRule::TYPE_BOTH => ReferralEarningRule::TYPE_BOTH,
            ]], 200);
        } else {
            throw $this->createNotFoundException();
        }
    }
}
