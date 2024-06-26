<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\WP;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Middleware;
use Automattic\WooCommerce\GoogleListingsAndAds\API\WP\OAuthService;
use Automattic\WooCommerce\GoogleListingsAndAds\HelperTraits\Utilities as UtilitiesTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\OptionsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\League\Container\Container;
use PHPUnit\Framework\MockObject\MockObject;

defined( 'ABSPATH' ) || exit;

/**
 * Class OAuthServiceTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\WP
 */
class OAuthServiceTest extends UnitTest {

	use UtilitiesTrait;

	/**
	 * @var OAuthService
	 */
	protected $service;

	/**
	 * @var Middleware|MockObject
	 */
	protected $middleware;

	/**
	 * @var Container
	 */
	protected $container;

	/**
	 * @var OptionsInterface|MockObject
	 */
	protected $options;


	protected const DUMMY_BLOG_ID   = '123';
	protected const DUMMY_ADMIN_URL = 'https://admin-example.com/wp-admin/';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->container  = new Container();
		$this->middleware = $this->createMock( Middleware::class );
		$this->options    = $this->createMock( OptionsInterface::class );

		// Mock the Blog ID from Jetpack.
		add_filter(
			'jetpack_options',
			function ( $value, $name ) {
				if ( $name === 'id' ) {
					return self::DUMMY_BLOG_ID;
				}

				return $value;
			},
			10,
			2
		);

		// Mock admin URL.
		add_filter(
			'admin_url',
			function ( $url, $path ) {
				return self::DUMMY_ADMIN_URL . $path;
			},
			10,
			2
		);

		$this->container->share( Middleware::class, $this->middleware );
		$this->service = new OAuthService();
		$this->service->set_options_object( $this->options );
		$this->service->set_container( $this->container );
	}

	/**
	 * Test get_auth_url() function.
	 */
	public function test_get_auth_url() {
		$client_id    = '12345';
		$redirect_uri = 'https://example.com';
		$nonce        = 'nonce-999';
		$blog_id      = self::DUMMY_BLOG_ID;
		$admin_url    = self::DUMMY_ADMIN_URL;
		$path         = '/google/setup-mc';

		$expected_response = [
			'clientId'    => $client_id,
			'redirectUri' => $redirect_uri,
			'nonce'       => $nonce,
		];

		$this->middleware->expects( $this->once() )
						->method( 'get_sdi_auth_params' )
						->willReturn( $expected_response );

		$this->options->expects( $this->once() )
						->method( 'update' )
						->with( OptionsInterface::GOOGLE_WPCOM_AUTH_NONCE, $nonce );

		$store_url          = "{$admin_url}admin.php?page=wc-admin&path={$path}";
		$store_url_encoded  = urlencode_deep( $store_url );
		$expected_state_raw = "nonce={$nonce}&store_url={$store_url_encoded}";
		$state              = $this->base64url_encode( $expected_state_raw );

		$expected_auth_url  = 'https://public-api.wordpress.com/oauth2/authorize';
		$expected_auth_url .= "?blog={$blog_id}";
		$expected_auth_url .= "&client_id={$client_id}";
		$expected_auth_url .= "&redirect_uri={$redirect_uri}";
		$expected_auth_url .= '&response_type=code';
		$expected_auth_url .= '&scope=wc-partner-access';
		$expected_auth_url .= "&state={$state}";
		$expected_auth_url  = esc_url_raw( $expected_auth_url );

		$auth_url = $this->service->get_auth_url( $path );

		// Compare the auth URLs.
		$this->assertEquals(
			$expected_auth_url,
			$auth_url
		);

		// Compare the state query parameters from the auth URL.
		$parsed_url = wp_parse_url( $auth_url );
		parse_str( $parsed_url['query'], $parsed_query );
		$state_raw = $this->base64url_decode( $parsed_query['state'] );
		$this->assertEquals(
			$expected_state_raw,
			$state_raw
		);

		// Ensure the base64 encoded state query has correct value.
		parse_str( $state_raw, $parsed_state );
		$this->assertEquals(
			$nonce,
			$parsed_state['nonce']
		);
		$this->assertEquals(
			$store_url,
			$parsed_state['store_url']
		);
	}
}
