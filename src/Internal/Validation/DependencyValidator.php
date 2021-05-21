<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class DependencyValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal
 */
abstract class DependencyValidator implements Validatable {

	/**
	 * Add a standard dependency validation error notice.
	 *
	 * @param RuntimeException $e
	 */
	protected function add_admin_notice( RuntimeException $e ) {
		add_action(
			'admin_notices',
			function() use ( $e ) {
				$this->admin_notice( $e->getMessage() );
			}
		);
	}

	/**
	 * Display an admin notice with the provided message.
	 *
	 * @param string $message
	 */
	protected function admin_notice( string $message ) {
		?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}
}
