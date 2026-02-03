<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Unit\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\WCCouponAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Framework\UnitTest;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\DataTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait\CouponTrait;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\TimePeriod as GoogleTimePeriod;
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

		$this->assertEquals( 'ONLINE', $adapted_coupon->getRedemptionChannel() );
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

		$this->assertEquals( 'en', $adapted_coupon->getContentLanguage() );
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

		$this->assertEquals( 'fr', $adapted_coupon->getContentLanguage() );
	}

	public function test_destination_ids_are_set() {
		$coupon = $this->create_ready_to_sync_coupon();

		foreach ( WCCouponAdapter::COUNTRIES_WITH_FREE_SHIPPING_DESTINATION as $free_shipping_destination ) {
			$adapted_coupon = new WCCouponAdapter(
				[
					'wc_coupon'     => $coupon,
					'targetCountry' => $free_shipping_destination,
				]
			);

			$this->assertEquals(
				[ 'Shopping_ads', 'Free_listings' ],
				$adapted_coupon->getPromotionDestinationIds()
			);
		}

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'IN',
			]
		);

		$this->assertEquals(
			[ 'Shopping_ads' ],
			$adapted_coupon->getPromotionDestinationIds()
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

		$this->assertEquals(
			"{$this->get_slug()}_{$coupon->get_id()}",
			$adapted_coupon->getPromotionId()
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

		$this->assertEquals( $coupon->get_code(), $adapted_coupon->getGenericRedemptionCode() );
		$this->assertEquals( $coupon->get_amount(), $adapted_coupon->getPercentOff() );
		$this->assertEquals( 'GENERIC_CODE', $adapted_coupon->getOfferType() );
		$this->assertEquals( 'PERCENT_OFF', $adapted_coupon->getCouponValueType() );
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
		$expected       = new GoogleTimePeriod(
			[
				'startTime' => '2021-01-01T02:03:45+00:00',
				'endTime'   => '2021-07-03T02:03:45+00:00',
			]
		);

		$actual = $adapted_coupon->getPromotionEffectiveTimePeriod();
		$this->assertEquals( $expected->getStartTime(), $actual->getStartTime() );
		$this->assertEquals( $expected->getEndTime(), $actual->getEndTime() );
	}

	public function test_disable_promotion() {
		$coupon    = $this->create_ready_to_sync_coupon();
		$postdate  = gmdate( DATE_ATOM );
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
		$dates = $adapted_coupon->getPromotionEffectiveTimePeriod();
		$now   = gmdate( DATE_ATOM );

		//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertEquals( $postdate, $dates->startTime );
		$this->assertGreaterThanOrEqual( $postdate, $dates->endTime );
		$this->assertLessThanOrEqual( $now, $dates->endTime );
		//phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
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

		$this->assertEquals( [ "gla_{$product_id_1}" ], $adapted_coupon->getItemId() );
		$this->assertEquals( [ "gla_{$product_id_2}" ], $adapted_coupon->getItemIdExclusion() );
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

		$this->assertEquals( [ 'Zulu Category', 'Alpha Category' ], $adapted_coupon->getProductType() );
		$this->assertEquals( [ 'Alpha Category > Beta Category' ], $adapted_coupon->getProductTypeExclusion() );
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
		update_post_meta( $coupon->get_id(), 'product_brands', $brand_1['term_id'] );

		// Exclude product 2 for the coupon.
		$coupon->set_excluded_product_ids( [ $product_2_id ] );

		// Exclude brand 2 (product 3) for the coupon.
		update_post_meta( $coupon->get_id(), 'exclude_product_brands', $brand_2['term_id'] );

		$coupon->save();

		$adapted_coupon = new WCCouponAdapter(
			[
				'wc_coupon'     => $coupon,
				'targetCountry' => 'US',
			]
		);

		// The brand inclusion will override the product inclusion, so the product 3 won't appear in the inclusion list at the moment.
		$this->assertEquals( [ "gla_{$product_1_id}", "gla_{$product_2_id}" ], $adapted_coupon->getItemId() );

		// The brand exclusion will respect the product inclusion, so the product 2 appears in the exclusion list.
		$this->assertEquals( [ "gla_{$product_2_id}", "gla_{$product_3_id}" ], $adapted_coupon->getItemIdExclusion() );
	}

	public function test_load_validator_metadata() {
		$metadata = new ClassMetadata( WCCouponAdapter::class );
		WCCouponAdapter::load_validator_metadata( $metadata );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'targetCountry' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'genericRedemptionCode' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'promotionId' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'productApplicability' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'offerType' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'redemptionChannel' ) );
		$this->assertTrue( $metadata->hasPropertyMetadata( 'couponValueType' ) );
	}

	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_currency', 'USD' );
	}
}
