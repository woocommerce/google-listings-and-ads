<?php
/**
 * Includes the composer Autoloader used for packages and classes in the src/ directory.
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class.
 *
 * @since 3.7.0
 */
class Autoloader {

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	/**
	 * Require the autoloader and return the result.
	 *
	 * If the autoloader is not present, let's log the failure and display a nice admin notice.
	 *
	 * @return boolean
	 */
	public static function init() {
		$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

		if ( ! is_readable( $autoloader ) ) {
			self::missing_autoloader();
			return false;
		}

		$autoloader_result = require $autoloader;
		if ( ! $autoloader_result ) {
			return false;
		}

		self::ensure_deprecation_helper();

		return $autoloader_result;
	}

	/**
	 * If the autoloader is missing, add an admin notice.
	 */
	protected static function missing_autoloader() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
				esc_html__( 'Your installation of Google for WooCommerce is incomplete. If you installed from GitHub, please refer to this document to set up your development environment: https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment', 'google-listings-and-ads' )
			);
		}
		add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							/* translators: 1: is a link to a support document. 2: closing link */
							esc_html__( 'Your installation of Google for WooCommerce is incomplete. If you installed from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'google-listings-and-ads' ),
							'<a href="' . esc_url( 'https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
	}

	/**
	 * Ensure the Symfony deprecation helper is available.
	 *
	 * Composer de-duplicates autoloaded "files" globally. If another plugin ships a
	 * prefixed copy of symfony/deprecation-contracts, Composer may consider the file
	 * already loaded even though trigger_deprecation() was never defined. In that
	 * case, explicitly require our copy.
	 *
	 * @return void
	 */
	protected static function ensure_deprecation_helper() {
		if ( function_exists( 'trigger_deprecation' ) ) {
			return;
		}

		$file = dirname( __DIR__ ) . '/vendor/symfony/deprecation-contracts/function.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		// Composer may have skipped this file due to another plugin loading a prefixed copy.
		require_once $file;
	}
}
