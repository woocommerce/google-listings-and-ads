<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class MissingLocalPostIdException
 *
 * Thrown when Google accepts a post but its response carries no post identifier. The post did
 * reach Google, so sending it again would create a duplicate.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\BusinessProfile
 */
class MissingLocalPostIdException extends Exception {}
