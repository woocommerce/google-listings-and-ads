<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools;

/**
 * Minimal stand-in for the global woocommerce_wpml object, exposing the
 * get_setting() method the WPML integration reads WCML settings through.
 *
 * @see \woocommerce_wpml::get_setting()
 */
class WCMLSettingsStub {

	/**
	 * Value returned for the default_currencies setting.
	 *
	 * @var mixed
	 */
	private $default_currencies;

	/**
	 * WCMLSettingsStub constructor.
	 *
	 * @param mixed $default_currencies Value returned for the default_currencies setting.
	 */
	public function __construct( $default_currencies ) {
		$this->default_currencies = $default_currencies;
	}

	/**
	 * Mirrors woocommerce_wpml::get_setting().
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Value returned for any other key.
	 *
	 * @return mixed
	 */
	public function get_setting( $key, $fallback = null ) {
		return 'default_currencies' === $key ? $this->default_currencies : $fallback;
	}
}
