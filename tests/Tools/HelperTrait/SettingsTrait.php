<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

/**
 * Trait SettingsTrait
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait SettingsTrait {
	/**
	 * @return string
	 */
	public function get_sample_target_country(): string {
		return 'US';
	}
}
