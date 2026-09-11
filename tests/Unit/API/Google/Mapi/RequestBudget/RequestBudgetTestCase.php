<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\RequestBudget;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\Create;

defined( 'ABSPATH' ) || exit;

/**
 * Class RequestBudgetTestCase
 *
 * Shared base for the request-budget regression suite: fixtures common to more than
 * one HTTP request-count test, so each suite's own file stays focused on the request
 * pattern it asserts rather than reprising shared mock plumbing.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\RequestBudget
 */
abstract class RequestBudgetTestCase extends UnitTest {

	protected const MERCHANT_ID = 12345;

	/**
	 * Mocked batch_async() handler: every sub-request in the batch succeeds.
	 *
	 * @param array<int, array{method: string, path: string, body?: array}> $requests
	 *
	 * @return \Automattic\WooCommerce\GoogleListingsAndAds\Vendor\GuzzleHttp\Promise\PromiseInterface
	 */
	public function respond_ok_to_every_sub_request( array $requests ) {
		$results = [];
		foreach ( $requests as $index => $sub ) {
			$offer_id          = $sub['body']['offerId'] ?? ( 'item' . $index );
			$results[ $index ] = [
				'status' => 200,
				'body'   => [
					'name'    => 'accounts/' . self::MERCHANT_ID . '/productInputs/' . $offer_id,
					'offerId' => $offer_id,
				],
			];
		}

		return Create::promiseFor( $results );
	}
}
