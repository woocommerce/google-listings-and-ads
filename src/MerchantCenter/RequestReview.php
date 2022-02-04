<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

/**
 *
 * ContainerAware used to retrieve
 * - Account Review Statuses
 * @package Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter
 */

class RequestReview implements Service, ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use OptionsAwareTrait;

	/**
	 * Account is waiting for review to start.
	 */
	public const REVIEW_STATUS_PENDING = 'PENDING_REVIEW';

	/**
	 * Account is under review.
	 */
	public const REVIEW_STATUS_UNDER_REVIEW = 'UNDER_REVIEW';

	/**
	 * 	If account has issues but offers are servable. Some of the issue can make account DISAPPROVED after a certain deadline.
	 */
	public const REVIEW_STATUS_WARNING = 'WARNING';

	/**
	 * There are one or more issues that needs to be resolved for account to be active for the program.
	 */
	public const REVIEW_STATUS_DISAPPROVED = 'DISAPPROVED';

	/**
	 * If the account has no issues and review is completed successfully.
	 */
	public const REVIEW_STATUS_APPROVED = 'APPROVED';

	/**
	 * If the account has to wait until request a new review.
	 */
	public const REVIEW_STATUS_BLOCKED = 'BLOCKED';


	/**
	 * Return the option name.
	 *
	 * @return string
	 */
	private function option_last_review_request(): string {
		return OptionsInterface::MC_LAST_REVIEW_REQUESTED_AT;
	}

	/**
	 * Get the last review request timestamp. This is necessary because we don't allow
	 * performing new requests until 7 days from the last request.
	 *
	 * @return int The Last Review request timestamp
	 */
	public function get_last_review_request() : int {
		return $this->options->get( $this->option_last_review_request(), 0 );
	}

	/**
	 * Set the last review request timestamp.
	 *
	 * @return bool If the update was successful
	 */
	public function set_last_review_request() : bool {
		return $this->options->update( $this->option_last_review_request(), time() );
	}

	/**
	 * Check if the user should wait to perform a new Review Request
	 *
	 * @return bool True if the user is allowed to perform a new review request
	 */
	public function is_allowed_new_review_request() : bool {
		return $this->get_next_review_request_attempt() > time();
	}

	/**
	 * @return int The timestamp for the next allowed reiew request.
	 */
	public function get_next_review_request_attempt() : int
	{
		$lastReview = $this->get_last_review_request();

		if (!$lastReview) {
			return time();
		}

		return strtotime('+7 days',  $this->get_last_review_request());
	}
}
