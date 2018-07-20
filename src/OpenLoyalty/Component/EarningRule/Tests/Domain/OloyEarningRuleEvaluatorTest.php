<?php

namespace OpenLoyalty\Component\EarningRule\Tests\Domain;

use OpenLoyalty\Bundle\SettingsBundle\Service\SettingsManager;
use OpenLoyalty\Component\Account\Domain\SystemEvent\AccountSystemEvents;
use OpenLoyalty\Component\Account\Domain\TransactionId;
use OpenLoyalty\Component\Core\Domain\Model\LabelMultiplier;
use OpenLoyalty\Component\Customer\Domain\CustomerId;
use OpenLoyalty\Component\Customer\Domain\Model\Status;
use OpenLoyalty\Component\Customer\Domain\ReadModel\InvitationDetailsRepository;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\EarningRuleAlgorithmFactoryInterface;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\MultiplyPointsForProductRuleAlgorithm;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\MultiplyPointsByProductLabelsRuleAlgorithm;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\PointsEarningRuleAlgorithm;
use OpenLoyalty\Component\EarningRule\Domain\Algorithm\ProductPurchaseEarningRuleAlgorithm;
use OpenLoyalty\Component\Core\Domain\Model\Label;
use OpenLoyalty\Component\Core\Domain\Model\SKU;
use OpenLoyalty\Component\EarningRule\Domain\EarningRuleId;
use OpenLoyalty\Component\EarningRule\Domain\EarningRuleRepository;
use OpenLoyalty\Component\EarningRule\Domain\EventEarningRule;
use OpenLoyalty\Component\EarningRule\Domain\MultiplyPointsByProductLabelsEarningRule;
use OpenLoyalty\Component\EarningRule\Domain\MultiplyPointsForProductEarningRule;
use OpenLoyalty\Component\EarningRule\Domain\OloyEarningRuleEvaluator;
use OpenLoyalty\Component\EarningRule\Domain\PointsEarningRule;
use OpenLoyalty\Component\EarningRule\Domain\PosId;
use OpenLoyalty\Component\EarningRule\Domain\ProductPurchaseEarningRule;
use OpenLoyalty\Component\Transaction\Domain\Model\Item;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetails;
use OpenLoyalty\Component\Transaction\Domain\ReadModel\TransactionDetailsRepository;
use OpenLoyalty\Component\Segment\Domain\ReadModel\SegmentedCustomersRepository;
use OpenLoyalty\Component\Customer\Domain\ReadModel\CustomerDetailsRepository;
use OpenLoyalty\Component\Transaction\Domain\SystemEvent\TransactionSystemEvents;

/**
 * Class OloyEarningRuleEvaluatorTest.
 */
