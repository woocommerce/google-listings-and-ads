<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Container;

use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Psr\Container\ContainerExceptionInterface;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Thrown for container errors that are not "not found" — for example,
 * trying to instantiate an abstract class or a circular dependency.
 */
class ContainerException extends Exception implements ContainerExceptionInterface {
}
