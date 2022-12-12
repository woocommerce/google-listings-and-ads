<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility;

use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\ImageUtility;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\DataTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Utility\DimensionUtility;

/**
 * Class ImageUtilityTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Utility
 */
class DimensionUtilityTest extends UnitTest {

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
	}

	public function test_image_is_bigger() {
		$image_1 = new DimensionUtility( 650, 400 );
		$image_2 = new DimensionUtility( 600, 300 );

		$this->assertTrue( $image_1->is_bigger( $image_2 ) );

	}

	public function test_image_is_smaller() {
		$image_1 = new DimensionUtility( 300, 400 );
		$image_2 = new DimensionUtility( 600, 300 );

		$this->assertFalse( $image_1->is_bigger( $image_2 ) );

	}

	public function test_image_is_equal_with_default_precision() {
		$image_1 = new DimensionUtility( 300, 400 );
		$image_2 = new DimensionUtility( 300, 400 );

		$this->assertTrue( $image_1->equals( $image_2 ) );

	}	

	public function test_image_is_equal_with_precision_of_2() {
		$image_1 = new DimensionUtility( 300, 400 );
		$image_2 = new DimensionUtility( 302, 398 );

		$this->assertTrue( $image_1->equals( $image_2, 2 ) );

	}		

	public function test_image_is_not_equal() {
		$image_1 = new DimensionUtility( 300, 400 );
		$image_2 = new DimensionUtility( 600, 300 );

		$this->assertFalse( $image_1->equals( $image_2 ) );

	}	


}
