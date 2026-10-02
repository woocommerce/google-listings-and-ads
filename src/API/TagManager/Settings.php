<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * The merchant's Google Tag Manager preferences, stored separately from the connection.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager
 */
class Settings implements OptionsAwareInterface {

	use OptionsAwareTrait;

	/**
	 * Default shape of the `tag_manager_settings` option.
	 *
	 * `snippet_injection_enabled` stays `null` until the merchant sets it, so an explicit
	 * choice can be told apart from the default.
	 *
	 * @var array
	 */
	protected const DEFAULT_SETTINGS = [
		'snippet_injection_enabled' => null,
	];

	/**
	 * Whether the container snippet should be injected on the storefront.
	 *
	 * Enabled unless the merchant has explicitly turned it off.
	 *
	 * @return bool
	 */
	public function is_snippet_injection_enabled(): bool {
		return false !== ( $this->get_settings()['snippet_injection_enabled'] ?? null );
	}

	/**
	 * Store the merchant's choice of whether to inject the container snippet.
	 *
	 * @param bool $enabled
	 *
	 * @return bool
	 */
	public function set_snippet_injection_enabled( bool $enabled ): bool {
		return $this->options->update(
			OptionsInterface::TAG_MANAGER_SETTINGS,
			array_merge( $this->get_settings(), [ 'snippet_injection_enabled' => $enabled ] )
		);
	}

	/**
	 * Delete the stored settings, so the next connection starts from the defaults.
	 *
	 * @return bool
	 */
	public function delete(): bool {
		return $this->options->delete( OptionsInterface::TAG_MANAGER_SETTINGS );
	}

	/**
	 * Get the stored settings.
	 *
	 * @return array
	 */
	private function get_settings(): array {
		return $this->options->get( OptionsInterface::TAG_MANAGER_SETTINGS, self::DEFAULT_SETTINGS );
	}
}
