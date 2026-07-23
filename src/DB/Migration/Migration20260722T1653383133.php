<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MarketService;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20260722T1653383133
 *
 * Backwards compatibility for the Markets feature: splits pre-existing per-country
 * flat-rate shipping overrides into their own editable secondary markets so a country
 * that had a distinct rate/delivery time (e.g. Cameroon) is not silently folded into
 * the primary market and left uneditable.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 3.9.0
 */
class Migration20260722T1653383133 extends AbstractMigration {

	/**
	 * @var MarketService
	 */
	protected $market_service;

	/**
	 * Migration constructor.
	 *
	 * @param wpdb          $wpdb
	 * @param MarketService $market_service
	 */
	public function __construct( wpdb $wpdb, MarketService $market_service ) {
		parent::__construct( $wpdb );
		$this->market_service = $market_service;
	}

	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number.
	 */
	public function get_applicable_version(): string {
		return '3.9.0';
	}

	/**
	 * Apply the migration.
	 *
	 * @return void
	 */
	public function apply(): void {
		$this->market_service->backfill_secondary_markets_from_shipping();
	}
}
