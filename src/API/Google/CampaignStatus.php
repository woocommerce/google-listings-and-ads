<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\V12\Enums\CampaignStatusEnum\CampaignStatus as AdsCampaignStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\StatusMapping;

/**
 * Mapping between Google and internal CampaignStatus
 * https://developers.google.com/google-ads/api/reference/rpc/v9/CampaignStatusEnum.CampaignStatus
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class CampaignStatus extends StatusMapping {

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
}
