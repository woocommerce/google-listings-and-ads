<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Exception;

use Throwable;

/**
 * This interface is used for all of our exceptions so that we can easily catch only our own exceptions.
 */
interface GoogleListingsAndAdsException extends Throwable {}
