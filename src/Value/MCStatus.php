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
}
