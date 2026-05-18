<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\MerchantApiException;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class MerchantApiExceptionTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi
 */
class MerchantApiExceptionTest extends UnitTest {

	public function test_accessors_return_constructor_inputs() {
		$body = [
			'error' => [
				'code'    => 400,
				'message' => 'Invalid request',
				'errors'  => [
					[
						'reason'  => 'invalidValue',
						'message' => 'offerId is required',
					],
				],
			],
		];

		$exception = new MerchantApiException( 400, $body, __METHOD__ );

		$this->assertSame( 400, $exception->get_http_status() );
		$this->assertSame( $body, $exception->get_response_body() );
		$this->assertSame( $body['error']['errors'], $exception->get_errors() );
		$this->assertSame( 'Invalid request', $exception->getMessage() );
		$this->assertSame( 400, $exception->getCode() );
	}

	public function test_constructor_fires_logging_action_once() {
		$called             = 0;
		$captured_method    = null;
		$captured_exception = null;

		$callback = function ( $exception, $method ) use ( &$called, &$captured_method, &$captured_exception ) {
			++$called;
			$captured_exception = $exception;
			$captured_method    = $method;
		};

		add_action( 'woocommerce_gla_mc_client_exception', $callback, 10, 2 );

		$exception = new MerchantApiException( 500, [], 'TestClass::test_method' );

		remove_action( 'woocommerce_gla_mc_client_exception', $callback, 10 );

		$this->assertSame( 1, $called );
		$this->assertSame( $exception, $captured_exception );
		$this->assertSame( 'TestClass::test_method', $captured_method );
	}

	public function test_default_message_when_body_has_no_error() {
		$exception = new MerchantApiException( 500, [], __METHOD__ );

		$this->assertSame( 'Merchant API request failed', $exception->getMessage() );
		$this->assertSame( [], $exception->get_errors() );
	}
}
