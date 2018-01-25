<?php
/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\SettingsBundle\Validator\Constraints;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ImageValidator as BaseImageValidator;

/**
 * Class ImageValidator.
 */
class ImageValidator extends BaseImageValidator
{
    /**
     * @var int
     */
    protected $minWidth;

    /**
     * @var int
     */
    protected $minHeight;

    /**
     * ImageValidator constructor.
     *
     * @param $minWidth
     * @param $minHeight
     */
    public function __construct($minWidth, $minHeight)
    {
        $this->minWidth = $minWidth;
        $this->minHeight = $minHeight;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Image) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Image');
        }

        if (null === $constraint->minHeight) {
            $constraint->minHeight = $this->minHeight;
        }
        if (null === $constraint->minWidth) {
            $constraint->minWidth = $this->minWidth;
        }

        parent::validate($value, $constraint);
    }
}
