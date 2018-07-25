<?php

namespace OpenLoyalty\Component\Customer\Domain\ReadModel;

use Broadway\ReadModel\InMemory\InMemoryRepository;
use Broadway\ReadModel\Testing\ProjectorScenarioTestCase;
use OpenLoyalty\Component\Account\Domain\AccountId;
use OpenLoyalty\Component\Account\Domain\Event\AccountWasCreated;
use OpenLoyalty\Component\Account\Domain\Event\PointsTransferHasBeenUnlocked;
use OpenLoyalty\Component\Account\Domain\Event\PointsWereAdded;
use OpenLoyalty\Component\Account\Domain\Model\AddPointsTransfer;
use OpenLoyalty\Component\Account\Domain\PointsTransferId;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetails;
use OpenLoyalty\Component\Account\Domain\ReadModel\AccountDetailsProjector;
use OpenLoyalty\Component\Account\Domain\CustomerId;
use Broadway\ReadModel\Projector;

/**
 * Class AccountDetailsProjectorTest.
 */
class AccountDetailsProjectorTest extends ProjectorScenarioTestCase
{
    /**
     * @var AccountId
     */
    protected $accountId;

    /**
     * @var CustomerId
     */
    protected $customerId;

    /**
     * {@inheritdoc}
     */
    protected function createProjector(InMemoryRepository $repository): Projector
    {
        $this->accountId = new AccountId('00000000-0000-0000-0000-000000000000');
        $this->customerId = new CustomerId('00000000-1111-0000-0000-000000000000');

        return new AccountDetailsProjector($repository);
    }

    /**
     * @test
     */
    public function it_creates_a_read_model()
    {
        $this->scenario->given(array())
            ->when(new AccountWasCreated($this->accountId, $this->customerId))
            ->then(array(
                $this->createReadModel(),
            ));
    }

    /**
     * @test
     */
    public function it_unlocks_points()
    {
        $pointsId = new PointsTransferId('00000000-0000-0000-0000-000000000000');
        $date = new \DateTime();
        $expectedReadModel = $this->createReadModel();
        $pointsTransfer = new AddPointsTransfer($pointsId, 100, 10, 10, $date);
        $pointsTransfer->unlock();
        $expectedReadModel->addPointsTransfer($pointsTransfer);
        $this->scenario->given(array())
            ->given([
                new AccountWasCreated($this->accountId, $this->customerId),
                new PointsWereAdded($this->accountId, new AddPointsTransfer($pointsId, 100, 10, 10, $date)),
            ])
            ->when(new PointsTransferHasBeenUnlocked($this->accountId, $pointsId))
            ->then(array(
                $expectedReadModel,
            ));
    }

    private function createReadModel()
    {
        return new AccountDetails($this->accountId, $this->customerId);
    }
}
