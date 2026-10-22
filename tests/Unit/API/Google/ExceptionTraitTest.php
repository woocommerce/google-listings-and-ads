<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\ExceptionTrait;
use Google\ApiCore\ApiException;
use PHPUnit\Framework\TestCase;

class ExceptionTraitTest extends TestCase {

	/**
	 * @dataProvider api_exception_status_provider
	 * @param string|null $status API exception status.
	 * @param string      $expected_key Expected error array key.
	 */
	public function test_api_exception_preserves_message_and_status_key( ?string $status, string $expected_key ): void {
		// phpcs:ignore Universal.Classes.RequireAnonClassParentheses.Missing
		$handler = new class {
			use ExceptionTrait {
				get_exception_errors as public;
			}
		};

		$this->assertSame(
			[ $expected_key => 'API error' ],
			$handler->get_exception_errors( new ApiException( 'API error', 7, $status ) )
		);
	}

	public function api_exception_status_provider(): array {
		return [
			'missing status' => [ null, '' ],
			'empty status'   => [ '', '' ],
			'named status'   => [ 'PERMISSION_DENIED', 'PERMISSION_DENIED' ],
		];
	}
}
