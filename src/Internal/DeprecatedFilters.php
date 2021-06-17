<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use WC_Deprecated_Hooks;

defined( 'ABSPATH' ) || exit;

/**
 * Deprecated Filters class.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal
 */
class DeprecatedFilters extends WC_Deprecated_Hooks implements Service {

	/**
	 * Array of deprecated hooks we need to handle.
	 * Format of 'new' => 'old'.
	 *
	 * @var array
	 */
	protected $deprecated_hooks = [
		'woocommerce_gla_enable_connection_test' => 'gla_enable_connection_test',
		'woocommerce_gla_enable_debug_logging'   => 'gla_enable_debug_logging',
		'woocommerce_gla_enable_reports'         => 'gla_enable_reports',
	];

	/**
	 * Array of versions when each hook has been deprecated.
	 *
	 * @var array
	 */
	protected $deprecated_version = [
		'gla_enable_connection_test' => '1.0.1',
		'gla_enable_debug_logging'   => '1.0.1',
		'gla_enable_reports'         => '1.0.1',
	];

	/**
	 * Hook into the new hook so we can handle deprecated hooks once fired.
	 *
	 * @param string $hook_name Hook name.
	 */
	public function hook_in( $hook_name ) {
		add_filter( $hook_name, [ $this, 'maybe_handle_deprecated_hook' ], -1000, 8 );
	}

	/**
	 * If the old hook is in-use, trigger it.
	 *
	 * @param  string $new_hook          New hook name.
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @param  mixed  $return_value      Returned value.
	 * @return mixed
	 */
	public function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value ) {
		if ( has_filter( $old_hook ) ) {
			$this->display_notice( $old_hook, $new_hook );
			$return_value = $this->trigger_hook( $old_hook, $new_callback_args );
		}
		return $return_value;
	}

	/**
	 * Fire off a legacy hook with it's args.
	 *
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @return mixed
	 */
	protected function trigger_hook( $old_hook, $new_callback_args ) {
		return apply_filters_ref_array( $old_hook, $new_callback_args );
	}
}
