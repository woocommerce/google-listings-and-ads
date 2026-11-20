<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\AdsAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\FreshReadOptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class AccountStateTest
 *
 * Tests the shared account creation state behaviour through the concrete
 * AdsAccountState class.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class AccountStateTest extends UnitTest {

	/** @var MockObject|FreshReadOptionsInterface $options */
	protected $options;

	/** @var AdsAccountState $state */
	protected $state;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( FreshReadOptionsInterface::class );
		$this->state   = new AdsAccountState();
		$this->state->set_options_object( $this->options );
	}

	public function test_complete_step_writes_onto_freshly_read_state() {
		// The fresh read contains a completion written by another request; it
		// must survive the write, so the cached value must not be used.
		$this->options->expects( $this->once() )
			->method( 'get_fresh' )
			->with( OptionsInterface::ADS_ACCOUNT_STATE, [] )
			->willReturn(
				[
					'set_id'         => [ 'status' => AdsAccountState::STEP_DONE ],
					'account_access' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		// The cached copy is stale: it is missing the set_id completion.
		$this->options->method( 'get' )
			->willReturn(
				[
					'set_id'         => [ 'status' => AdsAccountState::STEP_PENDING ],
					'account_access' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::ADS_ACCOUNT_STATE,
				[
					'set_id'         => [ 'status' => AdsAccountState::STEP_DONE ],
					'account_access' => [ 'status' => AdsAccountState::STEP_DONE ],
				]
			);

		$this->state->complete_step( 'account_access' );
	}

	public function test_complete_step_skips_the_fresh_read_when_already_done() {
		$this->options->method( 'get' )
			->willReturn(
				[
					'account_access' => [ 'status' => AdsAccountState::STEP_DONE ],
				]
			);

		$this->options->expects( $this->never() )
			->method( 'get_fresh' );

		$this->options->expects( $this->never() )
			->method( 'update' );

		$this->state->complete_step( 'account_access' );
	}

	public function test_complete_step_with_unknown_step_writes_nothing() {
		$this->options->expects( $this->once() )
			->method( 'get_fresh' )
			->with( OptionsInterface::ADS_ACCOUNT_STATE, [] )
			->willReturn( [] );

		$this->options->expects( $this->never() )
			->method( 'update' );

		$this->state->complete_step( 'set_id' );
	}

	public function test_get_fresh_returns_empty_array_when_option_missing() {
		$this->options->expects( $this->once() )
			->method( 'get_fresh' )
			->with( OptionsInterface::ADS_ACCOUNT_STATE, [] )
			->willReturn( false );

		$this->assertSame( [], $this->state->get_fresh() );
	}

	public function test_get_fresh_drops_steps_that_no_longer_exist() {
		$this->options->expects( $this->once() )
			->method( 'get_fresh' )
			->with( OptionsInterface::ADS_ACCOUNT_STATE, [] )
			->willReturn(
				[
					'set_id'      => [ 'status' => AdsAccountState::STEP_DONE ],
					'legacy_step' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->assertSame(
			[ 'set_id' => [ 'status' => AdsAccountState::STEP_DONE ] ],
			$this->state->get_fresh()
		);
	}

	public function test_get_fresh_falls_back_to_get_for_options_without_fresh_reads() {
		$options = $this->createMock( OptionsInterface::class );
		$options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::ADS_ACCOUNT_STATE, [] )
			->willReturn( [ 'set_id' => [ 'status' => AdsAccountState::STEP_DONE ] ] );

		$state = new AdsAccountState();
		$state->set_options_object( $options );

		$this->assertSame(
			[ 'set_id' => [ 'status' => AdsAccountState::STEP_DONE ] ],
			$state->get_fresh()
		);
	}

	public function test_last_incomplete_step_returns_first_step_not_done() {
		$this->options->expects( $this->once() )
			->method( 'get' )
			->with( OptionsInterface::ADS_ACCOUNT_STATE, [] )
			->willReturn(
				[
					'set_id'            => [ 'status' => AdsAccountState::STEP_DONE ],
					'account_access'    => [ 'status' => AdsAccountState::STEP_PENDING ],
					'conversion_action' => [ 'status' => AdsAccountState::STEP_PENDING ],
				]
			);

		$this->assertEquals( 'account_access', $this->state->last_incomplete_step() );
	}
}
