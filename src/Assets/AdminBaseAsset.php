<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Assets;

use Automattic\WooCommerce\GoogleForWC\Infrastructure\Conditional;

/**
 * Class BaseAdminAsset
 *
 * @package Automattic\WooCommerce\GoogleForWC\Assets
 */
abstract class AdminBaseAsset extends BaseAsset implements Conditional {

	/**
	 * Check whether this object is currently needed.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool {
		return is_admin();
	}

	/**
	 * Get the enqueue action to use.
	 *
	 * @return string
	 */
	protected function get_enqueue_action(): string {
		return 'admin_enqueue_scripts';
	}

	/**
	 * Get the enqueue action to use.
	 *
	 * @return string
	 */
	protected function get_dequeue_action(): string {
		return 'admin_print_scripts';
	}
}
