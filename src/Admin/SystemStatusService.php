<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin;

use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\NotificationsService;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class SystemStatusService
 * This class adds Google for WooCommerce information to the WooCommerce System Status Report
 *
 * @since 3.2.0
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
	 * SystemStatusService constructor
	 *
	 * @param NotificationsService $notifications_service
	 */
	public function __construct( NotificationsService $notifications_service ) {
		$this->notifications_service = $notifications_service;
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
		// Check if the notifications service is available to prevent fatal errors
		if ( ! $this->notifications_service ) {
			return;
		}

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
				<td class="help"><?php echo wc_help_tip( 'Current sync mode configuration status.' ); ?></td>
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
				<td class="help"><?php echo wc_help_tip( 'Current sync mode configuration status.' ); ?></td>
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

		foreach ( $sync_mode as $data_type => $modes ) {
			if ( ! is_array( $modes ) ) {
				continue;
			}

			$data_type_label = ucfirst( str_replace( '_', ' ', $data_type ) );
			
			?>
			<tr>
				<td data-export-label="<?php echo esc_attr( $data_type_label . ' Sync Mode' ); ?>">
					<?php echo esc_html( sprintf( '%s Sync Mode:', $data_type_label ) ); ?>
				</td>
				<td class="help">
					<?php echo wc_help_tip( sprintf( 'Shows the current API Pull and MC Push sync settings for %s data.', strtolower( $data_type_label ) ) ); ?>
				</td>
				<td>
					<?php
					$pull_enabled = isset( $modes['pull'] ) && $modes['pull'];
					$push_enabled = isset( $modes['push'] ) && $modes['push'];
					
					$pull_status_text = $pull_enabled ? 'Enabled' : 'Disabled';
					$push_status_text = $push_enabled ? 'Enabled' : 'Disabled';
					
					// Create a concise status that matches WooCommerce export patterns
					$status_summary = sprintf( 'API Pull: %s / MC Push: %s', $pull_status_text, $push_status_text );
					
					if ( $pull_enabled && $push_enabled ) {
						$class = 'yes';
						$icon = 'dashicons-yes';
					} elseif ( ! $pull_enabled && ! $push_enabled ) {
						$class = 'error';
						$icon = 'dashicons-warning';
					} else {
						$class = 'error'; // Mixed state
						$icon = 'dashicons-warning';
					}
					?>
					<mark class="<?php echo esc_attr( $class ); ?>">
						<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
						<?php echo esc_html( $status_summary ); ?>
					</mark>
				</td>
			</tr>
			<?php
		}
	}
}