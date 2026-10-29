<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\EventTracking;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\ActivatedEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\BaseEvent;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\GenericEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\SearchConsoleConnectionEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\SiteClaimEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Tracking\Events\SiteVerificationEvents;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;

defined( 'ABSPATH' ) || exit;

/**
 * Class EventTrackingTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Tracking
 */
class EventTrackingTest extends UnitTest {

	public function test_registers_every_event_class() {
		$expected_classes = [
			ActivatedEvents::class,
			GenericEvents::class,
			SearchConsoleConnectionEvents::class,
			SiteClaimEvents::class,
			SiteVerificationEvents::class,
		];

		$registered = [];

		/** @var MockObject|ContainerInterface $container */
		$container = $this->createMock( ContainerInterface::class );
		$container->method( 'get' )->willReturnCallback(
			function ( string $class_name ) use ( &$registered ) {
				/** @var MockObject|BaseEvent $event */
				$event = $this->getMockBuilder( BaseEvent::class )
					->disableOriginalConstructor()
					->onlyMethods( [ 'register' ] )
					->getMock();
				$event->expects( $this->once() )
					->method( 'register' )
					->willReturnCallback(
						function () use ( $class_name, &$registered ) {
							$registered[] = $class_name;
						}
					);

				return $event;
			}
		);

		$event_tracking = new EventTracking();
		$event_tracking->set_container( $container );

		$register_events = new ReflectionMethod( $event_tracking, 'register_events' );
		$register_events->setAccessible( true );
		$register_events->invoke( $event_tracking );

		$this->assertEqualsCanonicalizing( $expected_classes, $registered );
	}
}
