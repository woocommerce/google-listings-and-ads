<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class DependencyValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
abstract class DependencyValidator {
	/**
	 * Validate all dependencies that we require for the plugin to function properly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		try {
			$this->validate_all();

			return true;
		} catch ( RuntimeException $e ) {
			add_action(
				'admin_notices',
				function() use ( $e ) {
					$this->admin_notice( $e->getMessage() );
				}
			);

			return false;
		}
	}

	/**
	 * Execute all validation methods.
	 */
	abstract protected function validate_all(): void;

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
