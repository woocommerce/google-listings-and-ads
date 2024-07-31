<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Value;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;

defined( 'ABSPATH' ) || exit;

/**
 * Class Notification Status defining statues related to Partner Notifications
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Value
 */
class NotificationStatus implements ValueInterface {

	public const NOTIFICATION_PENDING_CREATE = 'pending_create';
	public const NOTIFICATION_PENDING_UPDATE = 'pending_update';
	public const NOTIFICATION_PENDING_DELETE = 'pending_delete';
	public const NOTIFICATION_CREATED        = 'created';
	public const NOTIFICATION_UPDATED        = 'updated';
	public const NOTIFICATION_DELETED        = 'deleted';

	public const ALLOWED_VALUES = [
		self::NOTIFICATION_PENDING_CREATE,
		self::NOTIFICATION_PENDING_UPDATE,
		self::NOTIFICATION_PENDING_DELETE,
		self::NOTIFICATION_CREATED,
		self::NOTIFICATION_DELETED,
		self::NOTIFICATION_UPDATED,
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
