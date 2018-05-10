<?php

namespace OpenLoyalty\Bundle\ImportBundle\Tests\Unit\Importer;

use OpenLoyalty\Component\Import\Infrastructure\Validator\XmlNodeValidator;

/**
 * Class XmlNodeValidatorTest.
 */
class XmlNodeValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_validate_required_success()
    {
        $node = new \SimpleXMLElement('<transaction><item>Value</item></transaction>');

        $nodeValidator = new XmlNodeValidator();
        $result = $nodeValidator->validate($node, 'item', ['required' => true]);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_validate_not_required_failed()
    {
        $node = new \SimpleXMLElement('<transaction><item2>Value</item2></transaction>');

        $nodeValidator = new XmlNodeValidator();
        $result = $nodeValidator->validate($node, 'item', ['required' => true]);

        $this->assertTrue($result == 'item is required node');
    }

    /**
     * @test
     */
    public function it_validate_date_format_success()
    {
        $node = new \SimpleXMLElement('<transaction><item>2005-08-15T15:52:01+00:00</item></transaction>');

        $nodeValidator = new XmlNodeValidator();
        $result = $nodeValidator->validate(
            $node,
            'item',
            ['required' => true, 'format' => XmlNodeValidator::DATE_FORMAT]
        );

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_validate_date_format_failed()
    {
        $node = new \SimpleXMLElement('<transaction><item>2005-08-15</item></transaction>');

        $nodeValidator = new XmlNodeValidator();
        $result = $nodeValidator->validate(
            $node,
            'item',
            ['required' => true, 'format' => XmlNodeValidator::DATE_FORMAT]
        );

        $this->assertTrue($result == 'item has invalid date format (ATOM required)');
    }

    /**
     * @test
     */
    public function it_validate_decimal_format_success()
    {
        $node = new \SimpleXMLElement('<transaction><item>233.55</item></transaction>');

        $nodeValidator = new XmlNodeValidator();
        $result = $nodeValidator->validate(
            $node,
            'item',
            ['required' => true, 'format' => XmlNodeValidator::DECIMAL_FORMAT]
        );

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_validate_decimal_format_failed()
    {
        $node = new \SimpleXMLElement('<transaction><item>445,33</item></transaction>');

        $nodeValidator = new XmlNodeValidator();
        $result = $nodeValidator->validate(
            $node,
            'item',
            ['required' => true, 'format' => XmlNodeValidator::DECIMAL_FORMAT]
        );

        $this->assertTrue($result == 'item should be number value');
    }

    /**
     * @test
     */
    public function it_validate_integer_format_success()
    {
        $node = new \SimpleXMLElement('<transaction><item>44</item></transaction>');

        $nodeValidator = new XmlNodeValidator();
        $result = $nodeValidator->validate(
            $node,
            'item',
            ['required' => true, 'format' => XmlNodeValidator::INTEGER_FORMAT]
        );

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_validate_integer_format_failed()
    {
        $node = new \SimpleXMLElement('<transaction><item>445.44</item></transaction>');

        $nodeValidator = new XmlNodeValidator();
        $result = $nodeValidator->validate(
            $node,
            'item',
            ['required' => true, 'format' => XmlNodeValidator::INTEGER_FORMAT]
        );

        $this->assertTrue($result == 'item should be integer value');
    }
}
