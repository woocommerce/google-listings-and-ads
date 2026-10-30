<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table\MerchantIssueTable;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AttributeManager;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductAbilityFields;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\ProductMetaHandler;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\ContainerAwareUnitTest;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Helper_Product;
use WP_Error;

/**
 * Class ProductAbilityFieldsTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 */
class ProductAbilityFieldsTest extends ContainerAwareUnitTest {

	/** @var MerchantCenterService|MockObject $merchant_center */
	protected $merchant_center;

	/** @var ProductAbilityFields $fields */
	protected $fields;

	/**
	 * Get a field by name.
	 *
	 * @param string $name Field name.
	 *
	 * @return array
	 */
	protected function field( string $name ): array {
		foreach ( $this->fields->get_fields() as $field ) {
			if ( $name === $field['field'] ) {
				return $field;
			}
		}
		$this->fail( "No field named $name." );
	}

	/**
	 * Get a filter callback by name.
	 *
	 * @param string $name Filter name.
	 *
	 * @return callable
	 */
	protected function filter( string $name ): callable {
		foreach ( $this->fields->get_filters() as $filter ) {
			if ( $name === $filter['filter'] ) {
				return $filter['callback'];
			}
		}
		$this->fail( "No filter named $name." );
	}

	public function test_field_names_are_slugs() {
		foreach ( $this->fields->get_fields() as $field ) {
			$this->assertSame( sanitize_key( $field['field'] ), $field['field'] );
		}
		$this->assertSame( [ 'simple', 'variation' ], $this->field( 'availability_date' )['object_types'] );
	}

	public function test_attribute_write_stays_in_memory_until_the_save() {
		$product = WC_Helper_Product::create_simple_product();

		$this->field( 'brand' )['update_callback']( $product, 'Google' );

		$this->assertSame( 'Google', $this->field( 'brand' )['get_callback']( $product ) );
		$this->assertEmpty( get_post_meta( $product->get_id(), '_wc_gla_brand', true ) );
	}

	public function test_null_deletes_an_integer_attribute() {
		$product = WC_Helper_Product::create_simple_product();
		$this->field( 'multipack' )['update_callback']( $product, 3 );
		$this->assertSame( 3, $this->field( 'multipack' )['get_callback']( $product ) );

		$this->field( 'multipack' )['update_callback']( $product, null );

		$this->assertNull( $this->field( 'multipack' )['get_callback']( $product ) );
	}

	public function test_gtin_goes_to_the_global_unique_id() {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertNull( $this->field( 'gtin' )['update_callback']( $product, '12345670' ) );

		$this->assertSame( '12345670', $product->get_global_unique_id() );
		$this->assertSame( '12345670', $this->field( 'gtin' )['get_callback']( $product ) );
	}

	public function test_gtin_read_falls_back_to_the_attribute_and_null_clears_both() {
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_wc_gla_gtin', '12345670' );
		$this->assertSame( '12345670', $this->field( 'gtin' )['get_callback']( $product ) );

		$product->set_global_unique_id( '123-456-70' );
		$this->assertSame( '12345670', $this->field( 'gtin' )['get_callback']( $product ) );

		$this->field( 'gtin' )['update_callback']( $product, null );
		$this->assertNull( $this->field( 'gtin' )['get_callback']( $product ) );
	}

	public function test_bad_gtin_is_refused() {
		$product = WC_Helper_Product::create_simple_product();

		$result = $this->field( 'gtin' )['update_callback']( $product, '123' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_gtin', $result->get_error_code() );
		$this->assertEmpty( $product->get_global_unique_id() );
	}

	public function test_visibility_of_a_variation_follows_its_parent() {
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );
		$field     = $this->field( 'visibility' );

		$this->assertSame( 'sync-and-show', $field['get_callback']( $variation ) );
		$this->assertNull( $field['update_callback']( $variation, 'sync-and-show' ) );

		$refused = $field['update_callback']( $variation, 'dont-sync-and-show' );
		$this->assertSame( 'visibility_on_parent', $refused->get_error_code() );

		$this->assertNull( $field['update_callback']( $variable, 'dont-sync-and-show' ) );
		$this->assertSame( 'dont-sync-and-show', $field['get_callback']( $variable ) );
	}

