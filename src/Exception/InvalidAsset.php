<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleForWC\Exception;

use LogicException;

/**
 * Class InvalidAsset
 *
 * @package Automattic\WooCommerce\GoogleForWC\Exception
 */
class InvalidAsset extends LogicException implements GoogleForWCException {

	/**
	 * Return an instance of the exception when an asset attempts to be enqueued without first being
	 * registered.
	 *
	 * @param string $handle The asset handle.
	 *
	 * @return static
	 */
	public static function asset_not_registered( string $handle ) {
		return new static(
			sprintf(
				'The asset "%s" was not registered before it was enqueued. The register() method must be called during init.',
				$handle
			)
		);
	}
}
