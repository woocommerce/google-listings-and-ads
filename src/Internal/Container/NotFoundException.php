<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\NotFoundExceptionInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown when the container cannot resolve a requested identifier.
 *
 * Implements PSR-11's NotFoundExceptionInterface so callers can rely on the
 * standard contract regardless of which container implementation produced it.
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface {
}
