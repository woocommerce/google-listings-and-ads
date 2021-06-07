<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExtensionRequirementException;
use WC_Bookings;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCBookingsValidator
 *
 * @package AutomatticWooCommerceGoogleListingsAndAdsInternalRequirements
 */
class WCBookingsValidator extends RequirementValidator {

	/**
	 * Validate all requirements for the plugin to function properly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		try {
			$this->validate_wc_bookings_active();
			return true;
		} catch ( ExtensionRequirementException $e ) {
			$this->add_admin_notice( $e );
			return false;
		}
	}

	/**
	 * Validate that the incompatible plugin is loaded.
	 *
	 * @throws ExtensionRequirementException When WooCommerce Bookings is active.
	 */
	protected function validate_wc_bookings_active() {
		if ( class_exists( WC_Bookings::class ) ) {
			throw ExtensionRequirementException::incompatible_plugin( 'WooCommerce Bookings' );
		}
	}
}
