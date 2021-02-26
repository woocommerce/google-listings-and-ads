<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class ChannelVisibility
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class ChannelVisibility implements CastableValueInterface, ValueInterface {

	public const SYNC_AND_SHOW      = 'sync-and-show';
	public const DONT_SYNC_AND_SHOW = 'dont-sync-and-show';

	public const ALLOWED_VALUES = [
		self::SYNC_AND_SHOW,
		self::DONT_SYNC_AND_SHOW,
	];

	/**
	 * @var string
	 */
	protected $visibility;

	/**
	 * PositiveInteger constructor.
	 *
	 * @param string $visibility The value.
	 *
	 * @throws InvalidValue When an invalid visibility type is provided.
	 */
	public function __construct( string $visibility ) {
		if ( ! in_array( $visibility, self::ALLOWED_VALUES, true ) ) {
			throw InvalidValue::not_in_allowed_list( $visibility, self::ALLOWED_VALUES );
		}

		$this->visibility = $visibility;
	}

	/**
	 * Get the value of the object.
	 *
	 * @return string
	 */
	public function get(): string {
		return $this->visibility;
	}

	/**
	 * Cast a value and return a new instance of the class.
	 *
	 * @param string $value Mixed value to cast to class type.
	 *
	 * @return ChannelVisibility
	 *
	 * @throws InvalidValue When an invalid visibility type is provided.
	 */
	public static function cast( $value ): ChannelVisibility {
		return new self( $value );
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->get();
	}
}
