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
		echo '<div class="notice notice-error">' . PHP_EOL;
		echo '	<p>' . esc_html( $message ) . '</p>' . PHP_EOL;
		echo '</div>' . PHP_EOL;
	}
}
