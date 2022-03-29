<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignBudget;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignBudgetTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property MockObject|OptionsInterface $options
 * @property AdsCampaignBudget           $budget
 */
class AdsCampaignBudgetTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEST_CAMPAIGN_ID = 1234567890;
	protected const TEST_BUDGET_ID   = 4455667788;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->ads_client_setup();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->options->method( 'get_ads_id' )->willReturn( $this->ads_id );

		$this->budget = new AdsCampaignBudget( $this->client );
		$this->budget->set_options_object( $this->options );
	}

	public function test_create_operation() {
		$operation = $this->budget->create_operation(
			'New Campaign',
			12
		)->getCampaignBudgetOperation();
		$this->assertTrue( $operation->hasCreate() );

		$budget = $operation->getCreate();
		$this->assertEquals( 'New Campaign Budget', $budget->getName() );
		$this->assertEquals( 12 * 1000000, $budget->getAmountMicros() );
		$this->assertEquals( $this->budget->temporary_resource_name(), $budget->getResourceName() );
	}

	public function test_edit_operation() {
		$this->generate_ads_campaign_budget_query_mock( self::TEST_BUDGET_ID );

		$operation = $this->budget->edit_operation(
			self::TEST_CAMPAIGN_ID,
			10.50
		)->getCampaignBudgetOperation();
		$this->assertTrue( $operation->hasUpdate() );

		$budget = $operation->getUpdate();
		$this->assertEquals( 10.50 * 1000000, $budget->getAmountMicros() );
		$this->assertEquals(
			$this->generate_campaign_budget_resource_name( self::TEST_BUDGET_ID ),
			$budget->getResourceName()
		);
	}
}
