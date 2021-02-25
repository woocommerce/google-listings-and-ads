<?php


namespace Automattic\WooCommerce\GoogleListingsAndAds\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Psr\Container\ContainerInterface;

/**
 * Class SiteVerificationMeta
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
class SiteVerificationMeta implements Service, Registerable {

	/**
	 * @var array
	 */
	private $settings;

	/**
	 * SiteVerificationMeta constructor.
	 *
	 * @param ContainerInterface $container The container object.
	 */
	public function __construct( ContainerInterface $container ) {
		$this->settings = $container->get( OptionsInterface::class )->get( OptionsInterface::SITE_VERIFICATION, [] );
	}


	/**
	 * Add the meta header hook.
	 */
	public function register(): void {
		add_action(
			'wp_head',
			function() {
				$this->display_meta_token();
			}
		);
	}

	/**
	 * Display the meta tag with the site verification token.
	 */
	protected function display_meta_token() {
		if ( empty( $this->settings['meta_tag'] ) ) {
			return;
		}
		echo wp_kses(
			$this->settings['meta_tag'],
			[
				'meta' => [
					'name'    => true,
					'content' => true,
				],
			]
		) . "\n";
	}
}
