<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Google\Service\Exception as GoogleException;
use Exception;
use Google\Service\SiteVerification as SiteVerificationService;
use Google\Service\SiteVerification\SiteVerificationWebResourceResource as WebResource;
use Google\Service\SiteVerification\SiteVerificationWebResourceResourceSite as WebResourceSite;
use Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequest as GetTokenRequest;
use Google\Service\SiteVerification\SiteVerificationWebResourceGettokenRequestSite as GetTokenRequestSite;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerification
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class SiteVerification {

	use PluginHelper;

	/**
	 * The container object.
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/** @var string */
	private const VERIFICATION_METHOD = 'META';

	/** @var string */
	public const VERIFICATION_STATUS_VERIFIED = 'yes';

	/** @var string */
	public const VERIFICATION_STATUS_UNVERIFIED = 'no';


	/**
	 * SiteVerification constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	/**
	 * Get the META token for site verification.
	 * https://developers.google.com/site-verification/v1/webResource/getToken
	 *
	 * @param string $identifier The URL of the site to verify (including protocol).
	 * @throws Exception When unable to retrieve meta token.
	 * @return string The meta tag to be used for verification.
	 */
	public function get_token( string $identifier ): string {
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
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_sv_client_exception', $e, __METHOD__ );
			throw new Exception(
				__( 'Unable to retrieve site verification token.', 'google-listings-and-ads' ),
				$e->getCode()
			);
		}

		return $response->getToken();
	}

	/**
	 * Instructs the Google Site Verification API to verify site ownership
	 * using the META method.
	 *
	 * @param string $identifier The URL of the site to verify (including protocol).
	 * @throws Exception When unable to verify token.
	 * @return bool True if the site was verified correctly.
	 */
	public function insert( string $identifier ): bool {
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
		} catch ( GoogleException $e ) {
			do_action( 'woocommerce_gla_sv_client_exception', $e, __METHOD__ );
			throw new Exception(
				__( 'Unable to insert site verification.', 'google-listings-and-ads' ),
				$e->getCode()
			);
		}

		return true;
	}
}
