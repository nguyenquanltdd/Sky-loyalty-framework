<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Bundle\PointsBundle\Event\Listener;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use OpenLoyalty\Component\Account\Domain\ReadModel\PointsTransferDetails;
use OpenLoyalty\Component\Pos\Domain\Pos;
use OpenLoyalty\Component\Pos\Domain\PosRepository;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;

/**
 * Class PointsTransferSerializationListener.
 */
class PointsTransferSerializationListener implements EventSubscriberInterface
{
    /**
     * @var PosRepository
     */
    protected $posRepository;

    /**
     * @var TransactionDetailsRepository
     */
    protected $transactionDetailsRepository;

    /**
     * PointsTransferSerializationListener constructor.
     *
     * @param PosRepository                $posRepository
     * @param TransactionDetailsRepository $transactionDetailsRepository
     */
    public function __construct(
        PosRepository $posRepository,
        TransactionDetailsRepository $transactionDetailsRepository
    ) {
        $this->posRepository = $posRepository;
        $this->transactionDetailsRepository = $transactionDetailsRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            array('event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'),
        );
    }

    /**
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var PointsTransferDetails $transfer */
        $transfer = $event->getObject();

        if ($transfer instanceof PointsTransferDetails) {
            if ($transfer->getPosIdentifier()) {
                $pos = $this->posRepository->oneByIdentifier($transfer->getPosIdentifier());
                if ($pos instanceof Pos) {
                    $event->getVisitor()->addData('posName', $pos->getName());
                }
            }

            if ($transfer->getTransactionId()) {
                $transaction = $this->transactionDetailsRepository->find($transfer->getTransactionId()->__toString());
                if ($transaction instanceof TransactionDetails) {
                    $event->getVisitor()->setData('transactionDocumentNumber', $transaction->getDocumentNumber());
                }
            }
            if ($transfer->getRevisedTransactionId()) {
                $transaction = $this->transactionDetailsRepository->find($transfer->getRevisedTransactionId()->__toString());
                if ($transaction instanceof TransactionDetails) {
                    $event->getVisitor()->setData('revisedTransactionDocumentNumber', $transaction->getDocumentNumber());
                }
            }
        }
    }
}
