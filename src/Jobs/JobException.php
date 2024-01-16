<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Jobs;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\GoogleListingsAndAdsException;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class JobException
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Jobs
 */
class JobException extends RuntimeException implements GoogleListingsAndAdsException {

	/**
	 * Create a new exception instance for when a job item is not found.
	 *
	 * @return static
	 */
	public static function item_not_found(): JobException {
		return new static( __( 'Job item not found.', 'google-listings-and-ads' ) );
	}

	/**
	 * Create a new exception instance for when a required job item is not provided.
	 *
	 * @param string $item The item name.
	 *
	 * @return static
	 */
	public static function item_not_provided( string $item ): JobException {
		return new static(
			sprintf(
			/* translators: %s: the job item name */
				__( 'Required job item "%s" not provided.', 'google-listings-and-ads' ),
				$item
			)
		);
	}

	/**
	 * Create a new exception instance for when the provided items are not WC_Product instances.
	 *
	 * @param string $type The expected type.
	 *
	 * @return static
	 */
	public static function item_is_not_expected_type( string $type ): JobException {
		return new static(
			/* translators: %s: the type */
			sprintf( __( 'Some provided items were not instances of %s.', 'google-listings-and-ads' ), $type )
		);
	}

	/**
	 * Create a new exception instance for when a job is stopped due to a high failure rate.
	 *
	 * @param string $job_name
	 *
	 * @return static
	 */
	public static function stopped_due_to_high_failure_rate( string $job_name ): JobException {
		return new static(
			sprintf(
				/* translators: %s: the job name */
				__( 'The "%s" job was stopped because its failure rate is above the allowed threshold.', 'google-listings-and-ads' ),
				$job_name
			)
		);
	}

	/**
	 * Create a new exception instance for when a job is stopped due to a high failure rate.
	 *
	 * @param string $job_name
	 *
	 * @return static
	 */
	public static function job_does_not_exist( string $job_name ): JobException {
		return new static(
			sprintf(
				/* translators: %s: the job name */
				__( 'The job named "%s" does not exist.', 'google-listings-and-ads' ),
				$job_name
			)
		);
	}
}
