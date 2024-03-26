<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class NotificationJobException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs\Notifications
 */
class NotificationJobException extends Exception implements GoogleListingsAndAdsException {
}
