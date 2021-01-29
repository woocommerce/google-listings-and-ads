<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\V6\Enums\CampaignStatusEnum\CampaignStatus as AdsCampaignStatus;

/**
 * Mapping between Google and internal CampaignStatus
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class CampaignStatus {

	/**
	 * Campaign is currently serving ads depending on budget information.
	 *
	 * @var string
	 */
	public const ENABLED = 'enabled';

	/**
	 * Campaign has been paused by the user.
	 *
	 * @var string
	 */
	public const PAUSED = 'paused';

	/**
	 * Campaign has been removed.
	 *
	 * @var string
	 */
	public const REMOVED = 'removed';

	/**
	 * Mapping between status number and it's label.
	 *
	 * @var string
	 */
	protected const MAPPING = [
		AdsCampaignStatus::ENABLED => self::ENABLED,
		AdsCampaignStatus::PAUSED  => self::PAUSED,
		AdsCampaignStatus::REMOVED => self::REMOVED,
	];

	/**
	 * Return the status as a label.
	 *
	 * @param int $number Status number.
	 *
	 * @return string
	 */
	public static function label( int $number ): string {
		return isset( self::MAPPING[ $number ] ) ? self::MAPPING[ $number ] : '';
	}

	/**
	 * Return the status as a number.
	 *
	 * @param string $label Status label.
	 *
	 * @return int
	 */
	public static function number( string $label ): int {
		$key = array_search( $label, self::MAPPING, true );
		return $key ?? 0;
	}

	/**
	 * Return all the status labels.
	 *
	 * @return array
	 */
	public static function labels(): array {
		return array_values( self::MAPPING );
	}
}
