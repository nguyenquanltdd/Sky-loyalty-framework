<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Form\Type;

use OpenLoyalty\Bundle\ActivationCodeBundle\Service\SmsSender;
use OpenLoyalty\Bundle\SettingsBundle\Entity\StringSettingEntry;
use OpenLoyalty\Bundle\SettingsBundle\Form\DataTransformer\BooleanSettingDataTransformer;
use OpenLoyalty\Bundle\SettingsBundle\Form\DataTransformer\ChoicesToJsonSettingDataTransformer;
use OpenLoyalty\Bundle\SettingsBundle\Form\DataTransformer\IntegerSettingDataTransformer;
use OpenLoyalty\Bundle\SettingsBundle\Form\DataTransformer\StringSettingDataTransformer;
use OpenLoyalty\Bundle\SettingsBundle\Model\Settings;
use OpenLoyalty\Bundle\SettingsBundle\Model\TranslationsEntry;
use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Bundle\SettingsBundle\Service\TranslationsProvider;
use OpenLoyalty\Component\Customer\Domain\Model\AccountActivationMethod;
use OpenLoyalty\Component\Customer\Domain\Model\Status;
use OpenLoyalty\Component\Customer\Infrastructure\TierAssignTypeProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

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
     * SettingsFormType constructor.
     *
     * @param SettingsManager      $settingsManager
     * @param TranslationsProvider $translationsProvider
     */
    public function __construct(SettingsManager $settingsManager, TranslationsProvider $translationsProvider)
    {
        $this->settingsManager = $settingsManager;
        $this->translationsProvider = $translationsProvider;
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
                ->create('currency', ChoiceType::class, [
                    'choices' => [
                        'PLN' => 'pln',
                        'USD' => 'usd',
                        'EUR' => 'eur',
                    ],
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new StringSettingDataTransformer('currency', $this->settingsManager))
        );
        $translations = $this->translationsProvider->getAvailableTranslationsList();
        $builder->add(
            $builder
                ->create('defaultFrontendTranslations', ChoiceType::class, [
                    'choices' => array_combine(
                        array_map(function (TranslationsEntry $entry) {
                            return $entry->getName();
                        }, $translations),
                        array_map(function (TranslationsEntry $entry) {
                            return $entry->getKey();
                        }, $translations)
                    ),
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new StringSettingDataTransformer('defaultFrontendTranslations', $this->settingsManager))
        );

        $builder->add(
            $builder
                ->create('customerStatusesEarning', ChoiceType::class, [
                    'choices' => array_combine(Status::getAvailableStatuses(), Status::getAvailableStatuses()),
                    'multiple' => true,
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new ChoicesToJsonSettingDataTransformer('customerStatusesEarning', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('accountActivationMethod', ChoiceType::class, [
                    'choices' => array_combine(AccountActivationMethod::getAvailableMethods(), AccountActivationMethod::getAvailableMethods()),
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new StringSettingDataTransformer('accountActivationMethod', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('customerStatusesSpending', ChoiceType::class, [
                    'choices' => array_combine(Status::getAvailableStatuses(), Status::getAvailableStatuses()),
                    'multiple' => true,
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new ChoicesToJsonSettingDataTransformer('customerStatusesSpending', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('timezone', TimezoneType::class, [
                    'preferred_choices' => ['Europe/Warsaw'],
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new StringSettingDataTransformer('timezone', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('programName', TextType::class, [
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new StringSettingDataTransformer('programName', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('programConditionsUrl', TextType::class, [
                    'required' => false,
                ])
                ->addModelTransformer(new StringSettingDataTransformer('programConditionsUrl', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('programConditionsUrl', TextType::class, [
                    'required' => false,
                ])
                ->addModelTransformer(new StringSettingDataTransformer('programConditionsUrl', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('programFaqUrl', TextType::class, [
                    'required' => false,
                ])
                ->addModelTransformer(new StringSettingDataTransformer('programFaqUrl', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('programUrl', TextType::class, [
                    'required' => false,
                ])
                ->addModelTransformer(new StringSettingDataTransformer('programUrl', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('programPointsSingular', TextType::class, [
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new StringSettingDataTransformer('programPointsSingular', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('programPointsPlural', TextType::class, [
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new StringSettingDataTransformer('programPointsPlural', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('helpEmailAddress', TextType::class, [
                    'required' => false,
                ])
                ->addModelTransformer(new StringSettingDataTransformer('helpEmailAddress', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('returns', CheckboxType::class, [
                    'required' => false,
                ])
                ->addModelTransformer(new BooleanSettingDataTransformer('returns', $this->settingsManager))
        );

        $builder->add(
            $builder
                ->create('pointsDaysActive', IntegerType::class, [
                    'required' => false,
                    'empty_data' => '',
                ])
                ->addModelTransformer(new IntegerSettingDataTransformer('pointsDaysActive', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('allTimeActive', CheckboxType::class, [
                    'required' => false,
                ])
                ->addModelTransformer(new BooleanSettingDataTransformer('allTimeActive', $this->settingsManager))
        );
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (!$data instanceof Settings) {
                return;
            }
            $allTime = $data->getEntry('allTimeActive');
            if (!$allTime || !$allTime->getValue()) {
                $days = $data->getEntry('pointsDaysActive');
                if (!$days || !$days->getValue()) {
                    $event->getForm()->get('pointsDaysActive')->addError(new FormError((new NotBlank())->message));
                }
            }
            $excludeDeliveryCosts = $data->getEntry('excludeDeliveryCostsFromTierAssignment');
            if ($excludeDeliveryCosts && $excludeDeliveryCosts->getValue()) {
                $ex = $data->getEntry('excludedDeliverySKUs');

                if (!$ex || !$ex->getValue() || count($ex->getValue()) == 0) {
                    $event->getForm()->get('excludedDeliverySKUs')->addError(new FormError((new NotBlank())->message));
                }
            }
        });
        // validate sms sender credentials if any
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            /** @var StringSettingEntry $activationMethod */
            $activationMethod = $data->getEntry('accountActivationMethod');

            if (!$data instanceof Settings ||
                null === $this->smsGateway ||
                $activationMethod->getValue() !== AccountActivationMethod::METHOD_SMS
            ) {
                return;
            }

            $areCredentialsValid = $this->smsGateway->validateCredentials($data->toArray());
            if (false === $areCredentialsValid) {
                $event->getForm()->get('accountActivationMethod')->addError(new FormError('Bad credentials'));
            }
        });
        $builder->add(
            $builder
                ->create('customersIdentificationPriority', CollectionType::class, [
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => CustomersIdentificationPriority::class,
                ])
                ->addModelTransformer(new ChoicesToJsonSettingDataTransformer('customersIdentificationPriority', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('tierAssignType', ChoiceType::class, [
                    'choices' => [
                        TierAssignTypeProvider::TYPE_POINTS => TierAssignTypeProvider::TYPE_POINTS,
                        TierAssignTypeProvider::TYPE_TRANSACTIONS => TierAssignTypeProvider::TYPE_TRANSACTIONS,
                    ],
                    'constraints' => [new NotBlank()],
                ])
                ->addModelTransformer(new StringSettingDataTransformer('tierAssignType', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('excludeDeliveryCostsFromTierAssignment', CheckboxType::class, [
                    'required' => false,
                ])
                ->addModelTransformer(new BooleanSettingDataTransformer('excludeDeliveryCostsFromTierAssignment', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('excludedDeliverySKUs', CollectionType::class, [
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => TextType::class,
                    'error_bubbling' => false,
                ])
                ->addModelTransformer(new ChoicesToJsonSettingDataTransformer('excludedDeliverySKUs', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('excludedLevelSKUs', CollectionType::class, [
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => TextType::class,
                ])
                ->addModelTransformer(new ChoicesToJsonSettingDataTransformer('excludedLevelSKUs', $this->settingsManager))
        );
        $builder->add(
            $builder
                ->create('excludedLevelCategories', CollectionType::class, [
                    'required' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_type' => TextType::class,
                ])
                ->addModelTransformer(new ChoicesToJsonSettingDataTransformer('excludedLevelCategories', $this->settingsManager))
        );
        $this->addSmsConfig($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OpenLoyalty\Bundle\SettingsBundle\Model\Settings',
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
                    ->create($name, TextType::class, [])
                    ->addModelTransformer(new StringSettingDataTransformer($name, $this->settingsManager));
            case 'bool':
                return $builder
                    ->create($name, CheckboxType::class, [])
                    ->addModelTransformer(new BooleanSettingDataTransformer($name, $this->settingsManager));
            case 'integer':
                return $builder
                    ->create($name, IntegerType::class, [])
                    ->addModelTransformer(new IntegerSettingDataTransformer($name, $this->settingsManager));
        }
    }
}
