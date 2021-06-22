<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\SiteVerification;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\BaseOptionsController;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\EmptySchemaPropertiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\API\TransportMethods;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\RESTServer;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerificationController
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Site\Controllers\Onboarding
 */
class SiteVerificationController extends BaseOptionsController {

	use EmptySchemaPropertiesTrait;
	use PluginHelper;

	/**
	 * @var SiteVerification
	 */
	protected $site_verification;

	/**
	 * BaseController constructor.
	 *
	 * @param RESTServer       $server
	 * @param SiteVerification $site_verification
	 */
	public function __construct( RESTServer $server, SiteVerification $site_verification ) {
		parent::__construct( $server );
		$this->site_verification = $site_verification;
	}

	/**
	 * Register rest routes with WordPress.
	 */
	public function register_routes(): void {
		$this->register_route(
			'site/verify',
			[
				[
					'methods'             => TransportMethods::CREATABLE,
					'callback'            => $this->get_verify_endpoint_create_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
				[
					'methods'             => TransportMethods::READABLE,
					'callback'            => $this->get_verify_endpoint_read_callback(),
					'permission_callback' => $this->get_permission_callback(),
				],
			]
		);
	}

	/**
	 * Run the site verification process.
	 *
	 * @return callable Callback to retrieve status and informative message about the verification attempt.
	 */
	protected function get_verify_endpoint_create_callback(): callable {
		return function() {
			$site_url = esc_url_raw( $this->get_site_url() );

			if ( ! wc_is_valid_url( $site_url ) ) {
				return $this->get_failure_status( __( 'Invalid site URL.', 'google-listings-and-ads' ) );
			}

			// Inform of previous verification.
			if ( $this->is_site_verified() ) {
				return $this->get_success_status( __( 'Site already verified.', 'google-listings-and-ads' ) );
			}

			// Retrieve the meta tag with verification token.
			try {
				$meta_tag = $this->site_verification->get_token( $site_url );
			} catch ( Exception $e ) {
				do_action( 'woocommerce_gla_site_verify_failure', [ 'step' => 'token' ] );

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
				if ( $this->site_verification->insert( $site_url ) ) {
					$site_verification_options['verified'] = 'yes';
					$this->options->update( OptionsInterface::SITE_VERIFICATION, $site_verification_options );
					do_action( 'woocommerce_gla_site_verify_success', [] );

					return $this->get_success_status( __( 'Site successfully verified.', 'google-listings-and-ads' ) );
				}
			} catch ( Exception $e ) {
				do_action( 'woocommerce_gla_site_verify_failure', [ 'step' => 'meta-tag' ] );

				return $this->get_failure_status( $e->getMessage() );
			}

			// Should never reach this point.
			do_action( 'woocommerce_gla_site_verify_failure', [ 'step' => 'unknown' ] );

			return $this->get_failure_status();
		};
	}

	/**
	 * Generate a failure status array
	 *
	 * @param string $details Additional details to pass in the error message.
	 *
	 * @return array
	 */
	private function get_failure_status( string $details = '' ): array {
		$status = [
			'status'  => 'error',
			'message' => __( 'Site verification failed.', 'google-listings-and-ads' ),
		];

		// Add details.
		if ( ! empty( $details ) ) {

			// Extract error message if possible (or use error JSON).
			if ( json_decode( $details, true ) ) {
				$details = json_decode( $details, true );
				$details = $details['error']['message'] ?? $details;
			}

			$status['details'] = $details;
		}

		return $status;
	}

	/**
	 * Generate a failure status array
	 *
	 * @param string $message Additional details to pass in the error message.
	 *
	 * @return array
	 */
	private function get_success_status( string $message ): array {
			return [
				'status'  => 'success',
				'message' => $message,
			];
	}

	/**
	 * Determine whether the site has already been verified.
	 *
	 * @return bool True if the site is marked as verified.
	 */
	private function is_site_verified(): bool {
		$current_options = $this->options->get( OptionsInterface::SITE_VERIFICATION );
		return ! empty( $current_options['verified'] ) && 'yes' === $current_options['verified'];
	}

	/**
	 * Retrieve the current verification status of the site.
	 *
	 * @return callable
	 */
	protected function get_verify_endpoint_read_callback(): callable {
			return function() {
				$verified = $this->is_site_verified();
				return [
					'status'   => 'success',
					'verified' => $verified,
					'message'  => $verified ? __( 'Site already verified.', 'google-listings-and-ads' ) : __( 'Site not verified.', 'google-listings-and-ads' ),
				];
			};
	}

	/**
	 * Get the item schema name for the controller.
	 *
	 * Used for building the API response schema.
	 *
	 * @return string
	 */
	protected function get_schema_title(): string {
		return 'google_site_verification';
	}
}
