<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

defined( 'ABSPATH' ) || exit;

/**
 * Construct a ScriptAsset loading the dependencies from a generated file.
 * Uses the AdminAssetHelper trait to enqueue the scripts for backend use.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Assets
 */
class AdminScriptWithBuiltDependenciesAsset extends ScriptWithBuiltDependenciesAsset {

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
