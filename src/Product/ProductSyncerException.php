<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductSyncerException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class ProductSyncerException extends Exception implements GoogleListingsAndAdsException {
}
