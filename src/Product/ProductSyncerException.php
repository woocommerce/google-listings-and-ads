<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductSyncerException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductSyncerException extends Exception implements GoogleListingsAndAdsException {

	/**
	 * Return true if the exception was caused by authentication failure.
	 *
	 * @return boolean
	 */
	public function is_authentication_failure(): bool {
		$previous = $this->getPrevious();

		if ( $previous instanceof MerchantApiException && in_array( $previous->get_http_status(), [ 401, 403 ], true ) ) {
			return true;
		}

		return false;
	}
}
