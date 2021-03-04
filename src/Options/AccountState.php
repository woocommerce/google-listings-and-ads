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
		$state = $this->options->get( $this->option_name(), [] );
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
	 * Update the account state option.
	 *
	 * @param array $state
	 */
	public function update( array $state ) {
		$this->options->update( $this->option_name(), $state );
	}
}
