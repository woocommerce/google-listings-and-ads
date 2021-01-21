<?php


namespace Automattic\WooCommerce\GoogleListingsAndAds;

use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Registerable;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Psr\Container\ContainerInterface;

/**
 * Class SiteVerification
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds
 */
class SiteVerification implements Service, Registerable {

	/**
	 * @var array
	 */
	private $settings;

	/**
	 * CompleteSetup constructor.
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
			},
			1
		);
	}

	/**
	 * Display the meta tag with the site verification token.
	 */
	public function display_meta_token() {
		if ( empty( $this->settings['meta_tag'] ) ) {
			return;
		}
		if ( 'yes' === ( $this->settings['verified'] ?? 'no' ) ) {
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
