<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Class AccountState
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
abstract class AccountState implements Service, OptionsAwareInterface {

	use OptionsAwareTrait;

	/** @var int Status value for a pending merchant account creation step */
	public const STEP_PENDING = 0;

	/** @var int Status value for a completed merchant account creation step */
	public const STEP_DONE = 1;

	/** @var int Status value for an unsuccessful merchant account creation step */
	public const STEP_ERROR = - 1;

	/**
	 * Return the option name.
	 *
	 * @return string
	 */
	abstract protected function option_name(): string;

	/**
	 * Return a list of account creation steps.
	 *
	 * @return string[]
	 */
	abstract protected function account_creation_steps(): array;

	/**
	 * Retrieve or initialize the account state option.
	 *
	 * @param bool $initialize_if_not_found True to initialize the array of steps.
	 *
	 * @return array The account creation steps and statuses.
	 */
	public function get( bool $initialize_if_not_found = true ): array {
		$state = $this->filter_known_steps( $this->options->get( $this->option_name(), [] ) );

		if ( empty( $state ) && $initialize_if_not_found ) {
			$state = [];
			foreach ( $this->account_creation_steps() as $step ) {
				$state[ $step ] = [
					'status'  => self::STEP_PENDING,
					'message' => '',
					'data'    => [],
				];
			}
			$this->update( $state );
		}

		return $state;
	}

	/**
	 * Retrieve the account state option, bypassing the request caches.
	 *
	 * The whole state array is written back on every update, so any write must
	 * start from the current database value rather than a copy cached earlier
	 * in the request; otherwise steps completed by concurrent requests are
	 * silently reverted.
	 *
	 * @return array The account creation steps and statuses.
	 */
	public function get_fresh(): array {
		// Fall back to the cached read for options objects that can't bypass the caches.
		$state = $this->options instanceof FreshReadOptionsInterface
			? $this->options->get_fresh( $this->option_name(), [] )
			: $this->options->get( $this->option_name(), [] );

		return $this->filter_known_steps( $state );
	}

	/**
	 * Drop entries for steps that are no longer in account_creation_steps().
	 *
	 * They can linger in the stored option after a plugin update, so no caller
	 * should report or throw on a step that no longer exists.
	 *
	 * @param mixed $state The stored account state.
	 *
	 * @return array
	 */
	private function filter_known_steps( $state ): array {
		return array_intersect_key(
			(array) $state,
			array_flip( $this->account_creation_steps() )
		);
	}

	/**
	 * Update the account state option.
	 *
	 * @param array $state
	 */
	public function update( array $state ) {
		$this->options->update( $this->option_name(), $state );
	}

	/**
	 * Mark a step as completed.
	 *
	 * @param string $step Name of the completed step.
	 */
	public function complete_step( string $step ) {
		// Skip the uncached read when the step is already done, since bypassing
		// the caches forces the autoloaded options to be reloaded.
		$cached = $this->get( false );
		if ( self::STEP_DONE === ( $cached[ $step ]['status'] ?? null ) ) {
			return;
		}

		$state = $this->get_fresh();

		if ( isset( $state[ $step ] ) ) {
			$state[ $step ]['status'] = self::STEP_DONE;
			$this->update( $state );
		}
	}

	/**
	 * Returns the name of the last incompleted step.
	 *
	 * @return string
	 */
	public function last_incomplete_step(): string {
		$incomplete = '';
		foreach ( $this->get( false ) as $name => $step ) {
			if ( ! isset( $step['status'] ) || self::STEP_DONE !== $step['status'] ) {
				$incomplete = $name;
				break;
			}
		}

		return $incomplete;
	}

	/**
	 * Returns any data from a specific step.
	 *
	 * @param string $step Step name.
	 * @return array
	 */
	public function get_step_data( string $step ): array {
		return $this->get( false )[ $step ]['data'] ?? [];
	}
}
