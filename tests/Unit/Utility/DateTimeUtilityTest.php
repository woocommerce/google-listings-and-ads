<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\DateTimeUtility;

/**
 * Class DateTimeUtilityTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility
 *
 * @property DateTimeUtility $datetime_utility
 */
class DateTimeUtilityTest extends UnitTest {

	public function test_returns_tz_as_is_if_not_offset() {
		$this->assertEquals( 'Europe/London', $this->datetime_utility->maybe_convert_tz_string( 'Europe/London' ) );
	}

	public function test_returns_tz_as_gmt_if_utc_provided() {
		$this->assertEquals( 'Etc/GMT', $this->datetime_utility->maybe_convert_tz_string( 'UTC' ) );
	}

	public function test_returns_tz_name_by_offset() {
		$this->assertEquals( 'America/New_York', $this->datetime_utility->maybe_convert_tz_string( '-5:00' ) );
		$this->assertEquals( 'Europe/London', $this->datetime_utility->maybe_convert_tz_string( '+00:00' ) );
		$this->assertEquals( 'Asia/Dubai', $this->datetime_utility->maybe_convert_tz_string( '+04:00' ) );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp() {
		parent::setUp();
		$this->datetime_utility = new DateTimeUtility();
	}
}
