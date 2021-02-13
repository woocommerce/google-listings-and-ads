<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Trait MerchantCenterTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits
 */
trait MerchantCenterTrait {

	use OptionsAwareTrait;

	/**
	 * Get whether Merchant Center setup is completed.
	 *
	 * @return bool
	 */
	protected function setup_complete(): bool {
		return boolval( $this->options->get( OptionsInterface::MC_SETUP_COMPLETED_AT, false ) );
	}
}
