<?php
declare(strict_types = 1);
namespace Automattic\WooCommerce\GoogleListingsAndAds\Coupon;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidMeta;
use Automattic\WooCommerce\GoogleListingsAndAds\Infrastructure\Service;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use BadMethodCallException;
use WC_Coupon;
defined( 'ABSPATH' ) || exit();

/**
 * Class CouponMetaHandler
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Coupon
 *
 * @method update_synced_at( WC_Coupon $coupon, $value )
 * @method delete_synced_at( WC_Coupon $coupon )
 * @method get_synced_at( WC_Coupon $coupon ): int|null
 * @method update_google_ids( WC_Coupon $coupon, array $value )
 * @method delete_google_ids( WC_Coupon $coupon )
 * @method get_google_ids( WC_Coupon $coupon ): array|null
 * @method update_visibility( WC_Coupon $coupon, $value )
 * @method delete_visibility( WC_Coupon $coupon )
 * @method get_visibility( WC_Coupon $coupon ): string|null
 * @method update_errors( WC_Coupon $coupon, array $value )
 * @method delete_errors( WC_Coupon $coupon )
 * @method get_errors( WC_Coupon $coupon ): array|null
 * @method update_failed_sync_attempts( WC_Coupon $coupon, int $value )
 * @method delete_failed_sync_attempts( WC_Coupon $coupon )
 * @method get_failed_sync_attempts( WC_Coupon $coupon ): int|null
 * @method update_sync_failed_at( WC_Coupon $coupon, int $value )
 * @method delete_sync_failed_at( WC_Coupon $coupon )
 * @method get_sync_failed_at( WC_Coupon $coupon ): int|null
 * @method update_sync_status( WC_Coupon $coupon, string $value )
 * @method delete_sync_status( WC_Coupon $coupon )
 * @method get_sync_status( WC_Coupon $coupon ): string|null
 * @method update_mc_status( WC_Coupon $coupon, string $value )
 * @method delete_mc_status( WC_Coupon $coupon )
 * @method get_mc_status( WC_Coupon $coupon ): string|null
 * @method update_notification_status( WC_Coupon $coupon, string $value )
 * @method delete_notification_status( WC_Coupon $coupon )
 * @method get_notification_status( WC_Coupon $coupon ): string|null
 */
class CouponMetaHandler implements Service {

	use PluginHelper;

	public const KEY_SYNCED_AT = 'synced_at';

	public const KEY_GOOGLE_IDS = 'google_ids';

	public const KEY_VISIBILITY = 'visibility';

	public const KEY_ERRORS = 'errors';

	public const KEY_FAILED_SYNC_ATTEMPTS = 'failed_sync_attempts';

	public const KEY_SYNC_FAILED_AT = 'sync_failed_at';

	public const KEY_SYNC_STATUS = 'sync_status';

	public const KEY_MC_STATUS = 'mc_status';

	public const KEY_NOTIFICATION_STATUS = 'notification_status';


	protected const TYPES = [
		self::KEY_SYNCED_AT            => 'int',
		self::KEY_GOOGLE_IDS           => 'array',
		self::KEY_VISIBILITY           => 'string',
		self::KEY_ERRORS               => 'array',
		self::KEY_FAILED_SYNC_ATTEMPTS => 'int',
		self::KEY_SYNC_FAILED_AT       => 'int',
		self::KEY_SYNC_STATUS          => 'string',
		self::KEY_MC_STATUS            => 'string',
		self::KEY_NOTIFICATION_STATUS  => 'string',
	];

	/**
	 *
	 * @param string $name
	 * @param mixed  $arguments
	 *
	 * @return mixed
	 *
	 * @throws BadMethodCallException If the method that's called doesn't exist.
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function __call( string $name, $arguments ) {
		$found_matches = preg_match( '/^([a-z]+)_([\w\d]+)$/i', $name, $matches );

		if ( ! $found_matches ) {
			throw new BadMethodCallException(
				sprintf(
					'The method %s does not exist in class CouponMetaHandler',
					$name
				)
			);
		}

		[
			$function_name,
			$method,
			$key
		] = $matches;

		// validate the method
		if ( ! in_array(
			$method,
			[
				'update',
				'delete',
				'get',
			],
			true
		) ) {
			throw new BadMethodCallException(
				sprintf(
					'The method %s does not exist in class CouponMetaHandler',
					$function_name
				)
			);
		}

		// set the value as the third argument if method is `update`
		if ( 'update' === $method ) {
			$arguments[2] = $arguments[1];
		}
		// set the key as the second argument
		$arguments[1] = $key;

		return call_user_func_array(
			[
				$this,
				$method,
			],
			$arguments
		);
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 * @param string    $key
	 * @param mixed     $value
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function update( WC_Coupon $coupon, string $key, $value ) {
		self::validate_meta_key( $key );

		if ( isset( self::TYPES[ $key ] ) ) {
			if ( in_array(
				self::TYPES[ $key ],
				[
					'bool',
					'boolean',
				],
				true
			) ) {
				$value = wc_bool_to_string( $value );
			} else {
				settype( $value, self::TYPES[ $key ] );
			}
		}

		$coupon->update_meta_data( $this->prefix_meta_key( $key ), $value );
		$coupon->save_meta_data();
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 * @param string    $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function delete( WC_Coupon $coupon, string $key ) {
		self::validate_meta_key( $key );

		$coupon->delete_meta_data( $this->prefix_meta_key( $key ) );
		$coupon->save_meta_data();
	}

	/**
	 *
	 * @param WC_Coupon $coupon
	 * @param string    $key
	 *
	 * @return mixed The value, or null if the meta key doesn't exist.
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	public function get( WC_Coupon $coupon, string $key ) {
		self::validate_meta_key( $key );

		$value = null;
		if ( $coupon->meta_exists( $this->prefix_meta_key( $key ) ) ) {
			$value = $coupon->get_meta( $this->prefix_meta_key( $key ), true );

			if ( isset( self::TYPES[ $key ] ) &&
				in_array(
					self::TYPES[ $key ],
					[
						'bool',
						'boolean',
					],
					true
				) ) {
				$value = wc_string_to_bool( $value );
			}
		}

		return $value;
	}

	/**
	 *
	 * @param string $key
	 *
	 * @throws InvalidMeta If the meta key is invalid.
	 */
	protected static function validate_meta_key( string $key ) {
		if ( ! self::is_meta_key_valid( $key ) ) {
			do_action(
				'woocommerce_gla_error',
				sprintf( 'Coupon meta key is invalid: %s', $key ),
				__METHOD__
			);

			throw InvalidMeta::invalid_key( $key );
		}
	}

	/**
	 *
	 * @param string $key
	 *
	 * @return bool Whether the meta key is valid.
	 */
	public static function is_meta_key_valid( string $key ): bool {
		return isset( self::TYPES[ $key ] );
	}

	/**
	 * Returns all available meta keys.
	 *
	 * @return array
	 */
	public static function get_all_meta_keys(): array {
		return array_keys( self::TYPES );
	}
}
