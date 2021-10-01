<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;

defined( 'ABSPATH' ) || exit;

/**
 * Class PhoneVerificationException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 *
 * @since 1.5.0
 */
class PhoneVerificationException extends ExceptionWithResponseData {}
