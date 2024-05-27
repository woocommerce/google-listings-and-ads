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
	public const ELIGIBLE       = 'ELIGIBLE';


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

		$valid_program_states    = [ self::ENABLED, self::NO_OFFERS ];
		$review_eligible_regions = [];

		foreach ( $response as $program_type_name => $program_type ) {

			// In case any Program is with no offers we consider it Onboarding
			if ( $program_type['globalState'] === self::NO_OFFERS ) {
				$status = self::ONBOARDING;
				break;
			}

			// In case any Program is not enabled or there are no regionStatuses we return null status
			if ( ! isset( $program_type['regionStatuses'] ) || ! in_array( $program_type['globalState'], $valid_program_states, true ) ) {
				continue;
			}

			// Otherwise, we compute the new status, issues and cooldown period
			foreach ( $program_type['regionStatuses'] as $region_status ) {
				$issues                  = array_merge( $issues, $region_status['reviewIssues'] ?? [] );
				$cooldown                = $this->maybe_update_cooldown_period( $region_status, $cooldown );
				$status                  = $this->maybe_update_status( $region_status['eligibilityStatus'], $status );
				$review_eligible_regions = $this->maybe_load_eligible_region( $region_status, $review_eligible_regions, $program_type_name );
			}
		}

		return [
			'issues'                => array_map( 'strtolower', array_values( array_unique( $issues ) ) ),
			'cooldown'              => $this->get_cooldown( $cooldown ), // add lifetime cache to cooldown time
			'status'                => $status,
			'reviewEligibleRegions' => array_unique( $review_eligible_regions ),
		];
	}
	/**
	 * Updates the cooldown period in case the new cooldown period date is available and later than the current cooldown period.
	 *
	 * @param array $region_status Associative array containing (maybe) a cooldown date property.
	 * @param int   $cooldown Referenced current cooldown to compare with
	 *
	 * @return int The cooldown
	 */
	private function maybe_update_cooldown_period( $region_status, $cooldown ) {
		if (
			isset( $region_status['reviewIneligibilityReasonDetails'] ) &&
			isset( $region_status['reviewIneligibilityReasonDetails']['cooldownTime'] )
		) {
			$region_cooldown = intval( strtotime( $region_status['reviewIneligibilityReasonDetails']['cooldownTime'] ) );

			if ( ! $cooldown || $region_cooldown > $cooldown ) {
				$cooldown = $region_cooldown;
			}
		}

		return $cooldown;
	}

	/**
	 * Updates the status reference in case the new status has more priority.
	 *
	 * @param String $new_status New status to check has more priority than the current one
	 * @param String $status Referenced current status
	 *
	 * @return String The status
	 */
	private function maybe_update_status( $new_status, $status ) {
		$status_priority_list = [
			self::ONBOARDING, // highest priority
			self::DISAPPROVED,
			self::WARNING,
			self::UNDER_REVIEW,
			self::PENDING_REVIEW,
			self::APPROVED,
		];

		$current_status_priority = array_search( $status, $status_priority_list, true );
		$new_status_priority     = array_search( $new_status, $status_priority_list, true );

		if ( $new_status_priority !== false && ( is_null( $status ) || $current_status_priority > $new_status_priority ) ) {
			return $new_status;
		}

		return $status;
	}


	/**
	 * Updates the regions where a request review is allowed.
	 *
	 * @param array                                      $region_status Associative array containing the region eligibility.
	 * @param array                                      $review_eligible_regions Indexed array with the current eligible regions.
	 * @param "freeListingsProgram"|"shoppingAdsProgram" $type The program type.
	 *
	 * @return array The (maybe) modified $review_eligible_regions array
	 */
	private function maybe_load_eligible_region( $region_status, $review_eligible_regions, $type = 'freeListingsProgram' ) {
		if (
			! empty( $region_status['regionCodes'] ) &&
			isset( $region_status['reviewEligibilityStatus'] ) &&
			$region_status['reviewEligibilityStatus'] === self::ELIGIBLE
		) {

			$region_codes = $region_status['regionCodes'];
			sort( $region_codes ); // sometimes the regions come unsorted between the different programs
			$region_id = $region_codes[0];

			if ( ! isset( $review_eligible_regions[ $region_id ] ) ) {
				$review_eligible_regions[ $region_id ] = [];
			}

			$review_eligible_regions[ $region_id ][] = strtolower( $type ); // lowercase as is how we expect it in WCS

		}

		return $review_eligible_regions;
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
			$cooldown = ( $cooldown + $this->get_account_review_lifetime() ) * 1000;
		}

		return $cooldown;
	}
}
