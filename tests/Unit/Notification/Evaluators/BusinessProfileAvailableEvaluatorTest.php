<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\BusinessProfileApiClient;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\Connection as BusinessProfileConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile\LocationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\BusinessProfileAvailableEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class BusinessProfileAvailableEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class BusinessProfileAvailableEvaluatorTest extends UnitTest {

	/** @var MockObject|BusinessProfileConnection $business_profile_connection */
	protected $business_profile_connection;

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var BusinessProfileAvailableEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Creates the evaluator with mocked dependencies before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->business_profile_connection = $this->createMock( BusinessProfileConnection::class );
		$this->onboarding_completed        = $this->createMock( OnboardingCompleted::class );

		$this->evaluator = new BusinessProfileAvailableEvaluator( $this->business_profile_connection, $this->onboarding_completed );
	}

	public function test_get_id() {
		$this->assertEquals( 'business-profile-available', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::BUSINESS_PROFILE_AVAILABLE, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration_is_permanent() {
		$this->assertNull( $this->evaluator->get_snooze_duration() );
	}

	public function test_should_not_show_when_onboarding_incomplete() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( false );
		$this->business_profile_connection->expects( $this->never() )->method( 'get_status' );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_show_when_no_location_connected() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->business_profile_connection->method( 'get_status' )->willReturn(
			[ 'status' => BusinessProfileConnection::STATUS_DISCONNECTED ]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_a_location_is_connected() {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );
		$this->business_profile_connection->method( 'get_status' )->willReturn(
			[
				'status'    => BusinessProfileConnection::STATUS_CONNECTED,
				'id'        => '2222',
				'accountId' => '106234255840114990952',
				'title'     => "Jane's Bakery - Riverside",
				'address'   => '456 Riverside Ave, San Francisco, 94107',
				'placeId'   => 'place-2222',
				'mapsUri'   => 'https://maps.google.com/?cid=2222',
			]
		);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	/**
	 * @dataProvider data_provider_stored_connection
	 *
	 * @param array $stored_connection
	 * @param bool  $expected
	 */
	public function test_reads_the_stored_connection_without_calling_google( array $stored_connection, bool $expected ) {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( true );

		$client = $this->createMock( BusinessProfileApiClient::class );
		$client->expects( $this->never() )->method( $this->anything() );

		$options = $this->createMock( OptionsInterface::class );
		$options->method( 'get' )->with( OptionsInterface::BUSINESS_PROFILE )->willReturn( $stored_connection );

		$location_service = new LocationService( $client );
		$location_service->set_options_object( $options );

		$evaluator = new BusinessProfileAvailableEvaluator( new BusinessProfileConnection( $location_service ), $this->onboarding_completed );

		$this->assertSame( $expected, $evaluator->should_show() );
	}

	/**
	 * @return array
	 */
	public function data_provider_stored_connection(): array {
		return [
			'nothing stored'     => [ [], true ],
			'location connected' => [
				[
					'account_id'       => '106234255840114990952',
					'location_id'      => '2222',
					'location_title'   => "Jane's Bakery - Riverside",
					'location_address' => '456 Riverside Ave, San Francisco, 94107',
					'place_id'         => 'place-2222',
					'maps_uri'         => 'https://maps.google.com/?cid=2222',
				],
				false,
			],
		];
	}
}
