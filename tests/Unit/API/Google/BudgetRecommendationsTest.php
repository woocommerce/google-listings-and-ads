<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\BudgetRecommendations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\GoogleAdsClientTrait;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class BudgetRecommendationsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google
 */
class BudgetRecommendationsTest extends UnitTest {

	use GoogleAdsClientTrait;

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var BudgetRecommendations $recommendations */
	protected $recommendations;

	protected const TEST_ADS_ID = 1234567890;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->ads_client_setup();

		$this->options    = $this->createMock( OptionsInterface::class );
		$this->transients = $this->createMock( TransientsInterface::class );

		$this->recommendations = new BudgetRecommendations( $this->client );
		$this->recommendations->set_options_object( $this->options );
		$this->recommendations->set_transients_object( $this->transients );
	}

	public function test_get_recommendations_empty_set() {
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_LOCATION_IDS )
			->willReturn( [ 'us' => [ 111 => 'US' ] ] );

		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );

		$this->generate_recommendations_mock( [] );
		$this->assertNull( $this->recommendations->get_recommendations( [ 'US' ] ) );
	}

	public function test_get_recommendations_exception() {
		$this->transients->expects( $this->once() )
			->method( 'get' )
			->with( TransientsInterface::ADS_LOCATION_IDS )
			->willReturn( [ 'us' => [ 111 => 'US' ] ] );

		$this->options->method( 'get_ads_id' )->willReturn( self::TEST_ADS_ID );

		$this->generate_recommendations_mock_exception(
			new ApiException( 'failed', 7 )
		);

		$this->assertNull( $this->recommendations->get_recommendations( [ 'US' ] ) );
		$this->assertEquals( 1, did_action( 'woocommerce_gla_ads_client_exception' ) );
	}
}
