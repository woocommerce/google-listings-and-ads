<?php
	declare(strict_types=1);

	namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product\AttributeMapping;

	use Automattic\WooCommerce\GoogleListingsAndAds\Product\AttributeMapping\AttributeMappingHelper;
	use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;

	/**
	 * Test suite for AttributeMappingHelper
	 *
	 * @group AttributeMapping
	 */
class AttributeMappingHelperTest extends UnitTest {

	/**
	 * Holds the AttributeMappingHelper object.
	 *
	 * @var AttributeMappingHelper
	 */
	public $attribute_mapping_helper;


	/**
	 * Test the AttributeMappingHelper::get_attributes function
	 */
	public function test_get_attributes(): void {
		$attributes = $this->attribute_mapping_helper->get_attributes();

		$this->assertIsArray( $attributes );
		$this->assertNotEmpty( $attributes );

		foreach ( $attributes as $attribute ) {
			$this->assertArrayHasKey( 'id', $attribute );
			$this->assertArrayHasKey( 'label', $attribute );
			$this->assertArrayHasKey( 'enum', $attribute );
		}
	}

	/**
	 * Test the AttributeMappingHelper::get_attribute_by_id function
	 */
	public function test_get_attribute_by_id(): void {
		$attributes = $this->attribute_mapping_helper->get_attributes();

		foreach ( $attributes as $attribute ) {
			$attribute_class = AttributeMappingHelper::get_attribute_by_id( $attribute['id'] );
			$this->assertNotNull( $attribute_class );
			$this->assertEquals( $attribute_class::get_id(), $attribute['id'] );
		}

		$this->assertNull( AttributeMappingHelper::get_attribute_by_id( 'non_existent_attribute_id' ) );
	}

	/**
	 * Test the AttributeMappingHelper::get_sources_for_attribute function
	 */
	public function test_get_sources_for_attribute(): void {
		$attributes = $this->attribute_mapping_helper->get_attributes();

		foreach ( $attributes as $attribute ) {
			$sources = $this->attribute_mapping_helper->get_sources_for_attribute( $attribute['id'] );

			$this->assertIsArray( $sources );
			$this->assertNotEmpty( $sources );

			foreach ( $sources as $source ) {
				$this->assertArrayHasKey( 'id', $source );
				$this->assertArrayHasKey( 'label', $source );
			}
		}

		$this->assertEmpty( $this->attribute_mapping_helper->get_sources_for_attribute( 'non_existent_attribute_id' ) );
	}

	/**
	 * Test the AttributeMappingHelper::get_category_condition_types function
	 */
	public function test_get_category_condition_types(): void {
		$category_condition_types = $this->attribute_mapping_helper->get_category_condition_types();

		$this->assertIsArray( $category_condition_types );
		$this->assertCount( 3, $category_condition_types );
		$this->assertContains( AttributeMappingHelper::CATEGORY_CONDITION_TYPE_ALL, $category_condition_types );
		$this->assertContains( AttributeMappingHelper::CATEGORY_CONDITION_TYPE_EXCEPT, $category_condition_types );
		$this->assertContains( AttributeMappingHelper::CATEGORY_CONDITION_TYPE_ONLY, $category_condition_types );
	}

	/**
	 * Test Setup Method
	 */
	public function setUp(): void {
		parent::setUp();
		$this->attribute_mapping_helper = new AttributeMappingHelper();
	}
}
