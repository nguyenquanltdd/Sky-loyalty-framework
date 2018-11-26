<?php
/*
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\TransactionBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class TransactionReturnDocument.
 *
 * @Annotation
 */
class TransactionReturnDocument extends Constraint
{
    /**
     * @var bool
     */
    private $isManuallyAssign;

    /**
     * TransactionReturnDocument constructor.
     *
     * @param bool $isManuallyAssign
     */
    public function __construct(bool $isManuallyAssign = false)
    {
        parent::__construct();
        $this->isManuallyAssign = $isManuallyAssign;
    }

    /**
     * getDefaultOption.
     */
    public function getDefaultOption(): bool
    {
        return $this->isManuallyAssign;
    }
}
