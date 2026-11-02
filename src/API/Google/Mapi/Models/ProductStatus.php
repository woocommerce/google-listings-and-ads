<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductStatus
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models
 */
class ProductStatus {

	/** @var ItemLevelIssue[] */
	protected $item_level_issues = [];

	/** @var array */
	protected $destination_statuses = [];

	/** @var string|null */
	protected $last_update_date;

	/** @var string|null */
	protected $google_expiration_date;

	/**
	 * Hydrate from a MAPI response array.
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$instance = new self();

		foreach ( $data['itemLevelIssues'] ?? [] as $issue ) {
			$instance->item_level_issues[] = ItemLevelIssue::from_array( $issue );
		}

		$instance->destination_statuses   = $data['destinationStatuses'] ?? [];
		$instance->last_update_date       = $data['lastUpdateDate'] ?? null;
		$instance->google_expiration_date = $data['googleExpirationDate'] ?? null;

		return $instance;
	}

	/**
	 * @return ItemLevelIssue[]
	 */
	public function get_item_level_issues(): array {
		return $this->item_level_issues;
	}

	/**
	 * @return array
	 */
	public function get_destination_statuses(): array {
		return $this->destination_statuses;
	}

	/**
	 * @return string|null
	 */
	public function get_last_update_date(): ?string {
		return $this->last_update_date;
	}

	/**
	 * @return string|null RFC 3339 timestamp, or null when Google reports no expiration.
	 */
	public function get_google_expiration_date(): ?string {
		return $this->google_expiration_date;
	}

	/**
	 * Derive the aggregated reporting context status from the per-context destination
	 * statuses, following the official enum semantics (eligible for all contexts and
	 * countries = ELIGIBLE, some = ELIGIBLE_LIMITED, pending in all = PENDING,
	 * otherwise NOT_ELIGIBLE_OR_DISAPPROVED). The product_view report precomputes this
	 * value; products.list does not carry it, so it is rebuilt here from the same inputs.
	 *
	 * A product with no destination statuses at all (still processing) returns an empty
	 * string: callers treat it like a product absent from the report.
	 *
	 * @return string Aggregated status enum value, or '' when no destination statuses exist.
	 */
	public function get_aggregated_reporting_context_status(): string {
		$has_approved    = false;
		$has_pending     = false;
		$has_disapproved = false;

		foreach ( $this->destination_statuses as $destination_status ) {
			$has_approved    = $has_approved || ! empty( $destination_status['approvedCountries'] );
			$has_pending     = $has_pending || ! empty( $destination_status['pendingCountries'] );
			$has_disapproved = $has_disapproved || ! empty( $destination_status['disapprovedCountries'] );
		}

		if ( ! $has_approved && ! $has_pending && ! $has_disapproved ) {
			return '';
		}

		if ( $has_approved ) {
			return ( $has_pending || $has_disapproved ) ? 'ELIGIBLE_LIMITED' : 'ELIGIBLE';
		}

		// No approvals: all-pending is PENDING; any disapproval makes it not eligible anywhere.
		return $has_disapproved ? 'NOT_ELIGIBLE_OR_DISAPPROVED' : 'PENDING';
	}
}
