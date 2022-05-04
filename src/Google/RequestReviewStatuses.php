<?php

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Helper class for Account request Review feature
 */
class RequestReviewStatuses implements Service {

	public const ENABLED        = 'ENABLED';
	public const DISAPPROVED    = 'DISAPPROVED';
	public const WARNING        = 'WARNING';
	public const UNDER_REVIEW   = 'UNDER_REVIEW';
	public const PENDING_REVIEW = 'PENDING_REVIEW';
	public const ONBOARDING     = 'ONBOARDING';
	public const APPROVED       = 'APPROVED';
	public const NO_OFFERS      = 'NO_OFFERS_UPLOADED';


	public const MC_ACCOUNT_REVIEW_LIFETIME = MINUTE_IN_SECONDS * 20; // 20 minutes

	/**
	 * Merges the different program statuses, issues and cooldown period date.
	 *
	 * @param array $response Associative array containing the response data from Google API
	 * @return array The computed status, with the issues and cooldown period.
	 */
	public function get_statuses_from_response( array $response ) {
		$issues   = [];
		$cooldown = 0;
		$status   = null;

		$valid_program_states = [ self::ENABLED, self::NO_OFFERS ];

		foreach ( $response as $program_type ) {

			// In case any Program is with no offers we consider it Onboarding
			if ( $program_type['data']['globalState'] === self::NO_OFFERS ) {
				$status = self::ONBOARDING;
				break;
			}

			// In case any Program is not enabled or there are no regionStatuses we return null status
			if ( ! isset( $program_type['data']['regionStatuses'] ) || ! in_array( $program_type['data']['globalState'], $valid_program_states, true ) ) {
				$status = null;
				break;
			}

			// otherwise we compute the new status, issues and cooldown period
			foreach ( $program_type['data']['regionStatuses'] as $region_status ) {
				$issues = array_merge( $issues, $region_status['reviewIssues'] ?? [] );
				self::maybe_update_cooldown_period( $region_status, $cooldown );
				self::maybe_update_status( $region_status['eligibilityStatus'], $status );
			}
		}

		return [
			'issues'   => array_map( 'strtolower', array_values( array_unique( $issues ) ) ),
			'cooldown' => self::get_cooldown( $cooldown ), // add lifetime cache to cooldown time
			'status'   => $status,
		];
	}
	/**
	 * Updates the cooldown period in case the new cooldown period date is available and later than the current cooldown period.
	 *
	 * @param array $region_status Associative array containing (maybe) a cooldown date property.
	 * @param int   $cooldown Referenced current cooldown to compare with
	 */
	private function maybe_update_cooldown_period( $region_status, &$cooldown ) {
		if (
			isset( $region_status['reviewIneligibilityReasonDetails'] ) &&
			isset( $region_status['reviewIneligibilityReasonDetails']['cooldownTime'] )
		) {
			$region_cooldown = intval( strtotime( $region_status['reviewIneligibilityReasonDetails']['cooldownTime'] ) );

			if ( ! $cooldown || $region_cooldown > $cooldown ) {
				$cooldown = $region_cooldown;
			}
		}
	}

	/**
	 * Updates the status reference in case the new status has more priority.
	 *
	 * @param String $new_status New status to check has more priority than the current one
	 * @param String $status Referenced current status
	 */
	private function maybe_update_status( $new_status, &$status ) {
		$status_priority_list = [
			self::ONBOARDING, // highest priority
			self::DISAPPROVED,
			self::WARNING,
			self::UNDER_REVIEW,
			self::PENDING_REVIEW,
			self::APPROVED,
		];

		if ( is_null( $status ) || array_search( $status, $status_priority_list, true ) > array_search( $new_status, $status_priority_list, true ) ) {
			$status = $new_status;
		}
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
	private function get_cooldown( int $cooldown ) {
		if ( $cooldown ) {
			$cooldown = ( $cooldown + self::get_account_review_lifetime() ) * 1000;
		}

		return $cooldown;
	}

}
