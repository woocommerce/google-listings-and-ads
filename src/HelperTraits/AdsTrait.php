<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Trait AdsTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits
 */
trait AdsTrait {

	use OptionsAwareTrait;

	/**
	 * Get whether Ads setup is completed.
	 *
	 * @return bool
	 */
	protected function is_ads_setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::ADS_SETUP_COMPLETED_AT, false ) );
	}
}
