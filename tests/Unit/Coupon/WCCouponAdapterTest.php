<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\WCCouponAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\DataTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use WC_Helper_Product;

/**
 * Class WCCouponAdapterTest
 *
 * @group Coupons
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon
 */
class WCCouponAdapterTest extends UnitTest {
	use CouponTrait;
	use PluginHelper;
	use DataTrait;

	public function test_throws_exception_if_wc_coupon_not_provided() {
		$this->expectException( InvalidValue::class );
		new WCCouponAdapter( [ 'targetCountry' => 'US' ] );
	}

	public function test_throws_exception_if_invalid_wc_coupon_provided() {
		$this->expectException( InvalidValue::class );
		new WCCouponAdapter(
			[
				'wc_couopon'    => new \stdClass(),
				'targetCountry' => 'US',
			]
		);
	}

	public function test_channel_is_always_set_to_online() {
		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $this->create_ready_to_sync_coupon(),
				'targetCountry' => 'US',
				'channel'       => 'local',
			]
		);

		$promotion = $adapted_coupon->get_promotion();
		$this->assertSame( [ 'ONLINE' ], $promotion['redemptionChannel'] );
	}

	public function test_content_language_is_set_by_default_to_en() {
		add_filter(
			'locale',
			function () {
				return null;
			}
		);

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $this->create_ready_to_sync_coupon(),
				'targetCountry' => 'US',
			]
		);

		$promotion = $adapted_coupon->get_promotion();
		$this->assertEquals( 'en', $promotion['contentLanguage'] );
	}

	public function test_content_language_is_set_to_wp_locale() {
		add_filter(
			'locale',
			function () {
				return 'fr_BE';
			}
		);

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $this->create_ready_to_sync_coupon(),
				'targetCountry' => 'US',
			]
		);

		$promotion = $adapted_coupon->get_promotion();
		$this->assertEquals( 'fr', $promotion['contentLanguage'] );
	}

	public function test_content_language_falls_back_to_en_for_unsupported_locale() {
		add_filter(
			'locale',
			function () {
				return 'als';
			}
		);

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $this->create_ready_to_sync_coupon(),
				'targetCountry' => 'US',
			]
		);

		$promotion = $adapted_coupon->get_promotion();
		$this->assertEquals( 'en', $promotion['contentLanguage'] );
	}

	public function test_destinations_are_set() {
		$coupon = $this->create_ready_to_sync_coupon();

		foreach ( WCCouponAdapter::COUNTRIES_WITH_FREE_SHIPPING_DESTINATION as $free_shipping_destination ) {
			$adapted_coupon = new WCCouponAdapter(
				[
					'wc_coupon'     => $coupon,
					'targetCountry' => $free_shipping_destination,
				]
			);

			$promotion = $adapted_coupon->get_promotion();
			$this->assertEquals(
				[ 'SHOPPING_ADS', 'FREE_LISTINGS' ],
				$promotion['attributes']['promotionDestinations']
			);
		}

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'IN',
			]
		);

		$promotion = $adapted_coupon->get_promotion();
		$this->assertEquals(
			[ 'SHOPPING_ADS' ],
			$promotion['attributes']['promotionDestinations']
		);
	}

	public function test_promotion_id_is_set() {
		$coupon         = $this->create_ready_to_sync_coupon();
		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'US',
			]
		);

		$promotion = $adapted_coupon->get_promotion();
		$this->assertEquals(
			"{$this->get_slug()}_{$coupon->get_id()}",
			$promotion['promotionId']
		);
	}

	public function test_coupon_code_and_amount_are_set() {
		$coupon         = $this->create_ready_to_sync_coupon();
		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'US',
			]
		);

		$promotion  = $adapted_coupon->get_promotion();
		$attributes = $promotion['attributes'];
		$this->assertEquals( $coupon->get_code(), $attributes['genericRedemptionCode'] );
		// percentOff is an int64, serialised as a string per the Merchant API spec.
		$this->assertSame( (string) (int) $coupon->get_amount(), $attributes['percentOff'] );
		$this->assertEquals( 'GENERIC_CODE', $attributes['offerType'] );
		$this->assertEquals( 'PERCENT_OFF', $attributes['couponValueType'] );
	}

	public function test_effective_dates_are_set() {
		$coupon    = $this->create_ready_to_sync_coupon();
		$postdate  = '2021-01-01T02:03:45';
		$post_args = [
			'ID'            => $coupon->get_id(),
			'post_date'     => $postdate,
			'post_date_gmt' => $postdate,
		];
		wp_update_post( $post_args );

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'US',
			]
		);
		$promotion      = $adapted_coupon->get_promotion();
		$period         = $promotion['attributes']['promotionEffectiveTimePeriod'];

		$this->assertEquals( '2021-01-01T02:03:45+00:00', $period['startTime'] );
		$this->assertEquals( '2021-07-03T02:03:45+00:00', $period['endTime'] );
	}

	public function test_disable_promotion() {
		$coupon = $this->create_ready_to_sync_coupon();
		// Offset-less GMT wall clock is stored verbatim in post_date (no tz conversion),
		// so it reads back as the exact instant the promotion start is derived from.
		$postdate  = gmdate( 'Y-m-d\TH:i:s' );
		$post_args = [
			'ID'            => $coupon->get_id(),
			'post_date'     => $postdate,
			'post_date_gmt' => $postdate,
		];
		wp_update_post( $post_args );

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'US',
			]
		);
		$adapted_coupon->disable_promotion( $coupon );
		$promotion = $adapted_coupon->get_promotion();
		$period    = $promotion['attributes']['promotionEffectiveTimePeriod'];

		// The start date is unchanged; disabling expires the promotion at (or just after) it.
		$this->assertEquals( "{$postdate}+00:00", $period['startTime'] );
		$this->assertGreaterThanOrEqual( strtotime( $period['startTime'] ), strtotime( $period['endTime'] ) );
		$this->assertLessThanOrEqual( strtotime( $postdate ) + 5, strtotime( $period['endTime'] ) );
	}

	public function test_product_id_restrictions() {
		$product_id_1 = wp_rand();
		$product_id_2 = wp_rand();
		$coupon       = $this->create_ready_to_sync_coupon();
		$coupon->set_product_ids( [ $product_id_1 ] );
		$coupon->set_excluded_product_ids( [ $product_id_2 ] );
		$coupon->save();

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'US',
			]
		);

		$promotion  = $adapted_coupon->get_promotion();
		$attributes = $promotion['attributes'];
		$this->assertEquals( [ "gla_{$product_id_1}" ], $attributes['itemIdInclusion'] );
		$this->assertEquals( [ "gla_{$product_id_2}" ], $attributes['itemIdExclusion'] );
	}

	public function test_product_type_restrictions() {
		$category_1 = wp_insert_term( 'Zulu Category', 'product_cat' );
		$category_2 = wp_insert_term( 'Alpha Category', 'product_cat' );
		$category_3 = wp_insert_term(
			'Beta Category',
			'product_cat',
			[ 'parent' => $category_2['term_id'] ],
		);

		$coupon = $this->create_ready_to_sync_coupon();
		$coupon->set_product_categories( [ $category_1['term_id'], $category_2['term_id'] ] );
		$coupon->set_excluded_product_categories( [ $category_3['term_id'] ] );
		$coupon->save();

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'US',
			]
		);

		$promotion  = $adapted_coupon->get_promotion();
		$attributes = $promotion['attributes'];
		$this->assertEquals( [ 'Zulu Category', 'Alpha Category' ], $attributes['productTypeInclusion'] );
		$this->assertEquals( [ 'Alpha Category > Beta Category' ], $attributes['productTypeExclusion'] );
	}

	public function test_brand_restrictions() {
		// compatibility-code "WC < 9.4" -- Brands in core was added in WooCommerce 9.4
		if ( version_compare( WC_VERSION, '9.4', '<' ) ) {
			self::markTestSkipped( 'WooCommerce 9.4 or newer is needed to test WooCommerce Brands in core.' );
		}

		require_once WC_ABSPATH . '/includes/class-wc-brands.php';
		\WC_Brands::init_taxonomy();

		$product_1    = WC_Helper_Product::create_simple_product();
		$product_1_id = $product_1->get_id();
		$product_2    = WC_Helper_Product::create_simple_product();
		$product_2_id = $product_2->get_id();
		$product_3    = WC_Helper_Product::create_simple_product();
		$product_3_id = $product_3->get_id();

		$brand_1 = wp_insert_term( 'Brand 1', 'product_brand' );
		$brand_2 = wp_insert_term( 'Brand 2', 'product_brand' );

		// Set the brand 1 for the product 1 and 2.
		wp_set_post_terms( $product_1_id, $brand_1['term_id'], 'product_brand' );
		wp_set_post_terms( $product_2_id, $brand_1['term_id'], 'product_brand' );

		// Set the brand 2 for the product 3.
		wp_set_post_terms( $product_3_id, $brand_2['term_id'], 'product_brand' );

		$coupon = $this->create_ready_to_sync_coupon();

		// Include product 3 for the coupon.
		$coupon->set_product_ids( [ $product_3_id ] );

		// Include brand 1 (product 1 and 2) for the coupon.
		update_post_meta( $coupon->get_id(), 'product_brands', [ $brand_1['term_id'] ] );

		// Exclude product 2 for the coupon.
		$coupon->set_excluded_product_ids( [ $product_2_id ] );

		// Exclude brand 2 (product 3) for the coupon.
		update_post_meta( $coupon->get_id(), 'exclude_product_brands', [ $brand_2['term_id'] ] );

		$coupon->save();

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'US',
			]
		);

		$promotion  = $adapted_coupon->get_promotion();
		$attributes = $promotion['attributes'];

		// The brand inclusion will override the product inclusion, so the product 3 won't appear in the inclusion list at the moment.
		$this->assertEquals( [ "gla_{$product_1_id}", "gla_{$product_2_id}" ], $attributes['itemIdInclusion'] );

		// The brand exclusion will respect the product inclusion, so the product 2 appears in the exclusion list.
		$this->assertEquals( [ "gla_{$product_2_id}", "gla_{$product_3_id}" ], $attributes['itemIdExclusion'] );
	}

	public function test_load_validator_metadata() {
		$metadata = new ClassMetadata( WCCouponAdapter::class );
		WCCouponAdapter::load_validator_metadata( $metadata );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'target_country' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'generic_redemption_code' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'promotion_id' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'product_applicability' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'offer_type' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'redemption_channel' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'coupon_value_type' ) );
	}

	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_currency', 'USD' );
	}
}
