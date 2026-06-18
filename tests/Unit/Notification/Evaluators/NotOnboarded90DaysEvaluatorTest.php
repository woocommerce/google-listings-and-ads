<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\NotOnboarded90DaysEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationSnoozeDurations;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotOnboarded90DaysEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class NotOnboarded90DaysEvaluatorTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var NotOnboarded90DaysEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options   = $this->createMock( OptionsInterface::class );
		$this->evaluator = new NotOnboarded90DaysEvaluator();
		$this->evaluator->set_options_object( $this->options );
	}

	public function test_get_id() {
		$this->assertEquals( 'not-onboarded-90-days', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::NOT_ONBOARDED_90_DAYS, $this->evaluator->get_priority() );
	}

	public function test_get_snooze_duration() {
		$this->assertEquals( NotificationSnoozeDurations::NOT_ONBOARDED_90_DAYS, $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_google_not_connected_and_wc_installed_over_90_days_ago() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, false ],
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 91 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, null ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_google_not_connected_and_plugin_installed_over_90_days_ago() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, false ],
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, null ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, time() - ( 91 * DAY_IN_SECONDS ) ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_wc_is_older_than_plugin_and_over_90_days() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, false ],
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 120 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, time() - ( 10 * DAY_IN_SECONDS ) ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_show_when_exactly_90_days_have_elapsed() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, false ],
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 90 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, null ],
			]
		);

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_google_connected() {
		$this->options->method( 'get' )
			->with( OptionsInterface::GOOGLE_CONNECTED, false )
			->willReturn( true );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_install_timestamps_missing() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, false ],
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, null ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, null ],
			]
		);

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_installed_less_than_90_days_ago() {
		$this->options->method( 'get' )->willReturnMap(
			[
				[ OptionsInterface::GOOGLE_CONNECTED, false, false ],
				[ OptionsInterface::WC_INSTALL_TIMESTAMP, null, time() - ( 30 * DAY_IN_SECONDS ) ],
				[ OptionsInterface::INSTALL_TIMESTAMP, null, time() - ( 30 * DAY_IN_SECONDS ) ],
			]
		);

		$this->assertFalse( $this->evaluator->should_show() );
	}
}
