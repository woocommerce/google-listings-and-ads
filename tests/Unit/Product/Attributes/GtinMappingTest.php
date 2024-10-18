<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\Attributes;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use PHPUnit\Framework\TestCase;
use WC_Helper_Product;

/**
 * Class GtinMappingTest
 *
 * Unit tests to confirm that the GTIN value is mapped correctly.
 * The value should be prioritised in the following order:
 *
 * 1. WooCommerce Core:       Global Unique ID
 * 2. Google for WooCommerce: GTIN attribute
 * 3. Google for WooCommerce: Attribute mapping rules
 */
class GtinMappingTest extends TestCase {

	/** @var string $core_gtin Mock value to be used as the core gtin field. */
	private $core_gtin = '219837492834';

	/** @var string $gla_gtin Mock value to be used as the Google for WooCommerce gtin field. */
	private $gla_gtin = 'gla-gtin-field';

	/** @var string $gla_attribute_mapping_gtin Mock value to be used as the Google for WooCommerce attribute mapping gtin value. */
	private $gla_attribute_mapping_gtin = 'gla-attribute-mapping-gtin';

	/**
	 * Test GTIN mapping from WooCommerce Core Global Unique ID.
	 *
	 * @return void
	 */
	public function test_gtin_populated_from_wc_core_global_unique_id() {
		$expected_gtin = $this->gla_gtin;
		$mock_product  = WC_Helper_Product::create_simple_product( false );

		if ( version_compare( WC_VERSION, '9.2', '>=' ) ) {
			$mock_product->set_global_unique_id( $this->core_gtin );
			$expected_gtin = $this->core_gtin;
		}

		$adapter = new WCProductAdapter();
		$adapter->mapTypes(
			[
				'wc_product'     => $mock_product,
				'targetCountry'  => 'US',
				'gla_attributes' => [
					'gtin' => $this->gla_gtin,
				],
			]
		);

		$this->assertEquals( $expected_gtin, $adapter->getGtin() );
	}

	/**
	 * Test GTIN mapping from Google for WooCommerce GTIN attribute.
	 *
	 * @return void
	 */
	public function test_gtin_populated_from_gla_gtin_attribute() {
		$mock_product = WC_Helper_Product::create_simple_product( false );
		$mock_product->set_sku( $this->gla_attribute_mapping_gtin );

		$adapter = new WCProductAdapter();
		$adapter->mapTypes(
			[
				'wc_product'     => $mock_product,
				'targetCountry'  => 'US',
				'mapping_rules'  => [
					[
						'attribute'               => 'gtin',
						'source'                  => 'product:sku',
						'category_condition_type' => 'all',
						'categories'              => '',
					],
				],
				'gla_attributes' => [
					'gtin' => $this->gla_gtin,
				],
			]
		);

		$this->assertEquals( $this->gla_gtin, $adapter->getGtin() );
	}

	/**
	 * Test GTIN mapping from Google for WooCommerce attribute mapping rules.
	 *
	 * @return void
	 */
	public function test_gtin_populated_from_attribute_mapping_rules() {
		$mock_product = WC_Helper_Product::create_simple_product( false );
		$mock_product->set_sku( $this->gla_attribute_mapping_gtin );

		$adapter = new WCProductAdapter();
		$adapter->mapTypes(
			[
				'wc_product'    => $mock_product,
				'targetCountry' => 'US',
				'mapping_rules' => [
					[
						'attribute'               => 'gtin',
						'source'                  => 'product:sku',
						'category_condition_type' => 'all',
						'categories'              => '',
					],
				],
			]
		);

		$this->assertEquals( $this->gla_attribute_mapping_gtin, $adapter->getGtin() );
	}

	/**
	 * Test GTIN remains empty when no data is available.
	 *
	 * @return void
	 */
	public function test_gtin_remains_empty_when_no_data_available() {
		$mock_product = WC_Helper_Product::create_simple_product( false );

		$adapter = new WCProductAdapter();
		$adapter->mapTypes(
			[
				'wc_product'    => $mock_product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( '', $adapter->getGtin() );
	}
}
