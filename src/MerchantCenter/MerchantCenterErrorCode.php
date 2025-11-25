<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantCenterErrorCode
 *
 * Acts as an enum of Merchant Center specific error codes.
 * Prefix all codes with MERCHANT_CENTER_.
 */
class MerchantCenterErrorCode {

	public const ACCOUNT_CREATE_FAILED        = 'MERCHANT_CENTER_ACCOUNT_CREATE_FAILED';
	public const ACCOUNT_LINK_FAILED          = 'MERCHANT_CENTER_ACCOUNT_LINK_FAILED';
	public const ACCOUNT_CLAIM_WEBSITE_FAILED = 'MERCHANT_CENTER_ACCOUNT_CLAIM_WEBSITE_FAILED';

	/**
	 * Validate a provided code is one of the known codes.
	 *
	 * @param string $code
	 * @return bool
	 */
	public static function is_valid( string $code ): bool {
		return in_array( $code, [
			self::ACCOUNT_CREATE_FAILED,
			self::ACCOUNT_LINK_FAILED,
			self::ACCOUNT_CLAIM_WEBSITE_FAILED,
		], true );
	}
}
