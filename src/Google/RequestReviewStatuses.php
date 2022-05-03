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

	public const MC_ACCOUNT_REVIEW_LIFETIME = MINUTE_IN_SECONDS * 20; // 20 minutes

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

		foreach ( $response as $program_type ) {
			foreach ( $program_type['data']['regionStatuses'] as $region_status ) {

				if (
					isset( $region_status['reviewIneligibilityReasonDetails'] ) &&
					isset( $region_status['reviewIneligibilityReasonDetails']['cooldownTime'] )
				) {
					$region_cooldown = intval( strtotime( $region_status['reviewIneligibilityReasonDetails']['cooldownTime'] ) );

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
			'cooldown' => self::get_cooldown( $cooldown ), // add lifetime cache to cooldown time
			'status'   => $status,
		];
	}

	/**
	 * Allows a hook to modify the lifetime of the Account review data.
	 *
	 * @return int
	 */
	public function get_account_review_lifetime(): int {
		return apply_filters( 'woocommerce_gla_mc_account_review_lifetime', self::MC_ACCOUNT_REVIEW_LIFETIME );
	}

	/**
	 * @param int $cooldown The cooldown in PHP format (seconds)
	 *
	 * @return int The cooldown in milliseconds and adding the lifetime cache
	 */
	private function get_cooldown( $cooldown ) {
		if ( $cooldown ) {
			$cooldown = ( $cooldown + self::get_account_review_lifetime() ) * 1000;
		}

		return $cooldown;
	}

}
