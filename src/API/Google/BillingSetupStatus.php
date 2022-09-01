<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\V11\Enums\BillingSetupStatusEnum\BillingSetupStatus as AdsBillingSetupStatus;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\StatusMapping;

/**
 * Mapping between Google and internal BillingSetupStatus
 * https://developers.google.com/google-ads/api/reference/rpc/v9/BillingSetupStatusEnum.BillingSetupStatus
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class BillingSetupStatus extends StatusMapping {

	/**
	 * Used for return value only. Represents value unknown in this version.
	 *
	 * @var string
	 */
	public const UNKNOWN = 'unknown';

	/**
	 * The billing setup is pending approval.
	 *
	 * @var string
	 */
	public const PENDING = 'pending';

	/**
	 * The billing setup has been approved.
	 *
	 * @var string
	 */
	public const APPROVED = 'approved';

	/**
	 * The billing setup was cancelled by the user prior to approval.
	 *
	 * @var string
	 */
	public const CANCELLED = 'cancelled';

	/**
	 * Mapping between status number and it's label.
	 *
	 * @var string
	 */
	protected const MAPPING = [
		AdsBillingSetupStatus::PENDING   => self::PENDING,
		AdsBillingSetupStatus::APPROVED  => self::APPROVED,
		AdsBillingSetupStatus::CANCELLED => self::CANCELLED,
	];
}
