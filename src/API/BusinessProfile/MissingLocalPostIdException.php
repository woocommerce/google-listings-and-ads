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
class MissingLocalPostIdException extends Exception {

	/** @var bool */
	private $image_omitted;

	/** @var string */
	private $outcome;

	/**
	 * MissingLocalPostIdException constructor.
	 *
	 * @param string $message
	 * @param int    $code
	 * @param bool   $image_omitted Whether the post was sent without its featured image, because no version of it was usable.
	 * @param string $outcome       The {@see SyncOutcome} for the accepted post: SUCCESS, SENT_PENDING_VERIFICATION
	 *                              when the location was unverified, or SENT_LOCATION_RESTRICTED when it's under
	 *                              review, a duplicate, suspended, or disabled.
	 */
	public function __construct( string $message, int $code, bool $image_omitted = false, string $outcome = SyncOutcome::SUCCESS ) {
		parent::__construct( $message, $code );

		$this->image_omitted = $image_omitted;
		$this->outcome       = $outcome;
	}

	/**
	 * Whether the post was sent without its featured image.
	 *
	 * @return bool
	 */
	public function is_image_omitted(): bool {
		return $this->image_omitted;
	}

	/**
	 * Get the {@see SyncOutcome} for the accepted post.
	 *
	 * @return string
	 */
	public function get_outcome(): string {
		return $this->outcome;
	}
}
