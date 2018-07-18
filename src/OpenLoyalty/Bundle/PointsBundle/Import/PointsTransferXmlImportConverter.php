<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\PointsBundle\Import;

use Broadway\ReadModel\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use OpenLoyalty\Bundle\PointsBundle\Service\PointsTransfersManager;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\Command\AddPoints;
use OpenLoyalty\Component\Account\Domain\Command\SpendPoints;
use OpenLoyalty\Component\Account\Domain\Model\PointsTransfer;
use OpenLoyalty\Component\Account\Domain\Model\SpendPointsTransfer;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetails;
use OpenLoyalty\Component\Import\Infrastructure\AbstractXMLImportConverter;
use OpenLoyalty\Component\Import\Infrastructure\Validator\XmlNodeValidator;

/**
 * Class PointsTransferXmlImportConverter.
 */
class PointsTransferXmlImportConverter extends AbstractXMLImportConverter
{
    /** @var  UuidGeneratorInterface */
    protected $uuidGenerator;

    /** @var  Repository */
    protected $accountRepository;

    /** @var PointsTransfersManager */
    protected $pointsTransfersManager;

    /**
     * PointsTransferXmlImportConverter constructor.
     *
     * @param UuidGeneratorInterface $uuidGenerator
     * @param Repository             $accountRepository
     * @param PointsTransfersManager $manager
     */
    public function __construct(
        UuidGeneratorInterface $uuidGenerator,
        Repository $accountRepository,
        PointsTransfersManager $manager
    ) {
        $this->uuidGenerator = $uuidGenerator;
        $this->accountRepository = $accountRepository;
        $this->pointsTransfersManager = $manager;
    }

    /**
     * @param $customerId
     *
     * @return AccountDetails
     *
     * @throws \Exception
     */
    protected function getAccountBy($customerId): AccountDetails
    {
        $accounts = $this->accountRepository->findBy(['customerId' => $customerId]);

        if (count($accounts) == 0) {
            throw new \Exception('Account does not exist for given customer');
        }

        return reset($accounts);
    }

    /**
     * {@inheritdoc}
     */
    public function convert(\SimpleXMLElement $element)
    {
        $this->checkValidNodes(
            $element,
            [
                'points' => ['format' => XmlNodeValidator::INTEGER_FORMAT, 'required' => true],
                'customerId' => ['required' => true],
                'type' => ['required' => true],
                'validityDuration' => ['required' => true],
            ]
        );

        $transferType = (string) $element->{'type'};
        $account = $this->getAccountBy((string) $element->{'customerId'});
        $pointsTransferId = new PointsTransferId($this->uuidGenerator->generate());

        switch ($transferType) {
            case PointsTransferDetails::TYPE_ADDING:
                return new AddPoints(
                    new AccountId($account->getId()),
                    $this->pointsTransfersManager->createAddPointsTransferInstance(
                        $pointsTransferId,
                        (string) $element->{'points'},
                        null,
                        false,
                        null,
                        (string) $element->{'comment'},
                        PointsTransfer::ISSUER_ADMIN
                    )
                );
            case PointsTransferDetails::TYPE_SPENDING:
                return new SpendPoints(
                    $account->getAccountId(),
                    new SpendPointsTransfer(
                        $pointsTransferId,
                        (string) $element->{'points'},
                        null,
                        false,
                        (string) $element->{'comment'},
                        PointsTransfer::ISSUER_ADMIN
                    )
                );
                break;
        }

        throw new \InvalidArgumentException(sprintf('type = %s', $transferType));
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(\SimpleXMLElement $element): string
    {
        return sprintf(
            '%s/(%s %s)',
            (string) $element->{'customerId'},
            (string) $element->{'type'},
            (string) $element->{'points'}
        );
    }
}
