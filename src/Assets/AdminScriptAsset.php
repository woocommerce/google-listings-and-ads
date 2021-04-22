<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Closure;

/**
 * Class AdminScriptAsset
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
class AdminScriptAsset extends ScriptAsset {

	use AdminAssetHelper;

	/**
	 * Get the condition closure to run when registering/enqueuing the asset.
	 *
	 * The asset will only be registered/enqueued if the closure returns true.
	 *
	 * @return Closure
	 */
	public function get_enqueue_condition(): Closure {
		return function () {
			return is_admin() && $this->enqueue_condition->call( $this );
		};
	}
}
