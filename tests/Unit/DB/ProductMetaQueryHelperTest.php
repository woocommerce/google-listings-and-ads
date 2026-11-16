<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\ProductMetaQueryHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\MCStatus;

/**
 * Class ProductMetaQueryHelperTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\DB
 */
class ProductMetaQueryHelperTest extends UnitTest {

	use PluginHelper;

	/** @var ProductMetaQueryHelper $helper */
	protected $helper;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;
		$this->helper = new ProductMetaQueryHelper( $wpdb );
	}

	public function test_count_products_by_value_counts_top_level_products_with_matching_values() {
		$this->create_product_with_status( MCStatus::APPROVED );
		$this->create_product_with_status( MCStatus::DISAPPROVED );
		$this->create_product_with_status( MCStatus::NOT_SYNCED );
		$this->factory()->post->create( [ 'post_type' => 'product' ] );

		$this->assertSame( 1, $this->helper->count_products_by_value( ProductMetaHandler::KEY_MC_STATUS, [ MCStatus::DISAPPROVED ] ) );
		$this->assertSame(
			2,
			$this->helper->count_products_by_value( ProductMetaHandler::KEY_MC_STATUS, [ MCStatus::APPROVED, MCStatus::DISAPPROVED ] )
		);
	}

	public function test_count_products_by_value_counts_a_variable_product_once_from_its_variations() {
		$parent = $this->create_product_with_status( MCStatus::PARTIALLY_APPROVED );
		$this->create_variation_with_status( $parent, MCStatus::APPROVED );
		$this->create_variation_with_status( $parent, MCStatus::DISAPPROVED );
		$this->create_variation_with_status( $parent, MCStatus::DISAPPROVED );

		$this->assertSame( 1, $this->helper->count_products_by_value( ProductMetaHandler::KEY_MC_STATUS, [ MCStatus::DISAPPROVED ] ) );
		$this->assertSame(
			1,
			$this->helper->count_products_by_value( ProductMetaHandler::KEY_MC_STATUS, [ MCStatus::APPROVED, MCStatus::PARTIALLY_APPROVED, MCStatus::DISAPPROVED ] )
		);
	}

	public function test_count_products_by_value_ignores_the_status_saved_on_a_variable_parent() {
		// The parent's own status is rebuilt from one results page at a time, so it depends on pagination.
		$parent = $this->create_product_with_status( MCStatus::DISAPPROVED );
		$this->create_variation_with_status( $parent, MCStatus::APPROVED );

		$this->assertSame( 0, $this->helper->count_products_by_value( ProductMetaHandler::KEY_MC_STATUS, [ MCStatus::DISAPPROVED ] ) );
	}

	public function test_count_products_by_value_returns_zero_without_values() {
		$this->create_product_with_status( MCStatus::APPROVED );

		$this->assertSame( 0, $this->helper->count_products_by_value( ProductMetaHandler::KEY_MC_STATUS, [] ) );
	}

	public function test_count_products_by_value_rejects_an_invalid_meta_key() {
		$this->expectException( InvalidMeta::class );

		$this->helper->count_products_by_value( 'not_a_gla_meta_key', [ 'value' ] );
	}

	public function test_count_products_by_value_returns_null_when_the_query_fails() {
		$wpdb = $this->getMockBuilder( \wpdb::class )
			->onlyMethods( [ 'prepare', 'get_var' ] )
			->disableOriginalConstructor()
			->getMock();
		$wpdb->method( 'get_var' )->willReturn( null );

		$helper = new ProductMetaQueryHelper( $wpdb );

		$this->assertNull( $helper->count_products_by_value( ProductMetaHandler::KEY_MC_STATUS, [ MCStatus::DISAPPROVED ] ) );
	}

	/**
	 * Create a variation with a Merchant Center status.
	 *
	 * @param int    $parent_id
	 * @param string $status
	 */
	protected function create_variation_with_status( int $parent_id, string $status ): void {
		$variation_id = $this->factory()->post->create(
			[
				'post_type'   => 'product_variation',
				'post_parent' => $parent_id,
			]
		);
		update_post_meta( $variation_id, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), $status );
	}

	/**
	 * Create a top-level product with a Merchant Center status.
	 *
	 * @param string $status
	 *
	 * @return int The product ID.
	 */
	protected function create_product_with_status( string $status ): int {
		$product_id = $this->factory()->post->create( [ 'post_type' => 'product' ] );
		update_post_meta( $product_id, $this->prefix_meta_key( ProductMetaHandler::KEY_MC_STATUS ), $status );

		return $product_id;
	}
}
