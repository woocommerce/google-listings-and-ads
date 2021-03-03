<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Internal;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\AdminConditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Conditional;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\FirstInstallInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class InstallTimestamp
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Internal
 */
class InstallTimestamp implements Conditional, FirstInstallInterface, OptionsAwareInterface {

	use AdminConditional;
	use OptionsAwareTrait;

	/**
	 * Logic to run when the plugin is first installed.
	 */
	public function first_install(): void {
		$this->options->add( OptionsInterface::INSTALL_TIMESTAMP, time() );
	}
}
