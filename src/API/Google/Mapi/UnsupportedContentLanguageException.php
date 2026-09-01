<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class UnsupportedContentLanguageException
 *
 * A content language Merchant Center does not support. Merchant API rejects a data source created
 * with one (400 FAILED_PRECONDITION, reason INVALID_STATE_PRIMARY_DATA_SOURCE_UPDATE) and the
 * rejection is permanent, so the write is refused locally instead of being attempted and retried.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi
 */
class UnsupportedContentLanguageException extends Exception implements GoogleListingsAndAdsException {

	/**
	 * UnsupportedContentLanguageException constructor.
	 *
	 * @param string $content_language The rejected language code.
	 */
	public function __construct( string $content_language ) {
		parent::__construct(
			sprintf(
				/* translators: %s: two-letter content language code */
				__( 'Merchant Center does not support the content language "%s".', 'google-listings-and-ads' ),
				$content_language
			)
		);
	}
}
