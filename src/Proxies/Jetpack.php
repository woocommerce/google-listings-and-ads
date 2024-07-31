<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Proxies;

use Automattic\Jetpack\Connection\Client;

/**
 * Class JP.
 *
 * This class provides proxy methods to wrap around Jetpack functions.
 *
 * @since 2.8.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Proxies
 */
class Jetpack {

	/**
	 * Makes an authorized remote request using Jetpack_Signature
	 *
	 * @param array $args the arguments for the remote request.
	 * @param mixed $body the body of the request.
	 *
	 * @return array|WP_Error — WP HTTP response on success
	 */
	public function remote_request( $args, $body = null ) {
		return Client::remote_request( $args, $body );
	}
}
