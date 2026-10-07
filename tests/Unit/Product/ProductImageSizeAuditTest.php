<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\JobRepository;
use Automattic\WooCommerce\GoogleListingsAndAds\Jobs\UpdateSmallImageProductCount;
use Automattic\WooCommerce\GoogleListingsAndAds\Options\TransientsInterface;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductImageSizeAudit;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WC_Product;

/**
 * Class ProductImageSizeAuditTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class ProductImageSizeAuditTest extends UnitTest {

	/** @var MockObject|TransientsInterface $transients */
	protected $transients;

	/** @var MockObject|UpdateSmallImageProductCount $job */
	protected $job;

	/** @var ProductImageSizeAudit $audit */
	protected $audit;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		$this->transients = $this->createMock( TransientsInterface::class );
		$this->job        = $this->createMock( UpdateSmallImageProductCount::class );

		$job_repository = $this->createMock( JobRepository::class );
		$job_repository->method( 'get' )
			->with( UpdateSmallImageProductCount::class )
			->willReturn( $this->job );

		$this->audit = new ProductImageSizeAudit( $wpdb, $this->transients, $job_repository );
	}

	public function test_counts_synced_products_with_small_main_image() {
		$this->create_synced_product_with_image( 400, 800 );
		$this->create_synced_product_with_image( 800, 499 );
		$this->create_synced_product_with_image( 500, 500 );
		$this->create_synced_product_with_image( 1200, 1200 );

		$this->assertEquals( 2, $this->audit->refresh_small_image_product_count() );
	}

	public function test_ignores_products_that_are_not_synced() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( $this->create_image( 100, 100 ) );
		$product->save();

		$this->assertEquals( 0, $this->audit->refresh_small_image_product_count() );
	}

	public function test_ignores_products_without_image_or_image_metadata() {
		$this->mark_as_synced( WC_Helper_Product::create_simple_product() );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( self::factory()->attachment->create() );
		$this->mark_as_synced( $product );

		$this->assertEquals( 0, $this->audit->refresh_small_image_product_count() );
	}

	public function test_falls_back_to_first_gallery_image() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_gallery_image_ids( [ $this->create_image( 300, 300 ), $this->create_image( 1000, 1000 ) ] );
		$this->mark_as_synced( $product );

		$this->assertEquals( 1, $this->audit->refresh_small_image_product_count() );
	}

	public function test_variation_falls_back_to_parent_image() {
		$variable = WC_Helper_Product::create_variation_product();
		$variable->set_image_id( $this->create_image( 200, 200 ) );
		$variable->save();

		$variation_ids = $variable->get_children();
		foreach ( $variation_ids as $variation_id ) {
			$this->mark_as_synced( wc_get_product( $variation_id ) );
		}

		// Give one variation its own large image.
		$variation = wc_get_product( $variation_ids[0] );
		$variation->set_image_id( $this->create_image( 1000, 1000 ) );
		$variation->save();

		$this->assertEquals( count( $variation_ids ) - 1, $this->audit->refresh_small_image_product_count() );
	}

	public function test_ignores_variable_parent_marked_as_synced() {
		$variable = WC_Helper_Product::create_variation_product();
		$variable->set_image_id( $this->create_image( 200, 200 ) );
		$variable->save();

		// Follow the normal sync lifecycle: syncing a variation also stores Google IDs on its parent.
		$this->mark_as_synced( $variable );
		foreach ( $variable->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			$variation->set_image_id( $this->create_image( 1000, 1000 ) );
			$this->mark_as_synced( $variation );
		}

		$this->assertEquals( 0, $this->audit->refresh_small_image_product_count() );
	}

	public function test_returns_fresh_cached_count_without_scheduling() {
		$this->transients->method( 'get' )
			->with( TransientsInterface::SMALL_IMAGE_PRODUCT_COUNT )
			->willReturn(
				[
					'count'       => 7,
					'computed_at' => time() - HOUR_IN_SECONDS,
				]
			);
		$this->transients->expects( $this->never() )->method( 'set' );
		$this->job->expects( $this->never() )->method( 'schedule' );

		$this->assertEquals( 7, $this->audit->get_small_image_product_count() );
	}

	public function test_returns_stale_cached_count_and_schedules_refresh() {
		$this->create_synced_product_with_image( 100, 100 );

		$this->transients->method( 'get' )
			->willReturn(
				[
					'count'       => 7,
					'computed_at' => time() - DAY_IN_SECONDS - 1,
				]
			);
		$this->transients->expects( $this->never() )->method( 'set' );
		$this->job->expects( $this->once() )->method( 'schedule' );

		$this->assertEquals( 7, $this->audit->get_small_image_product_count() );
	}

	public function test_returns_zero_and_schedules_refresh_when_not_cached() {
		$this->create_synced_product_with_image( 100, 100 );

		$this->transients->method( 'get' )->willReturn( null );
		$this->transients->expects( $this->never() )->method( 'set' );
		$this->job->expects( $this->once() )->method( 'schedule' );

		$this->assertEquals( 0, $this->audit->get_small_image_product_count() );
	}

	public function test_refresh_caches_count_with_calculation_time() {
		$this->create_synced_product_with_image( 100, 100 );

		$this->transients->expects( $this->once() )
			->method( 'set' )
			->with(
				TransientsInterface::SMALL_IMAGE_PRODUCT_COUNT,
				$this->callback(
					function ( $value ) {
						return is_array( $value )
							&& 1 === $value['count']
							&& abs( time() - $value['computed_at'] ) <= 5;
					}
				),
				7 * DAY_IN_SECONDS
			);

		$this->assertEquals( 1, $this->audit->refresh_small_image_product_count() );
	}

	/**
	 * Create a synced simple product with a main image of the given size.
	 *
	 * @param int $width
	 * @param int $height
	 */
	protected function create_synced_product_with_image( int $width, int $height ): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_image_id( $this->create_image( $width, $height ) );
		$this->mark_as_synced( $product );
	}

	/**
	 * Create an image attachment with the given "full" size dimensions.
	 *
	 * @param int $width
	 * @param int $height
	 *
	 * @return int Attachment ID.
	 */
	protected function create_image( int $width, int $height ): int {
		$attachment_id = self::factory()->attachment->create( [ 'post_mime_type' => 'image/jpeg' ] );
		wp_update_attachment_metadata(
			$attachment_id,
			[
				'width'  => $width,
				'height' => $height,
				'file'   => "image-{$width}x{$height}.jpg",
			]
		);

		return $attachment_id;
	}

	/**
	 * Mark a product as synced by setting its Google IDs.
	 *
	 * @param WC_Product $product
	 */
	protected function mark_as_synced( WC_Product $product ): void {
		$product->update_meta_data( '_wc_gla_google_ids', [ 'US' => 'online:en:US:gla_' . $product->get_id() ] );
		$product->save();
	}
}
