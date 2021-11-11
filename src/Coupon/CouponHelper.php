<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\MerchantCenter\MerchantCenterService;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Proxies\WC;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\ChannelVisibility;
use Automattic\WooCommerce\GoogleListingsAndAds\Value\SyncStatus;
use WC_Coupon;
defined( 'ABSPATH' ) || exit();

/**
 * Class CouponHelper
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 */
class CouponHelper implements Service {

	use PluginHelper;

	/**
	 *
	 * @var CouponMetaHandler
	 */
	protected $meta_handler;

	/**
	 *
	 * @var WC
	 */
	protected $wc;

	/**
	 *
	 * @var MerchantCenterService
	 */
	protected $merchant_center;

	/**
	 * CouponHelper constructor.
	 *
	 * @param CouponMetaHandler     $meta_handler
	 * @param WC                    $wc
	 * @param MerchantCenterService $merchant_center
	 */
	public function __construct(
		CouponMetaHandler $meta_handler,
		WC $wc,
		MerchantCenterService $merchant_center ) {
		$this->meta_handler    = $meta_handler;
		$this->wc              = $wc;
		$this->merchant_center = $merchant_center;
	}

	/**
	 * Mark a coupon as synced. This function accepts nullable $google_id,
	 * which guarantees version compatibility for Alpha, Beta and stable verison promtoion APIs.
	 *
	 * @param WC_Coupon   $coupon
	 * @param string|null $google_id
	 * @param string      $target_country
	 */
	public function mark_as_synced(
		WC_Coupon $coupon,
		?string $google_id,
		string $target_country ) {
		$this->meta_handler->update_synced_at( $coupon, time() );
		$this->meta_handler->update_sync_status( $coupon, SyncStatus::SYNCED );
		$this->update_empty_visibility( $coupon );

		// merge and update all google ids
		$current_google_ids = $this->meta_handler->get_google_ids( $coupon );
		$current_google_ids = ! empty( $current_google_ids ) ? $current_google_ids : [];
		$google_ids         = array_unique(
			array_merge(
				$current_google_ids,
				[
					$target_country => $google_id,
				]
			)
		);
		$this->meta_handler->update_google_ids( $coupon, $google_ids );
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 */
	public function mark_as_unsynced( WC_Coupon $coupon ) {
		$this->meta_handler->delete_synced_at( $coupon );
		$this->meta_handler->update_sync_status( $coupon, SyncStatus::NOT_SYNCED );
		$this->meta_handler->delete_google_ids( $coupon );
		$this->meta_handler->delete_errors( $coupon );
		$this->meta_handler->delete_failed_sync_attempts( $coupon );
		$this->meta_handler->delete_sync_failed_at( $coupon );
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 * @param string    $target_country
	 */
	public function remove_google_id_by_country( WC_Coupon $coupon, string $target_country ) {
		$google_ids = $this->meta_handler->get_google_ids( $coupon );
		if ( empty( $google_ids ) ) {
			return;
		}

		unset( $google_ids[ $target_country ] );

		if ( ! empty( $google_ids ) ) {
			$this->meta_handler->update_google_ids( $coupon, $google_ids );
		} else {
			// if there are no Google IDs left then this coupon is no longer considered "synced"
			$this->mark_as_unsynced( $coupon );
		}
	}

	/**
	 * Marks a WooCommerce coupon as invalid and stores the errors in a meta data key.
	 *
	 * @param WC_Coupon            $coupon
	 * @param InvalidCouponEntry[] $errors
	 */
	public function mark_as_invalid( WC_Coupon $coupon, array $errors ) {
		// bail if no errors exist
		if ( empty( $errors ) ) {
			return;
		}

		$this->meta_handler->update_errors( $coupon, $errors );
		$this->meta_handler->update_sync_status( $coupon, SyncStatus::HAS_ERRORS );
		$this->update_empty_visibility( $coupon );

		// TODO: Update failed sync attempts count in case of internal errors
	}

	/**
	 * Marks a WooCommerce coupon as pending synchronization.
	 *
	 * @param WC_Coupon $coupon
	 */
	public function mark_as_pending( WC_Coupon $coupon ) {
		$this->meta_handler->update_sync_status( $coupon, SyncStatus::PENDING );
		$this->meta_handler->delete_errors( $coupon );
	}

	/**
	 * Update empty (NOT EXIST) visibility meta values to SYNC_AND_SHOW.
	 *
	 * @param WC_Coupon $coupon
	 */
	protected function update_empty_visibility( WC_Coupon $coupon ): void {
		$visibility = $this->meta_handler->get_visibility( $coupon );

		if ( empty( $visibility ) ) {
			$this->meta_handler->update_visibility(
				$coupon,
				ChannelVisibility::SYNC_AND_SHOW
			);
		}
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return string[]|null An array of Google IDs stored for each WooCommerce coupon
	 */
	public function get_synced_google_ids( WC_Coupon $coupon ): ?array {
		return $this->meta_handler->get_google_ids( $coupon );
	}

	/**
	 * Get WooCommerce coupon
	 *
	 * @param int $coupon_id
	 *
	 * @return WC_Coupon
	 *
	 * @throws InvalidValue If the given ID doesn't reference a valid coupon.
	 */
	public function get_wc_coupon( int $coupon_id ): WC_Coupon {
		$coupon = $this->wc->maybe_get_coupon( $coupon_id );

		if ( ! $coupon instanceof WC_Coupon ) {
			throw InvalidValue::not_valid_coupon_id( $coupon_id );
		}

		return $coupon;
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return bool
	 */
	public function is_coupon_synced( WC_Coupon $coupon ): bool {
		$synced_at  = $this->meta_handler->get_synced_at( $coupon );
		$google_ids = $this->meta_handler->get_google_ids( $coupon );

		return ! empty( $synced_at ) && ! empty( $google_ids );
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return bool
	 */
	public function is_sync_ready( WC_Coupon $coupon ): bool {
		return ( ChannelVisibility::SYNC_AND_SHOW ===
			$this->get_channel_visibility( $coupon ) ) &&
			( CouponSyncer::is_coupon_supported( $coupon ) ) &&
			( ! $coupon->get_virtual() );
	}

	/**
	 * Whether the sync has failed repeatedly for the coupon within the given timeframe.
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return bool
	 *
	 * @see CouponSyncer::FAILURE_THRESHOLD The number of failed attempts allowed per timeframe
	 * @see CouponSyncer::FAILURE_THRESHOLD_WINDOW The specified timeframe
	 */
	public function is_sync_failed_recently( WC_Coupon $coupon ): bool {
		$failed_attempts = $this->meta_handler->get_failed_sync_attempts(
			$coupon
		);
		$failed_at       = $this->meta_handler->get_sync_failed_at( $coupon );

		// if it has failed more times than the specified threshold AND if syncing it has failed within the specified window
		return $failed_attempts > CouponSyncer::FAILURE_THRESHOLD &&
			$failed_at >
			strtotime( sprintf( '-%s', CouponSyncer::FAILURE_THRESHOLD_WINDOW ) );
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return string
	 */
	public function get_channel_visibility( WC_Coupon $coupon ): string {
		$visibility = $this->meta_handler->get_visibility( $coupon );

		if ( empty( $visibility ) ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Channel visibility forced to "%s" for visibility unknown (Post ID: %s).',
					ChannelVisibility::DONT_SYNC_AND_SHOW,
					$coupon->get_id()
				),
				__METHOD__
			);
			return ChannelVisibility::DONT_SYNC_AND_SHOW;
		}

		return $visibility;
	}

	/**
	 * Return a string indicating sync status based on several factors.
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return string|null
	 */
	public function get_sync_status( WC_Coupon $coupon ): ?string {
		return $this->meta_handler->get_sync_status( $coupon );
	}

	/**
	 * Return the string indicating the coupon status as reported by the Merchant Center.
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return string|null
	 */
	public function get_mc_status( WC_Coupon $coupon ): ?string {
		try {
			return $this->meta_handler->get_mc_status( $coupon );
		} catch ( InvalidValue $exception ) {
			do_action(
				'woocommerce_gla_debug_message',
				sprintf(
					'Coupon status returned null for invalid coupon (ID: %s).',
					$coupon->get_id()
				),
				__METHOD__
			);

			return null;
		}
	}

	/**
	 * Get validation errors for a specific coupon.
	 * Combines errors for variable coupons, which have a variation-indexed array of errors.
	 *
	 * @param WC_Coupon $coupon
	 *
	 * @return array
	 */
	public function get_validation_errors( WC_Coupon $coupon ): array {
		$errors = $this->meta_handler->get_errors( $coupon ) ?: [];

		$first_key = array_key_first( $errors );
		if ( ! empty( $errors ) && is_array( $errors[ $first_key ] ) ) {
			$errors = array_unique( array_merge( ...$errors ) );
		}

		return $errors;
	}
}
