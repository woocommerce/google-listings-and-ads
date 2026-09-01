<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;

/**
 * Helper class for the Account Request Review feature.
 *
 * Maps the Merchant API Issue Resolution `renderaccountissues` response onto the
 * review status the Request Review UI consumes. The old Content API program model
 * (per-region eligibility, cooldown windows) has no Merchant API equivalent, so the
 * status is derived from issue severity and the availability of the account-review
 * action.
 */
class RequestReviewStatuses implements Service {

	/** Derived account-review states. */
	public const APPROVED     = 'APPROVED';
	public const DISAPPROVED  = 'DISAPPROVED';
	public const WARNING      = 'WARNING';
	public const UNDER_REVIEW = 'UNDER_REVIEW';

	/** Issue Resolution impact severities. */
	public const SEVERITY_ERROR   = 'ERROR';
	public const SEVERITY_WARNING = 'WARNING';
	public const SEVERITY_INFO    = 'INFO';

	/** The external action type that maps to requesting an account review in Merchant Center. */
	public const EXTERNAL_REVIEW_ACTION = 'REVIEW_ACCOUNT_ISSUE_IN_MERCHANT_CENTER';

	public const MC_ACCOUNT_REVIEW_LIFETIME = MINUTE_IN_SECONDS * 20; // 20 minutes

	/**
	 * Lifetime of the optimistic UNDER_REVIEW cache entry. The Merchant API exposes no
	 * "under review" signal, so after a successful request we hold the client-side
	 * UNDER_REVIEW status for this longer window (reviews take at least several days) before
	 * it reverts to the live severity-based status.
	 */
	public const UNDER_REVIEW_LIFETIME = DAY_IN_SECONDS * 3; // 3 days

	/**
	 * Reduce a `renderaccountissues` response to the review status the UI needs.
	 *
	 * @param array $response RenderAccountIssuesResponse decoded as an array.
	 *
	 * @return array {
	 *     @type string     $status       Derived account-review status.
	 *     @type string[]   $issues       Titles of the account issues blocking approval.
	 *     @type array|null $reviewAction The account-review action (in-app or redirect), or null.
	 * }
	 */
	public function get_statuses_from_response( array $response ): array {
		$issues        = [];
		$review_action = null;
		$has_error     = false;
		$has_warning   = false;

		foreach ( $response['renderedIssues'] ?? [] as $issue ) {
			$severity    = $issue['impact']['severity'] ?? '';
			$has_error   = $has_error || self::SEVERITY_ERROR === $severity;
			$has_warning = $has_warning || self::SEVERITY_WARNING === $severity;

			// Only issues that affect approval (error/warning) belong in the review list;
			// informational issues are surfaced elsewhere and do not block a review.
			if ( ! empty( $issue['title'] ) && ( self::SEVERITY_ERROR === $severity || self::SEVERITY_WARNING === $severity ) ) {
				$issues[] = $issue['title'];
			}

			if ( null === $review_action ) {
				$review_action = $this->find_review_action( $issue['actions'] ?? [] );
			}
		}

		return [
			'status'       => $this->derive_status( $has_error, $has_warning ),
			'issues'       => array_values( array_unique( $issues ) ),
			'reviewAction' => $review_action,
		];
	}

	/**
	 * Find the account-review action among an issue's actions.
	 *
	 * The review action appears as an `externalAction` of type
	 * REVIEW_ACCOUNT_ISSUE_IN_MERCHANT_CENTER when issues are rendered for redirect, or as
	 * its `builtinUserInputAction` counterpart when rendered for in-app completion. This is
	 * the one piece that needs validating against a real disapproved account (this test
	 * account has no issues).
	 *
	 * @param array $actions List of Action resources for a rendered issue.
	 *
	 * @return array|null Normalised review action, or null when none is present.
	 */
	private function find_review_action( array $actions ): ?array {
		foreach ( $actions as $action ) {
			if ( self::EXTERNAL_REVIEW_ACTION === ( $action['externalAction']['type'] ?? '' ) ) {
				return [
					'type'        => 'redirect',
					'isAvailable' => (bool) ( $action['isAvailable'] ?? false ),
					'buttonLabel' => $action['buttonLabel'] ?? '',
					'uri'         => esc_url_raw( $action['externalAction']['uri'] ?? '' ),
				];
			}

			// An in-app action is only triggerable with an action context and a flow id;
			// skip it otherwise so we never post an empty flow to triggeraction.
			$flow    = $action['builtinUserInputAction']['flows'][0] ?? [];
			$flow_id = $flow['id'] ?? '';
			if ( isset( $action['builtinUserInputAction']['actionContext'] ) && '' !== $flow_id ) {
				return [
					'type'          => 'in_app',
					'isAvailable'   => (bool) ( $action['isAvailable'] ?? false ),
					'buttonLabel'   => $action['buttonLabel'] ?? '',
					'actionContext' => $action['builtinUserInputAction']['actionContext'],
					'flowId'        => $flow_id,
					'inputValues'   => $this->build_input_values( $flow['inputs'] ?? [] ),
				];
			}
		}

		return null;
	}

	/**
	 * Build the triggeraction inputValues for a review flow's input fields.
	 *
	 * The account-review flow gates on a confirmation checkbox ("I have resolved all the
	 * issues"), so triggeraction rejects an empty inputValues when the flow has a required input.
	 * Every checkbox in this flow is such a confirmation, which the merchant asserts by clicking
	 * Request review, so all checkboxes are confirmed rather than keyed off the `required` flag
	 * (which the render is not guaranteed to set). Text and choice inputs have no server-side
	 * value and are left to the Merchant Center redirect flow.
	 *
	 * @param array $inputs The flow's InputField list.
	 *
	 * @return array<int, array{inputFieldId: string, checkboxInputValue: array{value: bool}}>
	 */
	private function build_input_values( array $inputs ): array {
		$input_values = [];

		foreach ( $inputs as $input ) {
			$id = $input['id'] ?? '';
			if ( '' !== $id && isset( $input['checkboxInput'] ) ) {
				$input_values[] = [
					'inputFieldId'       => $id,
					'checkboxInputValue' => [ 'value' => true ],
				];
			}
		}

		return $input_values;
	}

	/**
	 * Derive a coarse account-review status from the issue severities present.
	 *
	 * @param bool $has_error   Whether any issue has ERROR severity.
	 * @param bool $has_warning Whether any issue has WARNING severity.
	 *
	 * @return string
	 */
	private function derive_status( bool $has_error, bool $has_warning ): string {
		if ( $has_error ) {
			return self::DISAPPROVED;
		}

		if ( $has_warning ) {
			return self::WARNING;
		}

		return self::APPROVED;
	}

	/**
	 * Allows a hook to modify the lifetime of the Account review data cache.
	 *
	 * @return int
	 */
	public function get_account_review_lifetime(): int {
		return apply_filters( 'woocommerce_gla_mc_account_review_lifetime', self::MC_ACCOUNT_REVIEW_LIFETIME );
	}

	/**
	 * Lifetime of the optimistic UNDER_REVIEW cache entry.
	 *
	 * @return int
	 */
	public function get_under_review_lifetime(): int {
		return apply_filters( 'woocommerce_gla_mc_under_review_lifetime', self::UNDER_REVIEW_LIFETIME );
	}
}
