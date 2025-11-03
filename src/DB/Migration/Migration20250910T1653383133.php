<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration;

use Automattic\WooCommerce\GoogleListingsAndAds\ActionScheduler\ActionScheduler;

defined( 'ABSPATH' ) || exit;

/**
 * Class Migration20250910T1653383133
 *
 * Migration class to remove the ads recommendation table.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\DB\Migration
 *
 * @since 3.5.0
 */
class Migration20250910T1653383133 extends AbstractMigration {

	/**
	 * @var ActionScheduler
	 */
	protected $action_scheduler;

	/**
	 * Migration constructor.
	 *
	 * @param \wpdb           $wpdb
	 * @param ActionScheduler $action_scheduler
	 */
	public function __construct( \wpdb $wpdb, ActionScheduler $action_scheduler ) {
		parent::__construct( $wpdb );
		$this->action_scheduler = $action_scheduler;
	}

	/**
	 * Returns the version to apply this migration for.
	 *
	 * @return string A version number.
	 */
	public function get_applicable_version(): string {
		return '3.5.0';
	}

	/**
	 * Apply the migrations.
	 *
	 * @return void
	 */
	public function apply(): void {
		// Remove the ads_recommendation table if it exists.
		$this->wpdb->query( "DROP TABLE IF EXISTS {$this->wpdb->prefix}gla_ads_recommendations" ); // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			// Remove any scheduled actions that use the ads recommendations table.
			$job_hooks = [
				'gla/jobs/update_ads_recommendations/start',
				'gla/jobs/update_ads_recommendations/process_item',
			];

			foreach ( $job_hooks as $hook ) {
				if ( $this->action_scheduler->has_scheduled_action( $hook ) ) {
					as_unschedule_all_actions( $hook );
				}
			}
		}
	}
}