	public function test_sync_errors_are_a_list_of_strings() {
		$product = WC_Helper_Product::create_simple_product();
		$product->update_meta_data( '_wc_gla_errors', [ 'price' => 'Price is required.' ] );

		$this->assertSame( [ 'Price is required.' ], $this->field( 'sync_errors' )['get_callback']( $product ) );
	}

	public function test_hiding_a_product_deletes_its_issues() {
		global $wpdb;

		$product = WC_Helper_Product::create_simple_product();
		$table   = $this->container->get( MerchantIssueTable::class );
		$table->install();
		$wpdb->insert(
			$table->get_name(),
			[
				'product_id'           => $product->get_id(),
				'issue'                => 'Invalid GTIN',
				'code'                 => 'invalid_gtin',
				'product'              => 'Test',
				'action'               => 'Fix it',
				'action_url'           => 'https://example.com',
				'applicable_countries' => '[]',
				'created_at'           => gmdate( 'Y-m-d H:i:s' ),
			]
		);
		$this->assertCount( 1, $this->field( 'merchant_issues' )['get_callback']( $product ) );

		$this->fields->sync_hidden_product_issues( [ $product ], [ 'extensions' => [ 'google' => [ 'visibility' => 'sync-and-show' ] ] ] );
		$this->assertCount( 1, $this->field( 'merchant_issues' )['get_callback']( $product ) );

		$this->fields->sync_hidden_product_issues( [ $product ], [ 'extensions' => [ 'google' => [ 'visibility' => 'dont-sync-and-show' ] ] ] );
		$this->assertNull( $this->field( 'merchant_issues' )['get_callback']( $product ) );
	}

	public function test_status_filter_matches_products_without_the_meta_for_its_unset_value() {
		$synced = $this->filter( 'sync_status' )( 'synced' )['meta_query'];
		$this->assertSame( [ [ 'key' => '_wc_gla_sync_status', 'value' => 'synced' ] ], $synced ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing

		$unsynced = $this->filter( 'sync_status' )( 'not-synced' )['meta_query'];
		$this->assertSame( 'OR', $unsynced['relation'] );
		$this->assertSame( 'NOT EXISTS', $unsynced[1]['compare'] );
	}

	public function test_missing_gtin_filter_checks_both_stores() {
		$clauses = $this->filter( 'missing_attributes' )( [ 'gtin', 'availability_date' ] )['meta_query'];

		$this->assertSame( '_wc_gla_gtin', $clauses[0][0][0]['key'] );
		$this->assertSame( '_global_unique_id', $clauses[0][1][0]['key'] );
		$this->assertSame( '_wc_gla_availabilityDate', $clauses[1][0]['key'] );
	}

	public function test_validator_refuses_a_new_backorder_without_a_date_when_connected() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'onbackorder' );

		$this->assertSame( 'availability_date_required', $this->fields->validate_availability_date( $product )->get_error_code() );

		$this->field( 'availability_date' )['update_callback']( $product, '2030-01-01T00:00:00' );
		$this->assertNull( $this->fields->validate_availability_date( $product ) );
	}

	public function test_validator_ignores_an_unrelated_change_and_a_store_that_is_not_connected() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'onbackorder' );
		$this->merchant_center->method( 'is_connected' )->willReturnOnConsecutiveCalls( false, true );

		$this->assertNull( $this->fields->validate_availability_date( $product ) );

		$product->save();
		$product->set_regular_price( '5' );
		$this->assertNull( $this->fields->validate_availability_date( $product ) );
	}

	public function test_validator_refuses_removing_the_date_of_a_backorder() {
		$this->merchant_center->method( 'is_connected' )->willReturn( true );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_status( 'onbackorder' );
		$this->field( 'availability_date' )['update_callback']( $product, '2030-01-01T00:00:00' );
		$product->save();

		$this->field( 'availability_date' )['update_callback']( $product, null );

		$this->assertSame( 'availability_date_required', $this->fields->validate_availability_date( $product )->get_error_code() );
	}

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		$this->merchant_center = $this->createMock( MerchantCenterService::class );
		$this->fields          = new ProductAbilityFields(
			$this->container->get( AttributeManager::class ),
			$this->container->get( ProductHelper::class ),
			$this->container->get( ProductMetaHandler::class ),
			$this->merchant_center,
			$this->container->get( MerchantIssueTable::class ),
			$wpdb
		);
	}
}
