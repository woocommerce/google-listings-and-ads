<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Brand;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\WCProductAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\DataTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\ProductTrait;
use Google\Service\ShoppingContent\ProductShipping;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;
use WC_DateTime;
use WC_Helper_Product;
use WC_Product;
use WC_Tax;

/**
 * Class WCProductAdapterTest
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Product
 * @group AttributeMapping
 */
class WCProductAdapterTest extends UnitTest {
	use ProductTrait;
	use PluginHelper;
	use DataTrait;

	public function test_throws_exception_if_wc_product_not_provided() {
		$this->expectException( InvalidValue::class );
		new WCProductAdapter( [ 'targetCountry' => 'US' ] );
	}

	public function test_throws_exception_if_invalid_wc_product_provided() {
		$this->expectException( InvalidValue::class );
		new WCProductAdapter(
			[
				'wc_product'    => new \stdClass(),
				'targetCountry' => 'US',
			]
		);
	}

	public function test_throws_exception_if_wc_product_is_variation_but_parent_not_provided() {
		$this->expectException( InvalidValue::class );
		new WCProductAdapter(
			[
				'wc_product'    => $this->generate_variation_product_mock(),
				'targetCountry' => 'US',
			]
		);
	}

	public function test_throws_exception_if_wc_product_is_variation_but_provided_parent_is_invalid() {
		$this->expectException( InvalidValue::class );
		new WCProductAdapter(
			[
				'wc_product'        => $this->generate_variation_product_mock(),
				'parent_wc_product' => new \stdClass(),
				'targetCountry'     => 'US',
			]
		);
	}

	public function test_throws_exception_if_target_country_empty() {
		$this->expectException( InvalidValue::class );
		new WCProductAdapter(
			[
				'wc_product'    => $this->generate_simple_product_mock(),
				'targetCountry' => '',
			]
		);
	}

	public function test_throws_exception_if_target_country_not_provided() {
		$this->expectException( InvalidValue::class );
		new WCProductAdapter(
			[
				'wc_product' => $this->generate_simple_product_mock(),
			]
		);
	}

	public function test_maps_rules_attributes() {
		$rules = $this->get_sample_rules();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => WC_Helper_Product::create_simple_product( false ),
				'mapping_rules'  => $rules,
				'gla_attributes' => [],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( $this->get_rule_attribute( 'gtin' ), $adapted_product->getGtin() );
		$this->assertEquals( $this->get_rule_attribute( 'mpn' ), $adapted_product->getMpn() );
		$this->assertEquals( $this->get_rule_attribute( 'brand' ), $adapted_product->getBrand() );
		$this->assertEquals( $this->get_rule_attribute( 'condition' ), $adapted_product->getCondition() );
		$this->assertEquals( $this->get_rule_attribute( 'gender' ), $adapted_product->getGender() );
		$this->assertContains( $this->get_rule_attribute( 'size' ), $adapted_product->getSizes() );
		$this->assertEquals( $this->get_rule_attribute( 'sizeSystem' ), $adapted_product->getSizeSystem() );
		$this->assertEquals( $this->get_rule_attribute( 'sizeType' ), $adapted_product->getSizeType() );
		$this->assertEquals( $this->get_rule_attribute( 'color' ), $adapted_product->getColor() );
		$this->assertEquals( $this->get_rule_attribute( 'material' ), $adapted_product->getMaterial() );
		$this->assertEquals( $this->get_rule_attribute( 'pattern' ), $adapted_product->getPattern() );
		$this->assertEquals( $this->get_rule_attribute( 'ageGroup' ), $adapted_product->getAgeGroup() );
		$this->assertEquals( 0, $adapted_product->getMultipack() );
		$this->assertEquals( true, $adapted_product->getIsBundle() );
		$this->assertEquals( true, $adapted_product->getAdult() );
	}

	public function test_maps_extra_gla_attributes() {
		$attributes = $this->get_sample_attributes();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => WC_Helper_Product::create_simple_product( false ),
				'mapping_rules'  => [],
				'gla_attributes' => $attributes,
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( $attributes['gtin'], $adapted_product->getGtin() );
		$this->assertEquals( $attributes['mpn'], $adapted_product->getMpn() );
		$this->assertEquals( $attributes['brand'], $adapted_product->getBrand() );
		$this->assertEquals( $attributes['condition'], $adapted_product->getCondition() );
		$this->assertEquals( $attributes['gender'], $adapted_product->getGender() );
		$this->assertContains( $attributes['size'], $adapted_product->getSizes() );
		$this->assertEquals( $attributes['sizeSystem'], $adapted_product->getSizeSystem() );
		$this->assertEquals( $attributes['sizeType'], $adapted_product->getSizeType() );
		$this->assertEquals( $attributes['color'], $adapted_product->getColor() );
		$this->assertEquals( $attributes['material'], $adapted_product->getMaterial() );
		$this->assertEquals( $attributes['pattern'], $adapted_product->getPattern() );
		$this->assertEquals( $attributes['ageGroup'], $adapted_product->getAgeGroup() );
		$this->assertEquals( $attributes['multipack'], $adapted_product->getMultipack() );
		$this->assertEquals( $attributes['isBundle'], $adapted_product->getIsBundle() );
		$this->assertEquals( $attributes['adult'], $adapted_product->getAdult() );
	}

