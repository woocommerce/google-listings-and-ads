<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;


/**
 * Class InvalidDomainName
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Exception
 */
class InvalidDomainName extends InvalidArgumentException implements GoogleListingsAndAdsException {
	/**
	 * Create a new instance of the exception when a Merchant Center account can't be created
	 * because of an invalid top-level domain name.
	 *
	 * @param string $domain_name The top level domain name.
	 *
	 * @return static
	 */
	public static function create_account_failed_invalid_top_level_domain_name( string $domain_name ): InvalidDomainName {
		return new static(
		/* translators: 1 top level domain name. */
			sprintf( __( 'Unable to create an account, the domain name "%s" must end with a valid top-level domain name.', 'google-listings-and-ads' ), $domain_name )
		);
	}
}
