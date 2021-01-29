<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;

defined( 'ABSPATH' ) || exit;

/**
 * Class SubmittableMetaBox
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Admin\MetaBox
 */
abstract class SubmittableMetaBox extends AbstractMetaBox implements Registerable {
	/**
	 * Verifies the WooCommerce meta box nonce.
	 *
	 * @return bool True is nonce is provided and valid, false otherwise.
	 */
	protected function verify_nonce(): bool {
		// todo: fix phpcs complaining about non-sanitized variable usage.
		return ! empty( $_POST['woocommerce_meta_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' );
	}
}
