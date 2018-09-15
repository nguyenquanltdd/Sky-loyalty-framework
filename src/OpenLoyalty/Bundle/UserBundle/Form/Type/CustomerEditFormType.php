<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CustomerEditFormType.
 */
class CustomerEditFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CustomerRegistrationFormType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fieldNames = ['firstName', 'lastName', 'agreement1'];

        // remove required and NotBlank constraints
        foreach ($fieldNames as $fieldName) {
            $field = $builder->get($fieldName);
            $options = $field->getOptions();
            $options['required'] = false;
            $options['constraints'] = array_filter($options['constraints'], function ($constraint) {
                return !($constraint instanceof NotBlank);
            });
            $fieldType = $field->getType()->getInnerType();
            $builder->add($fieldName, get_class($fieldType), $options);
        }

        // change user-supplied null values to empty arrays in compound fields
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();

            if (array_key_exists('address', $data)
                && empty($data['address'])) {
                $data['address'] = [];
                $event->setData($data);
            }

            if (array_key_exists('company', $data)
                && empty($data['company'])) {
                $data['company'] = [];
                $event->setData($data);
            }
        });
    }
}
