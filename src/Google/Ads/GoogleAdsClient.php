<?php
declare( strict_types=1 );

/**
 * Overrides vendor/googleads/google-ads-php/src/Google/Ads/GoogleAds/Lib/V6/GoogleAdsClient.php
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable WordPress.NamingConventions.ValidVariableName
 */

namespace Automattic\WooCommerce\GoogleListingsAndAds\Google\Ads;

use Google\Ads\GoogleAds\Util\V6\GoogleAdsFailures;
use Google\Auth\Credentials\InsecureCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;

/**
 * A Google Ads API client for handling common configuration and OAuth2 settings.
 */
class GoogleAdsClient {
	use ServiceClientFactoryTrait;

	/** @var Client $httpClient */
	private $httpClient = null;

	/**
	 * Default constructor
	 *
	 * @param string $endpoint Endpoint URL to send requests to.
	 */
	public function __construct( string $endpoint ) {
		$this->oAuth2Credential = new InsecureCredentials();
		$this->endpoint         = $endpoint;

		GoogleAdsFailures::init();
	}

	/**
	 * Set a guzzle client to use for requests.
	 *
	 * @param Client $client Guzzle client.
	 */
	public function setHttpClient( Client $client ) {
		$this->httpClient = $client;
	}

	/**
	 * Build a HTTP Handler to handle the requests.
	 */
	protected function buildHttpHandler() {
		return [ HttpHandlerFactory::build( $this->httpClient ), 'async' ];
	}
}
