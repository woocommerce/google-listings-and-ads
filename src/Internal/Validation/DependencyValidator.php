<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal\Validation;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

/**
 * Class DependencyValidator
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal\Validation
 */
abstract class DependencyValidator implements Validatable {

	/**
	 * @var DependencyValidator[]
	 */
	private static $instances = [];

	/**
	 * Get the instance of the Dependency object.
	 *
	 * @return DependencyValidator
	 */
	public static function instance(): DependencyValidator {
		$class = get_called_class();
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class();
		}
		return self::$instances[ $class ];
	}


	/**
	 * Add a standard dependency validation error notice.
	 *
	 * @param RuntimeException $e
	 */
	protected function add_admin_notice( RuntimeException $e ) {
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
