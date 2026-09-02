<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models;

use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ItemLevelIssue;
use Automattic\WooCommerce\GoogleListingsAndAds\API\Google\Mapi\Models\ProductStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProductStatusTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\API\Google\Mapi\Models
 */
class ProductStatusTest extends UnitTest {

	public function test_from_array_populates_all_fields() {
		$status = ProductStatus::from_array(
			[
				'itemLevelIssues'     => [
					[
						'code'        => 'missing_image',
						'description' => 'Missing image link',
					],
					[
						'code'        => 'invalid_price',
						'description' => 'Invalid price',
					],
				],
				'destinationStatuses' => [ 'SHOPPING_ADS', 'FREE_LISTINGS' ],
				'lastUpdateDate'      => '2026-05-14T10:00:00Z',
			]
		);

		$issues = $status->get_item_level_issues();
		$this->assertCount( 2, $issues );
		$this->assertContainsOnlyInstancesOf( ItemLevelIssue::class, $issues );
		$this->assertSame( 'missing_image', $issues[0]->get_code() );
		$this->assertSame( 'invalid_price', $issues[1]->get_code() );

		$this->assertSame( [ 'SHOPPING_ADS', 'FREE_LISTINGS' ], $status->get_destination_statuses() );
		$this->assertSame( '2026-05-14T10:00:00Z', $status->get_last_update_date() );
	}

	public function test_from_array_with_missing_fields_defaults_to_empty() {
		$status = ProductStatus::from_array( [] );

		$this->assertSame( [], $status->get_item_level_issues() );
		$this->assertSame( [], $status->get_destination_statuses() );
		$this->assertNull( $status->get_last_update_date() );
		$this->assertNull( $status->get_google_expiration_date() );
	}

	public function test_from_array_populates_google_expiration_date() {
		$status = ProductStatus::from_array( [ 'googleExpirationDate' => '2026-10-02T16:35:10.725858Z' ] );

		$this->assertSame( '2026-10-02T16:35:10.725858Z', $status->get_google_expiration_date() );
	}

	/**
	 * @dataProvider aggregated_status_provider
	 *
	 * @param array  $destination_statuses
	 * @param string $expected
	 */
	public function test_get_aggregated_reporting_context_status( array $destination_statuses, string $expected ) {
		$status = ProductStatus::from_array( [ 'destinationStatuses' => $destination_statuses ] );

		$this->assertSame( $expected, $status->get_aggregated_reporting_context_status() );
	}

	/**
	 * Matrix following the official enum semantics.
	 *
	 * @return array
	 */
	public function aggregated_status_provider(): array {
		$ads  = 'SHOPPING_ADS';
		$free = 'FREE_LISTINGS';

		return [
			'approved everywhere'                        => [
				[
					[
						'reportingContext'  => $ads,
						'approvedCountries' => [ 'US', 'CA' ],
					],
				],
				'ELIGIBLE',
			],
			'approved in all contexts'                   => [
				[
					[
						'reportingContext'  => $ads,
						'approvedCountries' => [ 'US' ],
					],
					[
						'reportingContext'  => $free,
						'approvedCountries' => [ 'US' ],
					],
				],
				'ELIGIBLE',
			],
			'approved and disapproved mix'               => [
				[
					[
						'reportingContext'     => $ads,
						'approvedCountries'    => [ 'US' ],
						'disapprovedCountries' => [ 'CA' ],
					],
				],
				'ELIGIBLE_LIMITED',
			],
			'approved in one context, disapproved other' => [
				[
					[
						'reportingContext'  => $ads,
						'approvedCountries' => [ 'US' ],
					],
					[
						'reportingContext'     => $free,
						'disapprovedCountries' => [ 'US' ],
					],
				],
				'ELIGIBLE_LIMITED',
			],
			'approved and pending mix'                   => [
				[
					[
						'reportingContext'  => $ads,
						'approvedCountries' => [ 'US' ],
					],
					[
						'reportingContext' => $free,
						'pendingCountries' => [ 'US' ],
					],
				],
				'ELIGIBLE_LIMITED',
			],
			'pending everywhere'                         => [
				[
					[
						'reportingContext' => $ads,
						'pendingCountries' => [ 'US' ],
					],
					[
						'reportingContext' => $free,
						'pendingCountries' => [ 'US' ],
					],
				],
				'PENDING',
			],
			'disapproved everywhere'                     => [
				[
					[
						'reportingContext'     => $ads,
						'disapprovedCountries' => [ 'US' ],
					],
				],
				'NOT_ELIGIBLE_OR_DISAPPROVED',
			],
			'pending and disapproved, no approval'       => [
				[
					[
						'reportingContext'     => $ads,
						'disapprovedCountries' => [ 'US' ],
					],
					[
						'reportingContext' => $free,
						'pendingCountries' => [ 'US' ],
					],
				],
				'NOT_ELIGIBLE_OR_DISAPPROVED',
			],
			'no destination statuses (still processing)' => [
				[],
				'',
			],
			'contexts with empty country lists'          => [
				[
					[
						'reportingContext'  => $ads,
						'approvedCountries' => [],
					],
				],
				'',
			],
		];
	}
}
