<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\V9\Enums\CampaignCriterionStatusEnum\CampaignCriterionStatus as AdsCampaignCriterionStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\StatusMapping;

/**
 * Mapping between Google and internal CampaignCriterionStatus
 * https://developers.google.com/google-ads/api/reference/rpc/v9/CampaignCriterionStatusEnum.CampaignCriterionStatus
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class CampaignCriterionStatus extends StatusMapping {

	/**
	 * The campaign criterion is enabled.
	 *
	 * @var string
	 */
	public const ENABLED = 'enabled';

	/**
	 * The campaign criterion is paused.
	 *
	 * @var string
	 */
	public const PAUSED = 'paused';

	/**
	 * The campaign criterion is removed.
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
		AdsCampaignCriterionStatus::ENABLED => self::ENABLED,
		AdsCampaignCriterionStatus::PAUSED  => self::PAUSED,
		AdsCampaignCriterionStatus::REMOVED => self::REMOVED,
	];
}
