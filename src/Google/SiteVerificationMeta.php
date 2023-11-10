<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerificationMeta
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Google
 */
class SiteVerificationMeta implements OptionsAwareInterface, Registerable, Service {

	use OptionsAwareTrait;

	/**
	 * Add the meta header hook.
	 */
	public function register(): void {
		add_action(
			'wp_head',
			function () {
				$this->display_meta_token();
			}
		);
	}

	/**
	 * Display the meta tag with the site verification token.
	 */
	protected function display_meta_token() {
		$settings = $this->options->get( OptionsInterface::SITE_VERIFICATION, [] );

		if ( empty( $settings['meta_tag'] ) ) {
			return;
		}

		echo '<!-- Google site verification - Google Listings & Ads -->' . PHP_EOL;
		echo wp_kses(
			$settings['meta_tag'],
			[
				'meta' => [
					'name'    => true,
					'content' => true,
				],
			]
		) . PHP_EOL;
	}
}
