<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

/**
 * Class AdminScriptAsset
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
class AdminScriptAsset extends ScriptAsset {

	use AdminAssetHelper;

	/**
	 * Get the condition callback to run when enqueuing the asset.
	 *
	 * The asset will only be enqueued if the callback returns true.
	 *
	 * @return bool
	 */
	public function can_enqueue(): bool {
		return is_admin() && parent::can_enqueue();
	}
}
