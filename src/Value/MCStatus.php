<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class MCStatus
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class MCStatus implements ValueInterface {

	public const APPROVED           = 'approved';
	public const PARTIALLY_APPROVED = 'partially_approved';
	public const EXPIRING           = 'expiring';
	public const PENDING            = 'pending';
	public const DISAPPROVED        = 'disapproved';
	public const NOT_SYNCED         = 'not_synced';

	public const ALLOWED_VALUES = [
		self::APPROVED,
		self::PARTIALLY_APPROVED,
		self::PENDING,
		self::EXPIRING,
		self::DISAPPROVED,
		self::NOT_SYNCED,
	];

	/**
	 * @var string
	 */
	protected $status;

	/**
	 * MCStatus constructor.
	 *
	 * @param string $status The value.
	 *
	 * @throws InvalidValue When an invalid status type is provided.
	 */
	public function __construct( string $status ) {
		if ( ! in_array( $status, self::ALLOWED_VALUES, true ) ) {
			throw InvalidValue::not_in_allowed_list( $status, self::ALLOWED_VALUES );
		}

		$this->status = $status;
	}

	/**
	 * Get the value of the object.
	 *
	 * @return string
	 */
	public function get(): string {
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->get();
	}

	/**
	 * Map a Merchant API aggregated reporting context status (as returned by the
	 * product_view report, or derived from a product's destination statuses) to
	 * the plugin's MC status vocabulary.
	 *
	 * @since x.x.x
	 *
	 * @param string $status Aggregated reporting context status, e.g. 'ELIGIBLE'.
	 *
	 * @return string One of the MCStatus constants.
	 */
	public static function from_aggregated_reporting_context_status( string $status ): string {
		switch ( $status ) {
			case 'ELIGIBLE':
				return self::APPROVED;
			case 'ELIGIBLE_LIMITED':
				return self::PARTIALLY_APPROVED;
			case 'NOT_ELIGIBLE_OR_DISAPPROVED':
				return self::DISAPPROVED;
			case 'PENDING':
				return self::PENDING;
			default:
				return self::NOT_SYNCED;
		}
	}
}
