<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class SyncStatus
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class SyncStatus implements ValueInterface {

	public const SYNCED     = 'synced';
	public const NOT_SYNCED = 'not-synced';
	public const HAS_ERRORS = 'has-errors';
	public const PENDING    = 'pending';

	public const ALLOWED_VALUES = [
		self::SYNCED,
		self::PENDING,
		self::HAS_ERRORS,
		self::NOT_SYNCED,
	];

	/**
	 * @var string
	 */
	protected $status;

	/**
	 * SyncStatus constructor.
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
