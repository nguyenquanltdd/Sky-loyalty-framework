<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\UserBundle\Form\Type;

use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Type as Numeric;

/**
 * Class AdminSelfEditFormType.
 */
class AdminSelfEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstName', TextType::class, [
            'required' => false,
        ]);
        $builder->add('lastName', TextType::class, [
            'required' => false,
        ]);
        $builder->add('phone', TextType::class, [
            'required' => false,
            'constraints' => [
                new PhoneNumber(),
                new Numeric(['type' => 'numeric', 'message' => 'Incorrect phone number format, use +00000000000']),
            ],
        ]);
        $builder->add('email', TextType::class, [
            'required' => true,
        ]);
    }
}
