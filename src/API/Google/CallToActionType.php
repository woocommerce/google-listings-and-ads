<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\V20\Enums\CallToActionTypeEnum\CallToActionType as AdsCallToActionType;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\StatusMapping;


/**
 * Mapping between Google and internal CallToActionType
 * https://developers.google.com/google-ads/api/reference/rpc/v20/CallToActionTypeEnum.CallToActionType
 *
 * @since 2.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class CallToActionType extends StatusMapping {

	/**
	 * Not specified.
	 *
	 * @var string
	 */
	public const UNSPECIFIED = 'unspecified';

	/**
	 * Represents value unknown in this version.
	 *
	 * @var string
	 */
	public const UNKNOWN = 'unknown';

	/**
	 * The call to action type is learn more.
	 *
	 * @var string
	 */
	public const LEARN_MORE = 'learn_more';

	/**
	 * The call to action type is get quote.
	 *
	 * @var string
	 */
	public const GET_QUOTE = 'get_quote';

	/**
	 * The call to action type is apply now.
	 *
	 * @var string
	 */
	public const APPLY_NOW = 'apply_now';

	/**
	 * The call to action type is sign up.
	 *
	 * @var string
	 */
	public const SIGN_UP = 'sign_up';

	/**
	 * The call to action type is contact us.
	 *
	 * @var string
	 */
	public const CONTACT_US = 'contact_us';

	/**
	 * The call to action type is subscribe.
	 *
	 * @var string
	 */
	public const SUBSCRIBE = 'subscribe';

	/**
	 * The call to action type is download.
	 *
	 * @var string
	 */
	public const DOWNLOAD = 'download';

	/**
	 * The call to action type is book now.
	 *
	 * @var string
	 */
	public const BOOK_NOW = 'book_now';

	/**
	 * The call to action type is shop now.
	 *
	 * @var string
	 */
	public const SHOP_NOW = 'shop_now';

	/**
	 * Mapping between status number and it's label.
	 *
	 * @var string
	 */
	protected const MAPPING = [
		AdsCallToActionType::UNSPECIFIED => self::UNSPECIFIED,
		AdsCallToActionType::UNKNOWN     => self::UNKNOWN,
		AdsCallToActionType::LEARN_MORE  => self::LEARN_MORE,
		AdsCallToActionType::GET_QUOTE   => self::GET_QUOTE,
		AdsCallToActionType::APPLY_NOW   => self::APPLY_NOW,
		AdsCallToActionType::SIGN_UP     => self::SIGN_UP,
		AdsCallToActionType::CONTACT_US  => self::CONTACT_US,
		AdsCallToActionType::SUBSCRIBE   => self::SUBSCRIBE,
		AdsCallToActionType::DOWNLOAD    => self::DOWNLOAD,
		AdsCallToActionType::BOOK_NOW    => self::BOOK_NOW,
		AdsCallToActionType::SHOP_NOW    => self::SHOP_NOW,

	];
}
