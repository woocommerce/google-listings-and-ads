<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Helper class for Account request Review feature
 */
class RequestReviewStatuses implements Service {

	public const DISAPPROVED    = 'DISAPPROVED';
	public const WARNING        = 'WARNING';
	public const ELIGIBLE       = 'ELIGIBLE';
	public const UNDER_REVIEW   = 'UNDER_REVIEW';
	public const PENDING_REVIEW = 'PENDING_REVIEW';
	public const ONBOARDING     = 'ONBOARDING';

	/**
	 * Merges the different program statuses based on priority
	 *
	 * @param array $response
	 *
	 * @return array
	 */
	public function get_statuses_from_response( array $response ) {
		$issues         = [];
		$review_allowed = false;
		$status         = 'APPROVED';

		foreach ( $response as $program_type ) {
			foreach ( $program_type['data']['regionStatuses'] as $region_status ) {

				if ( $region_status['reviewEligibilityStatus'] === self::ELIGIBLE ) {
					$review_allowed = true;
				}

				if ( $region_status['eligibilityStatus'] === self::DISAPPROVED || $region_status['eligibilityStatus'] === self::WARNING ) {
					if ( $status !== self::DISAPPROVED ) {
						$status = $region_status['eligibilityStatus'];
					}

					$issues = array_merge( $issues, $region_status['reviewIssues'] ?? [] );
				}

				if ( $status === self::DISAPPROVED ) {
					continue;
				}

				if ( $region_status['eligibilityStatus'] === self::UNDER_REVIEW ) {
					$status = self::UNDER_REVIEW;
				}

				if ( $status === self::UNDER_REVIEW ) {
					continue;
				}

				if ( $region_status['eligibilityStatus'] === self::PENDING_REVIEW ) {
					$status = self::PENDING_REVIEW;
				}

				if ( $status === self::PENDING_REVIEW ) {
					continue;
				}

				if ( $region_status['eligibilityStatus'] === self::ONBOARDING ) {
					$status = self::ONBOARDING;
				}
			}
		}

		return [
			'issues'        => array_unique( $issues ),
			'reviewAllowed' => $review_allowed,
			'status'        => $status,
		];
	}

}
