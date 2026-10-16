<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\Exports\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for building a row from an exportable entity.
 *
 * Implementations convert an entity object into an array of scalar values,
 * with each key representing a column in the final output (CSV, JSON, etc).
 */
interface ExportableRowBuilderInterface {
	/**
	 * Builds an exportable row from the given entity.
	 *
	 * @param mixed $entity The entity object (e.g., WC_Order, WP_Term).
	 * @return array<string, scalar>|null Associative array for one row, or null to skip.
	 */
	public function build_row( $entity ): ?array;
}
