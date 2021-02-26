<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use LogicException;

defined( 'ABSPATH' ) || exit;

/**
 * Class ActionSchedulerException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler
 */
class ActionSchedulerException extends LogicException implements GoogleListingsAndAdsException {

	/**
	 * Create a new exception instance for when a job item is not found.
	 *
	 * @param string $action Action name
	 *
	 * @return ActionSchedulerException
	 */
	public static function action_not_found( string $action ): ActionSchedulerException {
		return new static(
			sprintf(
			/* translators: %s: the action name */
				__( 'No action matching %s was found.', 'google-listings-and-ads' ),
				$action
			)
		);
	}
}
