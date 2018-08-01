<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Form\Type;

use OpenLoyalty\Bundle\ActivationCodeBundle\Provider\AvailableAccountActivationMethodsChoices;
use OpenLoyalty\Bundle\ActivationCodeBundle\Service\SmsSender;
use OpenLoyalty\Bundle\SettingsBundle\Entity\StringSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Form\EventListener\ActivationMethodSubscriber;
use OpenLoyalty\Bundle\SettingsBundle\Form\EventListener\AllTimeActiveSubscriber;
use OpenLoyalty\Bundle\SettingsBundle\Form\EventListener\AllTimeNotLockedSubscriber;
use OpenLoyalty\Bundle\SettingsBundle\Form\EventListener\ExcludeDeliveryCostSubscriber;
use OpenLoyalty\Bundle\SettingsBundle\Form\EventListener\MarketingVendorSubscriber;
use OpenLoyalty\Bundle\SettingsBundle\Model\Settings;
use OpenLoyalty\Bundle\SettingsBundle\Model\TranslationsEntry;
use OpenLoyalty\Bundle\SettingsBundle\Provider\AvailableCustomerStatusesChoices;
use OpenLoyalty\Bundle\SettingsBundle\Provider\AvailableMarketingVendors;
use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Bundle\SettingsBundle\Service\TranslationsProvider;
use OpenLoyalty\Bundle\SettingsBundle\Validator\Constraints\NotEmptyValue;
use OpenLoyalty\Bundle\SettingsBundle\Validator\Constraints\ValidHexColor;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\UrlValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class SettingsFormType.
 */
class SettingsFormType extends AbstractType
{
    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * @var TranslationsProvider
     */
    protected $translationsProvider;

    /**
     * @var SmsSender|null
     */
    protected $smsGateway = null;

    /**
     * @var AvailableMarketingVendors
     */
    protected $marketingVendors;

    /**
     * @var AvailableCustomerStatusesChoices
     */
    protected $availableCustomerStatusesChoices;

    /**
     * @var AvailableAccountActivationMethodsChoices
     */
    protected $accountActivationMethodsChoices;

    /**
     * SettingsFormType constructor.
     *
     * @param SettingsManager                          $settingsManager
     * @param TranslationsProvider                     $translationsProvider
     * @param AvailableMarketingVendors                $marketingVendors
     * @param AvailableCustomerStatusesChoices         $availableCustomerStatusesChoices
     * @param AvailableAccountActivationMethodsChoices $accountActivationMethodsChoices
     */
    public function __construct(
        SettingsManager $settingsManager,
        TranslationsProvider $translationsProvider,
        AvailableMarketingVendors $marketingVendors,
        AvailableCustomerStatusesChoices $availableCustomerStatusesChoices,
        AvailableAccountActivationMethodsChoices $accountActivationMethodsChoices
    ) {
        $this->settingsManager = $settingsManager;
        $this->translationsProvider = $translationsProvider;
        $this->marketingVendors = $marketingVendors;
        $this->availableCustomerStatusesChoices = $availableCustomerStatusesChoices;
        $this->accountActivationMethodsChoices = $accountActivationMethodsChoices;
    }

    /**
     * @param SmsSender $smsGateway
     */
    public function setSmsSender(SmsSender $smsGateway)
    {
        $this->smsGateway = $smsGateway;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            $builder
                ->create('currency', SettingsChoicesType::class, [
                    'choices' => [
                        'PLN' => 'pln',
                        'USD' => 'usd',
                        'EUR' => 'eur',
                    ],
                    'constraints' => [new NotEmptyValue()],
                ])
        );
        $translations = $this->translationsProvider->getAvailableTranslationsList();
        $builder->add(
            $builder
                ->create('defaultFrontendTranslations', SettingsChoicesType::class, [
                    'choices' => array_combine(
                        array_map(function (TranslationsEntry $entry) {
                            return $entry->getName();
                        }, $translations),
                        array_map(function (TranslationsEntry $entry) {
                            return $entry->getKey();
                        }, $translations)
                    ),
                    'constraints' => [new NotEmptyValue()],
                ])
        );

