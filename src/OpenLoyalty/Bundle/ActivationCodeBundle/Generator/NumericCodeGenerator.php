<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\ActivationCodeBundle\Generator;

/**
 * Class NumericCodeGenerator.
 */
class NumericCodeGenerator implements CodeGenerator
{
    /**
     * Type.
     */
    const TYPE = 'num';

    /**
     * @var AlphaNumericCodeGenerator
     */
    protected $alphaNumCodeGenerator;

    /**
     * NumericCodeGenerator constructor.
     *
     * @param AlphaNumericCodeGenerator $alphaNumCodeGenerator
     */
    public function __construct(AlphaNumericCodeGenerator $alphaNumCodeGenerator)
    {
        $this->alphaNumCodeGenerator = $alphaNumCodeGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $objectType, string $objectId, int $length)
    {
        $hash = $this->alphaNumCodeGenerator->generate($objectType, $objectId, 0);
        $hash = preg_replace('/[^0-9,.]/', '', $hash);

        return (int) substr($hash,  0, $length);
    }
}
