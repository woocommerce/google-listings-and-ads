<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Google\Service\Exception;
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
		$site_url = site_url();

		// Inform of previous verification.
		$current_options = $this->options->get( OptionsInterface::SITE_VERIFICATION );
		if ( ! empty( $current_options['verified'] ) && 'yes' === $current_options['verified'] ) {
			return [
				'status'  => 'success',
				'message' => __( 'Site already verified', 'google-listings-and-ads' ),
			];

		}

		// Retrieve the meta tag with verification token.
		/** @var SiteVerification $site_verification */
		$site_verification = $this->container->get( SiteVerification::class );
		try {
			$meta_tag = $site_verification->get_token( $site_url );
		} catch ( Exception $e ) {
			return $this->get_failure_status( $e->getMessage() );
		}

		// Store the meta tag in the options table and mark as unverified.
		$site_verification_options = [
			'verified' => 'no',
			'meta_tag' => $meta_tag,
		];
		$this->options->update(
			OptionsInterface::SITE_VERIFICATION,
			$site_verification_options
		);

		// Attempt verification.
		try {
			if ( $site_verification->insert( $site_url ) ) {
				$site_verification_options['verified'] = 'yes';
				$this->options->update( OptionsInterface::SITE_VERIFICATION, $site_verification_options );

				return [
					'status'  => 'success',
					'message' => __( 'Site successfully verified', 'google-listings-and-ads' ),
				];
			}
		} catch ( Exception $e ) {
			return $this->get_failure_status( $e->getMessage() );
		}
	}

	/**
	 * Generate a failure status array
	 *
	 * @param string $details Additional details to pass in the error message.
	 *
	 * @return array
	 */
	private function get_failure_status( string $details ): array {
		$status = [
			'status'  => '400',
			'message' => __( 'Error verifying site', 'google-listings-and-ads' ),
		];

		// Add details.
		if ( ! is_null( $details ) ) {

			// Extract error message if possible (or use error JSON).
			if ( json_decode( $details, true ) ) {
				$details = json_decode( $details, true );
				$details = $details['error']['message'] ?? $details;
			}

			$status['details'] = $details;
		}

		return $status;
	}
}
