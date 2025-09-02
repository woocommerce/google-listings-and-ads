<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class SystemStatusService
 * This class adds Google for WooCommerce information to the WooCommerce System Status Report
 *
 * @since 3.4.2
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin
 */
class SystemStatusService implements Service, Registerable {

	/**
	 * The NotificationsService instance
	 *
	 * @var NotificationsService
	 */
	private $notifications_service;

	/**
	 * @var MerchantCenterService $merchant_center
	 */
	private $merchant_center;

	/**
	 * SystemStatusService constructor
	 *
	 * @param NotificationsService  $notifications_service
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct( NotificationsService $notifications_service, MerchantCenterService $merchant_center ) {
		$this->notifications_service = $notifications_service;
		$this->merchant_center       = $merchant_center;
	}

	/**
	 * Register the service
	 */
	public function register(): void {
		add_action( 'woocommerce_system_status_report', [ $this, 'add_system_status_section' ] );
	}

	/**
	 * Add Google for WooCommerce section to System Status Report
	 */
	public function add_system_status_section(): void {
		?>
		<table class="wc_status_table widefat" cellspacing="0">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Google for WooCommerce">
						<h2><?php esc_html_e( 'Google for WooCommerce', 'google-listings-and-ads' ); ?></h2>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php $this->render_sync_mode_rows(); ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render the sync mode configuration rows
	 */
	private function render_sync_mode_rows(): void {
		try {
			$sync_mode = $this->notifications_service->get_current_sync_mode();
		} catch ( Exception $exception ) {
			?>
			<tr>
				<td data-export-label="Sync Mode Status"><?php esc_html_e( 'Sync Mode Status:', 'google-listings-and-ads' ); ?></td>
				<td class="help"><?php echo wp_kses_post( wc_help_tip( 'Current sync mode configuration status.' ) ); ?></td>
				<td>
					<mark class="error">
						<span class="dashicons dashicons-warning"></span> 
						<?php esc_html_e( 'Error retrieving sync mode configuration', 'google-listings-and-ads' ); ?>
					</mark>
				</td>
			</tr>
			<?php
			return;
		}

		if ( ! is_array( $sync_mode ) || empty( $sync_mode ) ) {
			?>
			<tr>
				<td data-export-label="Sync Mode Status"><?php esc_html_e( 'Sync Mode Status:', 'google-listings-and-ads' ); ?></td>
				<td class="help"><?php echo wp_kses_post( wc_help_tip( 'Current sync mode configuration status.' ) ); ?></td>
				<td>
					<mark class="error">
						<span class="dashicons dashicons-warning"></span> 
						<?php esc_html_e( 'No sync mode configuration found', 'google-listings-and-ads' ); ?>
					</mark>
				</td>
			</tr>
			<?php
			return;
		}

		$is_pull_ready = $this->notifications_service->is_ready();
		$is_push_ready = $this->merchant_center->is_ready_for_syncing();

		foreach ( $sync_mode as $data_type => $modes ) {
			if ( ! is_array( $modes ) ) {
				continue;
			}

			$data_type_label = ucfirst( str_replace( '_', ' ', $data_type ) );
			$pull_enabled    = $is_pull_ready && isset( $modes['pull'] ) && $modes['pull'];
			$push_enabled    = $is_push_ready && isset( $modes['push'] ) && $modes['push'];

			// API Pull row
			?>
			<tr>
				<td data-export-label="<?php echo esc_attr( $data_type_label . ' API Pull' ); ?>">
					<?php echo esc_html( sprintf( '%s API Pull:', $data_type_label ) ); ?>
				</td>
				<td class="help">
					<?php echo wp_kses_post( wc_help_tip( sprintf( 'Shows if API Pull sync is ready and enabled for %s data.', strtolower( $data_type_label ) ) ) ); ?>
				</td>
				<td>
					<?php if ( $pull_enabled ) : ?>
						<mark class="yes"><span class="dashicons dashicons-yes"></span> Enabled</mark>
					<?php else : ?>
						<mark class="error"><span class="dashicons dashicons-warning"></span> Disabled</mark>
					<?php endif; ?>
				</td>
			</tr>
			<?php

			// MC Push row
			?>
			<tr>
				<td data-export-label="<?php echo esc_attr( $data_type_label . ' MC Push' ); ?>">
					<?php echo esc_html( sprintf( '%s MC Push:', $data_type_label ) ); ?>
				</td>
				<td class="help">
					<?php echo wp_kses_post( wc_help_tip( sprintf( 'Shows if MC Push sync is ready and enabled for %s data.', strtolower( $data_type_label ) ) ) ); ?>
				</td>
				<td>
					<?php if ( $push_enabled ) : ?>
						<mark class="yes"><span class="dashicons dashicons-yes"></span> Enabled</mark>
					<?php else : ?>
						<mark class="error"><span class="dashicons dashicons-warning"></span> Disabled</mark>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		}
	}
}
