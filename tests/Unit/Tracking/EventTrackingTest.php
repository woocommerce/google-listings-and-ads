<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\Tracks as TracksProxy;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\TracksInterface;
use ReflectionProperty;

defined( 'ABSPATH' ) || exit;

/**
 * Class EventTrackingTest
 *
 * Fires the plugin's own hooks through the real container, so these tests also cover how the
 * event classes are registered.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking
 */
class EventTrackingTest extends ContainerAwareUnitTest {

	/** @var array Events sent to Tracks, each shaped `[ name, properties ]`. */
	protected $recorded = [];

	/** @var ReflectionProperty */
	protected $transport_property;

	/** @var TracksProxy The container's own transport, restored after each test. */
	protected $original_transport;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$tracks = $this->container->get( TracksInterface::class );

		$this->transport_property = new ReflectionProperty( $tracks, 'tracks' );
		$this->transport_property->setAccessible( true );
		$this->original_transport = $this->transport_property->getValue( $tracks );

		$transport = $this->createMock( TracksProxy::class );
		$transport->method( 'record_event' )->willReturnCallback(
			function ( string $name, array $properties = [] ) {
				$this->recorded[] = [ $name, $properties ];
			}
		);
		$this->transport_property->setValue( $tracks, $transport );
	}

	/**
	 * Runs after each test is executed.
	 */
	public function tearDown(): void {
		$this->transport_property->setValue( $this->container->get( TracksInterface::class ), $this->original_transport );

		parent::tearDown();
	}

	public function test_records_a_business_profile_location_connection() {
		do_action( 'woocommerce_gla_business_profile_location_connected' );

		$this->assertSame( [ 'gla_google_business_profile_location_connected' ], array_column( $this->recorded, 0 ) );
	}

	public function test_records_a_business_profile_sync_success_with_its_type() {
		do_action( 'woocommerce_gla_business_profile_sync_success', [ 'type' => 'create' ] );

		$this->assertSame( [ 'gla_google_business_profile_sync_success' ], array_column( $this->recorded, 0 ) );
		$this->assertSame( 'create', $this->recorded[0][1]['type'] );
	}

	public function test_records_a_business_profile_sync_failure_with_its_type_and_reason() {
		do_action(
			'woocommerce_gla_business_profile_sync_failure',
			[
				'type'   => 'create',
				'reason' => 'PERMISSION_DENIED',
			]
		);

		$this->assertSame( [ 'gla_google_business_profile_sync_failure' ], array_column( $this->recorded, 0 ) );
		$this->assertSame( 'create', $this->recorded[0][1]['type'] );
		$this->assertSame( 'PERMISSION_DENIED', $this->recorded[0][1]['reason'] );
	}
}