	public function test_gla_attribute_has_priority_over_rules() {
		$rules = $this->get_sample_rules();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => WC_Helper_Product::create_simple_product( false ),
				'mapping_rules'  => $rules,
				'gla_attributes' => [ 'gender' => 'man' ],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( $this->get_rule_attribute( 'condition' ), $adapted_product->getCondition() );
		$this->assertEquals( 'man', $adapted_product->getGender() );
	}

	public function test_attribute_value_can_be_overridden_via_filter() {
		add_filter(
			'woocommerce_gla_product_attribute_value_brand',
			function () {
				return 'Noodle';
			}
		);

		add_filter(
			'woocommerce_gla_product_attribute_value_gtin',
			function () {
				return '1234';
			}
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => WC_Helper_Product::create_simple_product( false ),
				'gla_attributes' => [
					Brand::get_id() => 'Google',
				],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( 'Noodle', $adapted_product->getBrand() );

		// if an attribute isn't previously set its value won't be overridden
		$this->assertNull( $adapted_product->getGtin() );
	}

	public function test_basic_attributes_can_be_overridden_via_filter() {
		add_filter(
			'woocommerce_gla_product_attribute_values',
			function ( array $attributes, WC_Product $product, WCProductAdapter $google_product ) {
				$attributes['imageLink']   = 'https://example.com/image_overide.png?prev=' . $google_product->getImageLink();
				$attributes['description'] = 'Overridden description!';
				$attributes['id']          = 'override_' . $product->get_id();

				return $attributes;
			},
			10,
			3
		);

		$product = WC_Helper_Product::create_simple_product(
			[
				'description' => 'Some long product description containing lorem ipsum and such.',
			]
		);

		$image = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ), $product->get_id() );

