<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi;

defined( 'ABSPATH' ) || exit;

/**
 * Class MapiPaths
 *
 * Base paths for the Merchant API.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi
 */
final class MapiPaths {

	public const PRODUCTS    = 'products/v1';
	public const DATASOURCES = 'datasources/v1';
	public const REPORTS     = 'reports/v1';
}
