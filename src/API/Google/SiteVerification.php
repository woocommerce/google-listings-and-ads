<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\ExceptionWithResponseData;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\ContainerAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\Interfaces\ContainerAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsAwareTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\Exception as GoogleServiceException;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification as SiteVerificationService;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification\SiteVerificationWebResourceResource as WebResource;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification\SiteVerificationWebResourceResourceSite as WebResourceSite;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequest as GetTokenRequest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequestSite as GetTokenRequestSite;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerification
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class SiteVerification implements ContainerAwareInterface, OptionsAwareInterface {

	use ContainerAwareTrait;
	use ExceptionTrait;
	use OptionsAwareTrait;
	use PluginHelper;

	/** @var string */
	private const VERIFICATION_METHOD = 'META';

	/** @var string */
	public const VERIFICATION_STATUS_VERIFIED = 'yes';

	/** @var string */
	public const VERIFICATION_STATUS_UNVERIFIED = 'no';

	/**
	 * Performs the three-step process of verifying the current site:
	 * 1. Retrieves the meta tag with the verification token.
	 * 2. Enables the meta tag in the head of the store (handled by SiteVerificationMeta).
	 * 3. Instructs the Site Verification API to verify the meta tag.
	 *
	 * @since 1.12.0
	 *
	 * @param string $site_url Site URL to verify.
	 *
	 * @throws Exception If any step of the site verification process fails.
	 */
	public function verify_site( string $site_url ) {
		if ( ! wc_is_valid_url( $site_url ) ) {
			do_action( 'woocommerce_gla_site_verify_failure', [ 'step' => 'site-url' ] );
			throw new Exception( __( 'Invalid site URL.', 'google-listings-and-ads' ) );
		}

		// Retrieve the meta tag with verification token.
		try {
			$meta_tag = $this->get_token( $site_url );
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_site_verify_failure', [ 'step' => 'token' ] );
			throw $e;
		}

		// Store the meta tag in the options table and mark as unverified.
		$site_verification_options = [
			'verified' => self::VERIFICATION_STATUS_UNVERIFIED,
			'meta_tag' => $meta_tag,
		];
		$this->options->update(
			OptionsInterface::SITE_VERIFICATION,
			$site_verification_options
		);

		// Attempt verification.
		try {
			$this->insert( $site_url );
			$site_verification_options['verified'] = self::VERIFICATION_STATUS_VERIFIED;
			$this->options->update( OptionsInterface::SITE_VERIFICATION, $site_verification_options );
			do_action( 'woocommerce_gla_site_verify_success', [] );
		} catch ( Exception $e ) {
			do_action( 'woocommerce_gla_site_verify_failure', [ 'step' => 'meta-tag' ] );
			throw $e;
		}
	}

	/**
	 * Get the META token for site verification.
	 * https://developers.google.com/site-verification/v1/webResource/getToken
	 *
	 * @param string $identifier The URL of the site to verify (including protocol).
	 *
	 * @return string The meta tag to be used for verification.
	 * @throws ExceptionWithResponseData When unable to retrieve meta token.
	 */
	protected function get_token( string $identifier ): string {
		/** @var SiteVerificationService $service */
		$service   = $this->container->get( SiteVerificationService::class );
		$post_body = new GetTokenRequest(
			[
				'verificationMethod' => self::VERIFICATION_METHOD,
				'site'               => new GetTokenRequestSite(
					[
						'type'       => 'SITE',
						'identifier' => $identifier,
					]
				),
			]
		);

		try {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$response = $service->webResource->getToken( $post_body );
		} catch ( GoogleServiceException $e ) {
			do_action( 'woocommerce_gla_sv_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Unable to retrieve site verification token: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$e->getCode(),
				null,
				[ 'errors' => $errors ]
			);
		}

		return $response->getToken();
	}

	/**
	 * Instructs the Google Site Verification API to verify site ownership
	 * using the META method.
	 *
	 * @param string $identifier The URL of the site to verify (including protocol).
	 *
	 * @throws ExceptionWithResponseData When unable to verify token.
	 */
	protected function insert( string $identifier ) {
		/** @var SiteVerificationService $service */
		$service   = $this->container->get( SiteVerificationService::class );
		$post_body = new WebResource(
			[
				'site' => new WebResourceSite(
					[
						'type'       => 'SITE',
						'identifier' => $identifier,
					]
				),
			]
		);

		try {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$service->webResource->insert( self::VERIFICATION_METHOD, $post_body );
		} catch ( GoogleServiceException $e ) {
			do_action( 'woocommerce_gla_sv_client_exception', $e, __METHOD__ );

			$errors = $this->get_exception_errors( $e );

			throw new ExceptionWithResponseData(
				/* translators: %s Error message */
				sprintf( __( 'Unable to insert site verification: %s', 'google-listings-and-ads' ), reset( $errors ) ),
				$e->getCode(),
				null,
				[ 'errors' => $errors ]
			);
		}
	}
}
