<?php

/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\TransactionBundle\Import;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use OpenLoyalty\Component\Import\Infrastructure\AbstractXMLImportConverter;
use OpenLoyalty\Component\Import\Infrastructure\Validator\XmlNodeValidator;
use OpenLoyalty\Component\Transaction\Domain\Command\RegisterTransaction;
use OpenLoyalty\Component\Transaction\Domain\PosId;
use OpenLoyalty\Component\Transaction\Domain\TransactionId;

/**
 * Class TransactionXmlImportConverter.
 */
class TransactionXmlImportConverter extends AbstractXMLImportConverter
{
    /** @var  UuidGeneratorInterface */
    protected $uuidGenerator;

    /**
     * TransactionXmlImportConverter constructor.
     *
     * @param UuidGeneratorInterface $uuidGenerator
     */
    public function __construct(UuidGeneratorInterface $uuidGenerator)
    {
        $this->uuidGenerator = $uuidGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(\SimpleXMLElement $element)
    {
        $this->checkValidNodes(
            $element,
            [
                'documentNumber' => ['required' => true],
                'purchaseDate' => ['format' => XmlNodeValidator::DATE_FORMAT, 'required' => true],
                'customer' => ['required' => true],
                'documentType' => ['required' => true],
                'items' => ['required' => true],
                'customer/name' => ['required' => true],
            ]
        );

        $transactionData = [
            'documentNumber' => (string) $element->{'documentNumber'},
            'purchasePlace' => (string) $element->{'purchasePlace'},
            'purchaseDate' => \DateTime::createFromFormat(DATE_ATOM, (string) $element->{'purchaseDate'}),
            'documentType' => (string) $element->{'documentType'},
        ];

        $customerData = [
            'name' => (string) $element->{'customer'}->{'name'},
            'email' => (string) $element->{'customer'}->{'email'},
            'nip' => (string) $element->{'customer'}->{'nip'},
            'phone' => (string) $element->{'customer'}->{'phone'},
            'loyaltyCardNumber' => (string) $element->{'customer'}->{'loyaltyCardNumber'},
            'address' => [
                'street' => (string) $element->{'customer'}->{'address'}->{'street'},
                'address1' => (string) $element->{'customer'}->{'address'}->{'address1'},
                'city' => (string) $element->{'customer'}->{'address'}->{'city'},
                'country' => (string) $element->{'customer'}->{'address'}->{'country'},
                'province' => (string) $element->{'customer'}->{'address'}->{'province'},
                'postal' => (string) $element->{'customer'}->{'address'}->{'postal'},
            ],
        ];

        $items = [];
        foreach ($element->{'items'}->children() as $item) {
            $labels = [];
            foreach ($item->{'labels'}->children() as $label) {
                $labels[] = [
                    'key' => (string) $label->{'key'},
                    'value' => (string) $label->{'value'},
                ];
            }

            $items[] = [
                'sku' => ['code' => (string) $item->{'sku'}->{'code'}],
                'name' => (string) $item->{'name'},
                'quantity' => (string) $item->{'quantity'},
                'grossValue' => (string) $item->{'grossValue'},
                'category' => (string) $item->{'category'},
                'maker' => (string) $item->{'maker'},
                'labels' => $labels,
            ];
        }

        return new RegisterTransaction(
            new TransactionId($this->uuidGenerator->generate()),
            $transactionData,
            $customerData,
            $items,
            new PosId((string) $element->{'posId'})
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(\SimpleXMLElement $element): string
    {
        return (string) $element->{'documentNumber'};
    }
}
