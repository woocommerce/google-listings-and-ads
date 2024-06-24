<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Options;

/**
 * Class AdsAccountState
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Options
 */
class AdsAccountState extends AccountState {

	/**
	 * Return the option name.
	 *
	 * @return string
	 */
	protected function option_name(): string {
		return OptionsInterface::ADS_ACCOUNT_STATE;
	}

	/**
	 * Return a list of account creation steps.
	 *
	 * @return string[]
	 */
	protected function account_creation_steps(): array {
		return [ 'set_id', 'account_access', 'conversion_action', 'link_merchant', 'billing' ];
	}
}
