<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

defined( 'ABSPATH' ) || exit;

/**
 * Class GoogleProductService
 *
 * Holds the product sync error reasons and batch size shared by the product syncer.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class GoogleProductService {

	public const INTERNAL_ERROR_REASON  = 'internalError';
	public const NOT_FOUND_ERROR_REASON = 'notFound';

	/**
	 * This is the maximum batch size recommended by Google
	 *
	 * @link https://developers.google.com/shopping-content/guides/how-tos/batch
	 */
	public const BATCH_SIZE = 1000;
}
