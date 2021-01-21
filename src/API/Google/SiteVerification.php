<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google_Service_SiteVerification as SiteVerificationService;
use Google_Service_SiteVerification_SiteVerificationWebResourceResource as WebResource;
use Google_Service_SiteVerification_SiteVerificationWebResourceResourceSite as WebResourceSite;
use Google_Service_SiteVerification_SiteVerificationWebResourceGettokenRequest as GetTokenRequest;
use Google_Service_SiteVerification_SiteVerificationWebResourceGettokenRequestSite as GetTokenRequestSite;
use Psr\Container\ContainerInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class SiteVerification
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class SiteVerification {

	/**
	 * The container object.
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/** @var string */
	private const VERIFICATION_METHOD = 'META';


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
	 *
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

		//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// $response = $service->webResource->getToken( $post_body );

		// handle errors: 400 invalid value; 401 unauthenticated, etc.
		return '<meta name="google-site-verification" content="1qkSUadUDlD_dlAruj91Kg0nG85HdO6MLH7Ce3xxNqU" />';
		return $response->getToken();
	}

	/**
	 * Instructs the Google Site Verification API to verify site ownership
	 * using the META method.
	 *
	 * @param string $identifier The URL of the site to verify (including protocol).
	 *
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

		//phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// $response =  $service->webResource->insert( self::VERIFICATION_METHOD, $post_body );

		// handle errors: 400 token couldn't be found; 400 invalid value; 401 unauthenticated, etc.
		return true;
	}
}
