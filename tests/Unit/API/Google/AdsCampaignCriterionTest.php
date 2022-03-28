<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\AdsCampaignCriterion;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\Ads\GoogleAds\V9\Enums\CampaignCriterionStatusEnum\CampaignCriterionStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Class AdsCampaignCriterionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 *
 * @property AdsCampaignCriterion $campaign_criterion
 */
class AdsCampaignCriterionTest extends UnitTest {

	use GoogleAdsClientTrait;

	protected const TEST_CAMPAIGN_ID = 1234567890;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();

		$this->ads_client_setup();

		$this->campaign_criterion = new AdsCampaignCriterion();
	}

	public function test_create_operations() {
		$campaign_resource_name = $this->generate_campaign_resource_name( self::TEST_CAMPAIGN_ID );
		$location_ids           = [2158, 2344, 2826];

		$operations = $this->campaign_criterion->create_operations(
			$campaign_resource_name,
			$location_ids
		);

		foreach ( $operations as $index => $operation ) {
			$operation_campaign_criterion = $operation->getCampaignCriterionOperation();
			$this->assertTrue( $operation_campaign_criterion->hasCreate() );

			$campaign_criterion = $operation_campaign_criterion->getCreate();
			$this->assertEquals( $campaign_resource_name, $campaign_criterion->getCampaign() );
			$this->assertEquals( CampaignCriterionStatus::ENABLED, $campaign_criterion->getStatus() );
			$this->assertEquals( false, $campaign_criterion->getNegative() );

			$location             = $campaign_criterion->getLocation();
			$geo_target_constant  = $location->getGeoTargetConstant();
			$expected_location_id = $location_ids[ $index ];
			$this->assertEquals( "geoTargetConstants/{$expected_location_id}", $geo_target_constant );
		}
	}
}