        $builder->add(
            $builder
                ->create('customerStatusesEarning', SettingsChoicesType::class, [
                    'choices' => $this->availableCustomerStatusesChoices->getChoices()['choices'],
                    'multiple' => true,
                    'required' => true,
                    'constraints' => [new NotEmptyValue()],
                    'transformTo' => 'json',
                ])
        );
        $builder->add(
            $builder
                ->create('accountActivationMethod', SettingsChoicesType::class, [
                    'choices' => $this->accountActivationMethodsChoices->getChoices()['choices'],
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ])
        );
        $builder->add(
            $builder->create(
                'marketingVendorsValue',
                SettingsChoicesType::class,
                [
                    'choices' => array_keys($this->marketingVendors->getChoices()['choices']),
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
        );

        $builder->add(
            $builder
                ->create('customerStatusesSpending', SettingsChoicesType::class, [
                    'choices' => $this->availableCustomerStatusesChoices->getChoices()['choices'],
                    'multiple' => true,
                    'required' => true,
                    'constraints' => [new NotEmptyValue()],
                    'transformTo' => 'json',
                ])
        );
        $builder->add(
            $builder
                ->create('timezone', SettingsTimezoneType::class, [
                    'preferred_choices' => ['Europe/Warsaw'],
                    'constraints' => [new NotEmptyValue()],
                ])
        );
        $builder->add(
            $builder->create('programName', SettingsTextType::class, ['constraints' => [new NotEmptyValue()]])
        );
        $builder->add($builder->create('programConditionsUrl', SettingsTextType::class, ['required' => false]));
        $builder->add($builder->create('programConditionsUrl', SettingsTextType::class, ['required' => false]));
        $builder->add($builder->create('programFaqUrl', SettingsTextType::class, ['required' => false]));
        $builder->add($builder->create('programUrl', SettingsTextType::class, ['required' => false]));
        $builder->add(
            $builder
                ->create('programPointsSingular', SettingsTextType::class, [
                    'constraints' => [new NotEmptyValue()],
                ])
        );
        $builder->add(
            $builder->create('programPointsPlural', SettingsTextType::class, ['constraints' => [new NotEmptyValue()]])
        );
        $builder->add($builder->create('helpEmailAddress', SettingsTextType::class, ['required' => false]));
        $builder->add($builder->create('returns', SettingsCheckboxType::class, ['required' => false]));

        $builder->add(
            $builder
                ->create('pointsDaysActive', SettingsIntegerType::class, [
                    'required' => false,
                    'empty_data' => '',
                ])
        );
        $builder->add($builder->create('allTimeNotLocked', SettingsCheckboxType::class, ['required' => false]));
        $builder->add(
            $builder
                ->create('pointsDaysLocked', SettingsIntegerType::class, [
                    'required' => false,
                    'empty_data' => '',
                ])
        );
        $builder->add($builder->create('allTimeActive', SettingsCheckboxType::class, ['required' => false]));
        $builder->add($builder->create('webhooks', SettingsCheckboxType::class, ['required' => false]));
        $builder->add(
            $builder
                ->create('uriWebhooks', SettingsTextType::class, [
                    'required' => false,
                    'constraints' => [
                        new Callback([$this, 'checkUrl']),
                    ],
                ])
        );
        $builder->add(
            $builder
                ->create('webhookHeaderName', SettingsTextType::class, [
                    'required' => false,
                ])
        );
        $builder->add(
            $builder
                ->create('webhookHeaderValue', SettingsTextType::class, [
                    'required' => false,
                ])
        );
        $builder->add(
            $builder
                ->create('accentColor', SettingsTextType::class, [
                    'constraints' => [
                        new ValidHexColor(),
                    ],
                ])
        );
        $builder->add($builder->create('cssTemplate', SettingsTextType::class));

        $builder->add(
            $builder
                ->create('customersIdentificationPriority', SettingsCollectionType::class, [
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => CustomersIdentificationPriority::class,
                    'transformTo' => 'json',
                ])
        );

        $builder->add(
            $builder
                ->create('tierAssignType', SettingsChoicesType::class, [
                    'choices' => [
                        TierAssignTypeProvider::TYPE_POINTS => TierAssignTypeProvider::TYPE_POINTS,
                        TierAssignTypeProvider::TYPE_TRANSACTIONS => TierAssignTypeProvider::TYPE_TRANSACTIONS,
                    ],
                    'constraints' => [new NotBlank()],
                ])
        );
        $builder->add(
            $builder
                ->create('excludeDeliveryCostsFromTierAssignment', SettingsCheckboxType::class, ['required' => false])
        );
        $builder->add(
            $builder
                ->create('excludedDeliverySKUs', SettingsCollectionType::class, [
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => TextType::class,
                    'error_bubbling' => false,
                    'transformTo' => 'json',
                ])
        );
        $builder->add(
            $builder
                ->create('excludedLevelSKUs', SettingsCollectionType::class, [
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => TextType::class,
                    'transformTo' => 'json',
                ])
        );
        $builder->add(
            $builder
                ->create('excludedLevelCategories', SettingsCollectionType::class, [
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => TextType::class,
                    'transformTo' => 'json',
                ])
        );

        $builder->addEventSubscriber(new AllTimeActiveSubscriber());
        $builder->addEventSubscriber(new AllTimeNotLockedSubscriber());
        $builder->addEventSubscriber(new ExcludeDeliveryCostSubscriber());
        $builder->addEventSubscriber(new ActivationMethodSubscriber($this->smsGateway));
        $builder->addEventSubscriber(new MarketingVendorSubscriber($this->marketingVendors));

        $this->addSmsConfig($builder);
    }

    /**
     * @param StringSettingEntry        $value
     * @param ExecutionContextInterface $context
     */
    public function checkUrl($value, ExecutionContextInterface $context)
    {
        if ($value) {
            $validator = new UrlValidator();
            $validator->initialize($context);
            $validator->validate($value->getValue(), new Url());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Settings::class,
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    private function addSmsConfig(FormBuilderInterface $builder)
    {
        // no sms gateway
        if (null === $this->smsGateway) {
            return;
        }

        $fields = $this->smsGateway->getNeededSettings();

        foreach ($fields as $name => $type) {
            $builder->add($this->createField($builder, $name, $type));
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string               $name
     * @param string               $type
     *
     * @return FormBuilderInterface
     */
    private function createField(FormBuilderInterface $builder, $name, $type)
    {
        switch ($type) {
            case 'text':
                return $builder
                    ->create($name, SettingsTextType::class, []);
            case 'bool':
                return $builder
                    ->create($name, SettingsCheckboxType::class, []);
            case 'integer':
                return $builder
                    ->create($name, SettingsIntegerType::class, []);
        }
    }
}
