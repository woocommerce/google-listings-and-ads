<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\MerchantAccountState;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class MerchantAccountStateTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Options
 */
class MerchantAccountStateTest extends UnitTest {

	/** @var MockObject|OptionsInterface $options */
	protected $options;

	/** @var MerchantAccountState $state */
	protected $state;

	public function setUp(): void {
		parent::setUp();

		$this->options = $this->createMock( OptionsInterface::class );
		$this->state   = new MerchantAccountState();
		$this->state->set_options_object( $this->options );
	}

	/**
	 * Build a stored step entry.
	 *
	 * @param int $status One of the AccountState::STEP_* values.
	 *
	 * @return array
	 */
	protected function step( int $status ): array {
		return [
			'status'  => $status,
			'message' => '',
			'data'    => [],
		];
	}

	public function test_get_drops_entries_for_steps_that_no_longer_exist() {
		$this->options->method( 'get' )
			->with( OptionsInterface::MERCHANT_ACCOUNT_STATE )
			->willReturn(
				[
					'set_id'      => $this->step( MerchantAccountState::STEP_DONE ),
					'legacy_step' => $this->step( MerchantAccountState::STEP_PENDING ),
					'verify'      => $this->step( MerchantAccountState::STEP_PENDING ),
				]
			);

		$this->options->expects( $this->never() )
			->method( 'update' );

		$this->assertSame(
			[
				'set_id' => $this->step( MerchantAccountState::STEP_DONE ),
				'verify' => $this->step( MerchantAccountState::STEP_PENDING ),
			],
			$this->state->get()
		);
	}

	public function test_get_initializes_when_only_unknown_steps_are_stored() {
		$this->options->method( 'get' )
			->willReturn( [ 'legacy_step' => $this->step( MerchantAccountState::STEP_PENDING ) ] );

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MERCHANT_ACCOUNT_STATE,
				$this->callback(
					function ( array $state ) {
						return ! empty( $state ) && ! isset( $state['legacy_step'] );
					}
				)
			);

		$state = $this->state->get();

		$this->assertArrayHasKey( 'set_id', $state );
		$this->assertArrayNotHasKey( 'legacy_step', $state );
	}

	public function test_last_incomplete_step_ignores_steps_that_no_longer_exist() {
		$this->options->method( 'get' )
			->willReturn(
				[
					'set_id'      => $this->step( MerchantAccountState::STEP_DONE ),
					'legacy_step' => $this->step( MerchantAccountState::STEP_PENDING ),
				]
			);

		$this->assertSame( '', $this->state->last_incomplete_step() );
	}

	public function test_complete_step_persists_state_without_unknown_steps() {
		$this->options->method( 'get' )
			->willReturn(
				[
					'set_id'      => $this->step( MerchantAccountState::STEP_PENDING ),
					'legacy_step' => $this->step( MerchantAccountState::STEP_PENDING ),
				]
			);

		$this->options->expects( $this->once() )
			->method( 'update' )
			->with(
				OptionsInterface::MERCHANT_ACCOUNT_STATE,
				[ 'set_id' => $this->step( MerchantAccountState::STEP_DONE ) ]
			);

		$this->state->complete_step( 'set_id' );
	}
}
