<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Requirements;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class RequirementValidator
 *
 * @package AutomatticWooCommerceGoogleListingsAndAdsInternalRequirements
 */
abstract class RequirementValidator implements RequirementValidatorInterface {

	use PluginHelper;

	/**
	 * @var RequirementValidator[]
	 */
	private static $instances = [];

	/**
	 * Get the instance of the RequirementValidator object.
	 *
	 * @return RequirementValidator
	 */
	public static function instance(): RequirementValidator {
		$class = get_called_class();
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class();
		}
		return self::$instances[ $class ];
	}


	/**
	 * Add a standard requirement validation error notice.
	 *
	 * @param RuntimeException $e
	 */
	protected function add_admin_notice( RuntimeException $e ) {
		// Check if the plugin is active. If not, display error message and terminate.

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( $this->get_plugin_basename() ) ) {
			die(
				esc_html( $e->getMessage() ) . PHP_EOL
			);
		}

		// Display notice error message.
		add_action(
			'admin_notices',
			function() use ( $e ) {
				echo '<div class="notice notice-error">' . PHP_EOL;
				echo '	<p>' . esc_html( $e->getMessage() ) . '</p>' . PHP_EOL;
				echo '</div>' . PHP_EOL;
			}
		);
	}
}
