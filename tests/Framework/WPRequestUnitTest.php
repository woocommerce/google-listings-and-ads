<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework;

defined( 'ABSPATH' ) || exit;

/**
 * Class WPRequestUnitTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework
 */
abstract class WPRequestUnitTest extends UnitTest {

	/**
	 * List of mocked requests.
	 *
	 * @var array
	 */
	protected $mocked_requests = [];

	/**
	 * Setup the filter for intercepting requests.
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'pre_http_request', [ $this, 'mocked_response' ], 1, 3 );
	}

	/**
	 * Clear the filter.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'pre_http_request', [ $this, 'mocked_response' ], 1, 3 );
	}

	/**
	 * Return mocked response if the URL matches.
	 *
	 * @param mixed  $preempt False if the request has not been filtered.
	 * @param array  $args    Request arguments.
	 * @param string $url     Request URL.
	 */
	public function mocked_response( $preempt, $args, $url ) {
		if ( isset( $this->mocked_requests[ $url ] ) ) {
			$response = $this->mocked_requests[ $url ];
			unset( $this->mocked_requests[ $url ] );

			return $response;
		}

		return false;
	}

	/**
	 * Add a mocked response for a specific URL.
	 *
	 * @param string $url      Request URL to match.
	 * @param string $response Response body to return.
	 * @param int    $status   Status code.
	 */
	protected function mock_wp_request( string $url, string $response, int $status = 200 ) {
		$this->mocked_requests[ $url ] = [
			'response' => [ 'code' => $status ],
			'body'     => $response,
		];
	}
}
