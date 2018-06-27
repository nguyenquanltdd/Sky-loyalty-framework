<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\CampaignBundle\Form\Type;

use OpenLoyalty\Bundle\CampaignBundle\Form\DataTransformer\CouponsDataTransformer;
use OpenLoyalty\Bundle\CampaignBundle\Form\DataTransformer\LevelsDataTransformer;
use OpenLoyalty\Bundle\CampaignBundle\Form\DataTransformer\SegmentsDataTransformer;
use OpenLoyalty\Bundle\CampaignBundle\Model\Campaign;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Class CampaignFormType.
 */
class CampaignFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $rewardTypes = [
            Campaign::REWARD_TYPE_DISCOUNT_CODE,
            Campaign::REWARD_TYPE_EVENT_CODE,
            Campaign::REWARD_TYPE_FREE_DELIVERY_CODE,
            Campaign::REWARD_TYPE_GIFT_CODE,
            Campaign::REWARD_TYPE_VALUE_CODE,
            Campaign::REWARD_TYPE_CASHBACK,
        ];

        $builder->add($builder->create('coupons', CollectionType::class, [
            'entry_type' => TextType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'error_bubbling' => false,
        ])->addModelTransformer(new CouponsDataTransformer()));

        $builder->add('reward', ChoiceType::class, [
            'choices' => array_combine($rewardTypes, $rewardTypes),
            'required' => true,
            'constraints' => [new NotBlank()],
        ]);
        $builder->add('name', TextType::class, [
            'required' => true,
            'constraints' => [new NotBlank()],
        ]);
        $builder->add('shortDescription', TextareaType::class, [
            'required' => false,
        ]);
        $builder->add('moreInformationLink', TextareaType::class, [
            'required' => false,
            'constraints' => [new Url()],
        ]);
        $builder->add('conditionsDescription', TextareaType::class, [
            'required' => false,
        ]);

        $builder->add('rewardValue', NumberType::class, [
            'scale' => 2,
            'required' => false,
            'constraints' => [new Optional(), new GreaterThan(['value' => 0]), new LessThan(['value' => 999999999])],
        ]);

        $builder->add('tax', NumberType::class, [
            'required' => false,
            'constraints' => [new Optional(), new GreaterThanOrEqual(['value' => 0]), new LessThan(['value' => 99])],
        ]);

        $builder->add('taxPriceValue', NumberType::class, [
            'scale' => 2,
            'required' => false,
            'constraints' => [new Optional(), new GreaterThan(['value' => 0]), new LessThan(['value' => 999999999])],
        ]);

        $builder->add('usageInstruction', TextareaType::class, [
            'required' => false,
        ]);
        $builder->add('active', CheckboxType::class, [
            'required' => false,
        ]);

        $builder->add('target', ChoiceType::class, [
            'required' => false,
            'choices' => [
                'level' => 'level',
                'segment' => 'segment',
            ],
            'mapped' => false,
        ]);
        $builder->add(
            $builder->create('levels', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])->addModelTransformer(new LevelsDataTransformer())
        );
        $builder->add(
            $builder->create('segments', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'error_bubbling' => false,
            ])->addModelTransformer(new SegmentsDataTransformer())
        );

        $builder->add('campaignActivity', CampaignActivityFormType::class, [
            'constraints' => [new Valid()],
        ]);

        $builder->add('labels', LabelsFormType::class, [
            'required' => false,
        ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'adjustCampaignForm']);
    }

    /**
     * @param FormEvent $event
     */
    public function adjustCampaignForm(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (isset($data['reward']) && $data['reward'] == Campaign::REWARD_TYPE_CASHBACK) {
            $form->add('pointValue', NumberType::class, [
                'scale' => 2,
                'required' => false,
                'constraints' => [new NotBlank()],
            ]);
        } else {
            $form->add('costInPoints', NumberType::class, [
                'scale' => 2,
                'required' => false,
                'constraints' => [new NotBlank()],
            ]);
            $form->add('unlimited', CheckboxType::class, [
                'required' => false,
            ]);
            $form->add('singleCoupon', CheckboxType::class, [
                'required' => false,
            ]);
            $form->add('limit', IntegerType::class, [
                'required' => false,
            ]);
            $form->add('limitPerUser', IntegerType::class, [
                'required' => false,
            ]);

            $form->add('campaignVisibility', CampaignVisibilityFormType::class, [
                'constraints' => [new Valid()],
            ]);
        }

        if (!isset($data['target'])) {
            return;
        }
        $target = $data['target'];
        if ($target == 'level') {
            $data['segments'] = [];
        } elseif ($target == 'segment') {
            $data['levels'] = [];
        }
        $event->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Campaign::class,
        ]);
    }
}
