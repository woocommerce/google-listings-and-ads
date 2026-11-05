<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager;

use Automattic\WooCommerce\GoogleListingsAndAds\API\TagManager\TagManagerApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class TagManagerApiExceptionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\TagManager
 */
class TagManagerApiExceptionTest extends UnitTest {

	/**
	 * @param int    $http_status
	 * @param string $message
	 * @param array  $errors     The `error.errors` entries of the response body.
	 *
	 * @return TagManagerApiException
	 */
	protected function make_exception( int $http_status, string $message = 'Failed', array $errors = [] ): TagManagerApiException {
		return new TagManagerApiException(
			$http_status,
			[
				'error' => [
					'message' => $message,
					'errors'  => $errors,
				],
			],
			__METHOD__
		);
	}

	/**
	 * @dataProvider quota_errors_provider
	 *
	 * @param TagManagerApiException $exception
	 */
	public function test_it_recognises_a_quota_error( TagManagerApiException $exception ) {
		$this->assertTrue( $exception->is_quota_error() );
		$this->assertFalse( $exception->is_permission_denied() );
	}

	/**
	 * @return array
	 */
	public function quota_errors_provider(): array {
		return [
			'too many requests'          => [ new TagManagerApiException( 429, [], __METHOD__ ) ],
			'rate limit reason on a 403' => [ new TagManagerApiException( 403, [ 'error' => [ 'errors' => [ [ 'reason' => 'rateLimitExceeded' ] ] ] ], __METHOD__ ) ],
			'user rate limit on a 403'   => [ new TagManagerApiException( 403, [ 'error' => [ 'errors' => [ [ 'reason' => 'userRateLimitExceeded' ] ] ] ], __METHOD__ ) ],
			'quota wording on a 403'     => [ new TagManagerApiException( 403, [ 'error' => [ 'message' => 'The account does not have enough quota to continue.' ] ], __METHOD__ ) ],
		];
	}

	public function test_it_recognises_a_refusal_of_permission() {
		$exception = $this->make_exception( 403, 'The caller does not have permission', [ [ 'reason' => 'forbidden' ] ] );

		$this->assertTrue( $exception->is_permission_denied() );
		$this->assertFalse( $exception->is_quota_error() );
	}

	public function test_it_recognises_a_refusal_reported_as_not_found() {
		$exception = $this->make_exception( 404, 'Not found or permission denied.' );

		$this->assertTrue( $exception->is_permission_denied() );
		$this->assertFalse( $exception->is_quota_error() );
	}

	/**
	 * @dataProvider other_failures_provider
	 *
	 * @param int $http_status
	 */
	public function test_it_treats_other_statuses_as_neither( int $http_status ) {
		$exception = $this->make_exception( $http_status, 'Failed', [ [ 'reason' => 'rateLimitExceeded' ] ] );

		$this->assertFalse( $exception->is_permission_denied() );
		$this->assertFalse( $exception->is_quota_error() );
	}

	/**
	 * @return array
	 */
	public function other_failures_provider(): array {
		return [
			'bad request'  => [ 400 ],
			'not found'    => [ 404 ],
			'server error' => [ 503 ],
		];
	}
}
