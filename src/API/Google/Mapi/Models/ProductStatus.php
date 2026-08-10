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

		$instance->destination_statuses = $data['destinationStatuses'] ?? [];
		$instance->last_update_date     = $data['lastUpdateDate'] ?? null;

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
}