class OloyEarningRuleEvaluatorTest extends \PHPUnit_Framework_TestCase
{
    const USER_ID = '00000000-0000-0000-0000-000000000000';

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_points_earning_rule()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(608, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_points_earning_rule_and_excluded_sku()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludedSKUs([new SKU('000')]);
        $pointsEarningRule->setExcludeDeliveryCost(false);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(208, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_points_earning_rule_and_excluded_label()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludedLabels([new Label('color', 'red')]);
        $pointsEarningRule->setExcludeDeliveryCost(false);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(560, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_points_earning_rule_without_delivery_costs()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludedLabels([new Label('color', 'red')]);
        $pointsEarningRule->setExcludeDeliveryCost(true);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(400, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_sku_rule()
    {
        $purchaseEarningRule = new ProductPurchaseEarningRule(
            new EarningRuleId('00000000-0000-0000-0000-000000000000')
        );
        $purchaseEarningRule->setSkuIds(['000']);
        $purchaseEarningRule->setPointsAmount(200);

        $evaluator = $this->getEarningRuleEvaluator([$purchaseEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(200, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_if_there_are_more_rules()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(10);
        $pointsEarningRule->setExcludeDeliveryCost(false);

        $pointsEarningRule2 = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule2->setPointValue(4);
        $pointsEarningRule2->setExcludeDeliveryCost(false);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule, $pointsEarningRule2]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(2128, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_if_there_are_more_rule_types()
    {
        $purchaseEarningRule = new ProductPurchaseEarningRule(
            new EarningRuleId('00000000-0000-0000-0000-000000000000')
        );
        $purchaseEarningRule->setSkuIds(['123']);
        $purchaseEarningRule->setPointsAmount(100);

        $pointsEarningRule2 = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule2->setPointValue(4);
        $pointsEarningRule2->setExcludeDeliveryCost(false);

        $evaluator = $this->getEarningRuleEvaluator([$purchaseEarningRule, $pointsEarningRule2]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(708, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_event_account_created()
    {
        $eventEarningRule = new EventEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $eventEarningRule->setEventName(AccountSystemEvents::ACCOUNT_CREATED);
        $eventEarningRule->setPointsAmount(200);

        $evaluator = $this->getEarningRuleEvaluator([$eventEarningRule]);
        $customerId = 11;
        $points = $evaluator->evaluateEvent(AccountSystemEvents::ACCOUNT_CREATED, $customerId);
        $this->assertEquals(200, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_event_first_purchase()
    {
        $eventEarningRule = new EventEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $eventEarningRule->setEventName(TransactionSystemEvents::CUSTOMER_FIRST_TRANSACTION);
        $eventEarningRule->setPointsAmount(56);
        $eventEarningRule->setPos([new PosId('00000000-0000-474c-1111-b0dd880c07e2')]);

        $evaluator = $this->getEarningRuleEvaluator([$eventEarningRule]);
        $customerId = 11;
        $points = $evaluator->evaluateEvent(TransactionSystemEvents::CUSTOMER_FIRST_TRANSACTION, $customerId);
        $this->assertEquals(56, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_points_earning_rule_if_excluded_label()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);
        $pointsEarningRule->setExcludedLabels([new Label('color', 'red')]);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(560, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_points_earning_rule_if_excluded_sku()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);
        $pointsEarningRule->setExcludedSKUs([new SKU('000')]);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(208, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_multiply_points_rule_by_sku()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);

        $multiplyPointsEarningRule = new MultiplyPointsForProductEarningRule(
            new EarningRuleId('00000000-0000-0000-0000-000000000000')
        );
        $multiplyPointsEarningRule->setMultiplier(3);
        $multiplyPointsEarningRule->setSkuIds([new SKU('123')]);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule, $multiplyPointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(704, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_multiply_points_rule_by_label()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);

        $multiplyPointsEarningRule = new MultiplyPointsForProductEarningRule(
            new EarningRuleId('00000000-0000-0000-0000-000000000000')
        );
        $multiplyPointsEarningRule->setMultiplier(3);
        $multiplyPointsEarningRule->setLabels([new Label('color', 'red')]);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule, $multiplyPointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(704, $points);
    }

    public function productLabelMultipliersProvider()
    {
        return [
            [[new LabelMultiplier('color', 'red', 3)], 704],
            [
                [
                    new LabelMultiplier('color', 'red', 3),
                    new LabelMultiplier('color', 'blue', 6),
                ],
                2704,
            ],
            [
                [
                    new LabelMultiplier('color', 'red', 0),
                    new LabelMultiplier('color', 'blue', 2),
                    new LabelMultiplier('size', 'xxl', 2),
                ],
                960,
            ],
            [
                [
                    new LabelMultiplier('color', 'red', 0),
                    new LabelMultiplier('color', 'blue', 0),
                ],
                160,
            ],
            [
                [
                    new LabelMultiplier('color', 'blue', 2),
                    new LabelMultiplier('color', 'orange', 3),
                ],
                1008,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider productLabelMultipliersProvider
     *
     * @param array $labelMultipliers
     * @param int   $expectedPoints
     */
    public function it_returns_proper_value_for_given_transaction_and_multiply_points_rule_by_label_multipliers(array $labelMultipliers, int $expectedPoints)
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);

        $multiplyPointsEarningRule = new MultiplyPointsByProductLabelsEarningRule(
            new EarningRuleId('00000000-0000-0000-0000-000000000000')
        );
        $multiplyPointsEarningRule->setLabelMultipliers($labelMultipliers);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule, $multiplyPointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals($expectedPoints, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_points_earning_with_above_minimal()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);
        $pointsEarningRule->setMinOrderValue(100);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(608, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_points_earning_with_bellow_minimal()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);
        $pointsEarningRule->setMinOrderValue(300);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule]);

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(0, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_value_for_given_transaction_and_order_rules()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);

        $pointsEarningRule2 = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule2->setPointValue(10);
        $pointsEarningRule2->setExcludeDeliveryCost(false);

        $multiplyPointsEarningRule = new MultiplyPointsForProductEarningRule(
            new EarningRuleId('00000000-0000-0000-0000-000000000000')
        );
        $multiplyPointsEarningRule->setMultiplier(3);
        $multiplyPointsEarningRule->setLabels([new Label('color', 'red')]);

        $multiplyPointsEarningRule2 = new MultiplyPointsForProductEarningRule(
            new EarningRuleId('00000000-0000-0000-0000-000000000000')
        );
        $multiplyPointsEarningRule2->setMultiplier(5);
        $multiplyPointsEarningRule2->setLabels([new Label('color', 'blue')]);

        $purchaseEarningRule = new ProductPurchaseEarningRule(
            new EarningRuleId('00000000-0000-0000-0000-000000000000')
        );
        $purchaseEarningRule->setSkuIds(['000']);
        $purchaseEarningRule->setPointsAmount(200);

        $evaluator = $this->getEarningRuleEvaluator(
            [$pointsEarningRule, $pointsEarningRule2, $multiplyPointsEarningRule, $multiplyPointsEarningRule2, $purchaseEarningRule]
        );

        $points = $evaluator->evaluateTransaction(new TransactionId('00000000-0000-0000-0000-000000000000'), new CustomerId(static::USER_ID));
        $this->assertEquals(8264, $points);
    }

    /**
     * @test
     */
    public function it_returns_proper_comment_for_given_transaction()
    {
        $pointsEarningRule = new PointsEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000000'));
        $pointsEarningRule->setPointValue(4);
        $pointsEarningRule->setExcludeDeliveryCost(false);
        $pointsEarningRule->setName('Test 1');
        $pointsEarningRule->setAllTimeActive(true);

        $pointsEarningRule1 = new MultiplyPointsForProductEarningRule(new EarningRuleId('00000000-0000-0000-0000-000000000001'));
        $pointsEarningRule1->setMultiplier(2);
        $pointsEarningRule1->setName('Test 2');
        $pointsEarningRule1->setSkuIds(['123', '000', '0001']);

        $evaluator = $this->getEarningRuleEvaluator([$pointsEarningRule, $pointsEarningRule1]);

        $pointsWithComment = $evaluator->evaluateTransactionWithComment(
            new TransactionId('00000000-0000-0000-0000-000000000000'),
            new CustomerId(static::USER_ID)
        );

        $this->assertArrayHasKey('points', $pointsWithComment);
        $this->assertArrayHasKey('comment', $pointsWithComment);
        $this->assertEquals(1216, $pointsWithComment['points']);
        $this->assertEquals('Test 1, Test 2', $pointsWithComment['comment']);
    }

    /**
     * @param array $rules
     *
     * @return OloyEarningRuleEvaluator
     */
    protected function getEarningRuleEvaluator(array $rules)
    {
        return new OloyEarningRuleEvaluator(
            $this->getEarningRuleRepository($rules),
            $this->getTransactionDetailsRepository(),
            $this->getEarningRuleAlgorithmFactory(),
            $this->getInvitationDetailsRepository(),
            $this->getSegmentedCustomersRepository(),
            $this->getCustomerDetailsRepository(),
            $this->getSettingsManager([Status::TYPE_ACTIVE])
        );
    }

    /**
     * @return EarningRuleAlgorithmFactoryInterface
     */
    protected function getEarningRuleAlgorithmFactory()
    {
        $algorithms = [
            PointsEarningRule::class => new PointsEarningRuleAlgorithm(),
            MultiplyPointsForProductEarningRule::class => new MultiplyPointsForProductRuleAlgorithm(),
            ProductPurchaseEarningRule::class => new ProductPurchaseEarningRuleAlgorithm(),
            MultiplyPointsByProductLabelsEarningRule::class => new MultiplyPointsByProductLabelsRuleAlgorithm(),
        ];

        $mock = $this->createMock(EarningRuleAlgorithmFactoryInterface::class);
        $mock->method('getAlgorithm')->will(
            $this->returnCallback(
                function ($class) use ($algorithms) {
                    return $algorithms[get_class($class)];
                }
            )
        );

        return $mock;
    }

    /**
     * @return TransactionDetailsRepository
     */
    protected function getTransactionDetailsRepository()
    {
        $transactionDetails = new TransactionDetails(
            new \OpenLoyalty\Component\Transaction\Domain\TransactionId('00000000-0000-0000-0000-000000000000')
        );
        $transactionDetails->setItems(
            [
                new Item(
                    new SKU('123'),
                    'item1',
                    1,
                    12,
                    'cat',
                    $maker = 'test',
                    [
                        new Label('color', 'red'),
                    ]
                ),
                new Item(
                    new SKU('000'),
                    'item2',
                    1,
                    100,
                    'cat',
                    $maker = 'test',
                    [
                        new Label('color', 'blue'),
                    ]
                ),
                new Item(
                    new SKU('0001'),
                    'delivery',
                    1,
                    40,
                    'cat',
                    $maker = 'test'
                ),
            ]
        );
        $transactionDetails->setExcludedDeliverySKUs(['0001']);

        $mock = $this->createMock(TransactionDetailsRepository::class);
        $mock->method('find')->with($this->isType('string'))
            ->willReturn($transactionDetails);

        return $mock;
    }

    /**
     * @return InvitationDetailsRepository
     */
    protected function getInvitationDetailsRepository()
    {
        $mock = $this->createMock(InvitationDetailsRepository::class);
        $mock->method('find')->with($this->isType('string'))
            ->willReturn([]);

        return $mock;
    }

    /**
     * @param array $earningRules
     *
     * @return EarningRuleRepository
     */
    protected function getEarningRuleRepository(array $earningRules)
    {
        /** @var EarningRuleRepository|\PHPUnit_Framework_MockObject_MockObject $mock */
        $mock = $this->createMock(EarningRuleRepository::class);
        $mock->method('findAllActive')
            ->with(
                $this->logicalOr(
                    $this->isInstanceOf(\DateTime::class),
                    $this->isNull()
                )
            )
            ->willReturn(
                $earningRules
            );
        $mock->method('findAllActiveEventRules')->with(
            $this->isType('string'),
            $this->isType('array'),
            $this->logicalOr(
                $this->isType('string'),
                $this->isNull()
            ),
            $this->logicalOr(
                $this->isInstanceOf(\DateTime::class),
                $this->isNull()
            )
        )->willReturn($earningRules);

        $mock->method('findAllActiveEventRulesBySegmentsAndLevels')
            ->with(
                $this->logicalOr(
                    $this->isInstanceOf(\DateTime::class),
                    $this->isNull()
                ),
                $this->isType('array'),
                $this->logicalOr(
                    $this->isType('string'),
                    $this->isNull()
                )
            )
            ->willReturn($earningRules);

        return $mock;
    }

    protected function getSegmentedCustomersRepository()
    {
        $mock = $this->createMock(SegmentedCustomersRepository::class);

        $dataToReturn = [];

        $mock->method('findByParameters')
            ->with(
                $this->isType('array'),
                $this->isType('bool')
            )->willReturn($dataToReturn);

        return $mock;
    }

    protected function getCustomerDetailsRepository()
    {
        $mock = $this->createMock(CustomerDetailsRepository::class);

        $dataToReturn = [];

        $mock->method('findOneByCriteria')
            ->with(
                $this->isType('array'),
                $this->isType('int')
            )->willReturn($dataToReturn);

        return $mock;
    }

    protected function getSettingsManager(array $statuses)
    {
        $settingsManager = $this->getMockBuilder(SettingsManager::class)->getMock();
        $settingsManager->method('getSettingByKey')->willReturn($statuses);

        return $settingsManager;
    }
}
