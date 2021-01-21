<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerificationController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding
 */
class SiteVerificationController extends BaseOptionsController {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * SiteVerificationController constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		parent::__construct( $container->get( RESTServer::class ), $container->get( OptionsInterface::class ) );
		$this->container = $container;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	protected function register_routes(): void {
		$this->register_route(
			'site/verify',
			[
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => function() {
						return $this->get_verify_endpoint_callback();
					},
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Run the site verification process.
	 *
	 * @return array Status and informative message about the verification attempt.
	 */
	protected function get_verify_endpoint_callback(): array {
		$current_options = $this->options->get( OptionsInterface::SITE_VERIFICATION );
		if ( ! empty( $current_options['verified'] ) && 'yes' === $current_options['verified'] ) {
			return [
				'status'  => 'success',
				'message' => __( 'Site already verified', 'google-listings-and-ads' ),
			];

		}

		/** @var SiteVerification $site_verification */
		$site_verification  = $this->container->get( SiteVerification::class );
		$meta_tag           = $site_verification->get_token( site_url() );
		$options_unverified = [
			'meta_tag' => $meta_tag,
			'verified' => 'no',
		];

		$this->options->update(
			OptionsInterface::SITE_VERIFICATION,
			$options_unverified
		);

		if ( $site_verification->insert( site_url() ) ) {
			$options_unverified['verified'] = 'yes';
			$this->options->update( OptionsInterface::SITE_VERIFICATION, $options_unverified );

			return [
				'status'  => 'success',
				'message' => __( 'Site successfully verified', 'google-listings-and-ads' ),
			];
		}

		return [
			'status'  => '400',
			'message' => __( 'Error verifying site', 'google-listings-and-ads' ),
		];
	}
}
