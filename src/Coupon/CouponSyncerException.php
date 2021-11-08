<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class CouponSyncerException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 */
class CouponSyncerException extends Exception implements GoogleListingsAndAdsException {
}