		$product->set_image_id( $image );
		$product->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals(
			'https://example.com/image_overide.png?prev=' . wp_get_attachment_image_url( $image ),
			$adapted_product->getImageLink()
		);
		$this->assertEquals(
			'Overridden description!',
			$adapted_product->getDescription()
		);
		$this->assertEquals(
			'override_' . $product->get_id(),
			$adapted_product->getId()
		);
	}

	public function test_attribute_values_filter_takes_precedence() {
		add_filter(
			'woocommerce_gla_product_attribute_value_gtin',
			function () {
				return '1234';
			}
		);

		add_filter(
			'woocommerce_gla_product_attribute_values',
			function ( array $attributes ) {
				$attributes['gtin'] = '56789';

				return $attributes;
			}
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => WC_Helper_Product::create_simple_product( false ),
				'gla_attributes' => [
					Brand::get_id() => 'Google',
				],
				'targetCountry'  => 'US',
			]
		);

		$this->assertEquals( '56789', $adapted_product->getGtin() );
	}

	public function test_channel_is_always_set_to_online() {
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => WC_Helper_Product::create_simple_product( false ),
				'targetCountry' => 'US',
				'channel'       => 'local',
			]
		);

		$this->assertEquals( 'online', $adapted_product->getChannel() );
	}

	public function test_content_language_is_set_by_default_to_en() {
		add_filter(
			'locale',
			function () {
				return null;
			}
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => WC_Helper_Product::create_simple_product( false ),
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( 'en', $adapted_product->getContentLanguage() );
	}

	public function test_content_language_is_set_to_wp_locale() {
		add_filter(
			'locale',
			function () {
				return 'fr_BE';
			}
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => WC_Helper_Product::create_simple_product( false ),
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( 'fr', $adapted_product->getContentLanguage() );
	}

	public function test_offer_id_is_set() {
		$product         = WC_Helper_Product::create_simple_product( false );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( "{$this->get_slug()}_{$product->get_id()}", $adapted_product->getOfferId() );
	}

	public function test_title_and_link_are_set() {
		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'name' => 'Sample Name',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 'Sample Name', $adapted_product->getTitle() );
		$this->assertEquals( $product->get_permalink(), $adapted_product->getLink() );
	}

	public function test_item_group_id_is_set_for_variations() {
		$variable        = WC_Helper_Product::create_variation_product();
		$variation       = wc_get_product( $variable->get_children()[0] );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'        => $variation,
				'parent_wc_product' => $variable,
				'targetCountry'     => 'US',
			]
		);
		$this->assertEquals( $variable->get_id(), $adapted_product->getItemGroupId() );
	}

	public function test_description_is_set() {
		$invalid_chars   = "\x00 \x08\x0B\x0C\x0E";
		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'description'       =>
					'<h1>Sample product</h1> description with some valid ⏰⌛⊗⊝Ɋ❤😍 and <strong data-title="what?">invalid</strong> unicode chars' . $invalid_chars .
					' and some registered short code like [video mp4="source.mp4"] and [gallery order="DESC" orderby="ID"] that ' .
					'will get <i>stripped out</i>, along with an unregistered short code [some-test-short-code id=1] that will remain intact.' .
					'<script>window.alert("this should be plain text!")</script><style>h1 {font-size: 2em;}</style>',
				'short_description' => 'Short description.',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$expected_description = '<h1>Sample product</h1> description with some valid ⏰⌛⊗⊝Ɋ❤😍 and <strong>invalid</strong> unicode chars  and some registered short code like ' .
								' and  that ' .
								'will get <i>stripped out</i>, along with an unregistered short code [some-test-short-code id=1] that will remain intact.' .
								'window.alert("this should be plain text!")h1 {font-size: 2em;}';

		$this->assertEquals( $expected_description, $adapted_product->getDescription() );
	}

	public function test_description_shortcodes_are_applied() {
		add_filter(
			'woocommerce_gla_product_description_apply_shortcodes',
			function () {
				return true;
			}
		);

		// add a sample shortcode to test
		add_shortcode(
			'wc_gla_sample_test_shortcode',
			function () {
				return 'sample-shortcode-rendered-result';
			}
		);

		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'description' => 'This product has a shortcode like [wc_gla_sample_test_shortcode] that will not get stripped out, ' .
								 'along with an unregistered short code [some-test-short-code id=1] that will also remain intact.',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$expected_description = 'This product has a shortcode like sample-shortcode-rendered-result that will not get stripped out, ' .
								'along with an unregistered short code [some-test-short-code id=1] that will also remain intact.';

		$this->assertEquals( $expected_description, $adapted_product->getDescription() );
	}

	public function test_short_description_is_set_if_description_empty() {
		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'description'       => '',
				'short_description' => 'Short description.',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( 'Short description.', $adapted_product->getDescription() );
	}

	public function test_short_description_is_set_if_filter_used() {
		add_filter(
			'woocommerce_gla_use_short_description',
			function () {
				return true;
			}
		);

		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'description'       => 'Long description.',
				'short_description' => 'Short description.',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( 'Short description.', $adapted_product->getDescription() );
	}

	public function test_description_can_be_modified_using_filter() {
		add_filter(
			'woocommerce_gla_product_attribute_value_description',
			function () {
				return 'Modified description!';
			}
		);

		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'description' => 'Long description.',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( 'Modified description!', $adapted_product->getDescription() );
	}

	public function test_description_is_trimmed_if_more_than_5000_chars() {
		$long_description = rand_long_str( 10000 );

		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'description' => $long_description,
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( 5000, mb_strlen( $adapted_product->getDescription(), 'utf-8' ) );
		$this->assertEquals( mb_substr( $long_description, 0, 5000, 'utf-8' ), $adapted_product->getDescription() );
	}

	public function test_parent_description_is_included_in_variation_description() {
		$variable = WC_Helper_Product::create_variation_product();
		$variable->set_description( 'Parent description.' );

		$variation = wc_get_product( $variable->get_children()[0] );
		$variation->set_description( 'Variation description.' );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'        => $variation,
				'parent_wc_product' => $variable,
				'targetCountry'     => 'US',
			]
		);

		$expected_description = <<<DESCRIPTION
Parent description.
Variation description.
DESCRIPTION;

		$this->assertEquals( $expected_description, $adapted_product->getDescription() );
	}

	public function test_parent_short_description_is_included_in_variation_description() {
		$variable = WC_Helper_Product::create_variation_product();
		$variable->set_description( '' );
		$variable->set_short_description( 'Parent short description.' );

		$variation = wc_get_product( $variable->get_children()[0] );
		$variation->set_description( 'Variation description.' );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'        => $variation,
				'parent_wc_product' => $variable,
				'targetCountry'     => 'US',
			]
		);

		$expected_description = <<<DESCRIPTION
