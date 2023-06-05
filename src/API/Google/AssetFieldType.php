<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\API\Google;

use Google\Ads\GoogleAds\V13\Enums\AssetFieldTypeEnum\AssetFieldType as AdsAssetFieldType;
use Automattic\WooCommerce\GoogleListingsAndAds\Internal\StatusMapping;
use UnexpectedValueException;


/**
 * Mapping between Google and internal AssetFieldTypes
 * https://developers.google.com/google-ads/api/reference/rpc/v13/AssetFieldTypeEnum.AssetFieldType
 *
 * @since 2.4.0
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\API\Google
 */
class AssetFieldType extends StatusMapping {

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
	 * The asset is linked for use as a headline.
	 *
	 * @var string
	 */
	public const HEADLINE = 'headline';

	/**
	 * The asset is linked for use as a description.
	 *
	 * @var string
	 */
	public const DESCRIPTION = 'description';

	/**
	 * The asset is linked for use as a marketing image.
	 *
	 * @var string
	 */
	public const MARKETING_IMAGE = 'marketing_image';

	/**
	 * The asset is linked for use as a long headline.
	 *
	 * @var string
	 */
	public const LONG_HEADLINE = 'long_headline';

	/**
	 * The asset is linked for use as a business name.
	 *
	 * @var string
	 */
	public const BUSINESS_NAME = 'business_name';

	/**
	 * The asset is linked for use as a square marketing image.
	 *
	 * @var string
	 */
	public const SQUARE_MARKETING_IMAGE = 'square_marketing_image';

	/**
	 * The asset is linked for use as a logo.
	 *
	 * @var string
	 */
	public const LOGO = 'logo';

	/**
	 * The asset is linked for use to select a call-to-action.
	 *
	 * @var string
	 */
	public const CALL_TO_ACTION_SELECTION = 'call_to_action_selection';

	/**
	 * The asset is linked for use as a portrait marketing image.
	 *
	 * @var string
	 */
	public const PORTRAIT_MARKETING_IMAGE = 'portrait_marketing_image';

	/**
	 * The asset is linked for use as a landscape logo.
	 *
	 * @var string
	 */
	public const LANDSCAPE_LOGO = 'landscape_logo';

	/**
	 * The asset is linked for use as a YouTube video.
	 *
	 * @var string
	 */
	public const YOUTUBE_VIDEO = 'youtube_video';

	/**
	 * The asset is linked for use as a media bundle.
	 *
	 * @var string
	 */
	public const MEDIA_BUNDLE = 'media_bundle';

	/**
	 * Mapping between status number and it's label.
	 *
	 * @var string
	 */
	protected const MAPPING = [
		AdsAssetFieldType::UNSPECIFIED              => self::UNSPECIFIED,
		AdsAssetFieldType::UNKNOWN                  => self::UNKNOWN,
		AdsAssetFieldType::HEADLINE                 => self::HEADLINE,
		AdsAssetFieldType::DESCRIPTION              => self::DESCRIPTION,
		AdsAssetFieldType::MARKETING_IMAGE          => self::MARKETING_IMAGE,
		AdsAssetFieldType::LONG_HEADLINE            => self::LONG_HEADLINE,
		AdsAssetFieldType::BUSINESS_NAME            => self::BUSINESS_NAME,
		AdsAssetFieldType::SQUARE_MARKETING_IMAGE   => self::SQUARE_MARKETING_IMAGE,
		AdsAssetFieldType::LOGO                     => self::LOGO,
		AdsAssetFieldType::CALL_TO_ACTION_SELECTION => self::CALL_TO_ACTION_SELECTION,
		AdsAssetFieldType::PORTRAIT_MARKETING_IMAGE => self::PORTRAIT_MARKETING_IMAGE,
		AdsAssetFieldType::LANDSCAPE_LOGO           => self::LANDSCAPE_LOGO,
		AdsAssetFieldType::YOUTUBE_VIDEO            => self::YOUTUBE_VIDEO,
		AdsAssetFieldType::MEDIA_BUNDLE             => self::MEDIA_BUNDLE,

	];

	/**
	 * Get the enum name for the given label.
	 *
	 * @param string $label The label.
	 * @return string The enum name.
	 *
	 * @throws UnexpectedValueException If the label does not exist.
	 */
	public static function name( string $label ): string {
		return AdsAssetFieldType::name( self::number( $label ) );
	}
}
