<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidVersion;

defined( 'ABSPATH' ) || exit;

/**
 * Class VersionValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
class VersionValidator {

	/**
	 * Validate all versions that we require for the plugin to function properly.
	 *
	 * @return bool
	 */
	public function validate(): bool {
		try {
			$this->validate_php_version();

			return true;
		} catch ( InvalidVersion $e ) {
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
	 * Validate the PHP version being used.
	 *
	 * @throws InvalidVersion When the PHP version does not meet the minimum version.
	 */
	protected function validate_php_version() {
		if ( ! version_compare( PHP_VERSION, '7.3', '>=' ) ) {
			throw InvalidVersion::from_php_version( PHP_VERSION, '7.3' );
		}
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
