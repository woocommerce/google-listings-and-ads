<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Connection as TagManagerConnection;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\Settings as TagManagerSettings;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\Evaluators\TagManagerAdsConversionConflictEvaluator;
use Automattic\WooCommerce\GoogleListingsAndAds\Notification\NotificationPriorities;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OnboardingCompleted;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerAdsConversionConflictEvaluatorTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Notification\Evaluators
 */
class TagManagerAdsConversionConflictEvaluatorTest extends UnitTest {

	/** @var MockObject|TagManagerConnection $tag_manager_connection */
	protected $tag_manager_connection;

	/** @var MockObject|TagManagerSettings $tag_manager_settings */
	protected $tag_manager_settings;

	/** @var MockObject|OnboardingCompleted $onboarding_completed */
	protected $onboarding_completed;

	/** @var TagManagerAdsConversionConflictEvaluator $evaluator */
	protected $evaluator;

	/**
	 * Creates the evaluator with mocked dependencies before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->tag_manager_connection = $this->createMock( TagManagerConnection::class );
		$this->tag_manager_settings   = $this->createMock( TagManagerSettings::class );
		$this->onboarding_completed   = $this->createMock( OnboardingCompleted::class );

		$this->evaluator = new TagManagerAdsConversionConflictEvaluator(
			$this->tag_manager_connection,
			$this->tag_manager_settings,
			$this->onboarding_completed
		);
	}

	public function test_get_id() {
		$this->assertEquals( 'tag-manager-ads-conversion-conflict', $this->evaluator->get_id() );
	}

	public function test_get_priority() {
		$this->assertEquals( NotificationPriorities::TAG_MANAGER_ADS_CONVERSION_CONFLICT, $this->evaluator->get_priority() );
	}

	public function test_resets_once_the_conflict_clears() {
		$this->assertSame(
			[ 'woocommerce_gla_tag_manager_ads_conversion_conflict_cleared' ],
			$this->evaluator->get_reset_hooks()
		);
	}

	public function test_get_snooze_duration_is_permanent() {
		$this->assertNull( $this->evaluator->get_snooze_duration() );
	}

	public function test_should_show_when_a_conflict_is_found_while_the_snippet_is_on_by_default() {
		$this->mock_state( true, true, true, false );

		$this->assertTrue( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_onboarding_incomplete() {
		$this->mock_state( false, true, true, false );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_without_a_conflict() {
		$this->mock_state( true, false, true, false );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_when_the_snippet_is_off() {
		// Includes a conflict found at connection, which turns the snippet off by default.
		$this->mock_state( true, true, false, false );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	public function test_should_not_show_once_the_merchant_has_set_the_snippet_toggle() {
		$this->mock_state( true, true, true, true );

		$this->assertFalse( $this->evaluator->should_show() );
	}

	/**
	 * Mocks the state the evaluator reads.
	 *
	 * @param bool $onboarding_complete Whether onboarding is complete.
	 * @param bool $conflict            Whether the last check found a Google Ads conversion tag.
	 * @param bool $snippet_active      Whether the snippet is added to the storefront.
	 * @param bool $merchant_choice     Whether the merchant has set the snippet toggle themselves.
	 */
	private function mock_state( bool $onboarding_complete, bool $conflict, bool $snippet_active, bool $merchant_choice ): void {
		$this->onboarding_completed->method( 'is_onboarding_complete' )->willReturn( $onboarding_complete );
		$this->tag_manager_connection->method( 'has_ads_conversion_conflict' )->willReturn( $conflict );
		$this->tag_manager_settings->method( 'is_snippet_active' )->willReturn( $snippet_active );
		$this->tag_manager_settings->method( 'has_snippet_injection_choice' )->willReturn( $merchant_choice );
	}
}
