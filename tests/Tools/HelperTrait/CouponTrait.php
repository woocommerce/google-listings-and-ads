<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait;

use Automattic\WooCommerce\GoogleListingsAndAds\Coupon\WCCouponAdapter;
use Automattic\WooCommerce\GoogleListingsAndAds\Google\DeleteCouponEntry;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Coupon;

/**
 * Trait Coupon
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Tests\Tools\HelperTrait
 */
trait CouponTrait {
	use SettingsTrait;

	/**
	 * Generates and returns a mock of a simple WC_Coupon object
	 *
	 * @return MockObject|WC_Coupon
	 */
	public function generate_simple_coupon_mock() {
		$coupon = $this->createMock( WC_Coupon::class );

		$coupon->expects( $this->any() )
			->method( 'get_id' )
			->willReturn( wp_rand() );

		return $coupon;
	}

	/**
	 * Creates a valid WC_Coupon object
	 *
	 * @return WC_Coupon
	 */
	public function create_simple_coupon() {
		$coupon = new WC_Coupon();
		$coupon->set_code( sprintf( 'simple_coupon_%d', wp_rand() ) );
		$coupon->set_amount( 10 );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_free_shipping( true );
		$coupon->save();

		return $coupon;
	}

	/**
	 * Creates a ready-to-sync WC_Coupon object
	 *
	 * @return WC_Coupon
	 */
	public function create_ready_to_sync_coupon() {
		$coupon = $this->create_simple_coupon();
		$coupon->update_meta_data( '_wc_gla_visibility', 'sync-and-show' );
		$coupon->save();

		return $coupon;
	}

	/**
	 * Creates a ready-to-delete WC_Coupon object
	 *
	 * @return WC_Coupon
	 */
	public function create_ready_to_delete_coupon() {
		$coupon = $this->create_simple_coupon();
		$coupon->update_meta_data( '_wc_gla_sync_status', SyncStatus::SYNCED );
		$coupon->update_meta_data( '_wc_gla_synced_at', time() );
		$coupon->update_meta_data( '_wc_gla_google_ids', [ 'US' => 'google_id' ] );
		$coupon->update_meta_data( '_wc_gla_visibility', 'sync-and-show' );
		$coupon->save();

		return $coupon;
	}

	/**
	 * Creates a ready-to-delete WC_Coupon object
	 *
	 * @param WC_Coupon $coupon
	 * @return DeleteCouponEntry
	 */
	public function generate_delete_coupon_entry( WC_Coupon $coupon ) {
		$adapted_coupon = new WCCouponAdapter( [ 'wc_coupon' => $coupon ] );
		$adapted_coupon->disable_promotion( $coupon );
		return new DeleteCouponEntry(
			$coupon->get_id(),
			$adapted_coupon->get_promotion(),
			$this->coupon_helper->get_synced_google_ids( $coupon )
		);
	}
}