Parent short description.
Variation description.
DESCRIPTION;

		$this->assertEquals( $expected_description, $adapted_product->getDescription() );
	}

	public function test_product_types_are_set_when_categories_defined() {
		$product    = WC_Helper_Product::create_simple_product();
		$category_1 = wp_insert_term( 'Zulu Category', 'product_cat' );
		$category_2 = wp_insert_term( 'Alpha Category', 'product_cat' );
		$category_3 = wp_insert_term(
			'Beta Category',
			'product_cat',
			[ 'parent' => $category_2['term_id'] ]
		);
		$product->set_category_ids( [ $category_1['term_id'], $category_3['term_id'] ] );
		$product->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertContains( 'Alpha Category > Beta Category', $adapted_product->getProductTypes() );
		$this->assertContains( 'Zulu Category', $adapted_product->getProductTypes() );
		$this->assertContains( 'Alpha Category', $adapted_product->getProductTypes() );
	}

	public function test_images_are_set() {
		$product = WC_Helper_Product::create_simple_product();

		$main_image = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ), $product->get_id() );

		$additional_images = [];
		// Intentionally add more than 10 images to test the limiting functionality.
		for ( $i = 0; $i < 12; $i++ ) {
			$additional_images[ $i ] = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-2.png' ), $product->get_id() );
		}

		$product->set_image_id( $main_image );
		$product->set_gallery_image_ids( $additional_images );
		$product->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( wp_get_attachment_image_url( $main_image ), $adapted_product->getImageLink() );

		// Assert that only up to 10 additional images are added.
		$additional_images = array_slice( $additional_images, 0, 10 );
		$this->assertEqualSets(
			array_map( 'wp_get_attachment_image_url', $additional_images ),
			$adapted_product->getAdditionalImageLinks()
		);
	}

	public function test_gallery_image_is_set_as_main_image_if_none_provided() {
		$product = WC_Helper_Product::create_simple_product();

		$image_1 = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ), $product->get_id() );

		$product->set_gallery_image_ids( [ $image_1 ] );
		$product->save();

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( wp_get_attachment_image_url( $image_1 ), $adapted_product->getImageLink() );
		$this->assertEmpty( $adapted_product->getAdditionalImageLinks() );
	}

	public function test_parent_images_are_set_for_variation_if_it_doesnt_have_any() {
		$variable         = WC_Helper_Product::create_variation_product();
		$variable_image_1 = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-1.png' ), $variable->get_id() );
		$variable_image_2 = $this->factory()->attachment->create_upload_object( $this->get_data_file_path( 'test-image-2.png' ), $variable->get_id() );

		$variable->set_image_id( $variable_image_1 );
		$variable->set_gallery_image_ids( [ $variable_image_2 ] );
		$variable->save();

		$variation = wc_get_product( $variable->get_children()[0] );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'        => $variation,
				'parent_wc_product' => $variable,
				'targetCountry'     => 'US',
			]
		);

		$this->assertEquals( wp_get_attachment_image_url( $variable_image_1 ), $adapted_product->getImageLink() );
		$this->assertEqualSets(
			[
				wp_get_attachment_image_url( $variable_image_2 ),
			],
			$adapted_product->getAdditionalImageLinks()
		);
	}

	public function test_availability_is_set_if_out_of_stock() {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->set_stock_status( 'outofstock' );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( WCProductAdapter::AVAILABILITY_OUT_OF_STOCK, $adapted_product->getAvailability() );
	}

	public function test_availability_is_set_if_in_stock() {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->set_stock_status( 'instock' );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( WCProductAdapter::AVAILABILITY_IN_STOCK, $adapted_product->getAvailability() );
	}

	public function test_availability_is_set_to_backorder_if_0_stock_and_backorder_enabled() {
		$product = WC_Helper_Product::create_simple_product( false );
		$product->set_manage_stock( true );

		$product->set_stock_status( 'instock' );
		$product->set_backorders( 'yes' );
		$product->set_stock_quantity( 0 );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( WCProductAdapter::AVAILABILITY_BACKORDER, $adapted_product->getAvailability() );
	}

	public function test_shipping_country_is_set_based_on_target_country() {
		$product = WC_Helper_Product::create_simple_product( false );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertCount( 1, $adapted_product->getShipping() );
		$this->assertEquals( 'US', $adapted_product->getShipping()[0]->getCountry() );
	}

	public function test_shipping_price_is_zero_for_virtual_products() {
		$product = WC_Helper_Product::create_simple_product( false, [ 'virtual' => true ] );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertCount( 1, $adapted_product->getShipping() );
		$this->assertNotNull( $adapted_product->getShipping()[0]->getPrice() );
		$this->assertEquals( 0, $adapted_product->getShipping()[0]->getPrice()->getValue() );
	}

	public function test_no_shipping_dimensions_and_weight_set_for_virtual_products() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'virtual' => true,
				'height'  => '3',
				'length'  => '3',
				'width'   => '3',
				'weight'  => '1.1',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertNull( $adapted_product->getShippingHeight() );
		$this->assertNull( $adapted_product->getShippingLength() );
		$this->assertNull( $adapted_product->getShippingWidth() );
		$this->assertNull( $adapted_product->getShippingWeight() );
	}

	public function test_shipping_dimensions_and_weight_are_set() {
		$dimensions_unit = get_option( 'woocommerce_dimension_unit' );
		$weight_unit     = get_option( 'woocommerce_weight_unit' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'height' => '30.5',
				'length' => '40.2',
				'width'  => '50.99',
				'weight' => '4.19',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( $dimensions_unit, $adapted_product->getShippingHeight()->getUnit() );
		$this->assertEquals( 30.5, $adapted_product->getShippingHeight()->getValue() );

		$this->assertEquals( $dimensions_unit, $adapted_product->getShippingLength()->getUnit() );
		$this->assertEquals( 40.2, $adapted_product->getShippingLength()->getValue() );

		$this->assertEquals( $dimensions_unit, $adapted_product->getShippingWidth()->getUnit() );
		$this->assertEquals( 50.99, $adapted_product->getShippingWidth()->getValue() );

		$this->assertEquals( $weight_unit, $adapted_product->getShippingWeight()->getUnit() );
		$this->assertEquals( 4.19, $adapted_product->getShippingWeight()->getValue() );
	}

	public function test_shipping_dimensions_and_weight_units_can_be_changed_via_filter() {
		add_filter(
			'woocommerce_gla_dimension_unit',
			function () {
				return 'in';
			}
		);
		add_filter(
			'woocommerce_gla_weight_unit',
			function () {
				return 'oz';
			}
		);

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'height' => '30',
				'length' => '40',
				'width'  => '50',
				'weight' => '2.5',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( 'in', $adapted_product->getShippingHeight()->getUnit() );
		$this->assertEquals( 'in', $adapted_product->getShippingLength()->getUnit() );
		$this->assertEquals( 'in', $adapted_product->getShippingWidth()->getUnit() );
		$this->assertEquals( 'oz', $adapted_product->getShippingWeight()->getUnit() );

		// Check that the values are also converted to the newly set unit
		$this->assertEquals( wc_get_dimension( 30, 'in' ), $adapted_product->getShippingHeight()->getValue() );
		$this->assertEquals( wc_get_dimension( 40, 'in' ), $adapted_product->getShippingLength()->getValue() );
		$this->assertEquals( wc_get_dimension( 50, 'in' ), $adapted_product->getShippingWidth()->getValue() );
		$this->assertEquals( wc_get_weight( 2.5, 'oz' ), $adapted_product->getShippingWeight()->getValue() );
	}

	public function test_shipping_dimensions_and_weight_units_use_default_if_invalid_value_provided_via_filters() {
		add_filter(
			'woocommerce_gla_dimension_unit',
			function () {
				return 'some-non-existing-unit';
			}
		);
		add_filter(
			'woocommerce_gla_weight_unit',
			function () {
				return 'some-other-non-existing-unit';
			}
		);

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'height' => '30',
				'length' => '40',
				'width'  => '50',
				'weight' => '2.5',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 'cm', $adapted_product->getShippingHeight()->getUnit() );
		$this->assertEquals( 'cm', $adapted_product->getShippingLength()->getUnit() );
		$this->assertEquals( 'cm', $adapted_product->getShippingWidth()->getUnit() );
		$this->assertEquals( 'g', $adapted_product->getShippingWeight()->getUnit() );
	}

	public function test_shipping_dimensions_and_weight_units_use_default_if_wc_options_are_not_supported() {
		update_option( 'woocommerce_dimension_unit', 'm' );
		update_option( 'woocommerce_weight_unit', 'kg' );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'height' => '3',
				'length' => '4',
				'width'  => '5',
				'weight' => '1',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 'cm', $adapted_product->getShippingHeight()->getUnit() );
		$this->assertEquals( 'cm', $adapted_product->getShippingLength()->getUnit() );
		$this->assertEquals( 'cm', $adapted_product->getShippingWidth()->getUnit() );
		$this->assertEquals( 'g', $adapted_product->getShippingWeight()->getUnit() );

		// Check that the values are also converted to the newly set unit
		$this->assertEquals( 300, $adapted_product->getShippingHeight()->getValue() );
		$this->assertEquals( 400, $adapted_product->getShippingLength()->getValue() );
		$this->assertEquals( 500, $adapted_product->getShippingWidth()->getValue() );
		$this->assertEquals( 1000, $adapted_product->getShippingWeight()->getValue() );
	}

	/**
	 * @param array $shipping_dimensions
	 *
	 * @dataProvider provide_incomplete_shipping_dimensions
	 */
	public function test_shipping_dimensions_are_not_set_if_any_not_provided( array $shipping_dimensions ) {
		$product = WC_Helper_Product::create_simple_product( false, $shipping_dimensions );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertNull( $adapted_product->getShippingHeight() );
		$this->assertNull( $adapted_product->getShippingLength() );
		$this->assertNull( $adapted_product->getShippingWidth() );
	}

	public function test_identifier_exists_is_null_if_gtin_and_mpn_not_provided() {
		$product         = WC_Helper_Product::create_simple_product( false );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertNull( $adapted_product->getIdentifierExists() );
	}

	public function test_identifier_exists_is_null_if_gtin_provided() {
		$product         = WC_Helper_Product::create_simple_product( false );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => $product,
				'targetCountry'  => 'US',
				'gla_attributes' => [
					GTIN::get_id() => '12345678',
				],
			]
		);
		$this->assertNull( $adapted_product->getIdentifierExists() );
	}

	public function test_identifier_exists_is_null_if_mpn_provided() {
		$product         = WC_Helper_Product::create_simple_product( false );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'     => $product,
				'targetCountry'  => 'US',
				'gla_attributes' => [
					MPN::get_id() => '12345678',
				],
			]
		);
		$this->assertNull( $adapted_product->getIdentifierExists() );
	}

	public function test_product_price_is_set() {
		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'             => 100,
				'regular_price'     => 100,
				'sale_price'        => 50,
				'date_on_sale_from' => '2021-01-01',
				'date_on_sale_to'   => '2099-01-01',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( get_option( 'woocommerce_currency' ), $adapted_product->getPrice()->getCurrency() );
		$this->assertEquals( 100, $adapted_product->getPrice()->getValue() );
		$this->assertEquals( 50, $adapted_product->getSalePrice()->getValue() );
		$this->assertEquals(
			sprintf( '%s/%s', (string) $product->get_date_on_sale_from(), (string) $product->get_date_on_sale_to() ),
			$adapted_product->getSalePriceEffectiveDate()
		);
	}

	public function test_product_price_can_be_modified_via_filter() {
		add_filter(
			'woocommerce_gla_product_attribute_value_price',
			function () {
				return 50;
			}
		);
		add_filter(
			'woocommerce_gla_product_attribute_value_sale_price',
			function () {
				return 20;
			}
		);

		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => 50,
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 50, $adapted_product->getPrice()->getValue() );
		$this->assertEquals( 20, $adapted_product->getSalePrice()->getValue() );
	}

	public function test_product_price_includes_or_excludes_tax_based_on_target_country() {
		add_filter(
			'wc_tax_enabled',
			function () {
				return true;
			}
		);
		add_filter(
			'woocommerce_prices_include_tax',
			function () {
				return false;
			}
		);
		WC_Tax::_insert_tax_rate(
			[
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			]
		);

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => 50,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'DE',
			]
		);
		$this->assertEquals( 120, $adapted_product->getPrice()->getValue() );
		$this->assertEquals( 60, $adapted_product->getSalePrice()->getValue() );

		$adapted_product_2 = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 100, $adapted_product_2->getPrice()->getValue() );
		$this->assertEquals( 50, $adapted_product_2->getSalePrice()->getValue() );

		$adapted_product_3 = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'CA',
			]
		);
		$this->assertEquals( 100, $adapted_product_3->getPrice()->getValue() );
		$this->assertEquals( 50, $adapted_product_3->getSalePrice()->getValue() );
	}

	public function test_product_price_excludes_tax_can_be_modified_via_filter() {
		add_filter(
			'wc_tax_enabled',
			function () {
				return true;
			}
		);
		add_filter(
			'woocommerce_prices_include_tax',
			function () {
				return false;
			}
		);
		add_filter(
			'woocommerce_gla_tax_excluded',
			function () {
				return false;
			}
		);
		WC_Tax::_insert_tax_rate(
			[
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			]
		);

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => 50,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'DE',
			]
		);
		$this->assertEquals( 120, $adapted_product->getPrice()->getValue() );
		$this->assertEquals( 60, $adapted_product->getSalePrice()->getValue() );

		$adapted_product_2 = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 120, $adapted_product_2->getPrice()->getValue() );
		$this->assertEquals( 60, $adapted_product_2->getSalePrice()->getValue() );

		$adapted_product_3 = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'CA',
			]
		);
		$this->assertEquals( 120, $adapted_product_3->getPrice()->getValue() );
		$this->assertEquals( 60, $adapted_product_3->getSalePrice()->getValue() );
	}

	public function test_product_sale_price_is_set_to_active_price_if_empty() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 50,
				'regular_price' => 100,
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 100, $adapted_product->getPrice()->getValue() );
		$this->assertEquals( 50, $adapted_product->getSalePrice()->getValue() );
	}

	public function test_product_sale_price_is_set_to_active_price_if_its_less() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 40,
				'regular_price' => 100,
				'sale_price'    => 50,
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 100, $adapted_product->getPrice()->getValue() );
		$this->assertEquals( 40, $adapted_product->getSalePrice()->getValue() );
	}

	public function test_product_sale_price_is_not_set_if_empty() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => '',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertNull( $adapted_product->getSalePrice() );
		$this->assertNull( $adapted_product->getSalePriceEffectiveDate() );
	}

	public function test_product_sale_price_is_not_set_if_null() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => null,
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertNull( $adapted_product->getSalePrice() );
		$this->assertNull( $adapted_product->getSalePriceEffectiveDate() );
	}

	public function test_product_sale_price_is_set_if_zero() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => 0,
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertEquals( 0, $adapted_product->getSalePrice()->getValue() );
	}

	public function test_product_sale_price_is_not_set_if_sale_end_date_passed() {
		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'           => 100,
				'regular_price'   => 100,
				'sale_price'      => 50,
				'date_on_sale_to' => '0000-01-01',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertNull( $adapted_product->getSalePrice() );
		$this->assertNull( $adapted_product->getSalePriceEffectiveDate() );
	}

	public function test_product_sale_price_effective_date_start_is_set_to_now_if_empty() {
		$now = (string) ( new WC_DateTime( 'now' ) );

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'           => 100,
				'regular_price'   => 100,
				'sale_price'      => 50,
				'date_on_sale_to' => '2099-01-01',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertNotNull( $adapted_product->getSalePriceEffectiveDate() );
		$dates = explode( '/', $adapted_product->getSalePriceEffectiveDate() );
		$this->assertCount( 2, $dates );

		// start date
		$this->assertNotEmpty( $dates[0] );
		$this->assertGreaterThanOrEqual( new WC_DateTime( $now ), new WC_DateTime( $dates[0] ) );

		// end date
		$this->assertNotEmpty( $dates[1] );
		$this->assertEquals( (string) new WC_DateTime( '2099-01-01' ), $dates[1] );
	}

	public function test_product_sale_price_effective_date_start_is_not_set_if_in_past_and_no_end_date() {
		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'             => 100,
				'regular_price'     => 100,
				'sale_price'        => 50,
				'date_on_sale_from' => '0000-01-01',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertNull( $adapted_product->getSalePriceEffectiveDate() );
	}

	public function test_product_sale_price_effective_date_end_is_set_to_one_day_if_start_in_future_but_no_end() {
		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'             => 100,
				'regular_price'     => 100,
				'sale_price'        => 50,
				'date_on_sale_from' => '2099-01-01',
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals(
			sprintf( '%s/%s', (string) new WC_DateTime( '2099-01-01' ), (string) new WC_DateTime( '2099-01-02' ) ),
			$adapted_product->getSalePriceEffectiveDate()
		);
	}

	public function test_product_sale_price_effective_date_is_null_if_not_set() {
		$product         = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'sale_price'    => 50,
			]
		);
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertNull( $adapted_product->getSalePriceEffectiveDate() );
	}

	public function test_is_variation() {
		$product         = WC_Helper_Product::create_simple_product( false );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertFalse( $adapted_product->is_variation() );

		$variable        = WC_Helper_Product::create_variation_product();
		$variation       = wc_get_product( $variable->get_children()[0] );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'        => $variation,
				'parent_wc_product' => $variable,
				'targetCountry'     => 'US',
			]
		);
		$this->assertTrue( $adapted_product->is_variation() );
	}

	public function test_is_virtual() {
		$product         = WC_Helper_Product::create_simple_product( false, [ 'virtual' => false ] );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertFalse( $adapted_product->is_virtual() );

		$product         = WC_Helper_Product::create_simple_product( false, [ 'virtual' => true ] );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);
		$this->assertTrue( $adapted_product->is_virtual() );
	}

	public function test_get_wc_product() {
		$product         = WC_Helper_Product::create_simple_product( false, [ 'virtual' => false ] );
		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$this->assertEquals( $product, $adapted_product->get_wc_product() );
	}

	public function test_set_target_country_recalculates_prices() {
		add_filter(
			'wc_tax_enabled',
			function () {
				return true;
			}
		);
		add_filter(
			'woocommerce_prices_include_tax',
			function () {
				return false;
			}
		);
		WC_Tax::_insert_tax_rate(
			[
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			]
		);

		$product = WC_Helper_Product::create_simple_product(
			false,
			[
				'price'         => 100,
				'regular_price' => 100,
				'tax_status'    => 'taxable',
				'tax_class'     => 'standard',
			]
		);

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'DE',
			]
		);
		$this->assertEquals( 120, $adapted_product->getPrice()->getValue() );

		$adapted_product->setTargetCountry( 'US' );
		$this->assertEquals( 100, $adapted_product->getPrice()->getValue() );
	}

	public function test_set_target_country_sets_shipping_country() {
		$product = WC_Helper_Product::create_simple_product( false );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'DE',
			]
		);
		$this->assertCount( 1, $adapted_product->getShipping() );
		$this->assertEquals( 'DE', $adapted_product->getShipping()[0]->getCountry() );

		$adapted_product->setTargetCountry( 'US' );
		$this->assertCount( 1, $adapted_product->getShipping() );
		$this->assertEquals( 'US', $adapted_product->getShipping()[0]->getCountry() );
	}

	public function test_add_shipping_country() {
		$product = WC_Helper_Product::create_simple_product( false );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'DE',
			]
		);

		$adapted_product->add_shipping_country( 'US' );

		// add it a second time to make sure it doesn't add duplicates
		$adapted_product->add_shipping_country( 'US' );

		$this->assertCount( 2, $adapted_product->getShipping() );

		$shipping_countries = array_map(
			function ( ProductShipping $shipping ) {
				return $shipping->getCountry();
			},
			$adapted_product->getShipping()
		);

		$this->assertCount( 2, $shipping_countries );
		$this->assertContains( 'US', $shipping_countries );
		$this->assertContains( 'DE', $shipping_countries );
	}

	public function test_add_shipping_country_sets_price_zero_for_virtual_products() {
		$product = WC_Helper_Product::create_simple_product( false, [ 'virtual' => true ] );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'DE',
			]
		);

		$adapted_product->add_shipping_country( 'US' );

		$this->assertCount( 2, $adapted_product->getShipping() );

		$this->assertEquals( 0, $adapted_product->getShipping()[0]->getPrice()->getValue() );
		$this->assertEquals( 0, $adapted_product->getShipping()[1]->getPrice()->getValue() );
	}

	public function test_remove_shipping_country() {
		$product = WC_Helper_Product::create_simple_product( false );

		$adapted_product = new WCProductAdapter(
			[
				'wc_product'    => $product,
				'targetCountry' => 'US',
			]
		);

		$adapted_product->remove_shipping_country( 'US' );

		$this->assertCount( 0, $adapted_product->getShipping() );
	}

	public function test_load_validator_metadata() {
		$metadata = new ClassMetadata( WCProductAdapter::class );
		WCProductAdapter::load_validator_metadata( $metadata );
		$this->assertTrue( $metadata->hasConstraints() );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'offerId' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'title' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'description' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'link' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'imageLink' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'additionalImageLinks' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'price' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'salePrice' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'gtin' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'mpn' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'sizes' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'sizeSystem' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'sizeType' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'color' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'material' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'pattern' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'ageGroup' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'adult' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'condition' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'multipack' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'isBundle' ) );
	}

	public function test_valid_image_name_with_utf8_nfd() {
		$adapted_product            = new WCProductAdapter();
		$adapted_product->imageLink = 'https://domain.com/ïmágê-ñåmè.jpg'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$violation = $this->validate_product_property( $adapted_product, 'imageLink' );
		$this->assertNull( $violation );
	}

	/**
	 * Validate a product property and return the first violation.
	 *
	 * @param WCProductAdapter $adapted_product Product to validate.
	 * @param string           $property        Property to check for violations.
	 * @return null|ConstraintViolation
	 */
	protected function validate_product_property( WCProductAdapter $adapted_product, string $property ) {
		$validator = Validation::createValidatorBuilder()
			->addMethodMapping( 'load_validator_metadata' )
			->getValidator();

		foreach ( $validator->validate( $adapted_product ) as $violation ) {
			if ( $violation->getPropertyPath() === $property ) {
				return $violation;
			}
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function provide_incomplete_shipping_dimensions(): array {
		return [
			[
				[
					'height' => '3',
					'length' => '4',
				],
			],
			[
				[
					'height' => '3',
					'width'  => '5',
				],
			],
			[
				[
					'length' => '4',
					'width'  => '5',
				],
			],
		];
	}

	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_dimension_unit', 'cm' );
		update_option( 'woocommerce_weight_unit', 'g' );
		update_option( 'woocommerce_currency', 'USD' );
	}

	public function tearDown(): void {
		parent::tearDown();

		// remove any added filter
		remove_all_filters( 'woocommerce_gla_dimension_unit' );
		remove_all_filters( 'woocommerce_gla_weight_unit' );
		remove_all_filters( 'woocommerce_gla_product_attribute_value_price' );
		remove_all_filters( 'woocommerce_gla_product_attribute_value_sale_price' );
		remove_all_filters( 'wc_tax_enabled' );
		remove_all_filters( 'woocommerce_prices_include_tax' );
		remove_all_filters( 'woocommerce_gla_product_description_apply_shortcodes' );
		remove_all_filters( 'woocommerce_gla_use_short_description' );
		remove_all_filters( 'woocommerce_gla_product_attribute_value_description' );
		remove_all_filters( 'woocommerce_gla_product_attribute_values' );

		// remove added shortcodes
		remove_shortcode( 'wc_gla_sample_test_shortcode' );
	}
}
