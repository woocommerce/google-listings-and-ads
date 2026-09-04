<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Class MCStatusTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Value
 */
class MCStatusTest extends UnitTest {

	/**
	 * @dataProvider aggregated_status_provider
	 *
	 * @param string $aggregated
	 * @param string $expected
	 */
	public function test_from_aggregated_reporting_context_status( string $aggregated, string $expected ) {
		$this->assertSame( $expected, MCStatus::from_aggregated_reporting_context_status( $aggregated ) );
	}

	/**
	 * @return array
	 */
	public function aggregated_status_provider(): array {
		return [
			[ 'ELIGIBLE', MCStatus::APPROVED ],
			[ 'ELIGIBLE_LIMITED', MCStatus::PARTIALLY_APPROVED ],
			[ 'NOT_ELIGIBLE_OR_DISAPPROVED', MCStatus::DISAPPROVED ],
			[ 'PENDING', MCStatus::PENDING ],
			[ 'AGGREGATED_REPORTING_CONTEXT_STATUS_UNSPECIFIED', MCStatus::NOT_SYNCED ],
			[ '', MCStatus::NOT_SYNCED ],
		];
	}
}
