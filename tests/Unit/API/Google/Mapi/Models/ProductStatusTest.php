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
	}
}
