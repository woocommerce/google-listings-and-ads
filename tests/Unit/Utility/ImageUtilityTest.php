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
class ImageUtilityTest extends UnitTest {

	use DataTrait;

	protected const SUBSIZE_IMAGE_KEY = 'test_subisize_key';

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->image_utility = new ImageUtility();
	}

	protected function assert_aspect_rate_tolerance( DimensionUtility $suggested, DimensionUtility $recommended, float $tolerance = 0.01 ) {
		$precision = ( $suggested->x / $suggested->y ) * $tolerance;
		$this->assertTrue( wp_fuzzy_number_match( $suggested->x / $suggested->y, $recommended->x / $recommended->y, $precision ) );
	}

	public function test_add_subsize_image() {
		// Image size: 64x64px.
		$image_id = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ) );

		$metadata = wp_get_attachment_metadata( $image_id );

		$this->assertArrayNotHasKey( self::SUBSIZE_IMAGE_KEY, $metadata['sizes'] );

		// Add subsize of 20x20px
		$new_subisze = new DimensionUtility( 20, 20 );
		$this->image_utility->maybe_add_subsize_image( $image_id, self::SUBSIZE_IMAGE_KEY, $new_subisze );

		$metadata_updated = wp_get_attachment_metadata( $image_id );

		$this->assertArrayHasKey( self::SUBSIZE_IMAGE_KEY, $metadata_updated['sizes'] );

	}

	public function test_real_size_bigger_than_suggested_square() {
		$real_size        = new DimensionUtility( 1350, 1550 );
		$recommended_size = new DimensionUtility( 1200, 1200 );
		$minimum_size     = new DimensionUtility( 300, 300 );

		$suggested_size = $this->image_utility->recommend_size( $real_size, $recommended_size, $minimum_size );

		$this->assertEquals( 1200, $suggested_size->x );
		$this->assertEquals( 1200, $suggested_size->y );

		$this->assert_aspect_rate_tolerance( $suggested_size, $recommended_size );

	}

	public function test_real_size_bigger_than_suggested_landscape() {
		$real_size        = new DimensionUtility( 850, 600 );
		$recommended_size = new DimensionUtility( 725, 525 );
		$minimum_size     = new DimensionUtility( 300, 200 );

		$suggested_size = $this->image_utility->recommend_size( $real_size, $recommended_size, $minimum_size );

		$this->assertEquals( 725, $suggested_size->x );
		$this->assertEquals( 525, $suggested_size->y );

		$this->assert_aspect_rate_tolerance( $suggested_size, $recommended_size );

	}

	public function test_real_size_smaller_than_suggested_square() {
		$real_size        = new DimensionUtility( 350, 350 );
		$recommended_size = new DimensionUtility( 700, 700 );
		$minimum_size     = new DimensionUtility( 300, 200 );

		$suggested_size = $this->image_utility->recommend_size( $real_size, $recommended_size, $minimum_size );

		$this->assertEquals( 350, $suggested_size->x );
		$this->assertEquals( 350, $suggested_size->y );

		$this->assert_aspect_rate_tolerance( $suggested_size, $recommended_size );

	}

	public function test_real_size_smaller_than_suggested_landscape() {
		$real_size        = new DimensionUtility( 350, 225 );
		$recommended_size = new DimensionUtility( 725, 525 );
		$minimum_size     = new DimensionUtility( 300, 200 );

		$suggested_size = $this->image_utility->recommend_size( $real_size, $recommended_size, $minimum_size );

		$this->assertEquals( 310, $suggested_size->x );
		$this->assertEquals( 225, $suggested_size->y );

		$this->assert_aspect_rate_tolerance( $suggested_size, $recommended_size );

	}

	public function test_recommend_size_less_than_the_minimum() {
		$real_size        = new DimensionUtility( 100, 400 );
		$recommended_size = new DimensionUtility( 600, 300 );
		$minimum_size     = new DimensionUtility( 300, 200 );

		$suggested_size = $this->image_utility->recommend_size( $real_size, $recommended_size, $minimum_size );

		$this->assertFalse( $suggested_size );

	}

	public function test_image_is_bigger() {
		$image_1 = new DimensionUtility( 650, 400 );
		$image_2 = new DimensionUtility( 600, 300 );

		$this->assertTrue( $this->image_utility->is_bigger( $image_1, $image_2 ) );

	}

	public function test_image_is_smaller() {
		$image_1 = new DimensionUtility( 300, 400 );
		$image_2 = new DimensionUtility( 600, 300 );

		$this->assertFalse( $this->image_utility->is_bigger( $image_1, $image_2 ) );

	}


}
