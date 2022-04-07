<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

class RequestReviewStatuses implements Service {

	const DISAPPROVED = 'DISAPPROVED';
	const WARNING = 'WARNING';
	const ELIGIBLE = 'ELIGIBLE';
	const UNDER_REVIEW = 'UNDER_REVIEW';
	const PENDING_REVIEW = 'PENDING_REVIEW';
	const ONBOARDING = 'ONBOARDING';

	public function get_statuses_from_response( array $response ) {

		$issues        = [];
		$reviewAllowed = false;
		$status        = 'APPROVED';

		foreach ( $response as $program_type ) {
			foreach ( $program_type['data']['regionStatuses'] as $region_status ) {

				if ( $region_status['reviewEligibilityStatus'] === $this::ELIGIBLE ) {
					$reviewAllowed = true;
				}

				if ( $region_status['eligibilityStatus'] === $this::DISAPPROVED || $region_status['eligibilityStatus'] === $this::WARNING ) {
					if ( $status !== $this::DISAPPROVED ) {
						$status = $region_status['eligibilityStatus'];
					}

					$issues = array_merge( $issues, $region_status['reviewIssues'] ?? [] );
				}

				if ( $status === $this::DISAPPROVED ) {
					continue;
				}

				if ( $region_status['eligibilityStatus'] === $this::UNDER_REVIEW ) {
					$status = $this::UNDER_REVIEW;
				}

				if ( $status === $this::UNDER_REVIEW ) {
					continue;
				}

				if ( $region_status['eligibilityStatus'] === $this::PENDING_REVIEW ) {
					$status = $this::PENDING_REVIEW;
				}

				if ( $status === $this::PENDING_REVIEW ) {
					continue;
				}

				if ( $region_status['eligibilityStatus'] === $this::ONBOARDING ) {
					$status = $this::ONBOARDING;
				}
			}
		}

		return [ 'issues' => array_unique( $issues ), 'reviewAllowed' => $reviewAllowed, 'status' => $status ];
	}

}
