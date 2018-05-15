<?php

/**
 * Copyright © 2018 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Import\Infrastructure\Validator;

/**
 * Class XmlNodeValidator.
 */
class XmlNodeValidator
{
    const DATE_FORMAT = 'date';
    const DECIMAL_FORMAT = 'decimal';
    const INTEGER_FORMAT = 'integer';

    private $defaultRequirements = [
        'required' => false,
        'format' => null,
    ];

    /**
     * @param \SimpleXMLElement $element
     * @param string            $xpath
     * @param array             $requirements
     *
     * @return null|string
     */
    public function validate(\SimpleXMLElement $element, string $xpath, array $requirements = [])
    {
        $requirements = array_merge($this->defaultRequirements, $requirements);

        if ($requirements['required'] == true && empty($element->xpath($xpath))) {
            return sprintf('%s is required node', $xpath);
        }

        if ($requirements['format']) {
            $value = $element->xpath($xpath);
            if (!isset($value[0])) {
                return sprintf('%s has format specified but not value', $xpath);
            }
            $parsedValue = (string) $value[0];

            switch ($requirements['format']) {
                case self::DATE_FORMAT:
                    $dt = \DateTime::createFromFormat(DATE_ATOM, $parsedValue);
                    if (!$dt) {
                        return sprintf('%s has invalid date format (ATOM required)', $xpath);
                    }
                    break;
                case self::DECIMAL_FORMAT:
                    if (!is_numeric($parsedValue)) {
                        return sprintf('%s should be number value', $xpath);
                    }
                    break;
                case self::INTEGER_FORMAT:
                    if ((string) (int) $parsedValue != $parsedValue) {
                        return sprintf('%s should be integer value', $xpath);
                    }
                    break;
            }
        }

        return;
    }
}
