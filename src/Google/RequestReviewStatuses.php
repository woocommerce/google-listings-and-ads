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
		$issues   = [];
		$cooldown = 0;
		$status   = 'APPROVED';
		$review_eligible_regions = [];

		foreach ( $response as $program_type ) {
			foreach ( $program_type['data']['regionStatuses'] as $region_status ) {

				if ( isset( $region_status['reviewEligibilityStatus'] ) && $region_status['reviewEligibilityStatus'] === self::ELIGIBLE ) {
					array_push( $review_eligible_regions, $region_status['regionCodes'][0] );
				}

				if (
					isset( $region_status['reviewIneligibilityReasonDetails'] ) &&
					isset( $region_status['reviewIneligibilityReasonDetails']['cooldownTime'] )
				) {
					$region_cooldown = intval( $region_status['reviewIneligibilityReasonDetails']['cooldownTime'] );
					if ( ! $cooldown || $region_cooldown > $cooldown ) {
						$cooldown = $region_cooldown;
					}
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
			'issues'   => array_map( 'strtolower', array_unique( $issues ) ),
			'cooldown' => $cooldown,
			'status'   => $status,
			'reviewEligibleRegions' => array_unique( $review_eligible_regions )
		];
	}

}
