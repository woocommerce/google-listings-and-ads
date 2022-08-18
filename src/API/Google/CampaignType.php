<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\V11\Enums\AdvertisingChannelTypeEnum\AdvertisingChannelType as AdsCampaignType;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\StatusMapping;

/**
 * Mapping between Google and internal CampaignTypes
 * https://developers.google.com/google-ads/api/reference/rpc/v9/AdvertisingChannelTypeEnum.AdvertisingChannelType
 *
 * @since 1.12.2
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class CampaignType extends StatusMapping {

	/**
	 * Not specified.
	 *
	 * @var string
	 */
	public const UNSPECIFIED = 'unspecified';

	/**
	 * Used for return value only. Represents value unknown in this version.
	 *
	 * @var string
	 */
	public const UNKNOWN = 'unknown';

	/**
	 * Search Network. Includes display bundled, and Search+ campaigns.
	 *
	 * @var string
	 */
	public const SEARCH = 'search';

	/**
	 * Google Display Network only.
	 *
	 * @var string
	 */
	public const DISPLAY = 'display';

	/**
	 * Shopping campaigns serve on the shopping property and on google.com search results.
	 *
	 * @var string
	 */
	public const SHOPPING = 'shopping';

	/**
	 * Hotel Ads campaigns.
	 *
	 * @var string
	 */
	public const HOTEL = 'hotel';

	/**
	 * Video campaigns.
	 *
	 * @var string
	 */
	public const VIDEO = 'video';

	/**
	 * App Campaigns, and App Campaigns for Engagement, that run across multiple channels.
	 *
	 * @var string
	 */
	public const MULTI_CHANNEL = 'multi_channel';

	/**
	 * Local ads campaigns.
	 *
	 * @var string
	 */
	public const LOCAL = 'local';

	/**
	 * Smart campaigns.
	 *
	 * @var string
	 */
	public const SMART = 'smart';

	/**
	 * Performance Max campaigns.
	 *
	 * @var string
	 */
	public const PERFORMANCE_MAX = 'performance_max';

	/**
	 * Mapping between status number and it's label.
	 *
	 * @var string
	 */
	protected const MAPPING = [
		AdsCampaignType::UNSPECIFIED     => self::UNSPECIFIED,
		AdsCampaignType::UNKNOWN         => self::UNKNOWN,
		AdsCampaignType::SEARCH          => self::SEARCH,
		AdsCampaignType::DISPLAY         => self::DISPLAY,
		AdsCampaignType::SHOPPING        => self::SHOPPING,
		AdsCampaignType::HOTEL           => self::HOTEL,
		AdsCampaignType::VIDEO           => self::VIDEO,
		AdsCampaignType::MULTI_CHANNEL   => self::MULTI_CHANNEL,
		AdsCampaignType::LOCAL           => self::LOCAL,
		AdsCampaignType::SMART           => self::SMART,
		AdsCampaignType::PERFORMANCE_MAX => self::PERFORMANCE_MAX,
	];
}
