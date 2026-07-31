<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\WP;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Class OAuthService
 * This class implements a service to handle WordPress.com OAuth.
 *
 * @since 2.8.0
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\WP
 */
class OAuthService implements Service {

	public const STATUS_APPROVED = 'approved';
	public const STATUS_ERROR    = 'error';
}
